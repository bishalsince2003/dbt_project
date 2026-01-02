<?php
// public/register.php
// Updated: uses OTP-verified mobile, normalizes mobile, checks duplicates pre-insert,
// and handles duplicate-key on insert. Shows friendly Bootstrap responses.

// require DB connection
require __DIR__ . '/../config/db.php';

session_start();

// Ensure DB connection uses correct charset (helps avoid utf issues)
if (isset($mysqli) && $mysqli instanceof mysqli) {
    $mysqli->set_charset('utf8mb4');
}

// ---------- helpers ----------
function generate_farmer_id() {
    return 'FMR' . date('Ymd') . str_pad(rand(0,9999), 4, '0', STR_PAD_LEFT);
}

function generate_unique_kisan_id($mysqli) {
    $try = 0;
    do {
        $k = date('Ymd') . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE kisan_id = ?");
        if (!$stmt) {
            // If prepare fails, break to avoid infinite loop — caller will see error later
            return $k;
        }
        $stmt->bind_param("s", $k);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        $try++;
        if ($try > 20) {
            // give brief pause if unlucky collisions
            sleep(1);
        }
    } while ($exists);
    return $k;
}

// safe output
function e($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

// normalize mobile to last 10 digits
function normalize_mobile($m) {
    $m = preg_replace('/\D/', '', (string)$m);
    if ($m === '') return '';
    return substr($m, -10);
}

/* ------------------ MUST: require OTP-verified mobile ------------------
   This ensures only numbers verified via OTP in session can register.
*/
if (empty($_SESSION['otp_verified']) || empty($_SESSION['verified_mobile'])) {
    // show friendly message instructing user to verify
    ?>
    <!doctype html>
    <html lang="hi">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <title>Verify Mobile</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
      <div class="container py-5">
        <div class="card shadow-sm">
          <div class="card-body">
            <h4 class="text-warning">Mobile verification required</h4>
            <p>कृपया सबसे पहले मोबाइल नंबर verify करें। OTP verification के बिना registration allowed नहीं है।</p>
            <a href="register_form.php" class="btn btn-primary">Verify Mobile</a>
          </div>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}

/* ----------------- collect & sanitize POST ----------------- */
$name = trim((string)($_POST['name'] ?? ''));
$father_spouse = trim((string)($_POST['father_spouse'] ?? ''));
$dob = $_POST['dob'] ?? null;
$gender = $_POST['gender'] ?? null;
// Use session-verified mobile (do NOT trust POST mobile)
$mobile_post = preg_replace('/\D/', '', (string)($_POST['mobile'] ?? ''));
$session_mobile = $_SESSION['verified_mobile'] ?? '';
$use_mobile = normalize_mobile($session_mobile ?: $mobile_post);

$aadhaar_raw = preg_replace('/\D/', '', (string)($_POST['aadhaar'] ?? ''));
$village = trim((string)($_POST['village'] ?? ''));
$block = trim((string)($_POST['block'] ?? ''));
$district = trim((string)($_POST['district'] ?? ''));
$pincode = preg_replace('/\D/', '', (string)($_POST['pincode'] ?? ''));
$category = trim((string)($_POST['category'] ?? ''));
$farmer_type = trim((string)($_POST['farmer_type'] ?? ''));

// Basic server-side validation
$errors = [];
if ($name === '') $errors[] = "Name is required.";
if ($use_mobile === '') $errors[] = "Mobile is required (and must be OTP-verified).";
elseif (!preg_match('/^[6-9]\d{9}$/', $use_mobile)) $errors[] = "Mobile must be a valid 10-digit Indian number.";
if ($aadhaar_raw !== '' && !preg_match('/^\d{12}$/', $aadhaar_raw)) $errors[] = "Aadhaar must be 12 digits (if provided).";

// File upload handling (safe)
$uploads_dir = __DIR__ . '/../uploads';
if (!is_dir($uploads_dir)) {
    if (!mkdir($uploads_dir, 0755, true)) {
        $errors[] = "Unable to create uploads directory.";
    }
}

$doc_path = null;
if (!empty($_FILES['doc']['name'])) {
    if ($_FILES['doc']['error'] === UPLOAD_ERR_OK) {
        // Basic mime check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['doc']['tmp_name']);
        finfo_close($finfo);

        $allowed = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png'
        ];

        if (!array_key_exists($mime, $allowed)) {
            $errors[] = "Unsupported document type. Allowed: PDF, JPG, PNG.";
        } else {
            // Generate safe filename
            try {
                $ext = $allowed[$mime];
                $newName = 'doc_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $dest = $uploads_dir . '/' . $newName;

                // move uploaded file
                if (!move_uploaded_file($_FILES['doc']['tmp_name'], $dest)) {
                    $errors[] = "Failed to save uploaded file.";
                } else {
                    $doc_path = $newName;
                    // optional: set restrictive permission
                    @chmod($dest, 0644);
                }
            } catch (Exception $ex) {
                $errors[] = "File handling error.";
            }
        }
    } else {
        $errors[] = "File upload error (code: " . intval($_FILES['doc']['error']) . ").";
    }
}

