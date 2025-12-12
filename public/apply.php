<?php
session_start();
require __DIR__ . '/../config/db.php';

// ensure user is logged-in
if (empty($_SESSION['logged_in']) || empty($_SESSION['farmer_kisan'])) {
    header("Location: login.php?next=" . urlencode("apply.php"));
    exit;
}
$kisan_id = $_SESSION['farmer_kisan'];

// safe output helper
function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// load user (to prefill)
$stmt = $mysqli->prepare("SELECT user_id, name, mobile, district, block, village FROM users WHERE kisan_id = ? LIMIT 1");
$stmt->bind_param("s", $kisan_id);
$stmt->execute();
$stmt->bind_result($user_id, $name, $mobile, $district, $block, $village);
$stmt->fetch();
$stmt->close();

if (empty($user_id)) {
    // edge-case: user disappeared
    echo "User not found. Please contact admin.";
    exit;
}

// load available schemes from DB if table exists, else fallback to array
$schemes = [];
$check = $mysqli->query("SHOW TABLES LIKE 'schemes'");
if ($check && $check->num_rows > 0) {
    $res = $mysqli->query("SELECT code, title, description FROM schemes ORDER BY code");
    while ($r = $res->fetch_assoc()) {
        $schemes[$r['code']] = $r['title'] . (!empty($r['description']) ? " — " . $r['description'] : "");
    }
    if (isset($res)) $res->free();
} else {
    // fallback static list
    $schemes = [
        'SCHEME_A' => 'Crop Support Scheme — smallholder support',
        'SCHEME_B' => 'Irrigation Assistance Scheme',
        'SCHEME_C' => 'Seed Subsidy Scheme'
    ];
}

// create CSRF token for form
if (empty($_SESSION['csrf_apply'])) {
    $_SESSION['csrf_apply'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['csrf_apply'];

?>
<!doctype html>
<html lang="hi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Apply for Scheme — DBT Demo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f4f6f9; font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", Arial; }
   body { font-family: Arial, sans-serif; background:#f4f6fb; color:#222; }
    .topbar { background: #fff; border-bottom:1px solid #e6e6e6; }
    .brand { display:flex; gap:10px; align-items:center; }
    .brand img { height:40px; width:40px; object-fit:cover; border-radius:6px; }
    .page-wrap { max-width:1000px; margin:28px auto; }
    .small-note { color:var(--muted); }

    /* .brand { background: linear-gradient(90deg,#0b5ed7,#1768ff); color:#fff; padding:10px 14px; border-radius:8px; font-weight:700; display:inline-block; } */
    .card { border-radius:10px; }
    .small-muted { color:#6c757d; }
  </style>
</head>
<body>
    <!-- Top bar -->
<header class="topbar">
  <div class="container d-flex justify-content-between align-items-center py-2">
    <div class="brand">
      <img src="img/logo.png" alt="logo" onerror="this.onerror=null; this.src='https://via.placeholder.com/40?text=DBT'">
      <div>
        <div style="font-weight:700; color:var(--brand)">DBT Portal</div>
        <div class="small-note">Integrated Beneficiary Management</div>
      </div>
    </div>

    <div class="d-none d-md-flex align-items-center gap-2">
      <a href="register_form.php" class="btn btn-outline-success btn-sm">Register</a>
      <a href="login.php" class="btn btn-outline-primary btn-sm">Farmer Login</a>
      <a href="admin/admin_login.php" class="btn btn-primary btn-sm">Admin Sign In</a>
    </div>
  </div>
</header>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <div class="brand">DBT — Apply for Scheme</div>
        <div class="small text-muted mt-1">Kisan Portal — Submit application for schemes</div>
      </div>
      <div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
          <div class="card-body">
            <h5 class="card-title">Apply for Scheme</h5>
            <p class="small-muted">Prefilled details come from your registration. Choose a scheme and submit application.</p>

            <!-- Prefilled user info -->
            <div class="border rounded p-3 mb-3 bg-white">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <strong><?= e($name) ?></strong><br>
                  <div class="small-muted">Kisan ID: <?= e($kisan_id) ?> &nbsp; | &nbsp; Mobile: <?= e($mobile) ?></div>
                  <div class="mt-1 small-muted">District: <?= e($district) ?> / Block: <?= e($block) ?> / Village: <?= e($village) ?></div>
                </div>
                <div class="text-end">
                  <div class="small-muted">Registered</div>
                </div>
              </div>
            </div>

            <form method="POST" action="apply_handler.php" enctype="multipart/form-data" id="applyForm" novalidate>
              <input type="hidden" name="user_id" value="<?= intval($user_id) ?>">
              <input type="hidden" name="kisan_id" value="<?= e($kisan_id) ?>">
              <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

              <div class="mb-3">
                <label class="form-label fw-semibold">Choose Scheme <span class="text-danger">*</span></label>
                <select name="scheme_code" class="form-select" required aria-required="true">
                  <option value="">-- Select scheme --</option>
                  <?php foreach($schemes as $code => $label): ?>
                    <option value="<?= e($code) ?>"><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Please select a scheme.</div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Reason / Short Note (optional)</label>
                <textarea name="remarks" rows="4" class="form-control" placeholder="Short note (optional)"></textarea>
              </div>

              <div class="row g-3">
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Bank Account (optional)</label>
                  <input type="text" name="bank_account" class="form-control" pattern="\d{9,18}" title="Enter numeric account number (9-18 digits)">
                  <div class="form-text small-muted">If you want direct transfer. Enter numbers only.</div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Upload Document (optional)</label>
                  <input class="form-control" type="file" name="doc" accept=".pdf,.jpg,.jpeg,.png" id="docInput">
                  <div class="form-text small-muted">Allowed: PDF, JPG, PNG. Max 5 MB.</div>
                </div>
              </div>

              <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">Submit Application</button>
                <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
              </div>
            </form>

            <div id="formMsg" class="mt-3"></div>

          </div>
        </div>
      </div>

      <aside class="col-lg-4">
        <div class="card shadow-sm p-3 mb-3">
          <h6 class="mb-2">Tips & Info</h6>
          <ul class="small-muted mb-0">
            <li>Make sure your mobile and Kisan ID are correct.</li>
            <li>Keep supporting documents clear and within size limit.</li>
            <li>You will receive updates on your registered mobile.</li>
          </ul>
        </div>

        <div class="card shadow-sm p-3">
          <h6 class="mb-2">Available Schemes</h6>
          <ul class="small-muted mb-0">
            <?php foreach($schemes as $c => $lbl): ?>
              <li><?= e($lbl) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </aside>
    </div>

    <footer class="mt-4 small text-muted">© <?= date('Y') ?> DBT Demo — Kisan Portal</footer>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Client-side file size check & form validation
  (function(){
    const form = document.getElementById('applyForm');
    const docInput = document.getElementById('docInput');
    const MAX_BYTES = 5 * 1024 * 1024; // 5MB

    form.addEventListener('submit', function(e){
      // HTML5 validation
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
        form.classList.add('was-validated');
        return;
      }

      // file size client check
      if (docInput.files.length > 0) {
        const f = docInput.files[0];
        if (f.size > MAX_BYTES) {
          e.preventDefault();
          e.stopPropagation();
          alert('Selected file is larger than 5 MB. Please choose a smaller file.');
          return;
        }
      }
      // allowed to submit — server will enforce checks again
    }, false);
  })();
</script>
</body>
</html>
