<?php
require_once '../includes/config.php';
requireRole('admin');

// Handle vendor approval
if (isset($_GET['action']) && $_GET['action'] == 'approve' && isset($_GET['id'])) {
    $vendorId = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE users SET vendor_status = 'approved' WHERE id = ? AND user_type = 'vendor'");
    $stmt->execute([$vendorId]);
    
    $_SESSION['success'] = "Vendor approved successfully";
    header('Location: vendors.php');
    exit();
}

// Handle vendor rejection
if (isset($_GET['action']) && $_GET['action'] == 'reject' && isset($_GET['id'])) {
    $vendorId = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE users SET vendor_status = 'rejected' WHERE id = ? AND user_type = 'vendor'");
    $stmt->execute([$vendorId]);
    
    $_SESSION['success'] = "Vendor rejected successfully";
    header('Location: vendors.php');
    exit();
}

// Get all vendors
$stmt = $pdo->query("SELECT * FROM users WHERE user_type = 'vendor' ORDER BY created_at DESC");
$vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once 'header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Vendor Management</h2>
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
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Registration Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendors as $vendor): ?>
                        <tr>
                            <td><?php echo $vendor['id']; ?></td>
                            <td><?php echo $vendor['name']; ?></td>
                            <td><?php echo $vendor['email']; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    switch($vendor['vendor_status']) {
                                        case 'approved': echo 'success'; break;
                                        case 'pending': echo 'warning'; break;
                                        case 'rejected': echo 'danger'; break;
                                        default: echo 'secondary';
                                    }
                                ?>">
                                    <?php echo ucfirst($vendor['vendor_status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($vendor['created_at'])); ?></td>
                            <td>
                                <?php if ($vendor['vendor_status'] == 'pending'): ?>
                                    <a href="vendors.php?action=approve&id=<?php echo $vendor['id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                    <a href="vendors.php?action=reject&id=<?php echo $vendor['id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                                <?php else: ?>
                                    <a href="vendors.php?action=view&id=<?php echo $vendor['id']; ?>" class="btn btn-sm btn-primary">View</a>
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