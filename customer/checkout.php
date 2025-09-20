<?php
require_once '../includes/config.php';
requireRole('customer');

// Check if cart is empty
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    $_SESSION['error'] = "Your cart is empty";
    header('Location: cart.php');
    exit();
}

// Get cart products
$cartItems = [];
$subtotal = 0;

$placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute(array_keys($_SESSION['cart']));
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $product) {
    $quantity = $_SESSION['cart'][$product['id']];
    $product['quantity'] = $quantity;
    $product['total'] = $product['price'] * $quantity;
    $cartItems[] = $product;
    $subtotal += $product['total'];
}

$deliveryFee = 0;
$commission = $subtotal * ADMIN_COMMISSION;
$total = $subtotal;

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $notes = $_POST['notes'] ?? '';

    $deliveryFee = getDeliveryFee($city);
    $total = $subtotal + $deliveryFee;

    if (!preg_match('/^(?:0|254)[1-9]\d{8}$/', $phone)) {
        $_SESSION['error'] = "Please enter a valid Kenyan phone number";
    } else {
        try {
            $pdo->beginTransaction();

            // Create order
            $stmt = $pdo->prepare("
                INSERT INTO orders (customer_id, total_amount, commission_amount, delivery_fee, status, shipping_address, shipping_city, notes) 
                VALUES (?, ?, ?, ?, 'pending', ?, ?, ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $total, $commission, $deliveryFee, $address, $city, $notes]);
            $orderId = $pdo->lastInsertId();

            // Add order items
            foreach ($cartItems as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price, vendor_id) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$orderId, $item['id'], $item['quantity'], $item['price'], $item['vendor_id']]);

                $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['id']]);
            }

            require_once '../includes/mpesa.php';
            $paymentResult = processPayment($phone, $total, $orderId);

            if ($paymentResult['success']) {
                $pdo->commit();
                $_SESSION['cart'] = [];
                $_SESSION['success'] = "Order placed successfully! Please complete the M-Pesa payment on your phone.";
                header('Location: orders.php');
                exit();
            } else {
                $pdo->rollBack();
                $_SESSION['error'] = "Payment initiation failed: " . $paymentResult['error'];
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Order processing failed: " . $e->getMessage();
        }
    }
}

$user = getCurrentUser($pdo);
?>

<?php include_once 'header.php'; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header"><h5>Checkout</h5></div>
            <div class="card-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">M-Pesa Phone Number *</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Delivery Address *</label>
                        <textarea name="address" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">City *</label>
                            <select name="city" class="form-control" required>
                                <option value="">-- Select City --</option>
                                <option value="Nairobi">Nairobi </option>
                                <option value="Mombasa">Mombasa</option>
                                <option value="Kisumu">Kisumu </option>
                                <option value="Eldoret">Eldoret </option>
                                <option value="Nakuru">Nakuru </option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Order Notes</label>
                            <textarea name="notes" class="form-control" rows="1"></textarea>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label" for="terms">I agree to the <a href="#">terms</a></label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100">Complete Order</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5>Order Summary</h5></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span>KSh <?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Delivery Fee:</span>
                    <span>KSh <?php echo number_format($deliveryFee, 2); ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <strong>Total:</strong>
                    <strong>KSh <?php echo number_format($total, 2); ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
