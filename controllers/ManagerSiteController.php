<?php
require_once __DIR__ . '/../models/User.php';

class ManagerSiteController extends Controller {
    public function __construct() {
        $this->requireRole([1]);
    }

    public function index() {
        $userModel = new User();
        $managers = $userModel->getManagers();
        
        $db = Database::connect();
        $sites = $db->query("SELECT id, name FROM sites ORDER BY name")->fetchAll();

        // Get current assignments for each manager
        foreach ($managers as &$m) {
            $m['assigned_site_ids'] = $userModel->getAssignedSiteIds($m['id']);
        }

        $this->view('users/assignments', [
            'pageTitle' => 'Manager-Site Assignments',
            'managers' => $managers,
            'sites' => $sites
        ]);
    }

    public function assign() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_POST['user_id'] ?? null;
            $siteIds = $_POST['site_ids'] ?? [];

            if ($userId) {
                $userModel = new User();
                if ($userModel->saveAssignments($userId, $siteIds)) {
                    $this->logAudit('Users', "Updated site assignments for user #$userId (" . count($siteIds) . " site(s))");
                    $_SESSION['toast'] = "Site assignments updated successfully";
                } else {
                    $_SESSION['error'] = "Failed to update assignments";
                }
            }
            $this->redirect('/users/assignments');
        }
    }
}
