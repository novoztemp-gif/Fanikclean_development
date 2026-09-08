<?php
require_once __DIR__ . '/../models/User.php';

class ManagerSiteController extends Controller {
    public function __construct() {
        $this->requireRole([1]);
    }

    public function index() {
        $db = Database::connect();
        $row = $db->query("
            SELECT
                (SELECT COALESCE(json_agg(m), '[]'::json) FROM (
                    SELECT u.*, r.name as role_name,
                        COALESCE((
                            SELECT json_agg(usa.site_id)
                            FROM user_site_assignments usa WHERE usa.user_id = u.id
                        ), '[]'::json) AS assigned_site_ids
                    FROM users u
                    JOIN roles r ON u.role_id = r.id
                    WHERE u.role_id = 2 AND u.status = 'Active'
                    ORDER BY u.full_name ASC
                ) m) AS managers_json,
                (SELECT COALESCE(json_agg(s), '[]'::json) FROM (
                    SELECT id, name FROM sites WHERE is_active = TRUE ORDER BY name
                ) s) AS sites_json
        ")->fetch();

        $managers = json_decode($row['managers_json'], true) ?: [];
        $sites = json_decode($row['sites_json'], true) ?: [];

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
