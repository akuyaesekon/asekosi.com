<?php
require_once '../includes/config.php';
requireRole('vendor');

$user = getCurrentUser($pdo);
$vendorId = $user['id'];

// Handle product actions
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'delete' && isset($_GET['id'])) {
        $productId = $_GET['id'];
        
        // Verify product belongs to this vendor
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND vendor_id = ?");
        $stmt->execute([$productId, $vendorId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $_SESSION['success'] = "Product deleted successfully";
        } else {
            $_SESSION['error'] = "Product not found or access denied";
        }
        
        header('Location: products.php');
        exit();
    } elseif ($_GET['action'] == 'toggle_status' && isset($_GET['id'])) {
        $productId = $_GET['id'];
        
        // Verify product belongs to this vendor
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND vendor_id = ?");
        $stmt->execute([$productId, $vendorId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $newStatus = $product['status'] == 'active' ? 'inactive' : 'active';
            $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $productId]);
            $_SESSION['success'] = "Product status updated successfully";
        } else {
            $_SESSION['error'] = "Product not found or access denied";
        }
        
        header('Location: products.php');
        exit();
    }
}

// Handle form submission for adding/editing products
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $stock = $_POST['stock'];
    $status = $_POST['status'];
    
    // Handle image upload
    $imageUrl = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = '../assets/images/products/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imageUrl = 'assets/images/products/' . $fileName;
        }
    }
    
    if (isset($_POST['product_id'])) {
        // Update existing product
        $productId = $_POST['product_id'];
        
        // Verify product belongs to this vendor
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND vendor_id = ?");
        $stmt->execute([$productId, $vendorId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            if ($imageUrl) {
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET name = ?, description = ?, price = ?, category = ?, stock = ?, status = ?, image_url = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $price, $category, $stock, $status, $imageUrl, $productId]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET name = ?, description = ?, price = ?, category = ?, stock = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $price, $category, $stock, $status, $productId]);
            }
            
            $_SESSION['success'] = "Product updated successfully";
        } else {
            $_SESSION['error'] = "Product not found or access denied";
        }
    } else {
        // Add new product
        $stmt = $pdo->prepare("
            INSERT INTO products (vendor_id, name, description, price, category, stock, status, image_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$vendorId, $name, $description, $price, $category, $stock, $status, $imageUrl]);
        $_SESSION['success'] = "Product added successfully";
    }
    
    header('Location: products.php');
    exit();
}

// Get vendor's products
$stmt = $pdo->prepare("SELECT * FROM products WHERE vendor_id = ? ORDER BY created_at DESC");
$stmt->execute([$vendorId]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get product for editing
$editProduct = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $productId = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND vendor_id = ?");
    $stmt->execute([$productId, $vendorId]);
    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$editProduct) {
        $_SESSION['error'] = "Product not found or access denied";
        header('Location: products.php');
        exit();
    }
}
?>

<?php include_once 'header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Products</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
        <i class="bi bi-plus"></i> Add New Product
    </button>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
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
                            <td><?php echo $product['category']; ?></td>
                            <td>KSh <?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['stock']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $product['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
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

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <?php if ($editProduct): ?>
                        <input type="hidden" name="product_id" value="<?php echo $editProduct['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Name *</label>
                            <input type="text" name="name" class="form-control" value="<?php echo $editProduct ? $editProduct['name'] : ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <select name="category" class="form-select" required>
                                <option value="">Select Category</option>
                                <option value="Electronics" <?php echo $editProduct && $editProduct['category'] == 'Electronics' ? 'selected' : ''; ?>>Electronics</option>
                                <option value="Fashion" <?php echo $editProduct && $editProduct['category'] == 'Fashion' ? 'selected' : ''; ?>>Fashion</option>
                                <option value="Home & Kitchen" <?php echo $editProduct && $editProduct['category'] == 'Home & Kitchen' ? 'selected' : ''; ?>>Home & Kitchen</option>
                                <option value="Beauty & Personal Care" <?php echo $editProduct && $editProduct['category'] == 'Beauty & Personal Care' ? 'selected' : ''; ?>>Beauty & Personal Care</option>
                                <option value="Sports & Outdoors" <?php echo $editProduct && $editProduct['category'] == 'Sports & Outdoors' ? 'selected' : ''; ?>>Sports & Outdoors</option>
                                <option value="Books" <?php echo $editProduct && $editProduct['category'] == 'Books' ? 'selected' : ''; ?>>Books</option>
                                <option value="Food & Grocery" <?php echo $editProduct && $editProduct['category'] == 'Food & Grocery' ? 'selected' : ''; ?>>Food & Grocery</option>
                                <option value="Health" <?php echo $editProduct && $editProduct['category'] == 'Health' ? 'selected' : ''; ?>>Laptops</option>
                                <option value="Toys & Games" <?php echo $editProduct && $editProduct['category'] == 'Toys & Games' ? 'selected' : ''; ?>>Toys & Games</option>
                                <option value="Automotive" <?php echo $editProduct && $editProduct['category'] == 'Automotive' ? 'selected' : ''; ?>>Phones</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea name="description" class="form-control" rows="3" required><?php echo $editProduct ? $editProduct['description'] : ''; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price (KSh) *</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?php echo $editProduct ? $editProduct['price'] : ''; ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stock Quantity *</label>
                            <input type="number" name="stock" class="form-control" min="0" value="<?php echo $editProduct ? $editProduct['stock'] : ''; ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="active" <?php echo $editProduct && $editProduct['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $editProduct && $editProduct['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Product Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <?php if ($editProduct && $editProduct['image_url']): ?>
                            <div class="mt-2">
                                <img src="../<?php echo $editProduct['image_url']; ?>" alt="Current image" width="100" class="rounded">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><?php echo $editProduct ? 'Update Product' : 'Add Product'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editProduct): ?>
    <script>
        // Show modal if editing product
        document.addEventListener('DOMContentLoaded', function() {
            var productModal = new bootstrap.Modal(document.getElementById('productModal'));
            productModal.show();
        });
    </script>
<?php endif; ?>

<?php include_once '../includes/footer.php'; ?>