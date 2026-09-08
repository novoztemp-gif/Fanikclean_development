<?php
require_once __DIR__ . '/../models/Dashboard.php';

class DashboardController extends Controller {
    public function __construct() {
        $this->requireRole([1, 2]);
    }

    public function index() {
        $dashboardModel = new Dashboard();
        // Admin sees everything (null); managers are scoped to their assigned sites.
        // Everything the dashboard needs comes back in one round trip now --
        // see Dashboard::getDashboardInsights() for why that matters here.
        $siteScope = $this->isAdmin() ? null : $this->getAssignedSiteIds();
        $insights = $dashboardModel->getDashboardInsights($siteScope);

        $this->view('dashboard/index', [
            'pageTitle' => 'Dashboard',
            'insights' => $insights,
            'siteScope' => $siteScope,
            'siteHeadcounts' => $insights['site_headcounts'],
            'workforceDist' => $insights['workforce_dist'],
            'presentToday' => $insights['present_today']
        ]);
    }
}
