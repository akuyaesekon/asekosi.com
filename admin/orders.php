<?php
require_once '../includes/config.php';
requireRole('admin');

// Handle order status update
if (isset($_GET['action']) && $_GET['action'] == 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $orderId = $_GET['id'];
    $status = $_GET['status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $orderId]);
    
    $_SESSION['success'] = "Order status updated successfully";
    header('Location: orders.php');
    exit();
}

// Get all orders with customer information
$stmt = $pdo->query("
    SELECT o.*, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.customer_id = u.id 
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once 'header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Order Management</h2>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
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
                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="orders.php?view=order&id=<?php echo $order['id']; ?>">View Details</a></li>
                                        <li><h6 class="dropdown-header">Update Status</h6></li>
                                        <li><a class="dropdown-item" href="orders.php?action=update_status&id=<?php echo $order['id']; ?>&status=pending">Mark as Pending</a></li>
                                        <li><a class="dropdown-item" href="orders.php?action=update_status&id=<?php echo $order['id']; ?>&status=paid">Mark as Paid</a></li>
                                        <li><a class="dropdown-item" href="orders.php?action=update_status&id=<?php echo $order['id']; ?>&status=shipped">Mark as Shipped</a></li>
                                        <li><a class="dropdown-item" href="orders.php?action=update_status&id=<?php echo $order['id']; ?>&status=delivered">Mark as Delivered</a></li>
                                        <li><a class="dropdown-item" href="orders.php?action=update_status&id=<?php echo $order['id']; ?>&status=cancelled">Mark as Cancelled</a></li>
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