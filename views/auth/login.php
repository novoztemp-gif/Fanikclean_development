<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - FanikClean</title>
<?php
// Real, non-sensitive aggregate counts for the brand panel — never fabricated.
// Login has no session yet, so this is read-only and best-effort.
require_once __DIR__ . '/../../config/Database.php';
$loginSiteCount = 0; $loginCheckedInToday = 0;
try {
    $ldb = Database::connect();
    // One round trip for both figures -- the DB is remote, so every extra
    // query here is real latency added to every visit to this pre-auth page.
    $row = $ldb->query("
        SELECT
            (SELECT COUNT(*) FROM sites WHERE is_active = TRUE) AS site_count,
            (SELECT COUNT(*) FROM attendance WHERE attendance_date = CURRENT_DATE AND status IN ('p','h')) AS checked_in
    ")->fetch();
    $loginSiteCount = (int) $row['site_count'];
    $loginCheckedInToday = (int) $row['checked_in'];
} catch (Throwable $e) {
    // DB unreachable — panel just shows zeros rather than breaking login.
}
?>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/login.css?v=<?= filemtime(__DIR__ . '/../../css/login.css') ?>">
</head>
<body>

<div class="screen">

  <div class="brand-panel">
    <div class="dotfield" aria-hidden="true">
      <span class="site-dot" style="top:18%; left:22%; animation-delay:.2s;"></span>
      <span class="site-dot" style="top:32%; left:68%; animation-delay:1.1s;"></span>
      <span class="site-dot" style="top:58%; left:15%; animation-delay:2.0s;"></span>
      <span class="site-dot" style="top:71%; left:52%; animation-delay:.7s;"></span>
      <span class="site-dot" style="top:44%; left:83%; animation-delay:1.6s;"></span>
      <span class="site-dot" style="top:83%; left:78%; animation-delay:2.5s;"></span>
      <span class="site-dot" style="top:12%; left:55%; animation-delay:3.1s;"></span>
    </div>
    <div class="brand-content">
      <div class="brand-mark"><span class="sq"></span>FanikClean</div>
      <h1>Site management &amp; attendance, in one register.</h1>
      <p>Track every site, mark daily attendance, and allot each worker to the right category and crew.</p>
      <div class="brand-stats">
        <div><b id="bs1" data-count="<?= $loginSiteCount ?>">0</b><span>active sites</span></div>
        <div><b id="bs2" data-count="<?= $loginCheckedInToday ?>">0</b><span>checked in today</span></div>
      </div>
    </div>
  </div>

  <div class="form-panel">
    <div class="form-card">
      <div class="form-head">
        <h2>Welcome back</h2>
        <p class="subtitle">Sign in to your FanikClean account</p>
      </div>

      <?php if(isset($_SESSION['error'])): ?>
        <div class="form-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
      <?php endif; ?>
      <?php if(isset($_SESSION['success'])): ?>
        <div class="form-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
      <?php endif; ?>

      <?php $selectedRole = ($_POST['role'] ?? $_SESSION['login_role'] ?? '1'); unset($_SESSION['login_role']); ?>

      <div class="role-tabs" role="tablist" aria-label="Login as">
        <div class="role-spot" id="role-spot"></div>
        <button type="button" class="role-tab<?= $selectedRole == '1' ? ' active' : '' ?>" data-role="1">Admin</button>
        <button type="button" class="role-tab<?= $selectedRole == '2' ? ' active' : '' ?>" data-role="2">Manager</button>
      </div>

      <form method="POST" action="/login" id="login-form">
        <input type="hidden" name="role" id="role" value="<?= htmlspecialchars($selectedRole) ?>">
        <div class="input-group">
          <label for="email">Email</label>
          <input type="email" name="email" id="email" placeholder="you@fanikclean.com" required autofocus>
        </div>
        <div class="input-group">
          <label for="password">Password</label>
          <input type="password" name="password" id="password" placeholder="••••••••" required>
        </div>
        <button type="submit" id="login-btn">
          <span class="btn-label">Sign in as <span id="role-label"><?= $selectedRole == '2' ? 'Manager' : 'Admin' ?></span></span>
          <span class="btn-spinner" aria-hidden="true"></span>
        </button>
      </form>
    </div>
  </div>

</div>

<script>
(function () {
  var tabs = document.querySelectorAll('.role-tab');
  var roleInput = document.getElementById('role');
  var roleLabel = document.getElementById('role-label');
  var spot = document.getElementById('role-spot');

  function placeSpot(tab) {
    spot.style.transform = 'translateX(' + tab.offsetLeft + 'px)';
    spot.style.width = tab.offsetWidth + 'px';
  }

  var activeTab = document.querySelector('.role-tab.active');
  if (activeTab) requestAnimationFrame(function () { placeSpot(activeTab); });

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');
      roleInput.value = tab.dataset.role;
      roleLabel.textContent = tab.textContent;
      placeSpot(tab);
    });
  });

  window.addEventListener('resize', function () {
    var a = document.querySelector('.role-tab.active');
    if (a) placeSpot(a);
  });

  document.getElementById('login-form').addEventListener('submit', function () {
    document.getElementById('login-btn').classList.add('loading');
  });

  // Ambient counters on the brand panel — real aggregate counts from the DB.
  function countUp(el, target, dur) {
    var start = performance.now();
    function frame(now) {
      var t = Math.min(1, (now - start) / dur);
      el.textContent = Math.round(target * (1 - Math.pow(1 - t, 3))).toLocaleString('en-IN');
      if (t < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }
  document.querySelectorAll('.brand-stats [data-count]').forEach(function (el, i) {
    var target = parseInt(el.dataset.count, 10) || 0;
    setTimeout(function () { countUp(el, target, 900); }, 500 + i * 150);
  });
})();
</script>
</body>
</html>
