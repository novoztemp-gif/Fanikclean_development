<?php
require_once __DIR__ . '/../config/Database.php';

class Attendance {
    private $db;
    public function __construct() { $this->db = Database::connect(); }

    /**
     * Save the attendance grid. Each record carries its own set of dates
     * (chosen per worker via the calendar picker): the worker's status / OT /
     * note is applied to every selected day. Each worker is stored under their
     * own site; when $allowedSiteIds is an array (managers), workers outside
     * that scope are skipped. Returns [savedRows, skipped].
     *
     * $records: [ workerId => ['status'=>, 'ot'=>, 'note'=>, 'dates'=>'Y-m-d,Y-m-d,...'] ]
     */
    /**
     * Read-only monthly register (muster roll). Returns one row per in-scope
     * active worker with a day-indexed map of their recorded attendance plus
     * per-worker totals, for rendering a worker × day matrix.
     *
     * $monthYear     'Y-m'
     * $scopeSiteIds  null = all sites (admin); array = manager scope (empty = none)
     * $filterSiteId  optional single-site narrow
     *
     * Each returned row: [
     *   id, full_name, worker_code, category_name, site_name,
     *   days    => [ dayInt => ['status'=>, 'ot_hours'=>, 'note'=>], ... ],
     *   totals  => ['p'=>,'a'=>,'h'=>,'off'=>,'pl'=>,'sd'=>,'ot'=>]
     * ]
     */
    public function getMonthlyRegister($monthYear, $scopeSiteIds = null, $filterSiteId = null) {
        // 1) Workers in scope.
        $where = ["w.status = 'Active'"];
        $wParams = [];
        if ($scopeSiteIds !== null) {
            if (empty($scopeSiteIds)) { return []; } // manager with no sites
            $keys = [];
            foreach ($scopeSiteIds as $i => $sid) { $keys[] = ":s$i"; $wParams["s$i"] = $sid; }
            $where[] = "w.site_id IN (" . implode(',', $keys) . ")";
        }
        if ($filterSiteId) { $where[] = "w.site_id = :fsid"; $wParams['fsid'] = $filterSiteId; }

        $wStmt = $this->db->prepare("
            SELECT w.id, w.full_name, w.worker_code,
                   wc.name AS category_name, s.name AS site_name
            FROM workers w
            JOIN worker_categories wc ON w.category_id = wc.id
            JOIN sites s ON w.site_id = s.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY s.name ASC, w.full_name ASC
        ");
        $wStmt->execute($wParams);
        $workers = $wStmt->fetchAll();
        if (empty($workers)) { return []; }

        // 2) All attendance rows for those workers in the month, keyed by worker+day.
        $ids = array_column($workers, 'id');
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $aStmt = $this->db->prepare("
            SELECT worker_id, EXTRACT(DAY FROM attendance_date)::int AS day,
                   status, ot_hours, note
            FROM attendance
            WHERE worker_id IN ($ph) AND TO_CHAR(attendance_date, 'YYYY-MM') = ?
        ");
        $aStmt->execute(array_merge(array_values($ids), [$monthYear]));

        $byWorker = [];
        foreach ($aStmt->fetchAll() as $r) {
            $byWorker[$r['worker_id']][(int)$r['day']] = [
                'status'   => $r['status'],
                'ot_hours' => $r['ot_hours'],
                'note'     => $r['note'],
            ];
        }

        // 3) Assemble rows with per-worker totals.
        $out = [];
        foreach ($workers as $w) {
            $days = $byWorker[$w['id']] ?? [];
            $totals = ['p' => 0, 'a' => 0, 'h' => 0, 'off' => 0, 'pl' => 0, 'sd' => 0, 'ot' => 0];
            foreach ($days as $d) {
                if (isset($totals[$d['status']])) { $totals[$d['status']]++; }
                $totals['ot'] += (float)($d['ot_hours'] ?? 0);
            }
            $w['days'] = $days;
            $w['totals'] = $totals;
            $out[] = $w;
        }
        return $out;
    }

    public function saveGrid($records, $userId, $allowedSiteIds = null) {
        if (empty($records)) {
            return false;
        }

        // Resolve each worker's current site in one query.
        $workerIds = array_keys($records);
        $ph = implode(',', array_fill(0, count($workerIds), '?'));
        $mapStmt = $this->db->prepare("SELECT id, site_id FROM workers WHERE id IN ($ph)");
        $mapStmt->execute(array_values($workerIds));
        $siteMap = [];
        foreach ($mapStmt->fetchAll() as $row) {
            $siteMap[$row['id']] = $row['site_id'];
        }

        $valid = ['p', 'a', 'h', 'off', 'pl', 'sd'];
        $saved = 0;

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO attendance (worker_id, site_id, attendance_date, status, ot_hours, note, updated_by)
                VALUES (:wid, :sid, :dt, :st, :ot, :nt, :usr)
                ON CONFLICT (worker_id, attendance_date)
                DO UPDATE SET status = EXCLUDED.status, ot_hours = EXCLUDED.ot_hours, note = EXCLUDED.note, site_id = EXCLUDED.site_id, updated_by = EXCLUDED.updated_by
            ");
            foreach ($records as $workerId => $rec) {
                $status = $rec['status'] ?? '';
                if (!in_array($status, $valid)) { continue; }

                $siteId = $siteMap[$workerId] ?? null;
                if (!$siteId) { continue; } // unassigned worker — cannot record site-based attendance
                if ($allowedSiteIds !== null && !in_array($siteId, $allowedSiteIds)) { continue; } // out of manager scope

                $ot = !empty($rec['ot']) ? $rec['ot'] : 0;
                $note = isset($rec['note']) ? trim($rec['note']) : '';

                $dates = isset($rec['dates']) ? array_filter(explode(',', $rec['dates'])) : [];
                foreach ($dates as $dt) {
                    $dt = trim($dt);
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dt)) { continue; }
                    $p = explode('-', $dt);
                    if (!checkdate((int)$p[1], (int)$p[2], (int)$p[0])) { continue; }

                    $stmt->execute([
                        'wid' => $workerId, 'sid' => $siteId, 'dt' => $dt,
                        'st' => $status, 'ot' => $ot,
                        'nt' => ($note === '' ? null : $note), 'usr' => $userId
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
}
