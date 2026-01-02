<?php
session_start();

// require DB if available (optional — keeps dashboard dynamic)
require_once __DIR__ . '/../../config/db.php';

if (empty($_SESSION['admin_logged'])) {
    header("Location: admin_login.php");
    exit;
}

function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// Attempt to fetch some quick stats (best-effort)
$stats = [
    'total_users' => null,
    'pending_apps' => null,
    'approved_apps' => null,
];

if (isset($mysqli) && $mysqli instanceof mysqli) {
    // total users
    if ($res = $mysqli->query("SELECT COUNT(*) AS c FROM users")) {
        $row = $res->fetch_assoc();
        $stats['total_users'] = intval($row['c']);
        $res->free();
    }
    // pending apps
    if ($res = $mysqli->query("SELECT COUNT(*) AS c FROM applications WHERE status = 'PENDING'")) {
        $row = $res->fetch_assoc();
        $stats['pending_apps'] = intval($row['c']);
        $res->free();
    }
    // approved apps
    if ($res = $mysqli->query("SELECT COUNT(*) AS c FROM applications WHERE status = 'APPROVED'")) {
        $row = $res->fetch_assoc();
        $stats['approved_apps'] = intval($row['c']);
        $res->free();
    }
}
?>
<!doctype html>
<html lang="hi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard — DBT Demo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f4f6f9; font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", Arial; }
   body { font-family: Arial, sans-serif; background:#f4f6fb; color:#222; }
    .topbar { background: #fff; border-bottom:1px solid #e6e6e6; }
    .brand { display:flex; gap:10px; align-items:center; }
    .brand img { height:40px; width:40px; object-fit:cover; border-radius:6px; }
    .page-wrap { max-width:1000px; margin:28px auto; }
    .small-note { color:var(--muted); }

    /* .topbar { background: linear-gradient(90deg,#0b5ed7,#1768ff); color: #fff; padding:12px 16px; border-radius:8px; font-weight:700; } */
    .card-stat { border-radius:10px; }
    .small-muted { color:#6c757d; }
    .brand-logo {
  height: 42px;
  width: auto;
  object-fit: contain;
}
  </style>
</head>
<body>
    <!-- Top bar -->
<header class="topbar">
  <div class="container d-flex justify-content-between align-items-center py-2">
    <a href="/dbt_project/public/index.php" class="text-decoration-none">
  <div class="d-flex align-items-center gap-2">
    <img src="/dbt_project/public/img/logo.png"
         alt="DBT Logo"
         class="brand-logo">

    <div class="lh-sm">
      <div class="fw-bold text-primary">DBT Portal</div>
      <small class="text-muted">Integrated Beneficiary Management</small>
    </div>
  </div>
</a>
    <div class="d-none d-md-flex align-items-center gap-2">
      <!-- <a href="register_form.php" class="btn btn-outline-success btn-sm">Register</a> -->
      <!-- <a href="login.php" class="btn btn-outline-primary btn-sm">Farmer Login</a> -->
      <!-- <a href="admin/admin_login.php" class="btn btn-primary btn-sm">Admin Sign In</a> -->
    </div>
  </div>
</header>
  <div class="container py-4">

    <div class="d-flex align-items-center gap-3 mb-3">
      <div class="topbar">Admin Panel — DBT Demo</div>
      <div class="ms-auto">
        <a href="admin_logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-8">
        <div class="card shadow-sm p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-1">Welcome, Admin</h4>
              <div class="small-muted">Use the quick actions to manage applications and users.</div>
            </div>
            <div class="text-end">
              <div class="small-muted">Signed in</div>
              <strong>Admin</strong>
            </div>
          </div>

          <hr>

          <div class="d-flex gap-2 flex-wrap">
            <a href="admin_applications.php" class="btn btn-primary">View Applications</a>
            <a href="admin_users.php" class="btn btn-outline-primary">Manage Users</a>
            <a href="admin_reports.php" class="btn btn-outline-secondary">Reports</a>
          </div>

        </div>

        <div class="card shadow-sm mt-3 p-3">
          <h6 class="mb-2">Recent Activity</h6>
          <div class="small-muted">No recent activity to show. Visit Applications to manage pending requests.</div>
        </div>
      </div>

      <aside class="col-md-4">
        <div class="row g-3">
          <div class="col-12">
            <div class="card card-stat p-3 text-center">
              <div class="small-muted">Total Registered Farmers</div>
              <div class="h3 my-2"><?= e($stats['total_users'] ?? '—') ?></div>
              <a href="admin_users.php" class="small-muted">View users</a>
            </div>
          </div>

          <div class="col-12">
            <div class="card card-stat p-3 text-center">
              <div class="small-muted">Pending Applications</div>
              <div class="h3 my-2 text-warning"><?= e($stats['pending_apps'] ?? '—') ?></div>
              <a href="admin_applications.php" class="small-muted">Review now</a>
            </div>
          </div>

          <div class="col-12">
            <div class="card card-stat p-3 text-center">
              <div class="small-muted">Approved Applications</div>
              <div class="h3 my-2 text-success"><?= e($stats['approved_apps'] ?? '—') ?></div>
              <a href="admin_applications.php?filter=approved" class="small-muted">View approved</a>
            </div>
          </div>
        </div>
      </aside>
    </div>

    <footer class="mt-4 small text-muted">
      © <?= date('Y') ?> DBT Demo — Admin
    </footer>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
