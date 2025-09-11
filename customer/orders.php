<?php
require_once '../includes/config.php';
requireRole('customer');

$user = getCurrentUser($pdo);

// Get orders
$stmt = $pdo->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o 
    WHERE o.customer_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once 'header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Orders</h2>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<?php if (count($orders) > 0): ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                <td><?php echo $order['item_count']; ?> item(s)</td>
                                <td>KSh <?php echo number_format($order['total_amount'], 2); ?></td>
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
                                <td>
                                    <a href="orders.php?view=order&id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="text-center py-5">
        <i class="bi bi-cart display-1 text-muted"></i>
        <h3 class="text-muted">No orders yet</h3>
        <p>You haven't placed any orders yet.</p>
        <a href="products.php" class="btn btn-primary">Start Shopping</a>
    </div>
<?php endif; ?>

<?php include_once '../includes/footer.php'; ?>