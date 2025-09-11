<?php
require_once '../includes/config.php';
requireRole('admin');

// Handle product actions
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'delete' && isset($_GET['id'])) {
        $productId = $_GET['id'];
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        
        $_SESSION['success'] = "Product deleted successfully";
        header('Location: products.php');
        exit();
    } elseif ($_GET['action'] == 'toggle_status' && isset($_GET['id'])) {
        $productId = $_GET['id'];
        $stmt = $pdo->prepare("SELECT status FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $newStatus = $product['status'] == 'active' ? 'inactive' : 'active';
            $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $productId]);
            $_SESSION['success'] = "Product status updated successfully";
        }
        
        header('Location: products.php');
        exit();
    }
}

// Get all products with vendor information
$stmt = $pdo->query("
    SELECT p.*, u.name as vendor_name 
    FROM products p 
    JOIN users u ON p.vendor_id = u.id 
    ORDER BY p.created_at DESC
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once 'header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Product Management</h2>
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
                        <th>Image</th>
                        <th>Name</th>
                        <th>Vendor</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <img src="<?php echo $product['image_url'] ? '../' . $product['image_url'] : '../assets/images/placeholder-product.png'; ?>" 
                                     alt="<?php echo $product['name']; ?>" width="50" height="50" class="rounded">
                            </td>
                            <td><?php echo $product['name']; ?></td>
                            <td><?php echo $product['vendor_name']; ?></td>
                            <td><?php echo $product['category']; ?></td>
                            <td>KSh <?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['stock']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $product['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="../customer/products.php?view=product&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-info" target="_blank">View</a>
                                <a href="products.php?action=toggle_status&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-<?php echo $product['status'] == 'active' ? 'warning' : 'success'; ?>">
                                    <?php echo $product['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                                </a>
                                <a href="products.php?action=delete&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>