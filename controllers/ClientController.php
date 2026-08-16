<?php
require_once __DIR__ . '/../models/Client.php';

class ClientController extends Controller {
    public function __construct() {
        $this->requireRole([1]);
    }

    public function index() {
        $clientModel = new Client();
        $clients = $clientModel->getAll();
        
        // Fetch sites for each client
        foreach ($clients as &$c) {
            $c['sites'] = $clientModel->getSitesByClientId($c['id']);
        }

        $this->view('clients/index', [
            'pageTitle' => 'Clients & Sites',
            'clients' => $clients
        ]);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $clientModel = new Client();
            $data = [
                'company_name' => $_POST['company_name'] ?? '',
                'contact_person' => $_POST['contact_person'] ?? '',
                'mobile' => $_POST['mobile'] ?? '',
                'email' => $_POST['email'] ?? '',
                'gstin' => $_POST['gstin'] ?? '',
                'address' => $_POST['address'] ?? '',
                'contract_start' => !empty($_POST['contract_start']) ? $_POST['contract_start'] : null,
                'contract_end' => !empty($_POST['contract_end']) ? $_POST['contract_end'] : null,
                'billing_cycle' => $_POST['billing_cycle'] ?? 'Monthly'
            ];
            $clientModel->create($data);
            $this->logAudit('Clients', "Added client: " . $data['company_name']);
            $_SESSION['toast'] = "Client added successfully";
            $this->redirect('/clients');
        }
    }
}
