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

// Delivery fees per city (editable)
$DELIVERY_FEES = [
    "Nairobi" => 150,
    "Mombasa" => 200,
    "Kisumu" => 180,
    "Eldoret" => 170,
    "Nakuru" => 160,
    "Default" => 250 // if city not listed
];

// Function to get delivery fee
function getDeliveryFee($city) {
    global $DELIVERY_FEES;
    $city = trim(ucwords(strtolower($city)));
    return $DELIVERY_FEES[$city] ?? $DELIVERY_FEES["Default"];
}

// User authentication helpers
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['user_type'] != $role) {
        header('Location: ../index.php?error=access_denied');
        exit();
    }
}

function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
