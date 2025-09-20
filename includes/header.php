<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/functions.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AsekosiGo</title>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<!-- Lottie Player -->
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
<!-- Custom CSS -->
<link rel="stylesheet" href="/asekogo/assets/css/style.css">

<style>
/* Gradient Navbar */
.navbar {
    padding: 0.75rem 2rem;
    background: linear-gradient(90deg, #4383e3 0%, #0a58ca 100%);
}
.navbar-brand {
    font-size: 1.75rem;
    letter-spacing: 1px;
    color: white;
}
.nav-link {
    font-weight: 500;
    margin-left: 0.5rem;
    color: white !important;
    transition: color 0.2s;
}
.nav-link:hover {
    color: #ffc107 !important;
}
.navbar .dropdown-menu {
    min-width: 12rem;
    border-radius: 0.5rem;
}
.badge-cart {
    position: absolute;
    top: 0.25rem;
    right: 0.25rem;
    font-size: 0.7rem;
}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg shadow sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <!-- Lottie Shopping Tray Animation -->
            <lottie-player 
                src="https://assets5.lottiefiles.com/packages/lf20_touohxv0.json" 
                background="transparent"  
                speed="1"  
                style="width: 40px; height: 40px;"  
                loop  
                autoplay>
            </lottie-player>
            <span class="ms-2 fw-bold">AsekosiGo</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Hello, <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if ($_SESSION['user_type'] == 'customer'): ?>
                                <li><a class="dropdown-item" href="/asekogo/customer/cart.php"><i class="bi bi-cart me-2"></i>Cart</a></li>
                                <li><a class="dropdown-item" href="/asekogo/customer/orders.php"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
                            <?php elseif ($_SESSION['user_type'] == 'vendor'): ?>
                                <li><a class="dropdown-item" href="/asekogo/vendor/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Vendor Dashboard</a></li>
                            <?php elseif ($_SESSION['user_type'] == 'admin'): ?>
                                <li><a class="dropdown-item" href="/asekogo/admin/dashboard.php"><i class="bi bi-shield-lock me-2"></i>Admin Dashboard</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/asekogo/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>

                    <?php if ($_SESSION['user_type'] == 'customer'): ?>
                        <li class="nav-item position-relative">
                            <a class="nav-link" href="/asekogo/customer/cart.php">
                                <i class="bi bi-cart fs-5"></i>
                                <span class="badge bg-warning text-dark badge-cart">0</span>
                            </a>
                        </li>
                    <?php endif; ?>

                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="index.php?view=login">Login</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-warning text-dark rounded-3 ms-2" href="index.php?view=register&type=vendor">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
