<?php
// topbar.php – premium agriculture + govt top bar (single file)
?>

<style>
/* ===============================
   PREMIUM AGRI-GOVT TOPBAR
================================ */
body {
  margin: 0;
  padding: 0;
}

.topbar{
  background:linear-gradient(90deg,#ffffff 60%, #f0fdf4);
  border-bottom:4px solid #dcfce7;
}

.topbar .container{
  min-height:68px;
}

/* Brand */
.brand-wrap{
  display:flex;
  align-items:center;
  gap:14px;
}

.brand-logo{
  height:44px;
  width:44px;
  object-fit:contain;
  border-radius:10px;
  background:#ecfdf5;
  padding:4px;
}

.brand-title{
  font-size:18px;
  font-weight:700;
  color:#14532d;
  letter-spacing:0.3px;
}

.brand-sub{
  font-size:12px;
  color:#6b7280;
}

/* Divider */
.brand-divider{
  width:1px;
  height:32px;
  background:#d1fae5;
}

/* Buttons */
.topbar .btn{
  border-radius:20px;
  font-weight:600;
  padding:6px 14px;
  transition:all .25s ease;
}

.topbar .btn:hover{
  transform:translateY(-2px);
  box-shadow:0 6px 16px rgba(0,0,0,.12);
}

/* Role highlight */
.role-badge{
  background:#ecfdf5;
  color:#166534;
  font-size:11px;
  padding:2px 10px;
  border-radius:999px;
  margin-top:2px;
  display:inline-block;
}

/* Mobile menu */
.dropdown-menu{
  border-radius:14px;
}
</style>

<header class="topbar shadow-sm">
  <div class="container-fluid px-4 d-flex align-items-center justify-content-between py-2">


    <!-- BRAND -->
    <a href="/dbt_project/public/index.php" class="text-decoration-none">
      <div class="brand-wrap">

        <img src="/dbt_project/public/img/logo.png"
             alt="DBT Logo"
             class="brand-logo">

        <div>
          <div class="brand-title">
            DBT Portal 🌾
          </div>
          <div class="brand-sub">
            Integrated Beneficiary Management
          </div>
          <span class="role-badge">Serving Farmers Digitally</span>
        </div>

      </div>
    </a>

    <!-- DESKTOP ACTIONS -->
    <div class="d-none d-md-flex align-items-center gap-2">
      <a href="/dbt_project/public/register_form.php"
         class="btn btn-outline-success btn-sm">
        Register
      </a>

      <a href="/dbt_project/public/login.php"
         class="btn btn-outline-primary btn-sm">
        Farmer Login
      </a>

      <a href="/dbt_project/public/admin/admin_login.php"
         class="btn btn-primary btn-sm">
        Admin
      </a>
    </div>

    

  </div>
  
</header>
