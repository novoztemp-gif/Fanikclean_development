<?php
class Controller {
    public function view($view, $data = []) {
        extract($data);
        require_once "views/layout/header.php";
        require_once "views/layout/sidebar.php";
        require_once "views/$view.php";
        require_once "views/layout/footer.php";
    }

    public function viewAuth($view, $data = []) {
        extract($data);
        require_once "views/$view.php";
    }

    public function redirect($url) {
        header("Location: $url");
        exit;
    }

    protected function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    protected function isAdmin() {
        return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
    }

    protected function isManager() {
        return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2;
    }

    protected function requireRole($allowedRoles) {
        $this->checkAuth();
        if (!in_array($_SESSION['role_id'], $allowedRoles)) {
            $_SESSION['error'] = "Unauthorized access";
            $this->redirect('/dashboard');
        }
    }

    protected function getAssignedSiteIds() {
        return $_SESSION['assigned_site_ids'] ?? [];
    }

    protected function canAccessSite($siteId) {
        if ($this->isAdmin()) return true;
        return in_array($siteId, $this->getAssignedSiteIds());
    }

    protected function getSiteId() {
        // Deprecated: multi-site support uses getAssignedSiteIds()
        return $_SESSION['assigned_site_ids'][0] ?? null;
    }

    /**
     * Record an entry in audit_logs. Best-effort: a logging failure (bad
     * connection, missing table, etc.) must never break the primary action,
     * so everything is wrapped in try/catch. $module drives the colour badge
     * on the Audit Log screen (Attendance/Billing/Config/Users/Workers/Leave/
     * Financial/Auth/Clients/Sites/Payroll/Invoices); $action is free text.
     */
    protected function logAudit($module, $action) {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, module) VALUES (:uid, :act, :mod)");
            $stmt->execute([
                'uid' => $_SESSION['user_id'] ?? null,
                'act' => $action,
                'mod' => $module
            ]);
        } catch (\Throwable $e) {
            // Auditing is non-critical — swallow and continue.
        }
    }
}
