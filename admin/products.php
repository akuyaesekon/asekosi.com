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

// Handle add product form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $vendor_id = $_POST['vendor_id'] ?? $_SESSION['user_id'];
    $status = 'active';
    $image_url = '';

    if (isset($_FILES['image']) && $_FILES['image']['tmp_name']) {
        $targetDir = '../assets/images/products/';
        $imageName = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $imageName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image_url = 'assets/images/products/' . $imageName;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock, vendor_id, status, image_url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$name, $category, $price, $stock, $vendor_id, $status, $image_url]);
    $_SESSION['success'] = "Product added successfully";
    header('Location: products.php');
    exit();
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
    <h2 class="fw-bold">Product Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">Add New Product</button>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($products as $product): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm rounded-4 hover-shadow h-100">
                <img src="<?php echo $product['image_url'] ? '../' . $product['image_url'] : '../assets/images/placeholder-product.png'; ?>" class="card-img-top" style="height:200px;object-fit:cover;">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $product['name']; ?></h5>
                    <p class="mb-1"><strong>Vendor:</strong> <?php echo $product['vendor_name']; ?></p>
                    <p class="mb-1"><strong>Category:</strong> <?php echo $product['category']; ?></p>
                    <p class="mb-1"><strong>Price:</strong> KSh <?php echo number_format($product['price'], 2); ?></p>
                    <p class="mb-1"><strong>Stock:</strong> <?php echo $product['stock']; ?></p>
                    <span class="badge bg-<?php echo $product['status'] == 'active' ? 'success' : 'secondary'; ?>">
                        <?php echo ucfirst($product['status']); ?>
                    </span>
                </div>
                <div class="card-footer d-flex gap-2">
                    <a href="../customer/products.php?view=product&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-info flex-fill" target="_blank">View</a>
                    <a href="products.php?action=toggle_status&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-<?php echo $product['status'] == 'active' ? 'warning' : 'success'; ?> flex-fill">
                        <?php echo $product['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                    </a>
                    <a href="products.php?action=delete&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger flex-fill" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-sm">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addProductLabel">Add New Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="add_product" value="1">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control rounded-3" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control rounded-3" name="category" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Price (KSh)</label>
                            <input type="number" step="0.01" class="form-control rounded-3" name="price" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stock</label>
                            <input type="number" class="form-control rounded-3" name="stock" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Product Image</label>
                            <input type="file" class="form-control rounded-3" name="image" accept="image/*">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Add Product</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 25px rgba(0,0,0,0.1);
    transition: 0.3s;
}
.card-footer a {
    font-size: 0.875rem;
}
</style>

<?php include_once '../includes/footer.php'; ?>
