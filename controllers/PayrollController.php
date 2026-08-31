<?php
require_once __DIR__ . '/../models/Payroll.php';

class PayrollController extends Controller {
    public function __construct() {
        $this->requireRole([1, 2]);
    }

    public function index() {
        $payrollModel = new Payroll();
        
        $month = $_GET['month'] ?? date('Y-m');
        $siteId = $_GET['site_id'] ?? null;
        $clientId = $_GET['client_id'] ?? null;
        
        $siteScope = $this->isAdmin() ? ($siteId ?: null) : ($siteId ?: $this->getAssignedSiteIds());
        
        // Final sanity check for managers
        if (!$this->isAdmin() && $siteId && !$this->canAccessSite($siteId)) {
            $siteScope = $this->getAssignedSiteIds();
            $_SESSION['error'] = "Unauthorized site selection";
        }

        $payrolls = $payrollModel->getAll($siteScope, $month, $clientId);
        
        $db = Database::connect();
        
        $clients = $db->query("SELECT id, company_name FROM clients ORDER BY company_name")->fetchAll();

        if ($this->isAdmin()) {
            $sites = $db->query("SELECT id, name, client_id FROM sites WHERE is_active = TRUE ORDER BY name")->fetchAll();
        } else {
            $assignedIds = $this->getAssignedSiteIds();
            if (!empty($assignedIds)) {
                $placeholders = implode(',', array_fill(0, count($assignedIds), '?'));
                $stmt = $db->prepare("SELECT id, name, client_id FROM sites WHERE id IN ($placeholders) AND is_active = TRUE ORDER BY name");
                $stmt->execute($assignedIds);
                $sites = $stmt->fetchAll();
            } else {
                $sites = [];
            }
        }

        $this->view('payroll/index', [
            'pageTitle' => 'Payroll Center',
            'payrolls' => $payrolls,
            'sites' => $sites,
            'clients' => $clients,
            'selectedMonth' => $month,
            'selectedSiteId' => $siteId,
            'selectedClientId' => $clientId
        ]);
    }

    public function export() {
        $month = $_GET['month'] ?? date('Y-m');
        $siteId = $_GET['site_id'] ?? null;
        $clientId = $_GET['client_id'] ?? null;
        
        $siteScope = $this->isAdmin() ? ($siteId ?: null) : ($siteId ?: $this->getAssignedSiteIds());
        
        $payrollModel = new Payroll();
        $payrolls = $payrollModel->getAll($siteScope, $month, $clientId);

        if (ob_get_level()) ob_end_clean();

        $filename = "Fanikclean_Payroll_" . ($month) . ".xls";
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        // Simple but effective HTML Table for "True Excel" experience
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><style>td { border: 0.5pt solid #ccc; }</style></head><body>';
        echo '<table>';
        echo '<tr><th colspan="9" style="font-size:18px; height:30px;">FANIKCLEAN SERVICES - PAYROLL REPORT (' . $month . ')</th></tr>';
        echo '<tr style="background:#f2f2f2; font-weight:bold;">';
        echo '<th>Worker Name</th><th>Category</th><th>Site</th><th>Month-Year</th><th>Days</th><th>Basic Pay</th><th>OT Pay</th><th>Advance</th><th>Net Salary</th>';
        echo '</tr>';

        foreach ($payrolls as $p) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($p['name']) . '</td>';
            echo '<td>' . htmlspecialchars($p['category_name']) . '</td>';
            echo '<td>' . htmlspecialchars($p['site_name']) . '</td>';
            echo '<td>' . htmlspecialchars($p['month_year']) . '</td>';
            echo '<td>' . number_format($p['days_worked'], 1) . '</td>';
            echo '<td>' . number_format($p['basic_pay'], 2) . '</td>';
            echo '<td>' . number_format($p['ot_pay'], 2) . '</td>';
            echo '<td>' . number_format($p['advance_deduction'], 2) . '</td>';
            echo '<td style="font-weight:bold;">' . number_format($p['net_pay'], 2) . '</td>';
            echo '</tr>';
        }
        echo '</table></body></html>';
        exit;
    }

    public function approve() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $monthYear = $_POST['month_year'] ?? date('Y-m');
            
            $payrollModel = new Payroll();
            $payrollModel->generateMonthly($monthYear);

            $this->logAudit('Payroll', "Computed & archived payroll for $monthYear");
            $_SESSION['toast'] = "Payroll computed & archived for $monthYear";
            $this->redirect('/payroll?month=' . $monthYear);
        }
    }
}
