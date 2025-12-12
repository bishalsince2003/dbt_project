<?php
// public/index.php
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>DBT Portal — Home</title>

  <!-- Bootstrap 5 (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>

  <style>
    :root{
      --brand:#0d6efd;
      --muted:#6c757d;
    }
    body { font-family: Arial, sans-serif; background:#f4f6fb; color:#222; }
    .topbar { background: #fff; border-bottom:1px solid #e6e6e6; }
    .brand { display:flex; gap:10px; align-items:center; }
    .brand img { height:40px; width:40px; object-fit:cover; border-radius:6px; }
    .page-wrap { max-width:1000px; margin:28px auto; }
    .small-note { color:var(--muted); }
    .carousel-inner img { 
    height: 550px;
    object-fit: cover;
    width:100%;
    border-radius:8px;
}

    @media (max-width:767px){ .carousel-inner img { height: 220px; } }
    .btn-row { margin-top:18px; display:flex; justify-content:center; gap:12px; flex-wrap:wrap; }
    footer { margin-top:28px; }
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

<!-- Main content -->
<div class="page-wrap container">

  <!-- Carousel (6 photos) -->
  <div id="farmerCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="3000">
    <!-- Indicators -->
    <div class="carousel-indicators">
      <button type="button" data-bs-target="#farmerCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
      <button type="button" data-bs-target="#farmerCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
      <button type="button" data-bs-target="#farmerCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
      <button type="button" data-bs-target="#farmerCarousel" data-bs-slide-to="3" aria-label="Slide 4"></button>
      <button type="button" data-bs-target="#farmerCarousel" data-bs-slide-to="4" aria-label="Slide 5"></button>
      <button type="button" data-bs-target="#farmerCarousel" data-bs-slide-to="5" aria-label="Slide 6"></button>
    </div>

    <div class="carousel-inner rounded shadow-sm">
      <div class="carousel-item active">
        <img src="img/farmers/farmer1.jpg" class="d-block w-130" alt="Farmer 1">
      </div>
      <div class="carousel-item">
        <img src="img/farmers/farmer2.jpg" class="d-block w-130" alt="Farmer 2">
      </div>
      <div class="carousel-item">
        <img src="img/farmers/farmer3.jpg" class="d-block w-130" alt="Farmer 3">
      </div>
      <div class="carousel-item">
        <img src="img/farmers/farmer4.jpg" class="d-block w-130" alt="Farmer 4">
      </div>
      <div class="carousel-item">
        <img src="img/farmers/farmer5.jpg" class="d-block w-130" alt="Farmer 5">
      </div>
      <div class="carousel-item">
        <img src="img/farmers/farmer6.jpg" class="d-block w-130" alt="Farmer 6">
      </div>
    </div>

    <!-- Controls -->
    <button class="carousel-control-prev" type="button" data-bs-target="#farmerCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#farmerCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Next</span>
    </button>
  </div>

 <div class="text-center mt-4">
    <h3 class="fw-bold">Empowering Farmers Through Digital DBT Services</h3>
    <div class="container mt-4">
  <div class="row text-center small-note">
    <div class="col-md-3 p-2">✔ Direct Benefit Transfer</div>
    <div class="col-md-3 p-2">✔ Fast Application Processing</div>
    <div class="col-md-3 p-2">✔ Secure OTP Login</div>
    <div class="col-md-3 p-2">✔ Track Application Status</div>
  </div>
</div>


</div>


</div>

<!-- Footer -->
<footer class="bg-white border-top mt-4">
  <div class="container d-flex justify-content-between align-items-center py-3">
    <div class="small-note">© <?=date('Y')?> DBT Demo Portal — Built for Internship</div>
    <div class="small-note">Contact: <a href="mailto:bishalsinghtas@gmail.com">bishalsinghtas@gmail.com</a></div>
  </div>
</footer>

</body>
</html>
