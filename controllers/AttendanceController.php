<?php
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/ManagerAttendance.php';

class AttendanceController extends Controller {
    
    public function index() {
        $this->checkAuth();
        $fromDate = $_GET['from_date'] ?? $_GET['date'] ?? date('Y-m-d');
        $toDate = $_GET['to_date'] ?? $fromDate;
        $filterSiteId = $_GET['site_id'] ?? null; // optional: narrow the list to one site
        $month = date('Y-m', strtotime($fromDate));

        $isAdmin = $this->isAdmin();
        $scopeSiteIds = $isAdmin ? null : $this->getAssignedSiteIds();

        $attModel = new Attendance();
        $bundle = $attModel->getIndexBundle($isAdmin, $scopeSiteIds, $month, $fromDate, $filterSiteId);
        $sites = $bundle['sites'];
        $workers = $bundle['workers'];

        $this->view('attendance/index', [
            'pageTitle' => 'Worker Attendance',
            'workers' => $workers,
            'sites' => $sites,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'selectedSiteId' => $filterSiteId
        ]);
    }

    /**
     * Read-only monthly attendance register (muster roll): workers × days matrix.
     * Month + optional site filter, role-scoped like index().
     */
    public function register() {
        $this->checkAuth();
        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) { $month = date('Y-m'); }
        $filterSiteId = $_GET['site_id'] ?? null;

        $isAdmin = $this->isAdmin();
        $scopeSiteIds = $isAdmin ? null : $this->getAssignedSiteIds();

        $attModel = new Attendance();
        $bundle = $attModel->getRegisterBundle($isAdmin, $scopeSiteIds, $month, $filterSiteId);
        $sites = $bundle['sites'];
        $rows = $bundle['rows'];

