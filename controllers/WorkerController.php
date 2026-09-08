<?php
require_once __DIR__ . '/../models/Worker.php';

class WorkerController extends Controller {
    public function __construct() {
        $this->requireRole([1, 2]);
    }

    public function index() {
        $workerModel = new Worker();

        $siteScope = $this->isAdmin() ? null : $this->getAssignedSiteIds();
        $data = $workerModel->getAllWithDropdowns($siteScope);
        $workers = $data['workers'];
        $clients = $data['clients'];
        $categories = $data['categories'];
        $sites = $data['sites'];

        $this->view('workers/index', [
            'pageTitle' => 'Workers',
            'workers' => $workers,
            'categories' => $categories,
            'sites' => $sites,
            'clients' => $clients
        ]);
    }

    public function profile() {
        $id = $_GET['id'] ?? null;
        if (!$id) { $this->redirect('/workers'); return; }

        $workerModel = new Worker();
        $worker = $workerModel->getById($id);
        
        if (!$worker) { $this->redirect('/workers'); return; }
        
        // Scope Check
        if (!$this->canAccessSite($worker['site_id'])) {
            $_SESSION['error'] = "Access denied to this site scope";
            $this->redirect('/workers');
            return;
        }

        $this->view('workers/profile', [
            'worker' => $worker,
            'pageTitle' => 'Worker Profile: ' . $worker['full_name']
        ]);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $workerModel = new Worker();
            
            $photoPath = $this->handlePhotoUpload();

            $data = [
                'full_name' => $_POST['full_name'] ?? '',
                'mobile' => $_POST['mobile'] ?? '',
                'aadhaar' => $_POST['aadhaar'] ?? '',
                'doj' => $_POST['doj'] ?? date('Y-m-d'),
                'category_id' => $_POST['category_id'] ?? 4,
                'site_id' => !empty($_POST['site_id']) ? $_POST['site_id'] : null,
                'status' => $_POST['status'] ?? 'Active',
                'esi_number' => $_POST['esi_number'] ?? '',
                'pf_number' => $_POST['pf_number'] ?? '',
                'age' => $_POST['age'] ?? null,
                'experience' => $_POST['experience'] ?? '',
                'uniform_issue_date' => $_POST['uniform_issue_date'] ?? null,
                'uniform_details' => $_POST['uniform_details'] ?? '',
                'guardian_name' => $_POST['guardian_name'] ?? '',
                'guardian_phone' => $_POST['guardian_phone'] ?? '',
                'guardian_place' => $_POST['guardian_place'] ?? '',
                'photo_path' => $photoPath
            ];
            
            $workerModel->create($data);
            $this->logAudit('Workers', "Added worker to roster: " . $data['full_name']);
            $_SESSION['toast'] = "Worker added to roster";
            $this->redirect('/workers');
        }
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            if (!$id) { $this->redirect('/workers'); return; }

            $workerModel = new Worker();
            
            $data = [
                'full_name' => $_POST['full_name'] ?? '',
                'mobile' => $_POST['mobile'] ?? '',
                'aadhaar' => $_POST['aadhaar'] ?? '',
                'doj' => $_POST['doj'] ?? date('Y-m-d'),
                'category_id' => $_POST['category_id'] ?? 4,
                'site_id' => !empty($_POST['site_id']) ? $_POST['site_id'] : null,
                'status' => $_POST['status'] ?? 'Active',
                'esi_number' => $_POST['esi_number'] ?? '',
                'pf_number' => $_POST['pf_number'] ?? '',
                'age' => $_POST['age'] ?? null,
                'experience' => $_POST['experience'] ?? '',
                'uniform_issue_date' => $_POST['uniform_issue_date'] ?? null,
                'uniform_details' => $_POST['uniform_details'] ?? '',
                'guardian_name' => $_POST['guardian_name'] ?? '',
                'guardian_phone' => $_POST['guardian_phone'] ?? '',
                'guardian_place' => $_POST['guardian_place'] ?? ''
            ];

            $photoPath = $this->handlePhotoUpload();
            if ($photoPath) {
                $data['photo_path'] = $photoPath;
            }
            