// If validation failed — show a friendly Bootstrap-styled error page and stop
if (count($errors) > 0) {
    ?>
    <!doctype html>
    <html lang="hi">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <title>Registration Error</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
      <div class="container py-5">
        <div class="card shadow-sm">
          <div class="card-body">
            <h4 class="card-title text-danger">Errors in registration</h4>
            <ul class="mt-3">
              <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
              <?php endforeach; ?>
            </ul>
            <a href="register_form.php" class="btn btn-secondary mt-3">Go back</a>
          </div>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}

/* ----------------- prepare masked aadhaar & ids ----------------- */
$aadhaar_last4 = $aadhaar_raw !== '' ? substr($aadhaar_raw, -4) : null;
$aadhaar_masked = $aadhaar_last4 ? 'XXXX-XXXX-' . $aadhaar_last4 : null;

// generate ids
$farmer_id = generate_farmer_id();
$kisan_id = generate_unique_kisan_id($mysqli);

/* ----------------- DUPLICATE MOBILE CHECK (pre-insert) ----------------- */
$dupStmt = $mysqli->prepare("SELECT user_id FROM users WHERE mobile = ? LIMIT 1");
if ($dupStmt) {
    $dupStmt->bind_param("s", $use_mobile);
    $dupStmt->execute();
    $dupStmt->store_result();

    if ($dupStmt->num_rows > 0) {
        // Mobile already exists — show friendly Bootstrap error and stop
        ?>
        <!doctype html>
        <html lang="hi">
        <head>
          <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
          <title>Mobile Already Registered</title>
          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
          <div class="container py-5">
            <div class="card shadow-sm">
              <div class="card-body">
                <h4 class="text-danger">यह मोबाइल नंबर पहले से पंजीकृत है</h4>
                <p>+91<?= e($use_mobile) ?> यह नंबर पहले से पंजीकृत है। यदि यह आपका नंबर है, तो कृपया लॉगिन करें।</p>
                <div class="mt-3">
                  <a href="login.php" class="btn btn-primary">Login</a>
                  <a href="register_form.php" class="btn btn-secondary ms-2">Use different number</a>
                </div>
              </div>
            </div>
          </div>
        </body>
        </html>
        <?php
        $dupStmt->close();
        if ($mysqli) $mysqli->close();
        exit;
    }

    $dupStmt->close();
} else {
    // If prepare failed, show friendly message and abort
    ?>
    <!doctype html><html lang="hi"><head>
      <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
      <title>Server error</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head><body class="bg-light">
      <div class="container py-5">
        <div class="card">
          <div class="card-body">
            <h4 class="text-danger">Server error</h4>
            <p>Database error while checking mobile. Please try again later.</p>
            <a href="register_form.php" class="btn btn-secondary">Go back</a>
          </div>
        </div>
      </div>
    </body></html>
    <?php
    if ($mysqli) $mysqli->close();
    exit;
}

/* ----------------- INSERT (with transaction and duplicate-key handling) ----------------- */
$sql = "INSERT INTO users 
  (farmer_id, kisan_id, name, father_spouse, dob, gender, mobile, aadhaar_last4, aadhaar_masked, village, block, district, pincode, category, farmer_type, doc_path, registration_status) 
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING')";

