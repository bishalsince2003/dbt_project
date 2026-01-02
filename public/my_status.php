<?php
session_start();
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

$kisan_id = $_SESSION['farmer_kisan'];

$stmt = $mysqli->prepare("
    SELECT scheme_code, applied_at, status,bank_status
    FROM applications
    WHERE kisan_id = ?
    ORDER BY applied_at DESC
");
$stmt->bind_param("s", $kisan_id);
$stmt->execute();
$result = $stmt->get_result();

function statusBadge($status) {
    if ($status === 'APPROVED') {
        return "<span class='badge bg-success'>Approved</span>";
    }
    if ($status === 'REJECTED') {
        return "<span class='badge bg-danger'>Rejected</span>";
    }
    return "<span class='badge bg-warning text-dark'>Pending</span>";
}
function bankMessage($row) {

    if ($row['bank_status'] === 'SENT') {
        return "<small style='color:blue'>Forwarded to Bank for Verification</small>";
    }

    if ($row['bank_status'] === 'REJECTED') {
        return "<small style='color:red'>Payment Rejected by Bank</small>";
    }

    if ($row['bank_status'] === 'PAID') {
        return "<small style='color:green'>Payment Credited Successfully</small>";
    }

    return "";
}

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Application Status</title>

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="/dbt_project/public/assets/css/common.css">

</head>



<body class="bg-light">
    <?php include __DIR__ . '/topbar.php'; ?>

<div class="container py-5">

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">📄 My Application Status</h4>
        </div>

        <div class="card-body">

            <p class="mb-3">
                <strong>Kisan ID:</strong>
                <span class="text-muted"><?= htmlspecialchars($kisan_id) ?></span>
            </p>

            <?php if ($result->num_rows === 0): ?>
                <div class="alert alert-info">
                    No applications found.
                </div>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Scheme Code</th>
                            <th>Applied At</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php $i=1; while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($row['scheme_code']) ?></td>
    <td><?= htmlspecialchars($row['applied_at']) ?></td>
    <td>
        <?= statusBadge($row['status']) ?><br>
        <?= bankMessage($row) ?>
    </td>
</tr>
<?php endwhile; ?>


                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

            <a href="dashboard.php" class="btn btn-outline-secondary mt-3">
                ⬅ Back to Dashboard
            </a>

        </div>
    </div>

</div>

</body>
</html>
