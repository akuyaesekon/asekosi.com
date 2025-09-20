<?php
require_once '../includes/config.php';

// Guest & customer logic
$guestMode = !isLoggedIn();
$notCustomer = !$guestMode && $_SESSION['user_type'] != 'customer';

// Add to cart
if (isset($_GET['action']) && $_GET['action'] == 'add_to_cart' && isset($_GET['id'])) {
    if ($guestMode) { $_SESSION['error'] = "Please login to add products to cart"; header('Location: ../index.php?view=login'); exit(); }
    if ($notCustomer) { $_SESSION['error'] = "Only customers can add products to cart"; header('Location: products.php'); exit(); }
    $productId = $_GET['id'];
    $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
    if ($quantity < 1) { $_SESSION['error'] = "Quantity must be at least 1"; header('Location: products.php'); exit(); }
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=? AND status='active'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product) {
        if ($product['stock'] < $quantity) { $_SESSION['error'] = "Insufficient stock"; header('Location: products.php'); exit(); }
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $quantity;
        $_SESSION['success'] = "Product added to cart successfully";
    } else { $_SESSION['error'] = "Product not found"; }
    header('Location: products.php'); exit();
}

// Filters
$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;
$sort = $_GET['sort'] ?? 'newest';

// Build query
$query = "SELECT * FROM products WHERE status='active'";
$params = [];
if ($category) { $query .= " AND category=?"; $params[] = $category; }
if ($search) { $query .= " AND (name LIKE ? OR description LIKE ? OR category LIKE ?)"; $term="%$search%"; $params[]=$term;$params[]=$term;$params[]=$term; }
switch($sort){
    case 'price_low': $query.=" ORDER BY price ASC"; break;
    case 'price_high': $query.=" ORDER BY price DESC"; break;
    case 'name': $query.=" ORDER BY name ASC"; break;
    case 'popular':
        $query = "SELECT p.*, COUNT(oi.product_id) as sales_count
                  FROM products p 
                  LEFT JOIN order_items oi ON p.id=oi.product_id
                  WHERE p.status='active' " .
                  ($category?"AND p.category=? ":"") .
                  ($search?"AND (p.name LIKE ? OR p.description LIKE ? OR p.category LIKE ?) ":"") .
                  "GROUP BY p.id ORDER BY sales_count DESC, p.created_at DESC";
        break;
    default: $query.=" ORDER BY created_at DESC"; break;
}
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Price filters
if ($minPrice !== null) $products = array_filter($products, fn($p)=>$p['price']>=$minPrice);
if ($maxPrice !== null) $products = array_filter($products, fn($p)=>$p['price']<=$maxPrice);

