<?php
require_once __DIR__ . '/../models/Dashboard.php';

class DashboardController extends Controller {
    public function __construct() {
        $this->requireRole([1, 2]);
    }

    public function index() {
        $dashboardModel = new Dashboard();
        // Admin sees everything (null); managers are scoped to their assigned sites.
        $siteScope = $this->isAdmin() ? null : $this->getAssignedSiteIds();
        $insights = $dashboardModel->getDashboardInsights($siteScope);
        $siteHeadcounts = $dashboardModel->getSiteHeadcounts($siteScope);
        $workforceDist = $dashboardModel->getWorkerRoleDistribution($siteScope);
        $presentToday = $dashboardModel->getPresentToday($siteScope);

        $this->view('dashboard/index', [
            'pageTitle' => 'Dashboard',
            'insights' => $insights,
            'siteScope' => $siteScope,
            'siteHeadcounts' => $siteHeadcounts,
            'workforceDist' => $workforceDist,
            'presentToday' => $presentToday
        ]);
    }
}
