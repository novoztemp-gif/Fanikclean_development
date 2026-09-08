<?php
require_once __DIR__ . '/../models/Billing.php';

class BillingController extends Controller {
    public function __construct() {
        $this->requireRole([1, 2]);
    }

    public function index() {
        require_once __DIR__ . '/../models/Invoice.php';
        $invModel = new Invoice();

        $siteScope = $this->isAdmin() ? null : $this->getAssignedSiteIds();
        $data = $invModel->getPendingBillingWithDropdowns($siteScope);
        $pending = $data['pending'];
        $clients = $data['clients'];
        $sites = $data['sites'];

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

            // Managers may only bill their assigned sites; enforce it regardless
            // of what was posted (a tampered/stale form can't bill other sites).
            $allowedSiteIds = null;
            if (!$this->isAdmin()) {
                $allowedSiteIds = $this->getAssignedSiteIds();
                if ($siteId && !in_array($siteId, $allowedSiteIds)) {
                    $_SESSION['error'] = "You can only generate billing for your assigned sites.";
                    $this->redirect('/billing');
                    return;
                }
            }

            $billingModel = new Billing();
            $billingModel->generateForDateRange($fromDate, $toDate, $clientId, $siteId, $allowedSiteIds);

            $scopeMsg = $clientId ? "for selected client" : "for all clients";
            $dateLabel = date('d M', strtotime($fromDate)) . ' – ' . date('d M Y', strtotime($toDate));
            $this->logAudit('Billing', "Generated billing $scopeMsg ($dateLabel)");
            $_SESSION['toast'] = "Billing generated $scopeMsg ($dateLabel)";
            $this->redirect('/billing');
        }
    }
}
