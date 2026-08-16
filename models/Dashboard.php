<?php
require_once __DIR__ . '/../config/Database.php';

class Dashboard {
    private $db;
    public function __construct() { $this->db = Database::connect(); }

    /**
     * Build the dashboard insight bundle.
     *
     * @param array|null $siteIds  null = Admin (all sites, unscoped).
     *                             array = Manager scope: figures are limited to
     *                             these site IDs; an empty array means the manager
     *                             has no assigned sites, so everything reads zero.
     */
    public function getDashboardInsights($siteIds = null) {
        $insights = [];
        $currentMonth = date('Y-m');
        $lastMonth = date('Y-m', strtotime('-1 month'));

        $scoped   = is_array($siteIds);            // manager view
        $noAccess = $scoped && count($siteIds) === 0; // manager with zero sites
        $in = ($scoped && !$noAccess) ? implode(',', array_fill(0, count($siteIds), '?')) : '';
        $sp = ($scoped && !$noAccess) ? array_values($siteIds) : [];

        // A manager with no assigned sites can act on nothing — short-circuit to zeros.
        if ($noAccess) {
            return [
                'total_workers' => 0, 'new_workers' => 0,
                'total_sites' => 0, 'client_count' => 0,
                'monthly_revenue' => 0, 'revenue_growth' => 0,
                'outstanding_amount' => 0, 'unpaid_invoices_count' => 0,
                'attendance_summary' => ['present' => 0, 'absent' => 0, 'half_day' => 0, 'ot_hours' => 0],
                'recent_invoices' => [],
                'pending_leave' => 0, 'pending_attendance' => 0, 'pending_billing' => 0,
            ];
        }

        // 1. Worker Insights
        $insights['total_workers'] = $this->scalar(
            "SELECT COUNT(*) FROM workers WHERE status = 'Active'" . ($scoped ? " AND site_id IN ($in)" : ''),
            $sp);
        $insights['new_workers'] = $this->scalar(
            "SELECT COUNT(*) FROM workers WHERE TO_CHAR(created_at, 'YYYY-MM') = ?" . ($scoped ? " AND site_id IN ($in)" : ''),
            array_merge([$currentMonth], $sp));

        // 2. Site/Client Insights
        $insights['total_sites'] = $this->scalar(
            "SELECT COUNT(*) FROM sites" . ($scoped ? " WHERE id IN ($in)" : ''), $sp);
        $insights['client_count'] = $this->scalar(
            "SELECT COUNT(DISTINCT client_id) FROM sites" . ($scoped ? " WHERE id IN ($in)" : ''), $sp);

        // 3. Revenue Insights (billing is per-site)
        $currRev = $this->scalar(
            "SELECT SUM(grand_total) FROM billing WHERE month_year = ?" . ($scoped ? " AND site_id IN ($in)" : ''),
            array_merge([$currentMonth], $sp)) ?: 0;
        $prevRev = $this->scalar(
            "SELECT SUM(grand_total) FROM billing WHERE month_year = ?" . ($scoped ? " AND site_id IN ($in)" : ''),
            array_merge([$lastMonth], $sp)) ?: 0;
        $insights['monthly_revenue'] = $currRev;
        $insights['revenue_growth'] = ($prevRev > 0) ? (($currRev - $prevRev) / $prevRev * 100) : 0;

        // 4. Outstanding Insights. Admin counts every unpaid invoice (including
        // orphaned ones with no billing row); managers must join billing to
        // filter by site, which naturally excludes unattributable orphans.
        if ($scoped) {
            $insights['outstanding_amount'] = $this->scalar(
                "SELECT SUM(i.amount) FROM invoices i JOIN billing b ON i.billing_id = b.id
                 WHERE i.status = 'Unpaid' AND b.site_id IN ($in)", $sp) ?: 0;
            $insights['unpaid_invoices_count'] = $this->scalar(
                "SELECT COUNT(*) FROM invoices i JOIN billing b ON i.billing_id = b.id
                 WHERE i.status = 'Unpaid' AND b.site_id IN ($in)", $sp);
        } else {
            $insights['outstanding_amount'] = $this->scalar("SELECT SUM(amount) FROM invoices WHERE status = 'Unpaid'") ?: 0;
            $insights['unpaid_invoices_count'] = $this->scalar("SELECT COUNT(*) FROM invoices WHERE status = 'Unpaid'");
        }

        // 5. Attendance Summary (Current Month)
        $attStmt = $this->run("
            SELECT
                COUNT(*) FILTER (WHERE status = 'p') as present,
                COUNT(*) FILTER (WHERE status = 'a') as absent,
                COUNT(*) FILTER (WHERE status = 'h') as half_day,
                SUM(COALESCE(ot_hours, 0)) as ot_hours
            FROM attendance
            WHERE TO_CHAR(attendance_date, 'YYYY-MM') = ?" . ($scoped ? " AND site_id IN ($in)" : ''),
            array_merge([$currentMonth], $sp));
        $insights['attendance_summary'] = $attStmt->fetch();

        // 6. Recent Invoices (Table)
        $insights['recent_invoices'] = $this->run("
            SELECT i.*, c.company_name
            FROM invoices i
            JOIN billing b ON i.billing_id = b.id
            JOIN clients c ON b.client_id = c.id" . ($scoped ? " WHERE b.site_id IN ($in)" : '') . "
            ORDER BY i.issue_date DESC
            LIMIT 4", $sp)->fetchAll();

        // 7. Pending Approvals
        $insights['pending_leave'] = $this->scalar(
            "SELECT COUNT(*) FROM leave_requests lr" .
            ($scoped ? " JOIN workers w ON lr.worker_id = w.id WHERE lr.status = 'Pending' AND w.site_id IN ($in)"
                     : " WHERE lr.status = 'Pending'"), $sp);
        $insights['pending_attendance'] = $this->scalar(
            "SELECT COUNT(DISTINCT attendance_date) FROM attendance WHERE locked = FALSE" . ($scoped ? " AND site_id IN ($in)" : ''),
            $sp);
        $insights['pending_billing'] = $this->scalar(
            "SELECT COUNT(*) FROM billing WHERE status = 'Pending'" . ($scoped ? " AND site_id IN ($in)" : ''),
            $sp);

        return $insights;
    }

    /** Run a parameterized statement and return it. */
    private function run($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Run a parameterized statement and return the first column of the first row. */
    private function scalar($sql, $params = []) {
        return $this->run($sql, $params)->fetchColumn();
    }

    public function getAttendanceTrends($siteIds = null) {
        [$filter, $params] = $this->siteFilter('site_id', $siteIds);
        return $this->run("
            SELECT attendance_date,
                   COUNT(*) FILTER (WHERE status = 'p') * 100.0 / NULLIF(COUNT(*), 0) as percentage
            FROM attendance
            WHERE attendance_date > CURRENT_DATE - INTERVAL '7 days' $filter
            GROUP BY attendance_date
            ORDER BY attendance_date ASC
        ", $params)->fetchAll();
    }

    public function getWorkerRoleDistribution($siteIds = null) {
        // Scope goes in the LEFT JOIN's ON clause so every category still
        // appears (with a 0 count) even when a manager has no workers.
        [$filter, $params] = $this->siteFilter('w.site_id', $siteIds, 'AND');
        return $this->run("
            SELECT wc.name, COUNT(w.id) as count
            FROM worker_categories wc
            LEFT JOIN workers w ON wc.id = w.category_id AND w.status = 'Active' $filter
            GROUP BY wc.name
            ORDER BY count DESC
        ", $params)->fetchAll();
    }

    /**
     * Build a site-scoping SQL fragment + params.
     * $siteIds null -> Admin, no filter. Array -> "$keyword col IN (?,..)".
     * Empty array (manager with no sites) -> "$keyword 1=0" (matches nothing).
     */
    private function siteFilter($column, $siteIds, $keyword = 'AND') {
        if (!is_array($siteIds)) return ['', []];
        if (count($siteIds) === 0) return [" $keyword 1=0", []];
        $in = implode(',', array_fill(0, count($siteIds), '?'));
        return [" $keyword $column IN ($in)", array_values($siteIds)];
    }
}
