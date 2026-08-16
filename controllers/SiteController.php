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
}
