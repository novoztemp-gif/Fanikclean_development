<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - FanikClean</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/login.css">
</head>
<body>

<div class="container">
  <h2>Welcome Back</h2>
  <p class="subtitle">Login to your account</p>

  <?php if(isset($_SESSION['error'])): ?>
    <div style="color:red; margin-bottom: 15px;"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
  <?php endif; ?>
  <?php if(isset($_SESSION['success'])): ?>
    <div style="color:green; margin-bottom: 15px;"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
  <?php endif; ?>

  <?php $selectedRole = ($_POST['role'] ?? $_SESSION['login_role'] ?? '1'); unset($_SESSION['login_role']); ?>

  <div class="role-tabs" role="tablist" aria-label="Login as">
    <button type="button" class="role-tab<?= $selectedRole == '1' ? ' active' : '' ?>" data-role="1">Admin</button>
    <button type="button" class="role-tab<?= $selectedRole == '2' ? ' active' : '' ?>" data-role="2">Manager</button>
  </div>

  <form method="POST" action="/login">
    <input type="hidden" name="role" id="role" value="<?= htmlspecialchars($selectedRole) ?>">
    <div class="input-group">
      <input type="email" name="email" id="email" placeholder="Email" required>
    </div>
    <div class="input-group">
      <input type="password" name="password" id="password" placeholder="Password" required>
    </div>
    <button type="submit">Login as <span id="role-label"><?= $selectedRole == '2' ? 'Manager' : 'Admin' ?></span></button>
  </form>

  <script>
    (function () {
      var tabs = document.querySelectorAll('.role-tab');
      var roleInput = document.getElementById('role');
      var roleLabel = document.getElementById('role-label');
      tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
          tabs.forEach(function (t) { t.classList.remove('active'); });
          tab.classList.add('active');
          roleInput.value = tab.dataset.role;
          roleLabel.textContent = tab.textContent;
        });
      });
    })();
  </script>
</div>

</body>
</html>
