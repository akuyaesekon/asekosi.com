<?php
require_once '../includes/config.php';
requireRole('vendor');

$user = getCurrentUser($pdo);
$vendorId = $user['id'];

// Get date range filters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get earnings summary
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT vp.order_id) as total_orders,
        SUM(vp.amount) as total_earnings,
        SUM(CASE WHEN vp.status = 'paid' THEN vp.amount ELSE 0 END) as paid_earnings,
        SUM(CASE WHEN vp.status = 'pending' THEN vp.amount ELSE 0 END) as pending_earnings
    FROM vendor_payouts vp
    JOIN orders o ON vp.order_id = o.id
    WHERE vp.vendor_id = ? 
    AND o.created_at BETWEEN ? AND ? + INTERVAL 1 DAY
");
$stmt->execute([$vendorId, $startDate, $endDate]);
$earningsSummary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get earnings by day for chart
$stmt = $pdo->prepare("
    SELECT 
        DATE(o.created_at) as date,
        COUNT(DISTINCT vp.order_id) as order_count,
        SUM(vp.amount) as daily_earnings
    FROM vendor_payouts vp
    JOIN orders o ON vp.order_id = o.id
    WHERE vp.vendor_id = ? 
    AND o.created_at BETWEEN ? AND ? + INTERVAL 1 DAY
    GROUP BY DATE(o.created_at)
    ORDER BY date
");
$stmt->execute([$vendorId, $startDate, $endDate]);
$dailyEarnings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get payout history
$stmt = $pdo->prepare("
    SELECT vp.*, o.id as order_id, o.total_amount, o.created_at as order_date
    FROM vendor_payouts vp
    JOIN orders o ON vp.order_id = o.id
    WHERE vp.vendor_id = ? 
    ORDER BY vp.created_at DESC
    LIMIT 10
");
$stmt->execute([$vendorId]);
$payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once 'header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Earnings & Payouts</h2>
    <div>
        <a href="earnings.php?export=csv&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-success">
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
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Orders</h5>
                <h2><?php echo $earningsSummary['total_orders'] ?? 0; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Total Earnings</h5>
                <h2>KSh <?php echo number_format($earningsSummary['total_earnings'] ?? 0, 2); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Pending Payouts</h5>
                <h2>KSh <?php echo number_format($earningsSummary['pending_earnings'] ?? 0, 2); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Earnings Chart -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Earnings Trend</h5>
            </div>
            <div class="card-body">
                <canvas id="earningsChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <!-- Payout Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Payout Status</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Paid Out:</span>
                    <span>KSh <?php echo number_format($earningsSummary['paid_earnings'] ?? 0, 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Pending Payout:</span>
                    <span>KSh <?php echo number_format($earningsSummary['pending_earnings'] ?? 0, 2); ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <strong>Total Earnings:</strong>
                    <strong>KSh <?php echo number_format($earningsSummary['total_earnings'] ?? 0, 2); ?></strong>
                </div>
                
                <div class="alert alert-info">
                    <h6>Payout Schedule</h6>
                    <p class="small mb-0">Payouts are processed every Friday for orders completed the previous week.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payout History -->
<div class="card">
    <div class="card-header">
        <h5>Recent Payouts</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Paid Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payouts as $payout): ?>
                        <tr>
                            <td>#<?php echo $payout['order_id']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($payout['order_date'])); ?></td>
                            <td>KSh <?php echo number_format($payout['amount'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $payout['status'] == 'paid' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($payout['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $payout['paid_at'] ? date('M j, Y', strtotime($payout['paid_at'])) : '—'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Earnings Chart
document.addEventListener('DOMContentLoaded', function() {
    const earningsCtx = document.getElementById('earningsChart');
    if (earningsCtx) {
        const earningsChart = new Chart(earningsCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . date('M j', strtotime($item['date'])) . "'"; }, $dailyEarnings)); ?>],
                datasets: [{
                    label: 'Daily Earnings',
                    data: [<?php echo implode(',', array_column($dailyEarnings, 'daily_earnings')); ?>],
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
                        text: 'Earnings Trend'
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
    }
});
</script>

<?php include_once '../includes/footer.php'; ?>