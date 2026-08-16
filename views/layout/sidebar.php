<?php
// ---- Live sidebar badge counts (replaces the old hardcoded 84 / 6 / 3) ----
// Scoped by role: Admin sees everything; Manager is limited to assigned sites.
require_once __DIR__ . '/../../config/Database.php';

$navCounts = ['attendance' => 0, 'leave' => 0, 'invoices' => 0];
try {
    $navDb   = Database::connect();
    $isAdmin = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
    $siteIds = $isAdmin ? [] : ($_SESSION['assigned_site_ids'] ?? []);

    // Managers with no assigned sites have nothing in scope -> all counts stay 0.
    if ($isAdmin || !empty($siteIds)) {
        $siteFilter = '';
        $params = [];
        if (!$isAdmin) {
            $ph = implode(',', array_fill(0, count($siteIds), '?'));
            $siteFilter = $ph; // reused per-query with the right column
            $params = $siteIds;
        }

        // Active workers not yet marked for today's attendance.
        // Only workers assigned to a site can appear in / be marked on the grid,
        // so unassigned (site_id IS NULL) workers are excluded — otherwise the
        // badge could never reach zero.
        $sql = "SELECT COUNT(*) FROM workers w WHERE w.status = 'Active' AND w.site_id IS NOT NULL
                AND NOT EXISTS (SELECT 1 FROM attendance a WHERE a.worker_id = w.id AND a.attendance_date = CURRENT_DATE)";
        if (!$isAdmin) { $sql .= " AND w.site_id IN ($siteFilter)"; }
        $st = $navDb->prepare($sql); $st->execute($params);
        $navCounts['attendance'] = (int) $st->fetchColumn();

        // Pending leave requests awaiting review
        $sql = "SELECT COUNT(*) FROM leave_requests lr JOIN workers w ON lr.worker_id = w.id WHERE lr.status = 'Pending'";
        if (!$isAdmin) { $sql .= " AND w.site_id IN ($siteFilter)"; }
        $st = $navDb->prepare($sql); $st->execute($params);
        $navCounts['leave'] = (int) $st->fetchColumn();

        // Invoices not yet paid
        $sql = "SELECT COUNT(*) FROM invoices i JOIN billing b ON i.billing_id = b.id WHERE i.status <> 'Paid'";
        if (!$isAdmin) { $sql .= " AND b.site_id IN ($siteFilter)"; }
        $st = $navDb->prepare($sql); $st->execute($params);
        $navCounts['invoices'] = (int) $st->fetchColumn();
    }
} catch (Throwable $e) {
    // Never let a badge query break the layout; just show no badges.
    $navCounts = ['attendance' => 0, 'leave' => 0, 'invoices' => 0];
}

