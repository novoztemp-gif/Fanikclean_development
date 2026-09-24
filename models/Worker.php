<?php
require_once __DIR__ . '/../config/Database.php';

class Worker {
    private $db;
    public function __construct() { $this->db = Database::connect(); }

    public function getAll($siteIds = null) {
        $query = "
            SELECT w.*, c.name as category_name, s.name as site_name 
            FROM workers w 
            LEFT JOIN worker_categories c ON w.category_id = c.id
            LEFT JOIN sites s ON w.site_id = s.id
        ";
        
        $params = [];
        if (is_array($siteIds)) {
            // A manager scoped to zero sites must see nothing -- not everything.
            if (count($siteIds) === 0) {
                $query .= " WHERE 1=0 ";
            } else {
                $placeholders = implode(',', array_fill(0, count($siteIds), '?'));
                $query .= " WHERE w.site_id IN ($placeholders) ";
                $params = $siteIds;
            }
        } elseif ($siteIds !== null) {
            $query .= " WHERE w.site_id = ? ";
            $params = [$siteIds];
        }
        
        $query .= " ORDER BY w.id DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Same result as getAll($siteIds) + the 3 Workers-page dropdown lookups, in one round trip.
    public function getAllWithDropdowns($siteIds = null) {
        $scoped = is_array($siteIds);
        $noAccess = $scoped && count($siteIds) === 0;

        $params = [];
        $siteFilter = '';
        if (!$noAccess && $scoped) {
            $params['site_ids'] = '{' . implode(',', array_map('intval', $siteIds)) . '}';
            $siteFilter = 'AND w.site_id = ANY(:site_ids::int[])';
        }

        $workersJson = "'[]'::json";
        if (!$noAccess) {
            $workersJson = "(SELECT COALESCE(json_agg(w), '[]'::json) FROM (
                SELECT wk.*, c.name as category_name, s.name as site_name
                FROM workers wk
                LEFT JOIN worker_categories c ON wk.category_id = c.id
                LEFT JOIN sites s ON wk.site_id = s.id
                WHERE 1=1 " . str_replace('w.site_id', 'wk.site_id', $siteFilter) . "
                ORDER BY wk.id DESC
            ) w)";
        }

