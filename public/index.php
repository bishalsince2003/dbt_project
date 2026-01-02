<?php
// public/index.php
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>DBT Portal — Direct Benefit Transfer</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>

<!-- Font -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="/dbt_project/public/assets/css/common.css">

<style>
  .modal-content{
  border-radius:20px;
}
.modal-body p{
  line-height:1.6;
}

:root{
  --brand:#2563eb;
  --dark:#0f172a;
  --muted:#64748b;
}
body{
  font-family:'Inter',sans-serif;
  background:#f4f6fb;
  color:#0f172a;
}

/* HERO */
.hero{
  background:linear-gradient(135deg,#0f172a,#1e3a8a);
  color:#fff;
  padding:64px;
  border-radius:24px;
  position:relative;
  overflow:hidden;
}
.hero::after{
  content:'';
  position:absolute;
  width:420px;
  height:420px;
  background:rgba(255,255,255,.06);
  border-radius:50%;
  top:-160px;
  right:-160px;
}
.hero *{position:relative;z-index:2}

/* Premium buttons */
.btn-premium{
  background:#fff;
  color:#0f172a;
  font-weight:600;
  border-radius:10px;
  padding:10px 18px;
}
.btn-outline-premium{
  border:1px solid rgba(255,255,255,.4);
  color:#fff;
  border-radius:10px;
  padding:10px 18px;
}

/* CAROUSEL */
.carousel-inner img{
  width:100%;
  object-fit:cover;
  border-radius:24px;
}
@media(max-width:768px){
  .carousel-inner img{height:220px}
}

/* Carousel arrows (premium) */
.carousel-control-prev,
.carousel-control-next{
  width:48px;
  height:48px;
  top:50%;
  transform:translateY(-50%);
  background:rgba(0,0,0,.45);
  border-radius:50%;
  backdrop-filter: blur(6px);
}
.carousel-control-prev:hover,
.carousel-control-next:hover{
  background:rgba(0,0,0,.7);
}

/* FEATURES */
.feature-card{
  background:#fff;
  border-radius:20px;
  padding:26px;
  border:1px solid #e5e7eb;
  transition:.35s ease;
}
.feature-card:hover{
  transform:translateY(-10px);
  box-shadow:0 25px 50px rgba(15,23,42,.18);
}

.weather-widget-hero{
  background:#ffffff;
  border-radius:14px;
  padding:10px 16px;
  min-width:220px;          /* 👈 KEY FIX */
  /* box-shadow:0 10px 25px rgba(0,0,0,.08); */
}

.weather-city{
  font-size:13px;
  font-weight:600;
  color:#0f172a;
  line-height:1.2;
}

.weather-meta{
  font-size:13px;
  color:#475569;
}


/* Subtle animation */
.reveal{
  opacity:0;
  transform:translateY(30px);
  animation:reveal .9s ease forwards;
}
@keyframes reveal{
  to{opacity:1;transform:none}
}
</style>
</head>

<body>

<?php include __DIR__ . '/topbar.php'; ?>

<div class="container my-5">

<!-- HERO -->
<section class="hero reveal mb-5">
  <div class="row align-items-center">
    <div class="col-md-7">
      <h1 class="fw-bold">Direct Benefit Transfer Portal</h1>
      <p class="mt-3 text-light opacity-75">
        A secure, transparent and technology-driven platform ensuring
        government benefits reach farmers directly and efficiently.
      </p>

      <div class="d-flex gap-3 mt-4 flex-wrap">
        <a href="register_form.php" class="btn btn-premium">
          Farmer Registration
        </a>
        <a href="login.php" class="btn btn-outline-premium">
          Login
        </a>
        <button class="btn btn-outline-premium"
        data-bs-toggle="modal"
        data-bs-target="#whyDbtModal">
        Why DBT Exists
        </button>
      </div>
    </div>

    <div class="col-md-5 mt-4 mt-md-0 ">
      <?php include __DIR__ . '/weather.php'; ?>
    </div>
  </div>
  

  
</section>



<!-- CAROUSEL -->
<section class="position-relative reveal mb-5">
  <div class="weather-overlay d-none d-md-block">
   
  </div>

  <div id="farmerCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
    <div class="carousel-inner shadow-lg">
      <div class="carousel-item active"><img src="img/farmers/farmer1.jpg"></div>
      <div class="carousel-item"><img src="img/farmers/farmer2.jpg"></div>
      <div class="carousel-item"><img src="img/farmers/farmer3.jpg"></div>
      <div class="carousel-item"><img src="img/farmers/farmer4.jpg"></div>
      <div class="carousel-item"><img src="img/farmers/farmer5.jpg"></div>
      <div class="carousel-item"><img src="img/farmers/farmer6.jpg"></div>

      
    </div>

    <!-- CONTROLS -->
    <button class="carousel-control-prev" type="button"
            data-bs-target="#farmerCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button"
            data-bs-target="#farmerCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
    </button>
  </div>
</section>

<!-- FEATURES -->
<section class="reveal">
  <div class="row g-4 text-center">
    <div class="col-md-3">
      <div class="feature-card">
        <h5>💸 Direct Transfer</h5>
        <p class="small text-muted mt-2">
          Funds credited straight to verified bank accounts.
        </p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="feature-card">
        <h5>⚡ Fast Processing</h5>
        <p class="small text-muted mt-2">
          Automated approval & validation pipeline.
        </p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="feature-card">
        <h5>🔐 Secure Access</h5>
        <p class="small text-muted mt-2">
          OTP-based login with role segregation.
        </p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="feature-card">
        <h5>📊 Real-time Tracking</h5>
        <p class="small text-muted mt-2">
          Track applications across all stages.
        </p>
      </div>
    </div>
  </div>
</section>

</div>

<!-- FOOTER -->
<footer class="bg-white border-top mt-5">
  <div class="container py-3 d-flex justify-content-between flex-wrap small text-muted">
    <div>© <?=date('Y')?> DBT Demo Portal</div>
    <div>Built for Internship · bishalsinghtas@gmail.com</div>
  </div>
</footer>
<!-- WHY DBT EXISTS MODAL -->
<div class="modal fade" id="whyDbtModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">

      <div class="modal-header border-0">
        <h5 class="modal-title fw-semibold">Why DBT Exists</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body px-4 pb-4">

        <p class="fs-6">
          For years, farmers waited weeks — sometimes months —
          for benefits that were already approved.
        </p>

        <p class="fs-6">
          Payments passed through multiple layers.
          Each layer added delay, confusion, and dependency.
        </p>

        <p class="fs-6">
          <strong>Direct Benefit Transfer removes every unnecessary step.</strong>
        </p>

        <p class="fs-6">
          Funds move straight from the government
          to the farmer’s verified bank account —
          securely, transparently, and on time.
        </p>

        <p class="fs-6 text-muted mt-3">
          This portal exists to ensure dignity, speed,
          and trust in every transaction.
        </p>

      </div>

    </div>
  </div>
</div>

</body>
</html>
