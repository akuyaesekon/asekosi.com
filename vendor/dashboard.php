<?php
require_once '../includes/config.php';
requireRole('vendor');

$user = getCurrentUser($pdo);
$vendorId = $user['id'];

// Get vendor statistics
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM products 
    WHERE vendor_id = ? AND status = 'active'
");
$stmt->execute([$vendorId]);
$totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT oi.order_id) as total 
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.id 
    WHERE oi.vendor_id = ? AND o.status = 'paid'
");
$stmt->execute([$vendorId]);
$totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("
    SELECT SUM(oi.price * oi.quantity) as total 
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.id 
    WHERE oi.vendor_id = ? AND o.status = 'paid'
");
$stmt->execute([$vendorId]);
$totalSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$stmt = $pdo->prepare("
    SELECT SUM(amount) as total 
    FROM vendor_payouts 
    WHERE vendor_id = ? AND status = 'paid'
");
$stmt->execute([$vendorId]);
$totalEarnings = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get recent orders
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.customer_id = u.id 
    JOIN order_items oi ON o.id = oi.order_id 
    WHERE oi.vendor_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$stmt->execute([$vendorId]);
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get low stock products
$stmt = $pdo->prepare("
    SELECT * FROM products 
    WHERE vendor_id = ? AND stock < 10 AND status = 'active' 
    ORDER BY stock ASC 
    LIMIT 5
");
$stmt->execute([$vendorId]);
$lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once 'header.php'; ?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Products</h5>
                <h2><?php echo $totalProducts; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Orders</h5>
                <h2><?php echo $totalOrders; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Total Sales</h5>
                <h2>KSh <?php echo number_format($totalSales, 2); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Total Earnings</h5>
                <h2>KSh <?php echo number_format($totalEarnings, 2); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Recent Orders -->
        <div class="card mb-4">
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
    
    <div class="col-md-4">
        <!-- Low Stock Alert -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Low Stock Alert</h5>
            </div>
            <div class="card-body">
                <?php if (count($lowStockProducts) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($lowStockProducts as $product): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo $product['name']; ?></h6>
                                    <small class="text-muted">Stock: <?php echo $product['stock']; ?></small>
                                </div>
                                <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">Restock</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">All products have sufficient stock.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="products.php?action=add" class="btn btn-primary">Add New Product</a>
                    <a href="products.php" class="btn btn-outline-primary">Manage Products</a>
                    <a href="orders.php" class="btn btn-outline-primary">View All Orders</a>
                    <a href="earnings.php" class="btn btn-outline-primary">View Earnings</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>