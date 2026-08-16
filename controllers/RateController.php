<?php
require_once __DIR__ . '/../config/Database.php';

class RateController extends Controller {
    public function __construct() { $this->requireRole([1]); }

    public function index() {
        $db = Database::connect();
        $categories = $db->query("SELECT * FROM worker_categories ORDER BY id")->fetchAll();
        $this->view('rates/index', [
            'pageTitle' => 'Rate Configuration',
            'categories' => $categories
        ]);
    }

    public function updateDefault() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $db = Database::connect();
            $stmt = $db->prepare("UPDATE worker_categories SET default_rate = :rate WHERE id = :id");
            $stmt->execute(['rate' => $_POST['rate'], 'id' => $_POST['id']]);
            $this->logAudit('Config', "Set category #{$_POST['id']} daily rate to " . $_POST['rate']);
            header('Location: /rates');
        }
    }
}
