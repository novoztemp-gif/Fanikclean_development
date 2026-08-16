<?php
require_once __DIR__ . '/../config/Database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function create($fullName, $email, $passwordHash, $roleId = 2, $guardianData = []) {
        // Check if any users exist in the system
        $countStmt = $this->db->query("SELECT COUNT(*) FROM users");
        $count = $countStmt->fetchColumn();
        
        // If this is the very first user, automatically make them an Admin (Role ID 1)
        if ($count == 0) {
            $roleId = 1;
        }

        $stmt = $this->db->prepare("
            INSERT INTO users (full_name, email, password_hash, role_id, guardian_name, guardian_phone, guardian_place)
            VALUES (:f, :e, :p, :r, :gn, :gp, :gpl)
        ");
        $ok = $stmt->execute([
            'f' => $fullName,
            'e' => $email,
            'p' => $passwordHash,
            'r' => $roleId,
            'gn' => $guardianData['guardian_name'] ?? null,
            'gp' => $guardianData['guardian_phone'] ?? null,
            'gpl' => $guardianData['guardian_place'] ?? null
        ]);
        // Return the new user's id so the caller can persist site assignments.
        return $ok ? $this->db->lastInsertId() : false;
    }

    public function getAll() {
        // Site scope comes solely from the multi-site table (user_site_assignments);
        // the legacy users.site_id column is no longer read. assigned_site_ids_csv
        // feeds the edit modal's multi-select pre-selection.
        return $this->db->query("
            SELECT u.*, r.name as role_name,
                   COALESCE(sa.site_names, '')   AS assigned_site_names,
                   COALESCE(sa.site_ids_csv, '') AS assigned_site_ids_csv,
                   COALESCE(sa.site_count, 0)    AS assigned_site_count
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN (
                SELECT usa.user_id,
                       STRING_AGG(st.name, ', ' ORDER BY st.name)          AS site_names,
                       STRING_AGG(usa.site_id::text, ',' ORDER BY st.name) AS site_ids_csv,
                       COUNT(*) AS site_count
                FROM user_site_assignments usa
                JOIN sites st ON usa.site_id = st.id
                GROUP BY usa.user_id
            ) sa ON sa.user_id = u.id
            ORDER BY u.id DESC
        ")->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT u.*, r.name as role_name,
                   COALESCE(sa.site_names, '') AS assigned_site_names,
                   COALESCE(sa.site_count, 0)  AS assigned_site_count
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN (
                SELECT usa.user_id,
                       STRING_AGG(st.name, ', ' ORDER BY st.name) AS site_names,
                       COUNT(*) AS site_count
                FROM user_site_assignments usa
                JOIN sites st ON usa.site_id = st.id
                GROUP BY usa.user_id
            ) sa ON sa.user_id = u.id
            WHERE u.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function update($id, $data) {
        // Site scope is managed via user_site_assignments (see saveAssignments),
        // not the legacy users.site_id column, which is no longer written here.
        $stmt = $this->db->prepare("
            UPDATE users
            SET full_name = :fn,
                role_id = :rid,
                status = :status,
                guardian_name = :gn,
                guardian_phone = :gp,
                guardian_place = :gpl
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'fn' => $data['full_name'],
            'rid' => $data['role_id'],
            'status' => $data['status'],
            'gn' => $data['guardian_name'] ?? null,
            'gp' => $data['guardian_phone'] ?? null,
            'gpl' => $data['guardian_place'] ?? null
        ]);
    }

    public function getAssignedSiteIds($userId) {
        $stmt = $this->db->prepare("SELECT site_id FROM user_site_assignments WHERE user_id = :uid");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getManagers() {
        $stmt = $this->db->prepare("
            SELECT u.*, r.name as role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.role_id = 2 
            ORDER BY u.full_name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function saveAssignments($userId, $siteIds) {
        $this->db->beginTransaction();
        try {
            // Clear old assignments
            $del = $this->db->prepare("DELETE FROM user_site_assignments WHERE user_id = :uid");
            $del->execute(['uid' => $userId]);

            // Add new assignments
            if (!empty($siteIds)) {
                $ins = $this->db->prepare("INSERT INTO user_site_assignments (user_id, site_id) VALUES (:uid, :sid)");
                foreach ($siteIds as $sid) {
                    $ins->execute(['uid' => $userId, 'sid' => $sid]);
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
