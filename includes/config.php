<?php
session_start();

// Include database connection
require_once 'database.php';

// Application configuration
define('APP_NAME', 'AsekosiGo');
define('APP_URL', 'https://343e86af0821.ngrok-free.app');

// M-Pesa Configuration
define('MPESA_ENV', 'sandbox');
define('MPESA_CONSUMER_KEY', 'OT5VRZ04wKPc2gHqgCBxc2tGlsB56W12XpI5IlTmduuem14w');
define('MPESA_CONSUMER_SECRET', 'OFvJh5ynUzIrbNh52G9z4pfBZOtDjWQRX3njhgwgCaB3XCG9AEyKTLz0hmigcKrl');
define('MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
define('MPESA_SHORTCODE', '174379');
define('MPESA_CALLBACK_URL', APP_URL .'api/mpesa-callback.php');
define('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline');

// Commission rates
define('ADMIN_COMMISSION', 0.15); // 15% commission
define('DELIVERY_FEE', 100); // KES 100 delivery fee

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit();
    }
}

// Check if user has specific role
function requireRole($role) {
    requireLogin();
    if ($_SESSION['user_type'] != $role) {
        header('Location: ../index.php?error=access_denied');
        exit();
    }
}

// Get current user data
function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>