<?php
session_start();
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['bank_logged_in'])) {
    header("Location: bank_login.php");
    exit;
}

$app_id = $_POST['app_id'] ?? 0;

$stmt = $mysqli->prepare("
    UPDATE applications
    SET bank_status='PAID', payment_status='PAID'
    WHERE app_id = ?
");
$stmt->bind_param("i", $app_id);
$stmt->execute();

header("Location: bank_dashboard.php");
exit;
