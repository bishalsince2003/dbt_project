cat > .env <<'EOF'

EOF<?php
session_start();
require __DIR__ . '/../config/db.php';

// helper: sanitize digits-only
function clean($v){ return preg_replace('/\D/', '', trim($v)); }
function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

/* ---------------- Twilio OTP Sender ----------------
   Replace the placeholders with your real API key/secret locally.
   Do NOT commit secrets to public repos. */
function send_otp_sms($to, $msg){
    $account_sid    = getenv('TWILIO_SID');
$api_key_sid    = getenv('TWILIO_API_KEY_SID');
$api_key_secret = getenv('TWILIO_API_KEY_SECRET');
$from_number    = getenv('TWILIO_FROM');   // <-- replace with your Twilio number

    $url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/Messages.json";

    $data = http_build_query([
        "To"   => $to,
        "From" => $from_number,
        "Body" => $msg
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_USERPWD, $api_key_sid . ":" . $api_key_secret);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $res = curl_exec($ch);
    $err = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return (!$err && $http >= 200 && $http < 300);
}

/* ---------------- OTP logic / rate limiting ----------------
   We store in session:
   - login_kisan, login_mobile
   - login_otp, login_otp_ts
   - login_otp_attempts, login_otp_sent_count, login_otp_last_sent_ts
*/

$error = "";
$info  = "";

// POST: Send OTP
if (isset($_POST['action']) && $_POST['action'] === 'send_otp') {
    $kisan_id = trim($_POST['kisan_id'] ?? '');
    $mobile   = clean($_POST['mobile'] ?? '');

    // server-side validation
    if ($kisan_id === '' || !preg_match('/^\d{10}$/', $mobile)) {
        $error = "Valid Kisan ID and 10-digit mobile are required.";
    } else {
        // Basic resend cooldown: 30 seconds
        $last_sent = $_SESSION['login_otp_last_sent_ts'] ?? 0;
        if (time() - $last_sent < 30) {
            $error = "Please wait " . (30 - (time() - $last_sent)) . " seconds before resending OTP.";
        } else {
            // Verify Kisan ID + mobile exist and match
            $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE kisan_id=? AND mobile=? LIMIT 1");
            if (!$stmt) {
                $error = "DB error (prepare).";
            } else {
                $stmt->bind_param("ss", $kisan_id, $mobile);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows === 0) {
                    $error = "Kisan ID or mobile number is incorrect.";
                } else {
                    // generate OTP and save to session (valid 5 minutes)
                    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

                    $_SESSION['login_kisan'] = $kisan_id;
                    $_SESSION['login_mobile'] = $mobile;
                    $_SESSION['login_otp'] = $otp;
                    $_SESSION['login_otp_ts'] = time();
                    $_SESSION['login_otp_attempts'] = 0;
                    $_SESSION['login_otp_last_sent_ts'] = time();
                    $_SESSION['login_otp_sent_count'] = ($_SESSION['login_otp_sent_count'] ?? 0) + 1;

                    $msg = "Your DBT Login OTP is $otp. Valid for 5 minutes.";
                    $to  = "+91" . $mobile;

                    if (send_otp_sms($to, $msg)) {
                        $info = "OTP sent to +91" . $mobile . ".";
                    } else {
                        $error = "Failed to send OTP. Check Twilio settings and server connectivity.";
                    }
                }
                $stmt->close();
            }
        }
    }
}

