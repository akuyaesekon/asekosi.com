<?php
require_once '../includes/config.php';
require_once '../includes/mpesa.php';

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    file_put_contents('../logs/mpesa_callback.log', date('Y-m-d H:i:s') . " - DB Error: " . $e->getMessage() . "\n", FILE_APPEND);
    exit;
}

// Log raw callback data
$callbackData = file_get_contents('php://input');
file_put_contents('../logs/mpesa_callback.log', date('Y-m-d H:i:s') . " - RAW: " . $callbackData . "\n", FILE_APPEND);

// Process callback
$mpesa = new Mpesa();
$result = $mpesa->handleCallback($callbackData);

if ($result && $result['result_code'] == 0 && !empty($result['order_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'paid', mpesa_receipt = ? WHERE id = ?");
        $stmt->execute([$result['mpesa_receipt'], $result['order_id']]);

        file_put_contents('../logs/mpesa_callback.log', date('Y-m-d H:i:s') . " - SUCCESS: Order {$result['order_id']} paid with {$result['mpesa_receipt']}\n", FILE_APPEND);

        header('Content-Type: application/json');
        echo json_encode([
            'ResultCode' => 0,
            'ResultDesc' => 'Callback processed successfully'
        ]);
        exit;
    } catch (PDOException $e) {
        file_put_contents('../logs/mpesa_callback.log', date('Y-m-d H:i:s') . " - DB Update Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// Failure response
file_put_contents('../logs/mpesa_callback.log', date('Y-m-d H:i:s') . " - FAILURE: " . json_encode($result) . "\n", FILE_APPEND);

header('Content-Type: application/json');
echo json_encode([
    'ResultCode' => 1,
    'ResultDesc' => 'Callback processing failed'
]);
