<?php
require_once '../includes/config.php';

// Check if user is logged in, but don't require it for browsing products
if (!isLoggedIn()) {
    $guestMode = true;
} else {
    $guestMode = false;
    if ($_SESSION['user_type'] != 'customer') {
        $notCustomer = true;
    } else {
        $notCustomer = false;
    }
}

// Handle add to cart
if (isset($_GET['action']) && $_GET['action'] == 'add_to_cart' && isset($_GET['id'])) {
    if ($guestMode) {
        $_SESSION['error'] = "Please login to add products to cart";
        header('Location: ../index.php?view=login');
        exit();
    }

    if ($notCustomer) {
        $_SESSION['error'] = "Only customers can add products to cart";
        header('Location: products.php');
        exit();
    }

    $productId = $_GET['id'];
    $quantity  = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;

    if ($quantity < 1) {
        $_SESSION['error'] = "Quantity must be at least 1";
        header('Location: products.php');
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        if ($product['stock'] < $quantity) {
            $_SESSION['error'] = "Insufficient stock. Only " . $product['stock'] . " items available";
            header('Location: products.php');
            exit();
        }

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }

        $_SESSION['success'] = "Product added to cart successfully";
    } else {
        $_SESSION['error'] = "Product not found or no longer available";
    }

    header('Location: products.php');
    exit();
}

// Get search and filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : null;
$vendor   = isset($_GET['vendor']) ? $_GET['vendor'] : null;
$search   = isset($_GET['search']) ? $_GET['search'] : null;
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;
$sort     = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query
$query = "SELECT p.*, u.name as vendor_name 
          FROM products p 
          JOIN users u ON p.vendor_id = u.id 
          WHERE p.status = 'active' 
          AND u.vendor_status = 'approved'";
$params = [];

if ($category && $category !== '') {
    $query .= " AND p.category = ?";
    $params[] = $category;
}

if ($vendor && $vendor !== '') {
    $query .= " AND p.vendor_id = ?";
    $params[] = $vendor;
}

if ($search && $search !== '') {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.category LIKE ?)";
    $searchTerm = "%$search%";
    $params[]   = $searchTerm;
    $params[]   = $searchTerm;
    $params[]   = $searchTerm;
}

switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'name':
        $query .= " ORDER BY p.name ASC";
        break;
    case 'popular':
        $query = "SELECT p.*, u.name as vendor_name, COUNT(oi.product_id) as sales_count
                  FROM products p 
                  JOIN users u ON p.vendor_id = u.id 
                  LEFT JOIN order_items oi ON p.id = oi.product_id
                  WHERE p.status = 'active' AND u.vendor_status = 'approved' " .
                  ($category ? "AND p.category = ? " : "") .
                  ($vendor ? "AND p.vendor_id = ? " : "") .
                  ($search ? "AND (p.name LIKE ? OR p.description LIKE ? OR p.category LIKE ?) " : "") .
                  "GROUP BY p.id 
                  ORDER BY sales_count DESC, p.created_at DESC";
        break;
    default:
        $query .= " ORDER BY p.created_at DESC";
        break;
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $products        = [];
    $_SESSION['error'] = "Error loading products. Please try again.";
}

// Apply price filters
if ($minPrice !== null && $minPrice > 0) {
    $products = array_filter($products, fn($p) => $p['price'] >= $minPrice);
}

if ($maxPrice !== null && $maxPrice > 0) {
    $products = array_filter($products, fn($p) => $p['price'] <= $maxPrice);
}