// Logged-in user's initials for the top-right avatar (was hardcoded "RK").
$navUserName = trim($_SESSION['user_name'] ?? '');
$navInitials = 'U';
if ($navUserName !== '') {
    $parts = preg_split('/\s+/', $navUserName);
    $navInitials = strtoupper(substr($parts[0], 0, 1) . (count($parts) > 1 ? substr(end($parts), 0, 1) : ''));
}
?>
<div class="app">
<!-- ============================================================ SIDEBAR -->
<aside class="sidebar">
  <div class="logo-area">
    <div class="logo-wrap">
      <div class="logo-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:white;"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      </div>
      <div>
        <div class="logo-name">FanikClean</div>
      </div>
    </div>
  </div>

  <div class="nav-section">
    <div class="nav-sec-label">Overview</div>
    <a href="/dashboard" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false || $_SERVER['REQUEST_URI'] == '/' ? 'active' : '' ?>">
      <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
      Dashboard
    </a>
  </div>

  <div class="nav-section">
    <div class="nav-sec-label">People</div>
    <a href="/workers" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'workers') !== false ? 'active' : '' ?>">
      <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Workers
    </a>
    <?php if($_SESSION['role_id'] == 1): ?>
      <a href="/clients" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'clients') !== false ? 'active' : '' ?>">
        <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/></svg>
        Clients & Sites
      </a>
      <a href="/users" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'users') !== false && strpos($_SERVER['REQUEST_URI'], 'assignments') === false ? 'active' : '' ?>">
        <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
        User Management
      </a>
      <a href="/users/assignments" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'users/assignments') !== false ? 'active' : '' ?>">
        <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
        Site Assignments
      </a>
    <?php endif; ?>
  </div>

  <div class="nav-section">
    <div class="nav-sec-label">Operations</div>
    <a href="/attendance" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'attendance') !== false && strpos($_SERVER['REQUEST_URI'], 'manager') === false && strpos($_SERVER['REQUEST_URI'], 'register') === false ? 'active' : '' ?>">
      <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="m9 16 2 2 4-4"/></svg>
      Attendance
      <?php if($navCounts['attendance'] > 0): ?><span class="nav-badge" title="Workers not yet marked today"><?= $navCounts['attendance'] ?></span><?php endif; ?>
    </a>
    <a href="/attendance/register" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'attendance/register') !== false ? 'active' : '' ?>">
      <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><rect width="4" height="7" x="7" y="10"/><rect width="4" height="12" x="15" y="5"/></svg>
      Att. Register
    </a>
    <?php if($_SESSION['role_id'] == 1): ?>
    <a href="/attendance/manager" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'attendance/manager') !== false ? 'active' : '' ?>">
      <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m19 8 2 2-2 2"/></svg>
      Manager Att.
    </a>
    <?php endif; ?>
    <?php if($_SESSION['role_id'] == 2): ?>
    <a href="/attendance/my" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'attendance/my') !== false ? 'active' : '' ?>">
      <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="10"/></svg>
      My Attendance
    </a>
    <?php endif; ?>
    <a href="/leave" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'leave') !== false ? 'active' : '' ?>">
      <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      Leave
      <?php if($navCounts['leave'] > 0): ?><span class="nav-badge" title="Pending leave requests"><?= $navCounts['leave'] ?></span><?php endif; ?>
    </a>
  </div>

  <div class="nav-section">
    <div class="nav-sec-label">Finance</div>
    <a href="/payroll" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'payroll') !== false ? 'active' : '' ?>">
      <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      Payroll
    </a>
    <a href="/billing" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'billing') !== false ? 'active' : '' ?>">
      <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="m17 5-5-3-5 3"/><path d="m17 19-5 3-5-3"/><path d="M2 12h20"/><path d="m5 7-3 5 3 5"/><path d="m19 7 3 5-3 5"/></svg>
      Billing
    </a>
    <a href="/invoices" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'invoices') !== false ? 'active' : '' ?>">
      <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
      Invoices
      <?php if($navCounts['invoices'] > 0): ?><span class="nav-badge" title="Unpaid invoices"><?= $navCounts['invoices'] ?></span><?php endif; ?>
    </a>
    <a href="/financial" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'financial') !== false ? 'active' : '' ?>">
      <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      Financial
    </a>
  </div>

  <?php if($_SESSION['role_id'] == 1): ?>
  <div class="nav-section">
    <div class="nav-sec-label">Config</div>
    <a href="/rates" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'rates') !== false ? 'active' : '' ?>">
      <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      Rate Config
    </a>
    <a href="/reports" class="nav-item">
      <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
      Reports
    </a>
    <a href="/audit" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'audit') !== false ? 'active' : '' ?>">
      <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="10"/></svg>
      Audit Log
    </a>
  </div>
  <?php endif; ?>

  <div class="sidebar-footer" style="padding: 20px;">
    <div style="font-size: 10px; opacity: 0.4; margin-bottom: 8px;">v1.0 - Preview Build</div>
    <a href="/logout" class="nav-item" style="color:var(--danger); width: 100%;">
      <svg class="nav-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
      Logout
    </a>
  </div>
</aside>

<!-- ============================================================ MAIN -->
<div class="main">
  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <div class="page-bc" id="page-bc">OVERVIEW</div>
      <div class="page-ttl" id="page-ttl"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></div>
    </div>
    <div class="topbar-right">
      <span class="role-chip"><?= isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1 ? 'Admin' : 'Manager'; ?></span>
      <div class="notif-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
        <div class="notif-dot"></div>
      </div>
      <div class="avatar" title="<?= htmlspecialchars($navUserName ?: 'User Profile') ?>"><?= htmlspecialchars($navInitials) ?></div>
    </div>
  </header>

  <!-- CONTENT -->
  <div class="content">
    <?php if(isset($_SESSION['toast'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                toast(<?= json_encode($_SESSION['toast']) ?>, "success");
            });
        </script>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                toast(<?= json_encode($_SESSION['error']) ?>, "error");
            });
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
