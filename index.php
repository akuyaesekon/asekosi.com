<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';  // 👈 add this


// Handle logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    logoutUser();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $result = loginUser($pdo, $email, $password);
    
    if ($result === true) {
        // Redirect based on user type
        if ($_SESSION['user_type'] == 'admin') {
            header('Location: admin/dashboard.php');
        } elseif ($_SESSION['user_type'] == 'vendor') {
            header('Location: vendor/dashboard.php');
        } else {
            header('Location: customer/products.php');
        }
        exit();
    } else {
        $loginError = $result;
    }
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $userType = $_POST['user_type'];
    
    $result = registerUser($pdo, $name, $email, $password, $userType);
    
    if ($result === true) {
        $registrationSuccess = "Registration successful. " . 
            ($userType == 'vendor' ? "Your vendor account is pending approval." : "You can now login.");
    } else {
        $registrationError = $result;
    }
}

// Get featured products
$featuredProducts = getProducts($pdo, null, null, null);
$featuredProducts = array_slice($featuredProducts, 0, 8); // Get first 8 products

// Show login/register form or homepage based on query parameter
$view = isset($_GET['view']) ? $_GET['view'] : 'home';
?>

<?php if ($view == 'home'): ?>
    <?php require_once 'includes/header.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero bg-light py-5 mb-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold">Welcome to AsekosiGo</h1>
                    <p class="lead">Kenya's premier multi-vendor marketplace. Shop from hundreds of vendors all in one place.</p>
                    <a href="customer/products.php" class="btn btn-primary btn-lg">Start Shopping</a>
                    <a href="index.php?view=register&type=vendor" class="btn btn-outline-primary btn-lg ms-2">Become a Vendor</a>
                </div>
                <div class="col-lg-6">
  <!-- Lottie animation embed -->
  <lottie-player 
      src="https://assets5.lottiefiles.com/packages/lf20_touohxv0.json"  
      background="transparent"  
      speed="1"  
      style="width: 100%; height: auto;"  
      loop  
      autoplay>
  </lottie-player>
</div>
            </div>
        </div>
    </section>
    
    <!-- Featured Products -->
    <section class="featured-products mb-5">
        <div class="container">
            <h2 class="text-center mb-4">Featured Products</h2>
            <div class="row">
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 product-card">
                            <img src="<?php echo $product['image_url'] ?: 'assets/images/placeholder-product.png'; ?>" 
                                 class="card-img-top" alt="<?php echo $product['name']; ?>" style="height: 200px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $product['name']; ?></h5>
                                <p class="card-text text-muted">Vendor: <?php echo $product['vendor_name']; ?></p>
                                <p class="card-text">KSh <?php echo number_format($product['price'], 2); ?></p>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="customer/products.php?action=add_to_cart&id=<?php echo $product['id']; ?>" 
                                   class="btn btn-primary btn-sm">Add to Cart</a>
                                <a href="customer/products.php?view=product&id=<?php echo $product['id']; ?>" 
                                   class="btn btn-outline-secondary btn-sm">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="customer/products.php" class="btn btn-outline-primary">View All Products</a>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features bg-light py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-truck fs-1 text-primary"></i>
                    </div>
                    <h3>Nationwide Delivery</h3>
                    <p>We deliver to all counties in Kenya at affordable rates.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-shield-check fs-1 text-primary"></i>
                    </div>
                    <h3>Secure Payments</h3>
                    <p>All transactions are secured with M-Pesa integration.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-people fs-1 text-primary"></i>
                    </div>
                    <h3>Multiple Vendors</h3>
                    <p>Shop from hundreds of trusted vendors in one place.</p>
                </div>
            </div>
        </div>
    </section>
    
    <?php require_once 'includes/footer.php'; ?>
    
<?php elseif ($view == 'login'): ?>
    <?php require_once 'includes/header.php'; ?>
    
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center">Login to Your Account</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($loginError)): ?>
                        <div class="alert alert-danger"><?php echo $loginError; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="login" value="1">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="index.php?view=register">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/footer.php'; ?>
    
<?php elseif ($view == 'register'): ?>
    <?php require_once 'includes/header.php'; ?>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center">Create New Account</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($registrationSuccess)): ?>
                        <div class="alert alert-success"><?php echo $registrationSuccess; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($registrationError)): ?>
                        <div class="alert alert-danger"><?php echo $registrationError; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="register" value="1">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="user_type" class="form-label">Account Type</label>
                                <select class="form-select" id="user_type" name="user_type" required>
                                    <option value="customer">Customer</option>
                                    <option value="vendor" <?php echo (isset($_GET['type']) && $_GET['type'] == 'vendor') ? 'selected' : ''; ?>>Vendor</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="index.php?view=login">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
    <?php require_once 'includes/footer.php'; ?>
<?php endif; ?>