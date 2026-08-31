<?php
require_once __DIR__ . '/../config/Database.php';

class SiteController extends Controller {
    public function __construct() {
        $this->requireRole([1]);
    }
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $clientId = $_POST['client_id'] ?? null;
            $name = $_POST['name'] ?? '';
            $address = $_POST['address'] ?? '';
            if ($clientId && $name) {
                $db = Database::connect();
                $stmt = $db->prepare("INSERT INTO sites (client_id, name, address) VALUES (:c, :n, :a)");
                $stmt->execute(['c' => $clientId, 'n' => $name, 'a' => $address]);
                $this->logAudit('Sites', "Created site '$name' for client #$clientId");
                $_SESSION['toast'] = "Site created successfully";
            }
        }
        $this->redirect('/clients');
    }

    // Soft-delete: hides the site from all operational selectors while keeping
    // its attendance/billing/invoice history intact (no rows are deleted).
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                $db = Database::connect();
                $s = $db->prepare("SELECT name FROM sites WHERE id = :id");
                $s->execute(['id' => $id]);
                $name = $s->fetchColumn();
                if ($name !== false) {
                    $stmt = $db->prepare("UPDATE sites SET is_active = FALSE WHERE id = :id");
                    $stmt->execute(['id' => $id]);
                    $this->logAudit('Sites', "Deactivated site '$name' (#$id)");
                    $_SESSION['toast'] = "Site '$name' deactivated";
                }
            }
        }
        $this->redirect('/clients');
    }

    // Reverse a soft-delete.
    public function restore() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                $db = Database::connect();
                $s = $db->prepare("SELECT name FROM sites WHERE id = :id");
                $s->execute(['id' => $id]);
                $name = $s->fetchColumn();
                if ($name !== false) {
                    $stmt = $db->prepare("UPDATE sites SET is_active = TRUE WHERE id = :id");
                    $stmt->execute(['id' => $id]);
                    $this->logAudit('Sites', "Reactivated site '$name' (#$id)");
                    $_SESSION['toast'] = "Site '$name' reactivated";
                }
            }
        }
        $this->redirect('/clients');
    }
}
