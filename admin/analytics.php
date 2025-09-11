<?php
require_once '../includes/config.php';
requireRole('admin');

// Get date range filters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get sales summary
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        SUM(commission_amount) as total_commission,
        AVG(total_amount) as avg_order_value
    FROM orders 
    WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY
    AND status = 'paid'
");
$stmt->execute([$startDate, $endDate]);
$salesSummary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get sales by day for chart
$stmt = $pdo->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as order_count,
        SUM(total_amount) as daily_revenue
    FROM orders 
    WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY
    AND status = 'paid'
    GROUP BY DATE(created_at)
    ORDER BY date
");
$stmt->execute([$startDate, $endDate]);
$dailySales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top products
$stmt = $pdo->prepare("
    SELECT 
        p.name,
        p.category,
        u.name as vendor_name,
        COUNT(oi.id) as units_sold,
        SUM(oi.price * oi.quantity) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN users u ON p.vendor_id = u.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.created_at BETWEEN ? AND ? + INTERVAL 1 DAY
    AND o.status = 'paid'
    GROUP BY oi.product_id
    ORDER BY revenue DESC
    LIMIT 10
");
$stmt->execute([$startDate, $endDate]);
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top vendors
$stmt = $pdo->prepare("
    SELECT 
        u.name as vendor_name,
        COUNT(DISTINCT oi.order_id) as order_count,
        SUM(oi.price * oi.quantity) as total_sales,
        SUM(oi.price * oi.quantity * (1 - ?)) as vendor_earnings
    FROM order_items oi
    JOIN users u ON oi.vendor_id = u.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.created_at BETWEEN ? AND ? + INTERVAL 1 DAY
    AND o.status = 'paid'
    GROUP BY oi.vendor_id
    ORDER BY total_sales DESC
    LIMIT 10
");
$stmt->execute([ADMIN_COMMISSION, $startDate, $endDate]);
$topVendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get sales by category
$stmt = $pdo->prepare("
    SELECT 
        p.category,
        COUNT(oi.id) as units_sold,
        SUM(oi.price * oi.quantity) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.created_at BETWEEN ? AND ? + INTERVAL 1 DAY
    AND o.status = 'paid'
    GROUP BY p.category
    ORDER BY revenue DESC
");
$stmt->execute([$startDate, $endDate]);
$salesByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once 'header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Sales Analytics & Reports</h2>
    <div>
        <a href="analytics.php?export=csv&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-success">
            <i class="bi bi-download"></i> Export CSV
        </a>
    </div>
</div>

<!-- Date Range Filter -->
<div class="card mb-4">
    <div class="card-header">
        <h5>Filter Report</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Orders</h5>
                <h2><?php echo $salesSummary['total_orders'] ?? 0; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Total Revenue</h5>
                <h2>KSh <?php echo number_format($salesSummary['total_revenue'] ?? 0, 2); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Total Commission</h5>
                <h2>KSh <?php echo number_format($salesSummary['total_commission'] ?? 0, 2); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Avg Order Value</h5>
                <h2>KSh <?php echo number_format($salesSummary['avg_order_value'] ?? 0, 2); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Sales Chart -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Daily Sales Trend</h5>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <!-- Sales by Category -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Sales by Category</h5>
            </div>
            <div class="card-body">
                <canvas id="categoryChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <!-- Top Products -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Top Selling Products</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Vendor</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $product): ?>
                                <tr>
                                    <td><?php echo $product['name']; ?></td>
                                    <td><?php echo $product['category']; ?></td>
                                    <td><?php echo $product['vendor_name']; ?></td>
                                    <td><?php echo $product['units_sold']; ?></td>
                                    <td>KSh <?php echo number_format($product['revenue'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <!-- Top Vendors -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Top Performing Vendors</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Vendor</th>
                                <th>Orders</th>
                                <th>Total Sales</th>
                                <th>Earnings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topVendors as $vendor): ?>
                                <tr>
                                    <td><?php echo $vendor['vendor_name']; ?></td>
                                    <td><?php echo $vendor['order_count']; ?></td>
                                    <td>KSh <?php echo number_format($vendor['total_sales'], 2); ?></td>
                                    <td>KSh <?php echo number_format($vendor['vendor_earnings'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: [<?php echo implode(',', array_map(function($item) { return "'" . date('M j', strtotime($item['date'])) . "'"; }, $dailySales)); ?>],
        datasets: [{
            label: 'Daily Revenue',
            data: [<?php echo implode(',', array_column($dailySales, 'daily_revenue')); ?>],
            borderColor: 'rgb(54, 162, 235)',
            backgroundColor: 'rgba(54, 162, 235, 0.1)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Revenue Trend'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'KSh ' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Category Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
const categoryChart = new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['category'] . "'"; }, $salesByCategory)); ?>],
        datasets: [{
            data: [<?php echo implode(',', array_column($salesByCategory, 'revenue')); ?>],
            backgroundColor: [
                'rgb(255, 99, 132)',
                'rgb(54, 162, 235)',
                'rgb(255, 205, 86)',
                'rgb(75, 192, 192)',
                'rgb(153, 102, 255)',
                'rgb(255, 159, 64)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            },
            title: {
                display: true,
                text: 'Sales by Category'
            }
        }
    }
});
</script>

<?php include_once '../includes/footer.php'; ?>