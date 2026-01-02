<?php
session_start();
require __DIR__ . '/../config/db.php';

// If not logged in → redirect to login
if (empty($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

$kid = $_SESSION['farmer_kisan'];

// Fetch farmer data (original logic kept intact)
$stmt = $mysqli->prepare("SELECT name, mobile, district, block, category FROM users WHERE kisan_id=? LIMIT 1");
$stmt->bind_param("s", $kid);
$stmt->execute();
$stmt->bind_result($name, $mobile, $district, $block, $category);
$stmt->fetch();
$stmt->close();

// safe-escape helper
function e($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="hi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Farmer Dashboard — DBT Demo</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root {
      --gov-blue: #0b5ed7;
      --muted: #6c757d;
      --card-bg: #ffffff;
    }
    body { font-family: Arial, sans-serif; background:#f4f6fb; color:#222; }
    .topbar { background: #fff; border-bottom:1px solid #e6e6e6; }
    .brand { display:flex; gap:10px; align-items:center; }
    .brand img { height:40px; width:40px; object-fit:cover; border-radius:6px; }
    .page-wrap { max-width:1000px; margin:28px auto; }
    .small-note { color:var(--muted); }

    body { background: #f4f6f9; font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", Arial; }
    .gov-topbar { background: linear-gradient(90deg, var(--gov-blue), #1768ff); color: #fff; padding: 0.75rem 1rem; }
    .gov-brand { font-weight: 700; letter-spacing: 0.2px; }
    .sidebar { min-height: 60vh; }
    .kpi { border-radius: 8px; background: var(--card-bg); box-shadow: 0 1px 4px rgba(15,23,42,0.04); padding: 1rem; }
    .small-muted { color: var(--muted); font-size: 0.9rem; }
    .avatar-circle { width:56px; height:56px; border-radius:50%; background:#e9f0ff; display:flex; align-items:center; justify-content:center; color:var(--gov-blue); font-weight:700; }
    a.btn-ghost { background: transparent; border: 1px solid #dfe8ff; color: var(--gov-blue); }
    footer.site-footer { padding: 1rem 0; color: var(--muted); font-size: 0.9rem; }
    @media (max-width:767px) {
      .sidebar { margin-bottom:1rem; }
    }
  </style>
   <link rel="stylesheet" href="/dbt_project/public/assets/css/common.css">
</head>
<body>

<?php include __DIR__ . '/topbar.php'; ?>

  <header class="gov-topbar">
    <div class="container d-flex align-items-center gap-3">
      <div class="gov-brand">DBT — Demo Portal</div>
      <div class="ms-auto small-muted">किसान पोर्टल (Farmer Dashboard)</div>
    </div>
  </header>

  <main class="container my-4">
    <div class="row g-4">
      <!-- Sidebar / Profile -->
      <aside class="col-lg-3 sidebar">
        <div class="card kpi">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar-circle" aria-hidden="true"><?= strtoupper(substr(e($name), 0, 1) ?: 'K') ?></div>
            <div>
              <div class="fw-bold" style="font-size:1.05rem"><?= e($name) ?></div>
              <div class="small-muted">Kisan ID: <strong><?= e($kid) ?></strong></div>
            </div>
          </div>

          <hr>

          <div class="small-muted">Contact</div>
          <div class="mt-1"><strong><?= e($mobile) ?></strong></div>

          <hr>

          <div class="small-muted">Location</div>
          <div class="mt-1"><?= e($block) ?>, <?= e($district) ?></div>

          <hr>

          <div class="small-muted">Category</div>
          <div class="mt-1"><?= e($category) ?></div>

          <div class="d-grid mt-3">
            <a href="apply.php" class="btn btn-primary">Apply for Scheme</a>
            <a href="logout.php" class="btn btn-outline-secondary mt-2">Logout</a>
          </div>
        </div>
      </aside>

      <!-- Main content -->
      <section class="col-lg-9">
        <div class="card p-4 kpi">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h4 class="mb-1">Welcome, <?= e($name) ?></h4>
              <div class="small-muted">Here is a quick summary of your profile</div>
            </div>
            <div class="text-end small-muted">
              <div>Logged in as</div>
              <strong>किसान</strong>
            </div>
          </div>

          <div class="row mt-4 g-3">
            <div class="col-md-4">
              <div class="p-3 border rounded bg-white">
                <div class="small-muted">Kisan ID</div>
                <div class="fw-bold mt-1"><?= e($kid) ?></div>
              </div>
            </div>

            <div class="col-md-4">
              <div class="p-3 border rounded bg-white">
                <div class="small-muted">Mobile</div>
                <div class="fw-bold mt-1"><?= e($mobile) ?></div>
              </div>
            </div>

            <div class="col-md-4">
              <div class="p-3 border rounded bg-white">
                <div class="small-muted">Category</div>
                <div class="fw-bold mt-1"><?= e($category) ?></div>
              </div>
            </div>

            <div class="col-12 mt-3">
              <div class="p-3 border rounded bg-white">
                <div class="small-muted">Address</div>
                <div class="mt-1"><?= e($block) ?>, <?= e($district) ?></div>
              </div>
            </div>
          </div>

          <hr class="my-4">

          <div>
            <h6 class="mb-2">Quick actions</h6>
            <div class="d-flex gap-2 flex-wrap">
              <a href="apply.php" class="btn btn-primary">Apply for Scheme</a>
              <a href="profile_edit.php" class="btn btn-ghost">Edit Profile</a>
              <a href="my_status.php" class="btn btn-ghost">Application Status</a>
              <a href="help.php" class="btn btn-outline-secondary">Help / FAQs</a>
            </div>
          </div>

        </div>

        <!-- Optional: recent applications / notices (placeholder) -->
        <div class="card mt-4 p-3">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Recent Activity</h6>
            <small class="small-muted">Last 6 months</small>
          </div>
          <div class="mt-3 small-muted">No recent applications found.</div>
        </div>

      </section>
    </div>

    <footer class="site-footer mt-4">
      <div class="d-flex justify-content-between align-items-center">
        <div>© <?= date('Y') ?> DBT Demo. All rights reserved.</div>
        <div class="small-muted">Contact: help@example.gov.in</div>
      </div>
    </footer>
  </main>

  <!-- Bootstrap JS (optional) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
