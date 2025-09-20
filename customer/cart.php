<?php
require_once '../includes/config.php';
requireRole('customer');

// Handle remove action
if (isset($_GET['remove'])) {
    $removeId = (int) $_GET['remove'];

    if (isset($_SESSION['cart'][$removeId])) {
        unset($_SESSION['cart'][$removeId]);
        $_SESSION['success'] = "Item removed from cart.";
    } else {
        $_SESSION['error'] = "Item not found in cart.";
    }

    header("Location: cart.php"); // Refresh page after removal
    exit();
}

// If cart is empty
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    $_SESSION['error'] = "Your cart is empty";
    header('Location: products.php');
    exit();
}

// Get cart items
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

// Default delivery fee (before city selection)
$deliveryFee = 0;
$total = $subtotal + $deliveryFee;

include_once 'header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header"><h5>My Cart</h5></div>
            <div class="card-body table-responsive">

                <!-- Show alerts -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price (KSh)</th>
                            <th>Quantity</th>
                            <th>Total (KSh)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <form action="update_cart.php" method="POST" class="d-flex">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               class="form-control form-control-sm me-2" min="1">
                                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                    </form>
                                </td>
                                <td><?php echo number_format($item['total'], 2); ?></td>
                                <td>
                                    <a href="cart.php?remove=<?php echo $item['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to remove this item?');">
                                        Remove
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Order Summary -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5>Order Summary</h5></div>
            <div class="card-body">
                <div class="mb-2">
                    <label class="form-label">Select City for Delivery:</label>
                    <select id="city" class="form-control">
                        <option value="">-- Select City --</option>
                        <option value="Nairobi">Nairobi</option>
                        <option value="Mombasa">Mombasa</option>
                        <option value="Kisumu">Kisumu</option>
                        <option value="Eldoret">Eldoret</option>
                        <option value="Nakuru">Nakuru</option>
                    </select>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span id="subtotal">KSh <?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Delivery Fee:</span>
                    <span id="delivery">KSh <?php echo number_format($deliveryFee, 2); ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <strong>Total:</strong>
                    <strong id="total">KSh <?php echo number_format($total, 2); ?></strong>
                </div>

                <a href="checkout.php" class="btn btn-success w-100">Proceed to Checkout</a>
            </div>
        </div>
    </div>
</div>

<script>
// Delivery fees (sync with config.php)
const deliveryFees = {
    "Nairobi": 150,
    "Mombasa": 200,
    "Kisumu": 180,
    "Eldoret": 170,
    "Nakuru": 160,
    "Default": 250
};

document.getElementById("city").addEventListener("change", function() {
    let city = this.value;
    let fee = deliveryFees[city] ?? deliveryFees["Default"];

    let subtotal = <?php echo $subtotal; ?>;
    let total = subtotal + fee;

    document.getElementById("delivery").innerText = "KSh " + fee.toFixed(2);
    document.getElementById("total").innerText = "KSh " + total.toFixed(2);
});
</script>

<?php include_once '../includes/footer.php'; ?>
