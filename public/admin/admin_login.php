<?php
session_start();

/* ---------------- Admin Credentials ----------------
   Aap chahe to isko DB meja store kara sakte ho, but for now
   same logic preserved (hardcoded username + password).
*/
$ADMIN_USER = "admin";
$ADMIN_PASS = "admin123";

/* ---------------- CSRF Token ---------------- */
if (empty($_SESSION['csrf_admin_login'])) {
    $_SESSION['csrf_admin_login'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['csrf_admin_login'];

$message = "";

/* ---------------- POST: Handle Login ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // verify CSRF
    if (!hash_equals($_SESSION['csrf_admin_login'], $_POST['csrf_token'] ?? '')) {
        $message = "Invalid request. Please refresh the page.";
    } else {

        $u = trim($_POST['username'] ?? '');
        $p = trim($_POST['password'] ?? '');

        if ($u === $ADMIN_USER && $p === $ADMIN_PASS) {

            // successful login → regenerate session
            session_regenerate_id(true);
            $_SESSION['admin_logged'] = true;

            // renew CSRF token or delete
            unset($_SESSION['csrf_admin_login']);

            header("Location: admin_dashboard.php");
            exit;
        } else {
            $message = "Invalid username or password.";
        }
    }
}
?>
<!doctype html>
<html lang="hi">
<head>
<meta charset="utf-8">
<title>Admin Login — DBT Demo</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  body {
    background:#f4f6f9;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", Arial;
  }
  body { font-family: Arial, sans-serif; background:#f4f6fb; color:#222; }
    .topbar { background: #fff; border-bottom:1px solid #e6e6e6; }
    .brand { display:flex; gap:10px; align-items:center; }
    .brand img { height:40px; width:40px; object-fit:cover; border-radius:6px; }
    .page-wrap { max-width:1000px; margin:28px auto; }
    .small-note { color:var(--muted); }

  .login-card {
    max-width: 420px;
    margin: auto;
    margin-top: 70px;
    border-radius: 12px;
  }
  /* .brand {
    background: linear-gradient(90deg,#0b5ed7,#1768ff);
    color:#fff;
    padding:12px 16px;
    border-radius:6px;
    font-size:20px;
    font-weight:700;
    text-align:center;
  } */
</style>

</head>
<body>
<!-- Top bar -->
<header class="topbar">
  <div class="container d-flex justify-content-between align-items-center py-2">
    <div class="brand">
      <img src="logo.png" alt="logo" onerror="this.onerror=null; this.src='https://via.placeholder.com/40?text=DBT'">
      <div>
        <div style="font-weight:700; color:var(--brand)">DBT Portal</div>
        <div class="small-note">Integrated Beneficiary Management</div>
      </div>
    </div>

    <!-- <div class="d-none d-md-flex align-items-center gap-2">
      <a href="register_form.php" class="btn btn-outline-success btn-sm">Register</a>
      <a href="login.php" class="btn btn-outline-primary btn-sm">Farmer Login</a>
      <a href="admin/admin_login.php" class="btn btn-primary btn-sm">Admin Sign In</a>
    </div> -->
  </div>
</header>
<div class="container">
  
  <div class="brand mb-4">DBT Portal — Admin Login</div>

  <div class="card shadow-sm login-card">
    <div class="card-body">

      <h4 class="mb-3">Sign in</h4>

      <?php if ($message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <form method="POST">

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" class="form-control" name="username" required autofocus>
        </div>

        <div class="mb-3">
          <label class="form-label">Password</label>
          <div class="input-group">
            <input type="password" class="form-control" name="password" id="pwd" required>
            <button class="btn btn-outline-secondary" type="button" onclick="togglePw()">Show</button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 mt-2">Login</button>

      </form>

    </div>
  </div>

  <p class="text-center mt-3 text-muted small">© <?= date('Y') ?> DBT Demo — Admin</p>

</div>

<script>
function togglePw(){
  let p = document.getElementById("pwd");
  p.type = p.type === "password" ? "text" : "password";
}
</script>

</body>
</html>
