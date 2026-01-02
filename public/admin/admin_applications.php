<?php
// admin_applications.php
session_start();
require __DIR__ . '/../../config/db.php';

if (empty($_SESSION['admin_logged'])) {
    header("Location: admin_login.php");
    exit;
}

/**
 * Helpers
 */
function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}
function verify_csrf_token($token) {
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Create token for form use
$csrf = generate_csrf_token();

// ---------- Handle Approve / Reject (POST) ----------
// ---------- Handle Approve / Reject (POST) ----------
$flash = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['app_id'], $_POST['action'], $_POST['csrf_token'])
) {

    if (!verify_csrf_token($_POST['csrf_token'])) {
        $flash = 'Invalid request (CSRF).';
    } else {

        $app_id = (int) $_POST['app_id'];
        $action = $_POST['action'];

        if ($action === 'approve') {

            // APPROVE + FORWARD TO BANK
            $stmt = $mysqli->prepare(
                "UPDATE applications 
                 SET status='APPROVED', bank_status='SENT' 
                 WHERE app_id=?"
            );
            $stmt->bind_param("i", $app_id);
            $stmt->execute();
            $stmt->close();

            $flash = "Application #{$app_id} approved and forwarded to bank.";

        } elseif ($action === 'reject') {

            // REJECT
            $stmt = $mysqli->prepare(
                "UPDATE applications 
                 SET status='REJECTED' 
                 WHERE app_id=?"
            );
            $stmt->bind_param("i", $app_id);
            $stmt->execute();
            $stmt->close();

            $flash = "Application #{$app_id} rejected.";
        }
    }

    // regenerate CSRF + redirect
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    $_SESSION['flash'] = $flash;
    header("Location: admin_applications.php");
    exit;
}


// Show flash from session if present
if (!empty($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// ---------- Fetch applications (with basic pagination) ----------
$perPage = 25;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Use prepared statement for count (optional)
$total = 0;
if ($countStmt = $mysqli->prepare("SELECT COUNT(*) FROM applications")) {
    $countStmt->execute();
    $countStmt->bind_result($total);
    $countStmt->fetch();
    $countStmt->close();
}

$sql = "SELECT a.*, u.name, u.mobile, u.kisan_id
        FROM applications a
        JOIN users u ON a.user_id = u.user_id
        ORDER BY a.applied_at DESC
        LIMIT ? OFFSET ?";

$stmt = $mysqli->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    die("Query prepare failed: " . e($mysqli->error));
}

// base path to uploads (ensure this is correct)
$uploads_base = realpath(__DIR__ . '/../../uploads') ?: (__DIR__ . '/../../uploads');
?>
<!doctype html>
<html lang="hi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin — Applications</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f4f6f9; font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", Arial; }
   body { font-family: Arial, sans-serif; background:#f4f6fb; color:#222; }
    .topbar { background: #fff; border-bottom:1px solid #e6e6e6; }
    .brand { display:flex; gap:10px; align-items:center; }
    .brand img { height:40px; width:40px; object-fit:cover; border-radius:6px; }
    .page-wrap { max-width:1000px; margin:28px auto; }
    .small-note { color:var(--muted); }

    /* .brand { background: linear-gradient(90deg,#0b5ed7,#1768ff); color:#fff; padding:10px 14px; border-radius:8px; font-weight:700; } */
    .small-muted { color:#6c757d; }
    table td, table th { vertical-align: middle; }
    .doc-link { text-decoration: none; }
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

    <div class="d-none d-md-flex align-items-center gap-2">
      <!-- <a href="register_form.php" class="btn btn-outline-success btn-sm">Register</a> -->
      <!-- <a href="login.php" class="btn btn-outline-primary btn-sm">Farmer Login</a> -->
      <!-- <a href="admin/admin_login.php" class="btn btn-primary btn-sm">Admin Sign In</a> -->
    </div>
  </div>
</header>
<div class="container py-4">
  <div class="d-flex align-items-center gap-3 mb-3">
    <div class="brand">Admin Panel — Applications</div>
    <div class="ms-auto">
      <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
      <a href="admin_logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-info"><?= e($flash) ?></div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body p-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Farmer Applications</h5>
        <div class="small-muted">Total: <?= intval($total) ?></div>
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>App No</th>
              <th>Farmer Name</th>
              <th>Kisan ID</th>
              <th>Scheme</th>
              <th>Status</th>
              <th>Bank Status</th>

              <th>Date</th>
              <th>Document</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result->num_rows === 0): ?>
              <tr><td colspan="8" class="text-center small-muted">No applications found.</td></tr>
            <?php else: ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><?= e($row['app_no']) ?></td>
                  <td><?= e($row['name']) ?></td>
                  <td><?= e($row['kisan_id'] ?? $row['kisan_id']) ?></td>
                  <td><?= e($row['scheme_code']) ?></td>
                  <td>
                    <?php
                      $status = strtoupper($row['status'] ?? 'PENDING');
                      $badgeClass = $status === 'APPROVED' ? 'success' : ($status === 'REJECTED' ? 'danger' : 'warning');
                    ?>
                    <span class="badge bg-<?= $badgeClass ?>"><?= e($status) ?></span>
                  </td>
                  <td>
<?php
$bankStatus = $row['bank_status'] ?? '';

if ($bankStatus === 'SENT') {
    echo '<span class="badge bg-primary">SENT TO BANK</span>';
}
elseif ($bankStatus === 'PAID') {
    echo '<span class="badge bg-success">PAID</span>';
}
elseif ($bankStatus === 'REJECTED') {
    echo '<span class="badge bg-danger">BANK REJECTED</span>';
}
else {
    echo '<span class="text-muted">—</span>';
}
?>
</td>


                  <td><?= e($row['applied_at']) ?></td>
                  <td>
                    <?php if (!empty($row['doc_path'])):
                        // sanitize filename and verify it exists inside uploads dir
                        $safeName = basename($row['doc_path']);
                        $filePath = realpath($uploads_base . '/' . $safeName);
                        $canView = $filePath && str_starts_with($filePath, $uploads_base) && file_exists($filePath);
                    ?>
                      <?php if ($canView): ?>
                        <a class="doc-link" href="<?= e('../../uploads/' . $safeName) ?>" target="_blank">View</a>
                      <?php else: ?>
                        <span class="small-muted">Missing</span>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="small-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($status === 'PENDING'): ?>
                      <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                        <input type="hidden" name="app_id" value="<?= intval($row['app_id']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                        <button name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                        <button name="action" value="reject" class="btn btn-sm btn-danger ms-1">Reject</button>
                      </form>
                    <?php else: ?>
                      <?= e($status) ?>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php
        $totalPages = max(1, (int)ceil($total / $perPage));
      ?>
      <?php if ($totalPages > 1): ?>
        <nav class="mt-3" aria-label="Applications pagination">
          <ul class="pagination pagination-sm">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      <?php endif; ?>

    </div>
  </div>

  <div class="mt-3 small text-muted">Tip: Use the search box in future to filter by Kisan ID / name (I can add this).</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
