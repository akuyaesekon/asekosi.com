<?php
require_once '../includes/config.php';
requireRole('admin');

$user = getCurrentUser($pdo);

// Get dashboard statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'customer'");
$totalCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'vendor' AND vendor_status = 'approved'");
$totalVendors = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
$totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
$totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'paid'");
$totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$stmt = $pdo->query("SELECT SUM(commission_amount) as total FROM orders WHERE status = 'paid'");
$totalCommission = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get recent orders
$stmt = $pdo->query("
    SELECT o.*, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.customer_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending vendor approvals
$stmt = $pdo->query("
    SELECT * FROM users 
    WHERE user_type = 'vendor' AND vendor_status = 'pending' 
    ORDER BY created_at DESC
");
$pendingVendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once 'header.php'; ?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Customers</h5>
                <h2><?php echo $totalCustomers; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Total Vendors</h5>
                <h2><?php echo $totalVendors; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Total Products</h5>
                <h2><?php echo $totalProducts; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Total Orders</h5>
                <h2><?php echo $totalOrders; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5>Revenue Overview</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="border rounded p-3 mb-3">
                            <h6>Total Revenue</h6>
                            <h4>KSh <?php echo number_format($totalRevenue, 2); ?></h4>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 mb-3">
                            <h6>Total Commission</h6>
                            <h4>KSh <?php echo number_format($totalCommission, 2); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Pending Vendor Approvals</h5>
                <span class="badge bg-danger"><?php echo count($pendingVendors); ?></span>
            </div>
            <div class="card-body">
                <?php if (count($pendingVendors) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($pendingVendors as $vendor): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo $vendor['name']; ?></h6>
                                    <small class="text-muted"><?php echo $vendor['email']; ?></small>
                                </div>
                                <a href="vendors.php?action=approve&id=<?php echo $vendor['id']; ?>" class="btn btn-sm btn-success">Approve</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No pending vendor approvals.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Recent Orders</h5>
            </div>
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
                            <?php foreach ($recentOrders as $order): ?>
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
                                        <a href="orders.php?view=order&id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>