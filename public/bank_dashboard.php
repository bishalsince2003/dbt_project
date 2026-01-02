<?php
session_start();
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['bank_logged_in'])) {
    header("Location: bank_login.php");
    exit;
}

$editId = $_GET['edit'] ?? null;

// Bank sees applications forwarded to bank
$stmt = $mysqli->query("
    SELECT 
        app_id,
        kisan_id,
        scheme_code,
        bank_account,
        applied_at,
        bank_status
    FROM applications
    WHERE bank_status IN ('SENT','PAID','REJECTED')
    ORDER BY applied_at DESC
");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Bank Dashboard</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body { background:#f4f6f9; }
    .status-badge { font-size: 0.85rem; }
    .table th { white-space: nowrap; }
  </style>
</head>

<body>

<div class="container-fluid py-4">

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1">🏦 Bank Payment Dashboard</h4>
      <div class="text-muted small">
        Logged in as <strong><?= htmlspecialchars($_SESSION['bank_user']) ?></strong>
      </div>
    </div>

    <a href="bank_login.php" class="btn btn-outline-danger btn-sm">
      Logout
    </a>
  </div>

  <!-- Card -->
  <div class="card shadow-sm">
    <div class="card-body p-0">

      <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>App ID</th>
              <th>Kisan ID</th>
              <th>Scheme</th>
              <th>Bank Account</th>
              <th>Applied At</th>
              <th class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>

<?php while($row = $stmt->fetch_assoc()): ?>
<?php
$bs    = $row['bank_status'];
$appId = $row['app_id'];
?>

<tr>
  <td><?= $appId ?></td>
  <td><?= htmlspecialchars($row['kisan_id']) ?></td>
  <td><?= htmlspecialchars($row['scheme_code']) ?></td>
  <td><?= $row['bank_account'] ?? '-' ?></td>
  <td><?= $row['applied_at'] ?></td>

  <td class="text-center">

<?php
/*
 Editable mode when:
 1) status is SENT
 2) OR arrow clicked (?edit=app_id)
*/
if ($bs === 'SENT' || $editId == $appId):
?>
    <form method="post" action="bank_action.php" class="d-flex justify-content-center gap-2">
      <input type="hidden" name="app_id" value="<?= $appId ?>">

      <select name="bank_decision" class="form-select form-select-sm w-auto" required>
        <option value="">Select</option>
        <option value="PAID" <?= $bs==='PAID'?'selected':'' ?>>Paid</option>
        <option value="REJECTED" <?= $bs==='REJECTED'?'selected':'' ?>>Rejected</option>
      </select>

      <button type="submit" class="btn btn-sm btn-primary">
        Update
      </button>
    </form>

<?php else: ?>

    <?php if ($bs === 'PAID'): ?>
      <span class="badge bg-success status-badge">Paid</span>
    <?php elseif ($bs === 'REJECTED'): ?>
      <span class="badge bg-danger status-badge">Rejected</span>
    <?php else: ?>
      <span class="badge bg-warning text-dark status-badge">Sent</span>
    <?php endif; ?>

    <a href="?edit=<?= $appId ?>" class="ms-2 text-decoration-none" title="Edit">
      ✏️
    </a>

<?php endif; ?>

  </td>
</tr>

<?php endwhile; ?>

          </tbody>
        </table>
      </div>

    </div>
  </div>

</div>

</body>
</html>