// POST: Verify OTP
if (isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
    $entered = trim($_POST['otp'] ?? '');
    $stored_otp = $_SESSION['login_otp'] ?? null;
    $otp_ts = $_SESSION['login_otp_ts'] ?? 0;
    $attempts = $_SESSION['login_otp_attempts'] ?? 0;

    // Basic checks
    if (empty($stored_otp)) {
        $error = "No OTP requested. Please request OTP first.";
    } elseif (time() - $otp_ts > 300) { // 5 minutes
        // expire
        unset($_SESSION['login_otp'], $_SESSION['login_otp_ts'], $_SESSION['login_otp_attempts']);
        $error = "OTP expired. Please request a new OTP.";
    } else {
        $_SESSION['login_otp_attempts'] = $attempts + 1;
        if ($_SESSION['login_otp_attempts'] > 5) {
            // too many attempts — clear OTP
            unset($_SESSION['login_otp'], $_SESSION['login_otp_ts'], $_SESSION['login_otp_attempts']);
            $error = "Too many incorrect attempts. Please request OTP again.";
        } elseif ($entered === $stored_otp) {
            // success — login user
            session_regenerate_id(true); // prevent session fixation
            $_SESSION['logged_in'] = true;
            $_SESSION['farmer_kisan'] = $_SESSION['login_kisan'];

            // clear OTP session data
            unset($_SESSION['login_otp'], $_SESSION['login_otp_ts'], $_SESSION['login_otp_attempts'], $_SESSION['login_otp_last_sent_ts']);

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Incorrect OTP. Attempts: " . $_SESSION['login_otp_attempts'];
        }
    }
}
?>
<!doctype html>
<html lang="hi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>DBT Farmer Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { font-family: Arial, sans-serif; background:#f4f6fb; color:#222; }
    .topbar { background: #fff; border-bottom:1px solid #e6e6e6; }
    .brand { display:flex; gap:10px; align-items:center; }
    .brand img { height:40px; width:40px; object-fit:cover; border-radius:6px; }
    .page-wrap { max-width:1000px; margin:28px auto; }
    .small-note { color:var(--muted); }
  body { background:#f4f6f9; font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", Arial; }
  /* .brand { background: linear-gradient(90deg,#0b5ed7,#1768ff); color:#fff; padding:12px 16px; border-radius:8px; font-weight:700; } */
  .card { border-radius:10px; }
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

  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="brand mb-3">DBT — Farmer Login</div>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($info): ?>
          <div class="alert alert-success"><?= e($info) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
          <div class="card-body">
            <form method="POST" novalidate>
              <div class="mb-3">
                <label class="form-label">Kisan ID</label>
                <input type="text" name="kisan_id" class="form-control" required
                       value="<?= e($_POST['kisan_id'] ?? $_SESSION['login_kisan'] ?? '') ?>">
              </div>

              <div class="mb-3">
                <label class="form-label">Mobile Number</label>
                <input type="text" name="mobile" maxlength="10" class="form-control" required
                       value="<?= e($_POST['mobile'] ?? $_SESSION['login_mobile'] ?? '') ?>">
                <div class="form-text">Enter the 10-digit mobile linked to your Kisan ID.</div>
              </div>

              <div class="d-flex gap-2 mb-3">
                <button type="submit" name="action" value="send_otp" class="btn btn-primary">Send OTP</button>
                <button type="button" class="btn btn-outline-secondary" onclick="document.querySelector('[name=mobile]').value='';document.querySelector('[name=kisan_id]').value='';">Clear</button>
              </div>

              <?php if (isset($_SESSION['login_otp'])): ?>
                <hr>
                <div class="mb-3">
                  <label class="form-label">Enter OTP</label>
                  <input type="text" name="otp" maxlength="6" class="form-control" required>
                  <div class="form-text">OTP valid for 5 minutes. Attempts allowed: 5.</div>
                </div>
                <div class="d-flex gap-2">
                  <button type="submit" name="action" value="verify_otp" class="btn btn-success">Verify OTP & Login</button>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="kisan_id" value="<?= e($_SESSION['login_kisan'] ?? '') ?>">
                    <input type="hidden" name="mobile" value="<?= e($_SESSION['login_mobile'] ?? '') ?>">
                    <button type="submit" name="action" value="send_otp" class="btn btn-outline-primary">Resend OTP</button>
                  </form>
                </div>
                <div class="mt-2 text-muted small">
                  <?php
                    $sent_at = $_SESSION['login_otp_last_sent_ts'] ?? null;
                    if ($sent_at) {
                      echo "Last sent: " . date('d M Y H:i:s', $sent_at);
                    }
                  ?>
                </div>
              <?php endif; ?>

            </form>

            <hr>
            <div class="small text-muted">Need help? Contact support: help@example.gov.in</div>
          </div>
        </div>

        <div class="mt-3 text-center small text-muted">
          © <?= date('Y') ?> DBT Demo
        </div>
      </div>
    </div>
  </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
