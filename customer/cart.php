<?php
require_once '../includes/config.php';
requireRole('customer');

// Handle cart actions
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'update' && isset($_GET['id']) && isset($_GET['quantity'])) {
        $productId = $_GET['id'];
        $quantity = intval($_GET['quantity']);
        
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
        
        $_SESSION['success'] = "Cart updated successfully";
    } elseif ($_GET['action'] == 'remove' && isset($_GET['id'])) {
        $productId = $_GET['id'];
        unset($_SESSION['cart'][$productId]);
        $_SESSION['success'] = "Product removed from cart";
    } elseif ($_GET['action'] == 'clear') {
        $_SESSION['cart'] = [];
        $_SESSION['success'] = "Cart cleared";
    }
    
    header('Location: cart.php');
    exit();
}

// Get cart products
$cartItems = [];
$subtotal = 0;

if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
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
}

$deliveryFee = DELIVERY_FEE;
$total = $subtotal + $deliveryFee;
?>

<?php include_once 'header.php'; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Shopping Cart</h5>
                <?php if (count($cartItems) > 0): ?>
                    <a href="cart.php?action=clear" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to clear your cart?')">Clear Cart</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (count($cartItems) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo $item['image_url'] ?: '../assets/images/placeholder-product.png'; ?>" 
                                                     alt="<?php echo $item['name']; ?>" width="60" height="60" class="rounded me-3">
                                                <div>
                                                    <h6 class="mb-0"><?php echo $item['name']; ?></h6>
                                                    <small class="text-muted"><?php echo $item['category']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>KSh <?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <form action="cart.php" method="GET" class="d-inline">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="100" 
                                                       class="form-control form-control-sm" style="width: 70px;" 
                                                       onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td>KSh <?php echo number_format($item['total'], 2); ?></td>
                                        <td>
                                            <a href="cart.php?action=remove&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-cart display-1 text-muted"></i>
                        <h3 class="text-muted">Your cart is empty</h3>
                        <p>Start shopping to add items to your cart.</p>
                        <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Order Summary</h5>
            </div>
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
                
                <?php if (count($cartItems) > 0): ?>
                    <a href="checkout.php" class="btn btn-primary w-100">Proceed to Checkout</a>
                <?php else: ?>
                    <button class="btn btn-primary w-100" disabled>Proceed to Checkout</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>