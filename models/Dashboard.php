<?php
require_once __DIR__ . '/../config/Database.php';

class Dashboard {
    private $db;
    public function __construct() { $this->db = Database::connect(); }

    /**
     * Build the full dashboard bundle -- every metric, chart series and list
     * the dashboard needs -- in a single round trip. The database here is
     * remote, and a plain "SELECT 1" costs ~500ms of pure network latency
     * regardless of query complexity, so round-trip COUNT dominates page
     * load time far more than query cost does. This used to be ~16 separate
     * queries (12 inside this method alone); bundling every scalar as a
     * subquery and every list as a json_agg column brings it down to one.
     *
     * @param array|null $siteIds  null = Admin (all sites, unscoped).
     *                             array = Manager scope: figures are limited to
     *                             these site IDs; an empty array means the manager
     *                             has no assigned sites, so everything reads zero.
     */
    public function getDashboardInsights($siteIds = null) {
        $currentMonth = date('Y-m');
        $lastMonth = date('Y-m', strtotime('-1 month'));

        $scoped   = is_array($siteIds);
        $noAccess = $scoped && count($siteIds) === 0;

        $empty = [
            'total_workers' => 0, 'new_workers' => 0,
            'total_sites' => 0, 'client_count' => 0,
            'monthly_revenue' => 0, 'revenue_growth' => 0,
            'outstanding_amount' => 0, 'unpaid_invoices_count' => 0,
            'attendance_summary' => ['present' => 0, 'absent' => 0, 'half_day' => 0, 'off_duty' => 0, 'ot_hours' => 0],
            'pending_attendance' => 0, 'pending_billing' => 0,
            'present_today' => 0,
            'site_headcounts' => [],
            'workforce_dist' => [],
            'attendance_trends' => [],
        ];

        // A manager with no assigned sites can act on nothing — short-circuit to zeros.
        if ($noAccess) return $empty;

        $params = ['cm' => $currentMonth, 'lm' => $lastMonth, 'limit' => 6];

        // Filter fragments per table -- built once, reused across subqueries.
        // ANY(:site_ids::int[]) lets the same named param be reused everywhere
        // instead of repeating the site list as N separate placeholders.
        $fW = $fS = $fB = $fI = $fA = $fWs = '';
        $invoiceJoin = '';
        if ($scoped) {
            $params['site_ids'] = '{' . implode(',', array_map('intval', $siteIds)) . '}';
            $fW  = 'AND site_id = ANY(:site_ids::int[])';        // workers
            $fS  = 'WHERE id = ANY(:site_ids::int[])';           // sites
            $fB  = 'AND site_id = ANY(:site_ids::int[])';        // billing
            $invoiceJoin = 'JOIN billing b ON i.billing_id = b.id';
            $fI  = 'AND b.site_id = ANY(:site_ids::int[])';      // invoices (joined to billing)
            $fA  = 'AND site_id = ANY(:site_ids::int[])';        // attendance
            $fWs = 'AND w.site_id = ANY(:site_ids::int[])';      // workers, aliased w
        }

        $sql = "SELECT
            (SELECT COUNT(*) FROM workers WHERE status = 'Active' $fW) AS total_workers,
            (SELECT COUNT(*) FROM workers WHERE TO_CHAR(created_at, 'YYYY-MM') = :cm $fW) AS new_workers,

            (SELECT COUNT(*) FROM sites $fS) AS total_sites,
            (SELECT COUNT(DISTINCT client_id) FROM sites $fS) AS client_count,

            (SELECT COALESCE(SUM(grand_total), 0) FROM billing WHERE month_year = :cm $fB) AS monthly_revenue,
            (SELECT COALESCE(SUM(grand_total), 0) FROM billing WHERE month_year = :lm $fB) AS last_month_revenue,

            (SELECT COALESCE(SUM(i.amount), 0) FROM invoices i $invoiceJoin WHERE i.status = 'Unpaid' $fI) AS outstanding_amount,
            (SELECT COUNT(*) FROM invoices i $invoiceJoin WHERE i.status = 'Unpaid' $fI) AS unpaid_invoices_count,

            (SELECT COUNT(*) FILTER (WHERE status = 'p') FROM attendance WHERE TO_CHAR(attendance_date,'YYYY-MM') = :cm $fA) AS att_present,
            (SELECT COUNT(*) FILTER (WHERE status = 'a') FROM attendance WHERE TO_CHAR(attendance_date,'YYYY-MM') = :cm $fA) AS att_absent,
            (SELECT COUNT(*) FILTER (WHERE status = 'h') FROM attendance WHERE TO_CHAR(attendance_date,'YYYY-MM') = :cm $fA) AS att_half_day,
            (SELECT COUNT(*) FILTER (WHERE status = 'off') FROM attendance WHERE TO_CHAR(attendance_date,'YYYY-MM') = :cm $fA) AS att_off_duty,
            (SELECT COALESCE(SUM(ot_hours), 0) FROM attendance WHERE TO_CHAR(attendance_date,'YYYY-MM') = :cm $fA) AS att_ot_hours,

            (SELECT COUNT(DISTINCT attendance_date) FROM attendance WHERE locked = FALSE $fA) AS pending_attendance,
            (SELECT COUNT(*) FROM billing WHERE status = 'Pending' $fB) AS pending_billing,
            (SELECT COUNT(*) FROM attendance WHERE attendance_date = CURRENT_DATE AND status IN ('p','h') $fA) AS present_today,

            (SELECT COALESCE(json_agg(sh), '[]'::json) FROM (
                SELECT s.name, COUNT(w.id) FILTER (WHERE w.status = 'Active') AS headcount
                FROM sites s
                LEFT JOIN workers w ON w.site_id = s.id
                WHERE s.is_active = TRUE " . ($scoped ? 'AND s.id = ANY(:site_ids::int[])' : '') . "
                GROUP BY s.id, s.name
                ORDER BY headcount DESC, s.name ASC
                LIMIT :limit
            ) sh) AS site_headcounts_json,

            (SELECT COALESCE(json_agg(wd), '[]'::json) FROM (
                SELECT wc.name, COUNT(w.id) AS count
                FROM worker_categories wc
                LEFT JOIN workers w ON wc.id = w.category_id AND w.status = 'Active' $fWs
                GROUP BY wc.name
                ORDER BY count DESC
            ) wd) AS workforce_dist_json,

            (SELECT COALESCE(json_agg(t), '[]'::json) FROM (
                SELECT attendance_date,
                       COUNT(*) FILTER (WHERE status = 'p') * 100.0 / NULLIF(COUNT(*), 0) AS percentage
                FROM attendance
                WHERE attendance_date > CURRENT_DATE - INTERVAL '7 days' $fA
                GROUP BY attendance_date
                ORDER BY attendance_date ASC
            ) t) AS attendance_trends_json
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        $currRev = (float) $row['monthly_revenue'];
        $prevRev = (float) $row['last_month_revenue'];

        return [
            'total_workers' => (int) $row['total_workers'],
            'new_workers' => (int) $row['new_workers'],
            'total_sites' => (int) $row['total_sites'],
            'client_count' => (int) $row['client_count'],
            'monthly_revenue' => $row['monthly_revenue'],
            'revenue_growth' => $prevRev > 0 ? (($currRev - $prevRev) / $prevRev * 100) : 0,
            'outstanding_amount' => $row['outstanding_amount'],
            'unpaid_invoices_count' => (int) $row['unpaid_invoices_count'],
            'attendance_summary' => [
                'present' => (int) $row['att_present'],
                'absent' => (int) $row['att_absent'],
                'half_day' => (int) $row['att_half_day'],
                'off_duty' => (int) $row['att_off_duty'],
                'ot_hours' => $row['att_ot_hours'],
            ],
            'pending_attendance' => (int) $row['pending_attendance'],
            'pending_billing' => (int) $row['pending_billing'],
            'present_today' => (int) $row['present_today'],
            'site_headcounts' => json_decode($row['site_headcounts_json'], true) ?: [],
            'workforce_dist' => json_decode($row['workforce_dist_json'], true) ?: [],
            'attendance_trends' => json_decode($row['attendance_trends_json'], true) ?: [],
        ];
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