// Load categories
try {
    $stmt       = $pdo->query("SELECT DISTINCT category FROM products WHERE status = 'active' ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categories = [];
}

// Load vendors
try {
    $stmt    = $pdo->query("SELECT id, name FROM users WHERE user_type = 'vendor' AND vendor_status = 'approved' ORDER BY name");
    $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $vendors = [];
}

$emptyResults = false;
if (empty($products)) {
    $emptyResults = true;
    if ($category || $vendor || $search || $minPrice || $maxPrice) {
        $emptyMessage = "No products found matching your filters.";
    } else {
        $emptyMessage = "No products available at the moment. Please check back later.";
    }
}
?>

<?php include_once 'header.php'; ?>

<div class="row">
    <!-- Sidebar Filters -->
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header"><h5>Filters</h5></div>
            <div class="card-body">
                <form method="GET">
                    <div class="mb-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search products...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" 
                                    <?php echo $category == $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Vendor</label>
                        <select name="vendor" class="form-select">
                            <option value="">All Vendors</option>
                            <?php foreach ($vendors as $ven): ?>
                                <option value="<?php echo $ven['id']; ?>" 
                                    <?php echo $vendor == $ven['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ven['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Price Range (KSh)</label>
                        <div class="row">
                            <div class="col">
                                <input type="number" name="min_price" class="form-control" 
                                       placeholder="Min" value="<?php echo $minPrice; ?>">
                            </div>
                            <div class="col">
                                <input type="number" name="max_price" class="form-control" 
                                       placeholder="Max" value="<?php echo $maxPrice; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sort By</label>
                        <select name="sort" class="form-select">
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">Clear Filters</a>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5>Marketplace Stats</h5></div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Total Products:</span><span><?php echo count($products); ?></span></div>
                <div class="d-flex justify-content-between"><span>Vendors:</span><span><?php echo count($vendors); ?></span></div>
                <div class="d-flex justify-content-between"><span>Categories:</span><span><?php echo count($categories); ?></span></div>
            </div>
        </div>
    </div>

    <!-- Product Grid -->
    <div class="col-md-9">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Products Marketplace</h2>
            <span class="text-muted"><?php echo count($products); ?> products found</span>
        </div>

        <?php if (!$emptyResults): ?>
            <div class="row">
                <?php foreach ($products as $product): ?>
                    <?php
                    $imagePath = $product['image_url']
                        ? "../assets/images/products/" . basename($product['image_url'])
                        : "../assets/images/placeholder-product.png";
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                 class="card-img-top" style="height:200px;object-fit:cover;" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <div class="card-body">
                                <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="text-muted"><small>Vendor: <?php echo htmlspecialchars($product['vendor_name']); ?></small></p>
                                <p class="fs-4">KSh <?php echo number_format($product['price'],2); ?></p>
                                <p>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category']); ?></span>
                                    <?php if ($product['stock'] > 0): ?>
                                        <span class="badge bg-success">In Stock (<?php echo $product['stock']; ?>)</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="card-footer bg-white">
                                <?php if ($product['stock'] > 0 && !$guestMode && !$notCustomer): ?>
                                    <a href="products.php?action=add_to_cart&id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">Add to Cart</a>
                                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#productModal<?php echo $product['id']; ?>">View Details</button>
                                <?php elseif ($guestMode): ?>
                                    <a href="../index.php?view=login" class="btn btn-primary btn-sm w-100">Login to Purchase</a>
                                <?php elseif ($notCustomer): ?>
                                    <button class="btn btn-secondary btn-sm w-100" disabled>Vendor Account</button>
                                <?php else: ?>
                                    <button class="btn btn-outline-secondary btn-sm w-100" disabled>Out of Stock</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Product Modal -->
                    <div class="modal fade" id="productModal<?php echo $product['id']; ?>">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body row">
                                    <div class="col-md-6">
                                        <img src="<?php echo htmlspecialchars($imagePath); ?>" class="img-fluid rounded">
                                    </div>
                                    <div class="col-md-6">
                                        <h3>KSh <?php echo number_format($product['price'],2); ?></h3>
                                        <p>Vendor: <?php echo htmlspecialchars($product['vendor_name']); ?></p>
                                        <p>Category: <?php echo htmlspecialchars($product['category']); ?></p>
                                        <p>Stock: <?php echo $product['stock']; ?></p>
                                        <h5>Description</h5>
                                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

                                        <?php if ($product['stock'] > 0 && !$guestMode && !$notCustomer): ?>
                                            <form method="GET">
                                                <input type="hidden" name="action" value="add_to_cart">
                                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                <div class="row">
                                                    <div class="col-4">
                                                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="form-control">
                                                    </div>
                                                    <div class="col-8">
                                                        <button type="submit" class="btn btn-primary w-100">Add to Cart</button>
                                                    </div>
                                                </div>
                                            </form>
                                        <?php elseif ($guestMode): ?>
                                            <div class="alert alert-info mt-2">Login to purchase</div>
                                        <?php elseif ($notCustomer): ?>
                                            <div class="alert alert-warning mt-2">Vendor accounts cannot purchase</div>
                                        <?php else: ?>
                                            <div class="alert alert-danger mt-2">Out of Stock</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <h3>No products found</h3>
                <p><?php echo $emptyMessage; ?></p>
                <a href="products.php" class="btn btn-primary">Clear Filters</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
