<?php
require_once __DIR__ . '/../models/Leave.php';

class LeaveController extends Controller {
    public function __construct() { $this->requireRole([1, 2]); }
    
    public function index() {
        $model = new Leave();
        $requests = $model->getAll();
        $this->view('leave/index', [
            'pageTitle' => 'Leave Management',
            'requests' => $requests
        ]);
    }

    public function approve() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $model = new Leave();
            $id = $_POST['id'];
            $model->updateStatus($id, 'Approved', $_SESSION['user_id']);
            $this->logAudit('Leave', "Approved leave request #$id");
            header('Location: /leave');
        }
    }

    public function reject() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $model = new Leave();
            $id = $_POST['id'];
            $model->updateStatus($id, 'Rejected', $_SESSION['user_id']);
            $this->logAudit('Leave', "Rejected leave request #$id");
            header('Location: /leave');
        }
    }
}
