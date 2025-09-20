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
    <h2 class="fw-bold">Order Management</h2>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($orders as $order): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 rounded-4 h-100 hover-shadow">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Order #<?php echo $order['id']; ?></span>
                    <span class="badge bg-<?php
                        switch($order['status']) {
                            case 'pending': echo 'warning'; break;
                            case 'paid': echo 'success'; break;
                            case 'shipped': echo 'info'; break;
                            case 'delivered': echo 'primary'; break;
                            case 'cancelled': echo 'danger'; break;
                            default: echo 'secondary';
                        }
                    ?>"><?php echo ucfirst($order['status']); ?></span>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Customer:</strong> <?php echo $order['customer_name']; ?></p>
                    <p class="mb-1"><strong>Amount:</strong> KSh <?php echo number_format($order['total_amount'], 2); ?></p>
                    <p class="mb-1"><strong>Date:</strong> <?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                    <a href="orders.php?view=order&id=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm flex-fill me-2">View Details</a>
                    <div class="dropdown flex-fill">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                            Update Status
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="orders.php?action=update_status&id=<?php echo $order['id']; ?>&status=pending">Mark as Pending</a></li>
                            <li><a class="dropdown-item" href="orders.php?action=update_status&id=<?php echo $order['id']; ?>&status=paid">Mark as Paid</a></li>
                            <li><a class="dropdown-item" href="orders.php?action=update_status&id=<?php echo $order['id']; ?>&status=shipped">Mark as Shipped</a></li>
                            <li><a class="dropdown-item" href="orders.php?action=update_status&id=<?php echo $order['id']; ?>&status=delivered">Mark as Delivered</a></li>
                            <li><a class="dropdown-item" href="orders.php?action=update_status&id=<?php echo $order['id']; ?>&status=cancelled">Mark as Cancelled</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 25px rgba(0,0,0,0.1);
    transition: 0.3s;
}
.card-footer a, .card-footer button {
    font-size: 0.875rem;
}
</style>

<?php include_once '../includes/footer.php'; ?>
