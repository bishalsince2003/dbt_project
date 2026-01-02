<?php
// apply_handler.php
session_start();
require __DIR__ . '/../config/db.php';

// helper
function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// --------------- AUTH / CSRF ----------------
if (empty($_SESSION['logged_in']) || empty($_SESSION['farmer_kisan'])) {
    http_response_code(403);
    die("Not authorized.");
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_apply']) || !hash_equals($_SESSION['csrf_apply'], $_POST['csrf_token'])) {
    http_response_code(400);
    die("Invalid request (CSRF).");
}

// ensure POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method not allowed.");
}

$kisan_id = $_SESSION['farmer_kisan'];

// --------------- READ & SANITIZE INPUT ----------------
$user_id      = intval($_POST['user_id'] ?? 0);
$posted_kisan = trim($_POST['kisan_id'] ?? '');
$scheme_code  = trim($_POST['scheme_code'] ?? '');
$remarks      = trim($_POST['remarks'] ?? '');
$bank_account = trim($_POST['bank_account'] ?? '');

// basic validation
$errors = [];
if ($user_id <= 0) $errors[] = "Missing user.";
if ($posted_kisan === '' || $posted_kisan !== $kisan_id) $errors[] = "Kisan ID mismatch.";
if ($scheme_code === '') $errors[] = "Please select a scheme.";

// If validation fails, show errors
if (count($errors) > 0) {
    ?>
    <!doctype html>
    <html lang="hi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Apply — Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head><body class="bg-light">
      <div class="container py-5">
        <div class="card shadow-sm">
          <div class="card-body">
            <h4 class="text-danger">Submission errors</h4>
            <ul>
              <?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
            <a href="apply.php" class="btn btn-secondary mt-3">Back</a>
          </div>
        </div>
      </div>
    </body></html>
    <?php
    exit;
}

// --------------- OPTIONAL: validate scheme exists ---------------
$stmt = $mysqli->prepare("SELECT 1 FROM schemes WHERE code = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("s", $scheme_code);
    $stmt->execute();
    $stmt->store_result();
    $scheme_exists = $stmt->num_rows > 0;
    $stmt->close();
} else {
    // If schemes table doesn't exist or prepare failed, assume static fallback allowed.
    $scheme_exists = true;
}
if (!$scheme_exists) {
    die("Selected scheme is invalid.");
}

// --------------- Prevent duplicate applications ---------------
$stmt = $mysqli->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ? AND scheme_code = ?");
if (!$stmt) {
    die("DB error (duplicate check): " . e($mysqli->error));
}
$stmt->bind_param("is", $user_id, $scheme_code);
$stmt->execute();
$stmt->bind_result($existingCount);
$stmt->fetch();
$stmt->close();

if ($existingCount > 0) {
    // user already has an application for this scheme
    ?>
    <!doctype html><html lang="hi"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Already Applied</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head><body class="bg-light">
      <div class="container py-5">
        <div class="card shadow-sm">
          <div class="card-body">
            <h4 class="text-warning">You have already applied for this scheme.</h4>
            <p>Please check your dashboard for application status.</p>
            <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
          </div>
        </div>
      </div>
    </body></html>
    <?php
    exit;
}

// --------------- Handle File Upload (safe) ---------------
$doc_path = null;
if (!empty($_FILES['doc']['name']) && is_uploaded_file($_FILES['doc']['tmp_name'])) {

    $uploads_dir = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads');
    if (!is_dir($uploads_dir) && !mkdir($uploads_dir, 0755, true)) {
        die("Unable to create uploads directory.");
    }

    // limit file size (5 MB)
    $maxBytes = 5 * 1024 * 1024;
    if ($_FILES['doc']['size'] > $maxBytes) {
        die("Uploaded file exceeds 5 MB limit.");
    }

    // mime check
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['doc']['tmp_name']);
    finfo_close($finfo);

    $allowed = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png'
    ];

    if (!array_key_exists($mime, $allowed)) {
        die("Unsupported document type. Allowed: PDF, JPG, PNG.");
    }

    // safe filename
    try {
        $ext = $allowed[$mime];
        $safeName = 'appdoc_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    } catch (Exception $ex) {
        $safeName = 'appdoc_' . time() . '_' . mt_rand(10000,99999) . '.' . $ext;
    }

    $target = $uploads_dir . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($_FILES['doc']['tmp_name'], $target)) {
        die("Failed to save uploaded file.");
    }

    // restrict permission
    @chmod($target, 0644);

    // store relative filename only
    $doc_path = $safeName;
}

// --------------- Insert application (transactional) ---------------
$app_id = 0;
$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

$insert_sql = "INSERT INTO applications 
    (user_id, kisan_id, scheme_code, remarks, bank_account, doc_path, status, applied_at)
    VALUES (?, ?, ?, ?, ?, ?, 'PENDING', NOW())";

$ins = $mysqli->prepare($insert_sql);
if (!$ins) {
    $mysqli->rollback();
    die("DB prepare failed: " . e($mysqli->error));
}

$ins->bind_param("isssss", $user_id, $kisan_id, $scheme_code, $remarks, $bank_account, $doc_path);
if (!$ins->execute()) {
    $ins->close();
    $mysqli->rollback();
    die("Insert failed: " . e($ins->error));
}

$app_id = $ins->insert_id;
$ins->close();

if (!$app_id) {
    $mysqli->rollback();
    die("Insert did not return an insert id.");
}

// generate application number and update
$app_no = 'APP' . date('Ymd') . str_pad($app_id, 5, '0', STR_PAD_LEFT);

$upd = $mysqli->prepare("UPDATE applications SET app_no = ? WHERE app_id = ?");
if (!$upd) {
    $mysqli->rollback();
    die("DB prepare failed (update): " . e($mysqli->error));
}
$upd->bind_param("si", $app_no, $app_id);
if (!$upd->execute()) {
    $upd->close();
    $mysqli->rollback();
    die("Update failed: " . e($upd->error));
}
$upd->close();

$mysqli->commit();

// Optionally: you can send SMS/notification here using your existing function

// clear CSRF token so form cannot be replayed
unset($_SESSION['csrf_apply']);
?>
<!doctype html>
<html lang="hi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Application Submitted</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style> body{background:#f4f6f9;font-family:system-ui,-apple-system,'Segoe UI',Roboto,Arial;} .card{border-radius:10px;}
  body { font-family: Arial, sans-serif; background:#f4f6fb; color:#222; }
    .topbar { background: #fff; border-bottom:1px solid #e6e6e6; }
    .brand { display:flex; gap:10px; align-items:center; }
    .brand img { height:40px; width:40px; object-fit:cover; border-radius:6px; }
    .page-wrap { max-width:1000px; margin:28px auto; }
    .small-note { color:var(--muted); }
 </style>
 <link rel="stylesheet" href="/dbt_project/public/assets/css/common.css">
</head>
<body>
    <!-- Top bar -->
 <?php include __DIR__ . '/topbar.php'; ?>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card shadow-sm p-4">
          <h3 class="text-success">✔ Your application has been submitted</h3>
          <p class="mb-1"><strong>Application Number:</strong> <?= e($app_no) ?></p>
          <p class="mb-1"><strong>Status:</strong> PENDING (Under Review)</p>
          <p class="small text-muted">You will receive updates on your registered mobile number.</p>

          <div class="mt-4">
            <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            <a href="apply.php" class="btn btn-outline-secondary ms-2">Apply for another</a>
            <?php if ($doc_path): ?>
              <a href="<?= e('../uploads/' . $doc_path) ?>" target="_blank" class="btn btn-link ms-2">View uploaded document</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