// Categories
$categories = $pdo->query("SELECT DISTINCT category FROM products WHERE status='active' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Empty check
$emptyResults = empty($products);
$emptyMessage = $emptyResults ? ($category||$search||$minPrice||$maxPrice ? "No products found matching your filters." : "No products available. Check back later.") : "";
?>

<?php include_once 'header.php'; ?>

<style>
.hover-shadow:hover { transform:translateY(-5px); box-shadow:0 10px 20px rgba(0,0,0,0.15)!important; transition:0.3s; }
.card-footer a:hover { text-decoration:none; }
</style>

<div class="row">
    <!-- Sidebar -->
    <div class="col-md-3 mb-4">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#filterBody" style="cursor:pointer;">
                <h5 class="mb-0">Filters</h5><i class="bi bi-chevron-down"></i>
            </div>
            <div class="collapse show" id="filterBody">
                <div class="card-body">
                    <form method="GET">
                        <div class="mb-3"><input type="text" name="search" class="form-control" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>"></div>
                        <div class="mb-3"><select name="category" class="form-select"><option value="">All Categories</option><?php foreach($categories as $cat): ?><option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category==$cat?'selected':'';?>><?php echo htmlspecialchars($cat); ?></option><?php endforeach;?></select></div>
                        <div class="mb-3 row"><div class="col"><input type="number" name="min_price" class="form-control" placeholder="Min" value="<?php echo $minPrice;?>"></div><div class="col"><input type="number" name="max_price" class="form-control" placeholder="Max" value="<?php echo $maxPrice;?>"></div></div>
                        <div class="mb-3"><select name="sort" class="form-select"><option value="newest" <?php echo $sort=='newest'?'selected':'';?>>Newest First</option><option value="price_low" <?php echo $sort=='price_low'?'selected':'';?>>Price Low-High</option><option value="price_high" <?php echo $sort=='price_high'?'selected':'';?>>Price High-Low</option><option value="name" <?php echo $sort=='name'?'selected':'';?>>Name A-Z</option><option value="popular" <?php echo $sort=='popular'?'selected':'';?>>Most Popular</option></select></div>
                        <button class="btn btn-primary w-100">Apply Filters</button>
                        <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">Clear Filters</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5>Marketplace Stats</h5></div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Total Products:</span><span><?php echo count($products);?></span></div>
                <div class="d-flex justify-content-between"><span>Categories:</span><span><?php echo count($categories);?></span></div>
            </div>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="col-md-9">
        <?php if(isset($_SESSION['success'])): ?><div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div><?php endif;?>
        <?php if(isset($_SESSION['error'])): ?><div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div><?php endif;?>

        <div class="d-flex justify-content-between align-items-center mb-3"><h2>Marketplace</h2><span class="text-muted"><?php echo count($products);?> products</span></div>

        <?php if(!$emptyResults): ?>
        <div class="row g-3">
            <?php foreach($products as $product):
                $img = $product['image_url'] ? "../assets/images/products/".basename($product['image_url']) : "../assets/images/placeholder-product.png"; ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 hover-shadow">
                        <img src="<?php echo $img;?>" class="card-img-top rounded-top-4" style="height:220px;object-fit:cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="fw-bold"><?php echo htmlspecialchars($product['name']);?></h5>
                            <p class="text-success fs-5 fw-semibold">KSh <?php echo number_format($product['price'],2);?></p>
                            <div class="mb-2">
                                <span class="badge bg-info"><?php echo htmlspecialchars($product['category']);?></span>
                                <?php if($product['stock']>0):?><span class="badge bg-success"><i class="bi bi-check-circle"></i> In Stock (<?php echo $product['stock'];?>)</span>
                                <?php else: ?><span class="badge bg-danger"><i class="bi bi-x-circle"></i> Out of Stock</span><?php endif;?>
                            </div>
                            <div class="mt-auto d-flex gap-2">
                                <?php if($product['stock']>0 && !$guestMode && !$notCustomer): ?>
                                    <a href="products.php?action=add_to_cart&id=<?php echo $product['id'];?>" class="btn btn-primary flex-fill">Add to Cart</a>
                                    <button class="btn btn-outline-secondary flex-fill" data-bs-toggle="modal" data-bs-target="#productModal<?php echo $product['id'];?>">View</button>
                                <?php elseif($guestMode): ?>
                                    <a href="../index.php?view=login" class="btn btn-primary w-100">Login to Buy</a>
                                <?php elseif($notCustomer): ?>
                                    <button class="btn btn-secondary w-100" disabled>Vendor Account</button>
                                <?php else: ?>
                                    <button class="btn btn-outline-secondary w-100" disabled>Out of Stock</button>
                                <?php endif;?>
                            </div>
                        </div>
                    </div>

                    <!-- Product Modal -->
                    <div class="modal fade" id="productModal<?php echo $product['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body row">
                                    <div class="col-md-6">
                                        <img src="<?php echo $img; ?>" class="img-fluid rounded" style="object-fit:contain;">
                                    </div>
                                    <div class="col-md-6">
                                        <h3 class="text-success">KSh <?php echo number_format($product['price'],2); ?></h3>
                                        <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
                                        <p><strong>Stock:</strong> <?php echo $product['stock']; ?></p>
                                        <h5>Description</h5>
                                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

                                        <?php if($product['stock']>0 && !$guestMode && !$notCustomer): ?>
                                            <form method="GET">
                                                <input type="hidden" name="action" value="add_to_cart">
                                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                <div class="row g-2 mt-2">
                                                    <div class="col-4">
                                                        <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?php echo $product['stock']; ?>">
                                                    </div>
                                                    <div class="col-8">
                                                        <button type="submit" class="btn btn-primary w-100">Add to Cart</button>
                                                    </div>
                                                </div>
                                            </form>
                                        <?php elseif($guestMode): ?>
                                            <div class="alert alert-info mt-2">Login to purchase</div>
                                        <?php elseif($notCustomer): ?>
                                            <div class="alert alert-warning mt-2">Vendor accounts cannot purchase</div>
                                        <?php else: ?>
                                            <div class="alert alert-danger mt-2">Out of Stock</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Modal -->

                </div>
            <?php endforeach;?>
        </div>
        <?php else: ?>
            <div class="text-center py-5">
                <h3>No products found</h3>
                <p><?php echo $emptyMessage;?></p>
                <a href="products.php" class="btn btn-primary">Clear Filters</a>
            </div>
        <?php endif;?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
