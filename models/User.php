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
            WHERE u.status != 'Deleted'
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
        // Lifecycle status is managed separately via suspend()/reactivate()/softDelete()
        // so it can't be bypassed through the general profile edit form.
        $stmt = $this->db->prepare("
            UPDATE users
            SET full_name = :fn,
                email = :em,
                role_id = :rid,
                guardian_name = :gn,
                guardian_phone = :gp,
                guardian_place = :gpl
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'fn' => $data['full_name'],
            'em' => $data['email'],
            'rid' => $data['role_id'],
            'gn' => $data['guardian_name'] ?? null,
            'gp' => $data['guardian_phone'] ?? null,
            'gpl' => $data['guardian_place'] ?? null
        ]);
    }

    // Case-insensitive uniqueness check, excluding the user's own row --
    // email is the login lookup key, so a duplicate would let two accounts
    // collide at sign-in.
    public function emailTakenByOther($email, $excludeId) {
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE LOWER(email) = LOWER(:email) AND id != :id");
        $stmt->execute(['email' => $email, 'id' => $excludeId]);
        return (bool) $stmt->fetchColumn();
    }

    // Reversible: blocks login and hides the user from managers/selectors,
    // but the row and all related history stay untouched. Only from Active.
    public function suspend($id) {
        $stmt = $this->db->prepare("UPDATE users SET status = 'Inactive' WHERE id = :id AND status = 'Active'");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // Undoes a suspend. Only from Inactive.
    public function reactivate($id) {
        $stmt = $this->db->prepare("UPDATE users SET status = 'Active' WHERE id = :id AND status = 'Inactive'");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // Permanent, irreversible. Only reachable from Inactive (must be suspended
    // first). Never deletes the row -- attendance, payroll, audit_logs and
    // invoices keep referencing this user id -- it just marks the account so
    // it can never log in or appear anywhere again.
    public function softDelete($id) {
        $stmt = $this->db->prepare("UPDATE users SET status = 'Deleted' WHERE id = :id AND status = 'Inactive'");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // Sets a new login password. The caller is responsible for hashing it --
    // this never touches or logs the plaintext value.
    public function updatePassword($id, $passwordHash) {
        $stmt = $this->db->prepare("UPDATE users SET password_hash = :h WHERE id = :id");
        return $stmt->execute(['h' => $passwordHash, 'id' => $id]);
    }

    public function countActiveAdmins() {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role_id = 1 AND status = 'Active'");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
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
            WHERE u.role_id = 2 AND u.status = 'Active'
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