        $sql = "SELECT
            $workersJson AS workers_json,
            (SELECT COALESCE(json_agg(x), '[]'::json) FROM (SELECT id, company_name FROM clients WHERE status = 'Active' ORDER BY company_name) x) AS clients_json,
            (SELECT COALESCE(json_agg(x), '[]'::json) FROM (SELECT id, name FROM worker_categories ORDER BY id) x) AS categories_json,
            (SELECT COALESCE(json_agg(x), '[]'::json) FROM (SELECT id, name, client_id FROM sites WHERE is_active = TRUE ORDER BY name) x) AS sites_json
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return [
            'workers' => json_decode($row['workers_json'], true) ?: [],
            'clients' => json_decode($row['clients_json'], true) ?: [],
            'categories' => json_decode($row['categories_json'], true) ?: [],
            'sites' => json_decode($row['sites_json'], true) ?: [],
        ];
    }

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT w.*, c.name as category_name, s.name as site_name 
            FROM workers w 
            LEFT JOIN worker_categories c ON w.category_id = c.id
            LEFT JOIN sites s ON w.site_id = s.id
            WHERE w.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $worker = $stmt->fetch();
        if ($worker) {
            $worker['assets'] = $this->getAssets($id);
        }
        return $worker;
    }

    // Soft delete: hides the worker from new selections (attendance, payroll,
    // site assignment) but keeps their id -- and therefore all attendance,
    // payroll and billing history that references it -- fully intact.
    public function softDelete($id) {
        $stmt = $this->db->prepare("UPDATE workers SET status = 'Removed' WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function restore($id) {
        $stmt = $this->db->prepare("UPDATE workers SET status = 'Active' WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getAssets($workerId) {
        $stmt = $this->db->prepare("SELECT * FROM worker_assets WHERE worker_id = :wid ORDER BY issue_date DESC");
        $stmt->execute(['wid' => $workerId]);
        return $stmt->fetchAll();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO workers (
                worker_code, full_name, mobile, aadhaar, doj, category_id, site_id, status,
                esi_number, pf_number, photo_path, age, experience, uniform_issue_date, uniform_details,
                guardian_name, guardian_phone, guardian_place
            ) 
            VALUES (
                :wc, :fn, :mob, :aadh, :doj, :cat, :site, :status,
                :esi, :pf, :photo, :age, :exp, :uid, :udet,
                :g_name, :g_phone, :g_place
            )
        ");
        return $stmt->execute([
            'wc' => 'W-' . rand(1000, 9999), 
            'fn' => $data['full_name'],
            'mob' => $data['mobile'],
            'aadh' => $data['aadhaar'],
            'doj' => $data['doj'],
            'cat' => $data['category_id'],
            'site' => $data['site_id'],
            'status' => $data['status'],
            'esi' => $data['esi_number'] ?? null,
            'pf' => $data['pf_number'] ?? null,
            'photo' => $data['photo_path'] ?? null,
            'age' => !empty($data['age']) ? $data['age'] : null,
            'exp' => $data['experience'] ?? null,
            'uid' => !empty($data['uniform_issue_date']) ? $data['uniform_issue_date'] : null,
            'udet' => $data['uniform_details'] ?? null,
            'g_name' => $data['guardian_name'] ?? null,
            'g_phone' => $data['guardian_phone'] ?? null,
            'g_place' => $data['guardian_place'] ?? null
        ]);
    }

    public function update($id, $data) {
        $sql = "
            UPDATE workers 
            SET full_name = :fn, 
                mobile = :mob, 
                aadhaar = :aadh, 
                doj = :doj, 
                category_id = :cat, 
                site_id = :site, 
                status = :status,
                esi_number = :esi,
                pf_number = :pf,
                age = :age,
                experience = :exp,
                uniform_issue_date = :uid,
                uniform_details = :udet,
                guardian_name = :g_name,
                guardian_phone = :g_phone,
                guardian_place = :g_place
        ";
        
        $params = [
            'id' => $id,
            'fn' => $data['full_name'],
            'mob' => $data['mobile'],
            'aadh' => $data['aadhaar'],
            'doj' => $data['doj'],
            'cat' => $data['category_id'],
            'site' => $data['site_id'],
            'status' => $data['status'],
            'esi' => $data['esi_number'] ?? null,
            'pf' => $data['pf_number'] ?? null,
            'age' => !empty($data['age']) ? $data['age'] : null,
            'exp' => $data['experience'] ?? null,
            'uid' => !empty($data['uniform_issue_date']) ? $data['uniform_issue_date'] : null,
            'udet' => $data['uniform_details'] ?? null,
            'g_name' => $data['guardian_name'] ?? null,
            'g_phone' => $data['guardian_phone'] ?? null,
            'g_place' => $data['guardian_place'] ?? null
        ];

        if (isset($data['photo_path'])) {
            $sql .= ", photo_path = :photo";
            $params['photo'] = $data['photo_path'];
        }

        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function bulkUpdateSite($workerIds, $siteId) {
        if (empty($workerIds) || !is_array($workerIds)) return false;
        
        $placeholders = implode(',', array_fill(0, count($workerIds), '?'));
        
        $stmt = $this->db->prepare("UPDATE workers SET site_id = ? WHERE id IN ($placeholders)");
        $params = array_merge([$siteId], $workerIds);
        
        return $stmt->execute($params);
    }

    public function bulkUpdateUniform($workerIds, $details, $issueDate) {
        if (empty($workerIds) || !is_array($workerIds)) return false;
        
        $placeholders = implode(',', array_fill(0, count($workerIds), '?'));
        
        $stmt = $this->db->prepare("UPDATE workers SET uniform_details = ?, uniform_issue_date = ? WHERE id IN ($placeholders)");
        $params = array_merge([$details, $issueDate], $workerIds);
        
        return $stmt->execute($params);
    }
}
