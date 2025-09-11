<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/functions.php"; // adjust if in another folder
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AsekosiGo</title>

    <!-- Bootstrap CSS (CDN version – guaranteed to work) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Your custom CSS -->
    <link rel="stylesheet" href="/asekogo/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">AsekosiGo</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <span class="nav-link">
                                Hello, <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User'; ?>
                            </span>
                        </li>

                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'customer'): ?>
                            <li class="nav-item"><a class="nav-link" href="/asekogo/customer/cart.php">Cart</a></li>
                            <li class="nav-item"><a class="nav-link" href="/asekogo/customer/orders.php">My Orders</a></li>

                        <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'vendor'): ?>
                            <li class="nav-item"><a class="nav-link" href="/asekogo/vendor/dashboard.php">Vendor Dashboard</a></li>

                        <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="/asekogo/admin/dashboard.php">Admin Dashboard</a></li>
                        <?php endif; ?>

                        <li class="nav-item"><a class="nav-link" href="/asekogo/logout.php">Logout</a></li>

                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="index.php?view=login">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?view=register&type=vendor">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
