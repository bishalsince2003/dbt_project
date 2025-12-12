<?php
session_start();

// ---------- helpers ----------
function clean_mobile($m) { return preg_replace('/\D/', '', trim($m)); }

/* Twilio SMS sending */
function send_sms_twilio($toE164, $msg) {
$account_sid    = getenv('TWILIO_SID');
$api_key_sid    = getenv('TWILIO_API_KEY_SID');
$api_key_secret = getenv('TWILIO_API_KEY_SECRET');
$from_number    = getenv('TWILIO_FROM');


    $url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/Messages.json";
    $data = http_build_query([
        "To"   => $toE164,
        "From" => $from_number,
        "Body" => $msg
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_USERPWD, $api_key_sid . ":" . $api_key_secret);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        error_log("Twilio curl error: " . $err);
        return false;
    }
    return ($http >= 200 && $http < 300);
}

// ---------- UI variables ----------
$otp_error = '';
$otp_info  = '';
$mobile_value = htmlspecialchars($_POST['mobile'] ?? $_SESSION['otp_mobile'] ?? $_SESSION['verified_mobile'] ?? '');
$is_verified = !empty($_SESSION['otp_verified']) && !empty($_SESSION['verified_mobile']);

// ---------- Handle POST actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Send OTP
    if ($_POST['action'] === 'send_otp') {

        $mobile = clean_mobile($_POST['mobile'] ?? '');
        if (!preg_match('/^\d{10}$/', $mobile)) {
            $otp_error = "Please enter a valid 10-digit mobile number.";
        } else {
            $last_ts = $_SESSION['otp_ts'] ?? 0;
            if (time() - $last_ts < 30) {
                $otp_error = "Please wait " . (30 - (time() - $last_ts)) . " seconds before resending OTP.";
            } else {
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $_SESSION['otp_mobile']   = $mobile;
                $_SESSION['otp_value']    = $otp;
                $_SESSION['otp_ts']       = time();
                $_SESSION['otp_attempts'] = 0;
                $_SESSION['otp_verified'] = false;

                $to  = "+91" . $mobile;
                $msg = "Your verification code is $otp. It is valid for 5 minutes.";

                if (send_sms_twilio($to, $msg)) {
                    $otp_info = "OTP sent to +91" . $mobile . ".";
                } else {
                    $otp_error = "Failed to send OTP. Check Twilio credentials.";
                }
            }
        }
    }

    // Verify OTP
    if ($_POST['action'] === 'verify_otp') {
        $mobile = clean_mobile($_POST['mobile'] ?? '');
        $entered = trim($_POST['otp'] ?? '');

        if ($mobile !== ($_SESSION['otp_mobile'] ?? '')) {
            $otp_error = "Mobile number mismatch.";
        } elseif (empty($_SESSION['otp_value'])) {
            $otp_error = "No OTP requested.";
        } elseif (time() - ($_SESSION['otp_ts'] ?? 0) > 300) {
            $otp_error = "OTP expired.";
            unset($_SESSION['otp_value'], $_SESSION['otp_ts'], $_SESSION['otp_mobile']);
        } else {
            $_SESSION['otp_attempts'] = ($_SESSION['otp_attempts'] ?? 0) + 1;

            if ($_SESSION['otp_attempts'] > 5) {
                $otp_error = "Too many attempts.";
                unset($_SESSION['otp_value'], $_SESSION['otp_ts'], $_SESSION['otp_mobile']);
            } elseif ($entered === ($_SESSION['otp_value'] ?? '')) {
                $_SESSION['otp_verified'] = true;
                $_SESSION['verified_mobile'] = $mobile;
                unset($_SESSION['otp_value'], $_SESSION['otp_ts'], $_SESSION['otp_attempts']);
                $otp_info = "Mobile verified successfully.";
                $is_verified = true;
            } else {
                $otp_error = "Incorrect OTP.";
            }
        }
        $mobile_value = htmlspecialchars($mobile);
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
<meta charset="UTF-8">
<title>Farmer Registration | DBT Bihar Demo</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<style>
    
     body { font-family: Arial, sans-serif; background:#f4f6fb; color:#222; }
    .topbar { background: #fff; border-bottom:1px solid #e6e6e6; }
    .brand { display:flex; gap:10px; align-items:center; }
    .brand img { height:40px; width:40px; object-fit:cover; border-radius:6px; }
    .page-wrap { max-width:1000px; margin:28px auto; }
    .small-note { color:var(--muted); }
    .card-gov {
        border-radius:12px;
        border:1px solid #dce3eb;
        box-shadow:0 2px 8px rgba(0,0,0,0.05);
    }
    .gov-header {
        background:#0b5ed7;
        color:#fff;
        padding:14px 20px;
        font-size:20px;
        font-weight:600;
        border-radius:6px;
        margin-bottom:20px;
    }
</style>

</head>
<body>
  <!-- Top bar -->
<header class="bg-white border-bottom">
  <div class="container d-flex justify-content-between align-items-center py-2">

    <!-- LEFT: clickable brand (logo + text) -->
    <a href="index.php" class="d-flex align-items-center gap-2 text-decoration-none flex-shrink-0">
      <img src="img/logo.png"
           alt="logo"
           
           class="rounded"
           style="height:40px;width:40px;object-fit:cover;">
      <div>
        <div class="fw-bold mb-0">DBT Portal</div>
        <div class="small-note text-muted ">Integrated Beneficiary Management</div>
      </div>
    </a>
    <div class="d-flex d-md-flex align-items-center gap-2">
      <a href="register_form.php" class="btn btn-outline-success btn-sm">Register</a>
      <a href="login.php" class="btn btn-outline-primary btn-sm">Farmer Login</a>
      <a href="admin/admin_login.php" class="btn btn-primary btn-sm">Admin Sign In</a>
    </div>
  </div>
</header>


<div class="container mt-4 mb-5">

    <div class="gov-header">किसान पंजीकरण (Farmer Registration)</div>

    <div class="card card-gov p-4">

        <p class="text-muted">Enter your details. Mobile verification is required before registration.</p>

        <form method="POST" enctype="multipart/form-data">

            <!-- MOBILE -->
            <div class="mb-3">
                <label class="form-label fw-bold">Mobile Number *</label>
                <div class="input-group">
                    <input type="text" class="form-control"
                        name="mobile"
                        required
                        pattern="\d{10}"
                        value="<?= $mobile_value ?>"
                        <?= $is_verified ? 'readonly' : '' ?>>
                    <button class="btn btn-primary" type="submit" name="action" value="send_otp">Send OTP</button>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($otp_error): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($otp_error) ?></div>
            <?php endif; ?>

            <?php if ($otp_info): ?>
                <div class="alert alert-success py-2"><?= htmlspecialchars($otp_info) ?></div>
            <?php endif; ?>

            <!-- OTP field -->
            <?php if (isset($_SESSION['otp_mobile']) && !$is_verified): ?>
                <div class="mb-3">
                    <label class="form-label fw-bold">Enter OTP</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="otp" maxlength="6" pattern="\d{6}">
                        <button class="btn btn-success" type="submit" name="action" value="verify_otp">Verify</button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($is_verified): ?>
                <div class="alert alert-success py-2">
                    ✔ Mobile verified: +91<?= htmlspecialchars($_SESSION['verified_mobile']) ?>
                </div>
            <?php endif; ?>

            <hr>

            <!-- REST OF FORM -->
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Full Name *</label>
                    <input class="form-control" type="text" name="name">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Father / Spouse Name</label>
                    <input class="form-control" type="text" name="father_spouse">
                </div>

                <div class="col-md-4 mt-3">
                    <label class="form-label fw-bold">Date of Birth</label>
                    <input class="form-control" type="date" name="dob">
                </div>

                <div class="col-md-4 mt-3">
                    <label class="form-label fw-bold">Gender</label>
                    <select class="form-select" name="gender">
                        <option value="">-- Select --</option>
                        <option value="MALE">Male</option>
                        <option value="FEMALE">Female</option>
                        <option value="OTHER">Other</option>
                    </select>
                </div>

                <div class="col-md-4 mt-3">
                    <label class="form-label fw-bold">Aadhaar Number (optional)</label>
                    <input class="form-control" type="text" name="aadhaar" pattern="\d{12}">
                </div>

                <div class="col-md-4 mt-3">
                    <label class="form-label fw-bold">Village</label>
                    <input class="form-control" type="text" name="village">
                </div>

                <div class="col-md-4 mt-3">
                    <label class="form-label fw-bold">Block</label>
                    <input class="form-control" type="text" name="block">
                </div>

                <div class="col-md-4 mt-3">
                    <label class="form-label fw-bold">District</label>
                    <input class="form-control" type="text" name="district">
                </div>

                <div class="col-md-4 mt-3">
                    <label class="form-label fw-bold">Pincode</label>
                    <input class="form-control" type="text" name="pincode">
                </div>

                <div class="col-md-4 mt-3">
                    <label class="form-label fw-bold">Category</label>
                    <select class="form-select" name="category">
                        <option value="">-- Select --</option>
                        <option value="GENERAL">General</option>
                        <option value="SC">SC</option>
                        <option value="ST">ST</option>
                        <option value="OBC">OBC</option>
                    </select>
                </div>

                <div class="col-md-4 mt-3">
                    <label class="form-label fw-bold">Farmer Type</label>
                    <select class="form-select" name="farmer_type">
                        <option value="">-- Select --</option>
                        <option value="Marginal">Marginal</option>
                        <option value="Small">Small</option>
                        <option value="Medium">Medium</option>
                        <option value="Large">Large</option>
                    </select>
                </div>

                <div class="col-md-12 mt-3">
                    <label class="form-label fw-bold">Upload Document (PDF/JPG/PNG)</label>
                    <input class="form-control" type="file" name="doc" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>

            <div class="mt-4">
                <?php if (!$is_verified): ?>
                    <div class="alert alert-warning py-2">Please verify your mobile before registering.</div>
                    <button class="btn btn-secondary" type="button" disabled>Register</button>
                <?php else: ?>
                    <button class="btn btn-primary px-4" type="submit" formaction="register.php" name="final_submit" value="1">
                        Register
                    </button>
                <?php endif; ?>
            </div>

        </form>

    </div>
</div>

</body>
</html>
