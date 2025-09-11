<?php
require_once '../includes/config.php';
requireRole('admin');

// Handle payout action
if (isset($_GET['action']) && $_GET['action'] == 'process_payout' && isset($_GET['id'])) {
    $payoutId = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE vendor_payouts SET status = 'paid', paid_at = NOW() WHERE id = ?");
    $stmt->execute([$payoutId]);
    
    $_SESSION['success'] = "Payout processed successfully";
    header('Location: commissions.php');
    exit();
}

// Get all payouts with vendor information
$stmt = $pdo->query("
    SELECT vp.*, u.name as vendor_name, o.id as order_id, o.total_amount
    FROM vendor_payouts vp
    JOIN users u ON vp.vendor_id = u.id
    JOIN orders o ON vp.order_id = o.id
    ORDER BY vp.created_at DESC
");
$payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$totalPayouts = 0;
$pendingPayouts = 0;
$paidPayouts = 0;

foreach ($payouts as $payout) {
    $totalPayouts += $payout['amount'];
    if ($payout['status'] == 'pending') {
        $pendingPayouts += $payout['amount'];
    } else {
        $paidPayouts += $payout['amount'];
    }
}
?>

<?php include_once 'header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Commission Management</h2>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Payouts</h5>
                <h2>KSh <?php echo number_format($totalPayouts, 2); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Pending Payouts</h5>
                <h2>KSh <?php echo number_format($pendingPayouts, 2); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Paid Payouts</h5>
                <h2>KSh <?php echo number_format($paidPayouts, 2); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Payout ID</th>
                        <th>Vendor</th>
                        <th>Order ID</th>
                        <th>Order Amount</th>
                        <th>Payout Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payouts as $payout): ?>
                        <tr>
                            <td>#<?php echo $payout['id']; ?></td>
                            <td><?php echo $payout['vendor_name']; ?></td>
                            <td>#<?php echo $payout['order_id']; ?></td>
                            <td>KSh <?php echo number_format($payout['total_amount'], 2); ?></td>
                            <td>KSh <?php echo number_format($payout['amount'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $payout['status'] == 'paid' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($payout['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($payout['created_at'])); ?></td>
                            <td>
                                <?php if ($payout['status'] == 'pending'): ?>
                                    <a href="commissions.php?action=process_payout&id=<?php echo $payout['id']; ?>" class="btn btn-sm btn-success">Process Payout</a>
                                <?php else: ?>
                                    <span class="text-muted">Paid on <?php echo date('M j, Y', strtotime($payout['paid_at'])); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>