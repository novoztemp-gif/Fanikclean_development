<?php
require_once __DIR__ . '/../models/Billing.php';

class BillingController extends Controller {
    public function index() {
        require_once __DIR__ . '/../models/Invoice.php';
        require_once __DIR__ . '/../models/Client.php';
        $invModel = new Invoice();
        $clientModel = new Client();
        
        $siteScope = $this->isAdmin() ? null : $this->getAssignedSiteIds();
        $pending = $invModel->getPendingBilling($siteScope);
        $clients = $clientModel->getAll();
        
        $db = Database::connect();
        $sites = $db->query("SELECT id, name, client_id FROM sites ORDER BY name")->fetchAll();

        $this->view('billing/index', [
            'pageTitle' => 'Billing Engine',
            'pending' => $pending,
            'clients' => $clients,
            'sites' => $sites
        ]);
    }

    public function generate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fromDate = $_POST['from_date'] ?? date('Y-m-01');
            $toDate = $_POST['to_date'] ?? date('Y-m-t');
            $clientId = $_POST['client_id'] ?? null;
            $siteId = $_POST['site_id'] ?? null;
            
            $billingModel = new Billing();
            $billingModel->generateForDateRange($fromDate, $toDate, $clientId, $siteId);

            $scopeMsg = $clientId ? "for selected client" : "for all clients";
            $dateLabel = date('d M', strtotime($fromDate)) . ' – ' . date('d M Y', strtotime($toDate));
            $this->logAudit('Billing', "Generated billing $scopeMsg ($dateLabel)");
            $_SESSION['toast'] = "Billing generated $scopeMsg ($dateLabel)";
            $this->redirect('/billing');
        }
    }
}
