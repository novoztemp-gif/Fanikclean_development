<?php
require_once __DIR__ . '/../models/Billing.php';

class BillingController extends Controller {
    public function __construct() {
        $this->requireRole([1, 2]);
    }

    public function index() {
        require_once __DIR__ . '/../models/Invoice.php';
        require_once __DIR__ . '/../models/Client.php';
        $invModel = new Invoice();
        $clientModel = new Client();

        $siteScope = $this->isAdmin() ? null : $this->getAssignedSiteIds();
        $pending = $invModel->getPendingBilling($siteScope);

        $db = Database::connect();

        // Scope the Client/Site form dropdowns: admin sees all; a manager sees
        // only their assigned sites and the clients that own them.
        if ($this->isAdmin()) {
            $clients = $clientModel->getAll();
            $sites = $db->query("SELECT id, name, client_id FROM sites ORDER BY name")->fetchAll();
        } else {
            $assigned = $this->getAssignedSiteIds();
            if (!empty($assigned)) {
                $ph = implode(',', array_fill(0, count($assigned), '?'));
                $sStmt = $db->prepare("SELECT id, name, client_id FROM sites WHERE id IN ($ph) ORDER BY name");
                $sStmt->execute(array_values($assigned));
                $sites = $sStmt->fetchAll();

                $cStmt = $db->prepare("
                    SELECT DISTINCT c.id, c.company_name
                    FROM clients c JOIN sites s ON s.client_id = c.id
                    WHERE s.id IN ($ph) ORDER BY c.company_name
                ");
                $cStmt->execute(array_values($assigned));
                $clients = $cStmt->fetchAll();
            } else {
                $sites = [];
                $clients = [];
            }
        }

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
