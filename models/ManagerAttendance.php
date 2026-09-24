<?php
require_once __DIR__ . '/../config/Database.php';

class ManagerAttendance {
    private $db;
    public function __construct() { $this->db = Database::connect(); }

    // Same result as User::getManagers() + getByMonth() + getMonthlyPLCounts(),
    // in one round trip (AttendanceController::managerAttendance() is admin-only,
    // so no site scoping to fold in here).
    public function getManagerAttendanceBundle($monthYear) {
        $sql = "SELECT
            (SELECT COALESCE(json_agg(m), '[]'::json) FROM (
                SELECT u.*, r.name as role_name
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.role_id = 2 AND u.status = 'Active'
                ORDER BY u.full_name ASC
            ) m) AS managers_json,
            (SELECT COALESCE(json_agg(h), '[]'::json) FROM (
                SELECT ma.*, u.full_name as manager_name, u.email
                FROM manager_attendance ma
                JOIN users u ON ma.user_id = u.id
                WHERE TO_CHAR(ma.attendance_date, 'YYYY-MM') = :my
                ORDER BY ma.attendance_date DESC, u.full_name ASC
            ) h) AS history_json,
            (SELECT COALESCE(json_agg(p), '[]'::json) FROM (
                SELECT user_id, COUNT(*) as count
                FROM manager_attendance
                WHERE status = 'pl' AND TO_CHAR(attendance_date, 'YYYY-MM') = :my
                GROUP BY user_id
            ) p) AS pl_counts_json
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['my' => $monthYear]);
        $row = $stmt->fetch();

        $plCounts = [];
        foreach (json_decode($row['pl_counts_json'], true) ?: [] as $r) {
            $plCounts[$r['user_id']] = $r['count'];
        }

        return [
            'managers' => json_decode($row['managers_json'], true) ?: [],
            'history' => json_decode($row['history_json'], true) ?: [],
            'plCounts' => $plCounts,
        ];
    }

    public function getByMonth($monthYear) {
        // monthYear format: 2026-04
        $stmt = $this->db->prepare("
            SELECT ma.*, u.full_name as manager_name, u.email 
            FROM manager_attendance ma
            JOIN users u ON ma.user_id = u.id
            WHERE TO_CHAR(ma.attendance_date, 'YYYY-MM') = :my
            ORDER BY ma.attendance_date DESC, u.full_name ASC
        ");
        $stmt->execute(['my' => $monthYear]);
        return $stmt->fetchAll();
    }

    public function getByUser($userId, $monthYear) {
        $stmt = $this->db->prepare("
            SELECT * FROM manager_attendance 
            WHERE user_id = :uid AND TO_CHAR(attendance_date, 'YYYY-MM') = :my
            ORDER BY attendance_date DESC
        ");
        $stmt->execute(['uid' => $userId, 'my' => $monthYear]);
        return $stmt->fetchAll();
    }

    /**
     * Save the manager attendance grid. Each record carries its own set of dates
     * (chosen per manager via the calendar picker): the manager's status / note is
     * applied to every selected day. Dates are validated with checkdate.
     * Returns the number of records written, or false on failure.
     *
     * $records: [ userId => ['status'=>, 'note'=>, 'dates'=>'Y-m-d,Y-m-d,...'] ]
     */
    public function saveGrid($records, $adminUserId) {
        if (empty($records)) {
            return false;
        }

        $valid = ['p', 'off', 'h', 'pl', 'sd'];
        $saved = 0;

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO manager_attendance (user_id, attendance_date, status, note, updated_by)
                VALUES (:uid, :adate, :status, :note, :upby)
                ON CONFLICT (user_id, attendance_date)
                DO UPDATE SET status = EXCLUDED.status, note = EXCLUDED.note, updated_by = EXCLUDED.updated_by, updated_at = CURRENT_TIMESTAMP
            ");

            foreach ($records as $userId => $rec) {
                $status = $rec['status'] ?? '';
                if (!in_array($status, $valid)) { continue; }

                $note = isset($rec['note']) ? trim($rec['note']) : '';
                $dates = isset($rec['dates']) ? array_filter(explode(',', $rec['dates'])) : [];
                foreach ($dates as $dt) {
                    $dt = trim($dt);
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dt)) { continue; }
                    $p = explode('-', $dt);
                    if (!checkdate((int)$p[1], (int)$p[2], (int)$p[0])) { continue; }

                    $stmt->execute([
                        'uid' => $userId,
                        'adate' => $dt,
                        'status' => $status,
                        'note' => ($note === '' ? null : $note),
                        'upby' => $adminUserId
                    ]);
                    $saved++;
                }
            }
            $this->db->commit();
            return $saved;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    /**
     * Read-only monthly register for manager attendance: one row per manager
     * (role 2) with a day-indexed map of their recorded status + per-manager
     * totals, for a managers × day matrix. Manager statuses have no Absent/OT.
     *
     * Each row: [ id, full_name, email,
     *   days   => [ dayInt => ['status'=>, 'note'=>], ... ],
     *   totals => ['p'=>,'off'=>,'h'=>,'pl'=>,'sd'=>] ]
     */
    public function getMonthlyRegister($monthYear) {
        $managers = $this->db->query("
            SELECT id, full_name, email FROM users WHERE role_id = 2 ORDER BY full_name ASC
        ")->fetchAll();
        if (empty($managers)) { return []; }

        $ids = array_column($managers, 'id');
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $aStmt = $this->db->prepare("
            SELECT user_id, EXTRACT(DAY FROM attendance_date)::int AS day, status, note
            FROM manager_attendance
            WHERE user_id IN ($ph) AND TO_CHAR(attendance_date, 'YYYY-MM') = ?
        ");
        $aStmt->execute(array_merge(array_values($ids), [$monthYear]));

        $byUser = [];
        foreach ($aStmt->fetchAll() as $r) {
            $byUser[$r['user_id']][(int)$r['day']] = ['status' => $r['status'], 'note' => $r['note']];
        }

        $out = [];
        foreach ($managers as $m) {
            $days = $byUser[$m['id']] ?? [];
            $totals = ['p' => 0, 'off' => 0, 'h' => 0, 'pl' => 0, 'sd' => 0];
            foreach ($days as $d) {
                if (isset($totals[$d['status']])) { $totals[$d['status']]++; }
            }
            $m['days'] = $days;
            $m['totals'] = $totals;
            $out[] = $m;
        }
        return $out;
    }

    public function getMonthlyPLCounts($monthYear) {
        $stmt = $this->db->prepare("
            SELECT user_id, COUNT(*) as count 
            FROM manager_attendance 
            WHERE status = 'pl' AND TO_CHAR(attendance_date, 'YYYY-MM') = :my
            GROUP BY user_id
        ");
        $stmt->execute(['my' => $monthYear]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // returns [user_id => count]
    }
}
