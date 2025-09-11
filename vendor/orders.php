<?php
require_once '../includes/config.php';
requireRole('vendor');

$user = getCurrentUser($pdo);
$vendorId = $user['id'];

// Handle order status update
if (isset($_GET['action']) && $_GET['action'] == 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $orderId = $_GET['id'];
    $status = $_GET['status'];
    
    // Verify order belongs to this vendor
    $stmt = $pdo->prepare("
        SELECT oi.id 
        FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE oi.order_id = ? AND oi.vendor_id = ?
    ");
    $stmt->execute([$orderId, $vendorId]);
    
    if ($stmt->rowCount() > 0) {
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $orderId]);
        $_SESSION['success'] = "Order status updated successfully";
    } else {
        $_SESSION['error'] = "Order not found or access denied";
    }
    
    header('Location: orders.php');
    exit();
}

// Get orders for this vendor
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name, 
           COUNT(oi.id) as item_count,
           SUM(oi.price * oi.quantity) as order_total
    FROM orders o 
    JOIN users u ON o.customer_id = u.id 
    JOIN order_items oi ON o.id = oi.order_id 
    WHERE oi.vendor_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC
");
$stmt->execute([$vendorId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once 'header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Order Management</h2>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo $order['customer_name']; ?></td>
                            <td><?php echo $order['item_count']; ?> item(s)</td>
                            <td>KSh <?php echo number_format($order['order_total'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    switch($order['status']) {
                                        case 'pending': echo 'warning'; break;
                                        case 'paid': echo 'success'; break;
                                        case 'shipped': echo 'info'; break;
                                        case 'delivered': echo 'primary'; break;
                                        case 'cancelled': echo 'danger'; break;
                                        default: echo 'secondary';
                                    }
                                ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="orders.php?view=order&id=<?php echo $order['id']; ?>">View Details</a></li>
                                        <?php if ($order['status'] == 'paid'): ?>
                                            <li><a class="dropdown-item" href="orders.php?action=update_status&id=<?php echo $order['id']; ?>&status=shipped">Mark as Shipped</a></li>
                                        <?php elseif ($order['status'] == 'shipped'): ?>
                                            <li><a class="dropdown-item" href="orders.php?action=update_status&id=<?php echo $order['id']; ?>&status=delivered">Mark as Delivered</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>