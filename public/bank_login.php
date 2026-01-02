<?php
session_start();
require __DIR__ . '/../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $mysqli->prepare("SELECT bank_id FROM bank_admins WHERE username=? AND password=? LIMIT 1");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $_SESSION['bank_logged_in'] = true;
        $_SESSION['bank_user'] = $username;
        header("Location: bank_dashboard.php");
        exit;
    } else {
        $error = "Invalid login credentials";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Bank Admin Login</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background: #f4f6f9;
    }
    .login-card {
      max-width: 420px;
      margin: auto;
    }
  </style>
</head>

<body>

<div class="container vh-100 d-flex align-items-center justify-content-center">

  <div class="card shadow login-card">
    <div class="card-body p-4">

      <h4 class="text-center mb-3">🏦 Bank Admin Login</h4>
      <p class="text-center text-muted mb-4">
        Secure access for bank officials
      </p>

      <?php if ($error): ?>
        <div class="alert alert-danger py-2">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="post">

        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text"
                 name="username"
                 class="form-control"
                 required>
        </div>

        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password"
                 name="password"
                 class="form-control"
                 required>
        </div>

        <button type="submit" class="btn btn-primary w-100">
          Login
        </button>

      </form>

    </div>
  </div>

</div>

</body>
</html>