$ok = false;
$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    $mysqli->rollback();
    $dbErr = "Prepare failed: " . e($mysqli->error);
    ?>
    <!doctype html>
    <html lang="hi"><head>
      <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
      <title>Database error</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
      <div class="container py-5">
        <div class="card">
          <div class="card-body">
            <h4 class="text-danger">Database error</h4>
            <pre><?= e($mysqli->error) ?></pre>
            <a href="register_form.php" class="btn btn-secondary">Go back</a>
          </div>
        </div>
      </div>
    </body></html>
    <?php
    exit;
}

// Bind parameters — 16 strings as in original
$bindTypes = str_repeat('s', 16);
$stmt->bind_param(
    $bindTypes,
    $farmer_id,
    $kisan_id,
    $name,
    $father_spouse,
    $dob,
    $gender,
    $use_mobile,         // use the verified/normalized mobile
    $aadhaar_last4,
    $aadhaar_masked,
    $village,
    $block,
    $district,
    $pincode,
    $category,
    $farmer_type,
    $doc_path
);

if ($stmt->execute()) {
    $ok = true;
    $mysqli->commit();

    // registration succeeded — clear OTP session markers to prevent reuse
    unset($_SESSION['otp_value'], $_SESSION['otp_ts'], $_SESSION['otp_attempts']);
    unset($_SESSION['otp_verified'], $_SESSION['verified_mobile']);
} else {
    $errno = $mysqli->errno;
    $errmsg = $mysqli->error;
    $mysqli->rollback();

    // handle duplicate-key race-condition (MySQL code 1062)
    if ($errno == 1062) {
        // Duplicate entry — show friendly message + login prompt
        ?>
        <!doctype html>
        <html lang="hi">
        <head>
          <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
          <title>Already Registered</title>
          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
          <div class="container py-5">
            <div class="card shadow-sm">
              <div class="card-body">
                <h4 class="text-danger">यह नंबर पहले से पंजीकृत है</h4>
                <p>+91<?= e($use_mobile) ?> यह नंबर पहले से पंजीकृत है (duplicates prevented). यदि यह आपका नंबर है तो कृपया लॉगिन करें।</p>
                <div class="mt-3">
                  <a href="login.php" class="btn btn-primary">Login</a>
                  <a href="register_form.php" class="btn btn-secondary ms-2">Use different number</a>
                </div>
              </div>
            </div>
          </div>
        </body>
        </html>
        <?php
        $stmt->close();
        if ($mysqli) $mysqli->close();
        exit;
    } else {
        // other DB error: show dev info (or log in production)
        $dbErr = $errmsg;
    }
}

$stmt->close();
$mysqli->close();

/* ----------------- SHOW result page ----------------- */
?>
<!doctype html>
<html lang="hi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Registration Result</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/dbt_project/public/assets/css/common.css">

  <style>
    body { background:#f4f6f9; }
    .card { border-radius:10px; }
  </style>
</head>
<body>
    <!-- Top bar -->
<?php include __DIR__ . '/topbar.php'; ?>

  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <?php if ($ok): ?>
          <div class="card shadow-sm">
            <div class="card-body">
              <h3 class="text-success">Registration Successful</h3>
              <p class="mb-1"><strong>Farmer ID:</strong> <?= e($farmer_id) ?></p>
              <p class="mb-1"><strong>Kisan ID:</strong> <?= e($kisan_id) ?></p>
              <p class="small text-muted">Save these IDs — Kisan ID will be needed to apply for schemes.</p>

              <div class="mt-4">
                <a href="register_form.php" class="btn btn-outline-primary">Register another</a>
                <a href="login.php" class="btn btn-primary ms-2">Go to Login</a>
                <a href="test_db.php" class="btn btn-link ms-2">DB Test</a>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="card shadow-sm">
            <div class="card-body">
              <h3 class="text-danger">Registration failed</h3>
              <p class="text-muted">There was a problem saving your registration.</p>
              <?php if (!empty($dbErr)): ?>
                <div class="alert alert-warning"><strong>DB message:</strong><br><pre><?= e($dbErr) ?></pre></div>
              <?php endif; ?>
              <a href="register_form.php" class="btn btn-secondary">Go back</a>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