            $workerModel->update($id, $data);
            $this->logAudit('Workers', "Updated worker #$id: " . $data['full_name']);
            $_SESSION['toast'] = "Worker profile updated";
            $this->redirect('/workers');
        }
    }

    public function apiGetBySite() {
        $this->checkAuth();
        header('Content-Type: application/json');
        $site_id = $_GET['site_id'] ?? null;
        $month = $_GET['month'] ?? date('Y-m'); // 2026-04
        $date  = $_GET['date'] ?? date('Y-m-d'); // day whose saved attendance to pre-load
        if (!$site_id) { echo json_encode([]); return; }

        $db = Database::connect();
        $stmt = $db->prepare("
            SELECT w.id, w.full_name, w.worker_code, w.category_id, wc.name as category_name,
                   COALESCE(att.pl_count, 0) as pl_count,
                   td.status   as saved_status,
                   td.ot_hours as saved_ot,
                   td.note     as saved_note
            FROM workers w
            JOIN worker_categories wc ON w.category_id = wc.id
            LEFT JOIN (
                SELECT worker_id, COUNT(*) as pl_count
                FROM attendance
                WHERE status = 'pl' AND TO_CHAR(attendance_date, 'YYYY-MM') = :my
                GROUP BY worker_id
            ) att ON w.id = att.worker_id
            LEFT JOIN attendance td ON td.worker_id = w.id AND td.attendance_date = :dt
            WHERE w.site_id = :sid AND w.status = 'Active'
            ORDER BY w.full_name ASC
        ");
        $stmt->execute(['sid' => $site_id, 'my' => $month, 'dt' => $date]);
        echo json_encode($stmt->fetchAll());
    }

    public function addAsset() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $workerId = $_POST['worker_id'];
            $item = $_POST['item_name'];
            $date = $_POST['issue_date'] ?? date('Y-m-d');
            
            $db = Database::connect();
            $stmt = $db->prepare("INSERT INTO worker_assets (worker_id, item_name, issue_date) VALUES (?, ?, ?)");
            $stmt->execute([$workerId, $item, $date]);

            $this->logAudit('Workers', "Issued asset '$item' to worker #$workerId");
            $_SESSION['toast'] = "Asset ($item) issued successfully";
            $this->redirect('/workers/profile?id=' . $workerId);
        }
    }

    public function deleteAsset() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $assetId = $_POST['asset_id'];
            $workerId = $_POST['worker_id'];
            
            $db = Database::connect();
            $stmt = $db->prepare("DELETE FROM worker_assets WHERE id = ?");
            $stmt->execute([$assetId]);

            $this->logAudit('Workers', "Removed asset #$assetId from worker #$workerId");
            $_SESSION['toast'] = "Record removed from history";
            $this->redirect('/workers/profile?id=' . $workerId);
        }
    }

    private function handlePhotoUpload() {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['photo']['tmp_name'];
            $name = time() . '_' . basename($_FILES['photo']['name']);
            $targetPath = 'uploads/workers/' . $name;
            
            if (move_uploaded_file($tmpPath, $targetPath)) {
                return '/' . $targetPath;
            }
        }
        return null;
    }

    public function bulkTransfer() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $workerIds = $_POST['worker_ids'] ?? [];
            $siteId = $_POST['site_id'] ?? null;
            
            if (!empty($workerIds) && !empty($siteId)) {
                $workerModel = new Worker();
                if ($workerModel->bulkUpdateSite($workerIds, $siteId)) {
                    $this->logAudit('Workers', "Transferred " . count($workerIds) . " worker(s) to site #$siteId");
                    $_SESSION['toast'] = "Successfully transferred " . count($workerIds) . " workers.";
                } else {
                    $_SESSION['error'] = "Failed to transfer workers.";
                }
            } else {
                $_SESSION['error'] = "Invalid selection or destination site.";
            }
            $this->redirect('/workers');
        }
    }

    public function bulkUniform() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $workerIds = $_POST['worker_ids'] ?? [];
            $details = $_POST['uniform_details'] ?? '';
            $issueDate = $_POST['uniform_issue_date'] ?? date('Y-m-d');
            
            if (!empty($workerIds)) {
                $workerModel = new Worker();
                if ($workerModel->bulkUpdateUniform($workerIds, $details, $issueDate)) {
                    $this->logAudit('Workers', "Updated uniform for " . count($workerIds) . " worker(s)");
                    $_SESSION['toast'] = "Uniform updated for " . count($workerIds) . " workers.";
                } else {
                    $_SESSION['error'] = "Failed to update uniforms.";
                }
            } else {
                $_SESSION['error'] = "No workers selected.";
            }
            $this->redirect('/workers');
        }
    }
}

