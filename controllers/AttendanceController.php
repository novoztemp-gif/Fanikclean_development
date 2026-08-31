<?php
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/ManagerAttendance.php';
require_once __DIR__ . '/../models/User.php';

class AttendanceController extends Controller {
    
    public function index() {
        $this->checkAuth();
        $fromDate = $_GET['from_date'] ?? $_GET['date'] ?? date('Y-m-d');
        $toDate = $_GET['to_date'] ?? $fromDate;
        $filterSiteId = $_GET['site_id'] ?? null; // optional: narrow the list to one site
        $month = date('Y-m', strtotime($fromDate));

        $db = Database::connect();

        // Resolve the site scope for the current user.
        if ($this->isAdmin()) {
            $sites = $db->query("SELECT id, name FROM sites WHERE is_active = TRUE ORDER BY name")->fetchAll();
            $scopeSiteIds = null; // null = all sites
        } else {
            // Read assignments live from the DB — the login-time session snapshot
            // goes stale when an Admin assigns sites after the manager logged in.
            $userModel = new User();
            $scopeSiteIds = $userModel->getAssignedSiteIds($_SESSION['user_id']);
            $_SESSION['assigned_site_ids'] = $scopeSiteIds;
            if (empty($scopeSiteIds)) {
                $sites = [];
            } else {
                $ph = implode(',', array_fill(0, count($scopeSiteIds), '?'));
                $stmt = $db->prepare("SELECT id, name FROM sites WHERE id IN ($ph) AND is_active = TRUE ORDER BY name");
                $stmt->execute(array_values($scopeSiteIds));
                $sites = $stmt->fetchAll();
            }
        }

        // Load every worker in scope (all sites by default), with their site name,
        // monthly PL count, and any attendance already saved for the chosen day.
        $workers = [];
        if ($this->isAdmin() || !empty($scopeSiteIds)) {
            $params = ['my' => $month, 'dt' => $fromDate];
            $where = ["w.status = 'Active'"];

            if (!$this->isAdmin()) {
                $keys = [];
                foreach ($scopeSiteIds as $i => $sid) { $keys[] = ":s$i"; $params["s$i"] = $sid; }
                $where[] = "w.site_id IN (" . implode(',', $keys) . ")";
            }
            if ($filterSiteId) {
                $where[] = "w.site_id = :fsid";
                $params['fsid'] = $filterSiteId;
            }

            $sql = "
                SELECT w.id, w.full_name, w.worker_code, w.site_id,
                       wc.name AS category_name, s.name AS site_name,
                       COALESCE(att.pl_count, 0) AS pl_count,
                       td.status   AS saved_status,
                       td.ot_hours AS saved_ot,
                       td.note     AS saved_note
                FROM workers w
                JOIN worker_categories wc ON w.category_id = wc.id
                JOIN sites s ON w.site_id = s.id
                LEFT JOIN (
                    SELECT worker_id, COUNT(*) AS pl_count FROM attendance
                    WHERE status = 'pl' AND TO_CHAR(attendance_date, 'YYYY-MM') = :my
                    GROUP BY worker_id
                ) att ON w.id = att.worker_id
                LEFT JOIN attendance td ON td.worker_id = w.id AND td.attendance_date = :dt
                WHERE " . implode(' AND ', $where) . "
                ORDER BY s.name ASC, w.full_name ASC
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $workers = $stmt->fetchAll();
        }

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

        $db = Database::connect();

        // Resolve the site scope for the current user (mirrors index()).
        if ($this->isAdmin()) {
            $sites = $db->query("SELECT id, name FROM sites WHERE is_active = TRUE ORDER BY name")->fetchAll();
            $scopeSiteIds = null;
        } else {
            $userModel = new User();
            $scopeSiteIds = $userModel->getAssignedSiteIds($_SESSION['user_id']);
            $_SESSION['assigned_site_ids'] = $scopeSiteIds;
            if (empty($scopeSiteIds)) {
                $sites = [];
            } else {
                $ph = implode(',', array_fill(0, count($scopeSiteIds), '?'));
                $stmt = $db->prepare("SELECT id, name FROM sites WHERE id IN ($ph) AND is_active = TRUE ORDER BY name");
                $stmt->execute(array_values($scopeSiteIds));
                $sites = $stmt->fetchAll();
            }
        }

        $attModel = new Attendance();
        $rows = $attModel->getMonthlyRegister($month, $scopeSiteIds, $filterSiteId);

        $this->view('attendance/register', [
            'pageTitle' => 'Attendance Register',
            'rows' => $rows,
            'sites' => $sites,
            'month' => $month,
            'selectedSiteId' => $filterSiteId
        ]);
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
        
        $userModel = new User();
        $managers = $userModel->getManagers();
        
        $maModel = new ManagerAttendance();
        // Fetch specific day history
        $history = $maModel->getByMonth($monthYear); 
        $plCounts = $maModel->getMonthlyPLCounts($monthYear);

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
                $userModel = new User();
                $allowedSiteIds = $userModel->getAssignedSiteIds($_SESSION['user_id']);
                $_SESSION['assigned_site_ids'] = $allowedSiteIds;
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
