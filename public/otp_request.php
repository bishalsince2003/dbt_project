<?php
session_start();

/* Clean mobile */
function clean_mobile($m){
    return preg_replace('/\D/', '', trim($m));
}

/* Twilio SMS sender */
function send_sms_twilio($toE164, $msg) {

    // ⭐⭐⭐ CHANGE THESE ⭐⭐⭐
   $account_sid     = getenv("TWILIO_ACCOUNT_SID");
    $api_key_sid     = getenv("TWILIO_API_KEY_SID");
    $api_key_secret  = getenv("TWILIO_API_KEY_SECRET");
    $from_number     = getenv("TWILIO_FROM_NUMBER");  

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

    $response = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return (!$err && $code >= 200 && $code < 300);
}

/* HANDLE POST REQUESTS */
$step = "mobile";  // default step

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* STEP 1 — SEND OTP */
    if (isset($_POST['send_otp'])) {

        $mobile = clean_mobile($_POST['mobile']);

        if (!preg_match('/^\d{10}$/', $mobile)) {
            $error = "Enter valid 10-digit mobile number.";
        } else {
            $otp = str_pad(rand(0,999999), 6, '0', STR_PAD_LEFT);

            $_SESSION['otp_mobile'] = $mobile;
            $_SESSION['otp_value'] = $otp;
            $_SESSION['otp_ts'] = time();
            $_SESSION['otp_verified'] = false;

            $to  = "+91".$mobile;
            $msg = "Your OTP is $otp. Valid for 5 minutes.";

            if (send_sms_twilio($to, $msg)) {
                $step = "otp"; // show OTP box on same page
            } else {
                $error = "Failed to send OTP. Try again.";
            }
        }
    }

    /* STEP 2 — VERIFY OTP */
    if (isset($_POST['verify_otp'])) {
        $user_otp = trim($_POST['otp']);
        $mobile   = clean_mobile($_POST['mobile']);

        if ($mobile !== ($_SESSION['otp_mobile'] ?? '')) {
            $error = "Mobile number mismatch.";
        }
        elseif (time() - ($_SESSION['otp_ts'] ?? 0) > 300) {
            $error = "OTP expired. Request a new one.";
            $step = "mobile";
        }
        elseif ($user_otp !== ($_SESSION['otp_value'] ?? '')) {
            $error = "Incorrect OTP.";
            $step = "otp";
        }
        else {
            $_SESSION['otp_verified'] = true;
            $_SESSION['verified_mobile'] = $mobile;

            header("Location: register_form.php");
            exit;
        }
    }
}
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Mobile OTP Verification</title>
</head>

<body>

<h2>Mobile Verification</h2>

<?php if(!empty($error)): ?>
<p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<!-- STEP 1: ENTER MOBILE -->
<?php if($step === "mobile"): ?>
<form method="POST">
    <label>Enter Mobile Number:</label><br>
    <input type="text" name="mobile" maxlength="10" required placeholder="9876543210">
    <br><br>
    <button type="submit" name="send_otp">Send OTP</button>
</form>
<?php endif; ?>


<!-- STEP 2: OTP INPUT ON SAME PAGE -->
<?php if($step === "otp"): ?>
<p>OTP sent to <strong><?= htmlspecialchars($_SESSION['otp_mobile']) ?></strong></p>

<form method="POST">
    <input type="hidden" name="mobile" value="<?= htmlspecialchars($_SESSION['otp_mobile']) ?>">

    <label>Enter OTP:</label><br>
    <input type="text" name="otp" maxlength="6" required placeholder="Enter OTP">
    <br><br>
    <button type="submit" name="verify_otp">Verify OTP</button>
</form>

<p><a href="otp_request.php">Resend OTP</a></p>
<?php endif; ?>

</body>
</html>