        $this->view('attendance/register', [
            'pageTitle' => 'Attendance Register',
            'rows' => $rows,
            'sites' => $sites,
            'month' => $month,
            'selectedSiteId' => $filterSiteId
        ]);
    }

    /**
     * Downloads the same monthly register as a proper .xlsx workbook —
     * one column per calendar day plus the P/Off/H/PL/SD/OT totals, exactly
     * mirroring what's on screen at /attendance/register.
     */
    public function exportRegister() {
        $this->checkAuth();
        require_once __DIR__ . '/../core/XlsxWriter.php';

        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) { $month = date('Y-m'); }
        $filterSiteId = $_GET['site_id'] ?? null;

        $scopeSiteIds = $this->isAdmin() ? null : $this->getAssignedSiteIds();

        $attModel = new Attendance();
        $rows = $attModel->getMonthlyRegister($month, $scopeSiteIds, $filterSiteId);

        $daysInMonth = (int)date('t', strtotime($month . '-01'));
        $codeLabel = ['p' => 'P', 'off' => 'Off', 'h' => 'H', 'pl' => 'PL', 'sd' => 'SD'];

        $headers = ['Worker Name', 'Worker Code', 'Category', 'Site'];
        $colWidths = [22, 12, 14, 20];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $headers[] = date('d', strtotime(sprintf('%s-%02d', $month, $d)));
            $colWidths[] = 4;
        }
        foreach (['Present', 'Off Duty', 'Half-Day', 'Paid Leave', 'Special Duty', 'OT Hours'] as $totalLabel) {
            $headers[] = $totalLabel;
            $colWidths[] = 11;
        }

        $data = [];
        foreach ($rows as $r) {
            $line = [
                $r['full_name'],
                $r['worker_code'],
                $r['category_name'] ?? 'General',
                $r['site_name'],
            ];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $cell = $r['days'][$d] ?? null;
                $line[] = ($cell && isset($codeLabel[$cell['status']])) ? $codeLabel[$cell['status']] : '';
            }
            $line[] = $r['totals']['p'] ?: 0;
            $line[] = $r['totals']['off'] ?: 0;
            $line[] = $r['totals']['h'] ?: 0;
            $line[] = $r['totals']['pl'] ?: 0;
            $line[] = $r['totals']['sd'] ?: 0;
            $line[] = $r['totals']['ot'] ?: 0;
            $data[] = $line;
        }

        $monthLabel = date('F Y', strtotime($month . '-01'));
        $siteLabel = '';
        if ($filterSiteId) {
            $siteStmt = Database::connect()->prepare("SELECT name FROM sites WHERE id = :id");
            $siteStmt->execute(['id' => $filterSiteId]);
            $siteName = $siteStmt->fetchColumn();
            if ($siteName) { $siteLabel = ' — ' . $siteName; }
        }

        $this->logAudit('Attendance', "Exported attendance register for $monthLabel");

        XlsxWriter::download(
            "attendance-register-$month.xlsx",
            'Attendance Register',
            "FanikClean — Attendance Register — $monthLabel$siteLabel",
            $headers,
            $data,
            $colWidths
        );
    }

    /**
     * Read-only monthly register for manager attendance (managers × days matrix).
     * Admin-only, mirrors register() but for the manager_attendance table.
     */
    public function managerRegister() {
        $this->requireRole([1]);
        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) { $month = date('Y-m'); }

        $maModel = new ManagerAttendance();
        $rows = $maModel->getMonthlyRegister($month);

        $this->view('attendance/manager_register', [
            'pageTitle' => 'Manager Attendance Register',
            'rows' => $rows,
            'month' => $month
        ]);
    }

    public function managerAttendance() {
        $this->requireRole([1]);
        $date = $_GET['date'] ?? date('Y-m-d');
        $monthYear = date('Y-m', strtotime($date));
        
        $maModel = new ManagerAttendance();
        $bundle = $maModel->getManagerAttendanceBundle($monthYear);
        $managers = $bundle['managers'];
        $history = $bundle['history'];
        $plCounts = $bundle['plCounts'];

        $this->view('attendance/manager', [
            'pageTitle' => 'Daily Manager Attendance',
            'managers' => $managers,
            'history' => $history,
            'date' => $date,
            'month' => $monthYear,
            'plCounts' => $plCounts
        ]);
    }

    public function viewMyAttendance() {
        $this->requireRole([2]); // Manager only staff
        
        $monthYear = $_GET['month'] ?? date('Y-m');
        $maModel = new ManagerAttendance();
        $history = $maModel->getByUser($_SESSION['user_id'], $monthYear);

        $this->view('attendance/my_attendance', [
            'pageTitle' => 'My Attendance History',
            'history' => $history,
            'month' => $monthYear
        ]);
    }

    public function saveBulk() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fromDate = $_POST['from_date'] ?? date('Y-m-d');
            $filterSiteId = $_POST['site_id'] ?? null; // only used to preserve the filter on redirect
            $attendanceData = $_POST['attendance'] ?? [];

            if (empty($attendanceData)) { $this->redirect('/attendance'); return; }

            // Managers may only save workers within their assigned sites; each worker
            // is saved under their own site (the grid spans multiple sites now).
            $allowedSiteIds = null;
            if (!$this->isAdmin()) {
                $allowedSiteIds = $this->getAssignedSiteIds();
            }

            $attModel = new Attendance();
            // Each worker carries its own selected dates (from the calendar picker).
            $savedRows = $attModel->saveGrid($attendanceData, $_SESSION['user_id'], $allowedSiteIds);

            if ($savedRows === false) {
                $_SESSION['error'] = "Failed to save attendance.";
            } else {
                $this->logAudit('Attendance', "Saved worker attendance ($savedRows record" . ($savedRows == 1 ? '' : 's') . ")");
                $_SESSION['toast'] = "Attendance saved ($savedRows record" . ($savedRows == 1 ? '' : 's') . ").";
            }

            $redirect = '/attendance?from_date=' . $fromDate;
            if ($filterSiteId) { $redirect .= '&site_id=' . $filterSiteId; }
            $this->redirect($redirect);
        }
    }

    public function saveManagerAttendance() {
        $this->requireRole([1]);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $date = $_POST['attendance_date'] ?? date('Y-m-d'); // anchor day (for redirect)
            // Each manager carries its own selected dates (from the calendar picker).
            $attendanceData = $_POST['manager_attendance'] ?? []; // [uid => [status, note, dates]]

            $maModel = new ManagerAttendance();
            $savedRows = $maModel->saveGrid($attendanceData, $_SESSION['user_id']);

            if ($savedRows === false) {
                $_SESSION['error'] = "Failed to update internal records";
            } else {
                $this->logAudit('Attendance', "Saved manager attendance ($savedRows record" . ($savedRows == 1 ? '' : 's') . ")");
                $_SESSION['toast'] = "Manager attendance saved ($savedRows record" . ($savedRows == 1 ? '' : 's') . ").";
            }
            $this->redirect('/attendance/manager?date=' . $date);
        }
    }
}
