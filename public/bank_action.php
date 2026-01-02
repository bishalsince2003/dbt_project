<?php
session_start();
require __DIR__ . '/../config/db.php';

if (!isset($_SESSION['bank_logged_in'])) {
    header("Location: bank_login.php");
    exit;
}

$app_id = (int)($_POST['app_id'] ?? 0);
$decision = $_POST['bank_decision'] ?? '';

if ($app_id > 0 && in_array($decision, ['PAID','REJECTED'])) {

    $mysqli->query("
        UPDATE applications
        SET bank_status = '$decision'
        WHERE app_id = $app_id
    ");
}

header("Location: bank_dashboard.php");
exit;
