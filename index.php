<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';  

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Manual logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logoutUser();
    header('Location: index.php');
    exit();
}

// Auto-logout if trying to access vendor registration
if (isset($_GET['view'], $_GET['type']) && $_GET['view'] === 'register' && $_GET['type'] === 'vendor') {
    if (isset($_SESSION['user_id'])) {
        logoutUser();
        header('Location: index.php?view=register&type=vendor');
        exit();
    }
}

// --------------------------
// LOGIN HANDLING
// --------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $result = loginUser($pdo, $email, $password);
    
    if ($result === true) {
        // Redirect based on user type
        switch ($_SESSION['user_type']) {
            case 'admin':
                header('Location: admin/dashboard.php');
                break;
            case 'vendor':
                header('Location: vendor/dashboard.php');
                break;
            default:
                header('Location: customer/products.php');
        }
        exit();
    } else {
        $loginError = $result;
    }
}

// --------------------------
// REGISTRATION HANDLING
// --------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $userType = $_POST['user_type'];
    
    $result = registerUser($pdo, $name, $email, $password, $userType);
    
    if ($result === true) {
        $registrationSuccess = "Registration successful. " . 
            ($userType === 'vendor' ? "Your vendor account is pending approval." : "You can now login.");
    } else {
        $registrationError = $result;
    }
}

// --------------------------
// FETCH FEATURED PRODUCTS
// --------------------------
$featuredProducts = getProducts($pdo, null, null, null);
$featuredProducts = array_slice($featuredProducts, 0, 8); // First 8 products

// --------------------------
// VIEW LOGIC
// --------------------------
$view = isset($_GET['view']) ? $_GET['view'] : 'home';
?>

<?php if ($view === 'home'): ?>
    <?php require_once 'includes/header.php'; ?>

    <!-- Hero Section with Lottie -->
    <section class="hero bg-light py-5 mb-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-1 fw-bold">Welcome to AsekosiGo</h1>
                    <p class="lead">Kenya's premier local marketplace. Shop from hundreds of vendors all in one place.</p>
                    <a href="index.php?view=login" class="btn btn-primary btn-lg">Start Shopping</a>
                    <a href="index.php?view=register&type=vendor" class="btn btn-outline-primary btn-lg ms-2">Become a Vendor</a>
                </div>
                <div class="col-lg-6 text-center">
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

    <?php require_once 'includes/footer.php'; ?>

<?php elseif ($view === 'login'): ?>
    <?php require_once 'includes/header.php'; ?>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

    <div class="container mt-5">
        <div class="row justify-content-center align-items-center">
            <!-- Login Card -->
            <div class="col-md-6 col-lg-5 order-2 order-md-1">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-primary text-white text-center py-4 rounded-top-4">
                        <h2 class="fw-bold display-6 mb-1" style="letter-spacing: 1px;">Welcome to AsekosiGO!</h2>
                        <p class="mb-0 small text-light">Login to your account and start your shopping adventure</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($loginError)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $loginError; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="login" value="1">
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <input type="email" class="form-control rounded-3" id="email" name="email" placeholder="you@example.com" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold">Password</label>
                                <input type="password" class="form-control rounded-3" id="password" name="password" placeholder="Enter your password" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 rounded-3">Login</button>
                        </form>

                        <div class="text-center mt-3">
                            <p class="mb-0 small">Don't have an account? <a href="index.php?view=register" class="text-primary fw-semibold">Register here</a></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lottie Animation Column -->
            <div class="col-md-6 col-lg-5 text-center order-1 order-md-2 mb-4 mb-md-0">
                <lottie-player 
                    src="https://assets5.lottiefiles.com/packages/lf20_jcikwtux.json"  
                    background="transparent"  
                    speed="1"  
                    loop  
                    autoplay  
                    style="width: 100%; max-width: 400px;">
                </lottie-player>
            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>

<?php elseif ($view === 'register'): ?>
    <?php require_once 'includes/header.php'; ?>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

    <div class="container mt-5">
        <div class="row justify-content-center align-items-center">
            <!-- Registration Card -->
            <div class="col-md-6 col-lg-5 order-2 order-md-1">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-primary text-white text-center py-4 rounded-top-4">
                        <h2 class="fw-bold display-6 mb-1" style="letter-spacing: 1px;">Create Account</h2>
                        <p class="mb-0 small text-light">Join AsekosiGO and start shopping or selling</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($registrationSuccess)): ?>
                            <div class="alert alert-success"><?php echo $registrationSuccess; ?></div>
                        <?php endif; ?>
                        <?php if (isset($registrationError)): ?>
                            <div class="alert alert-danger"><?php echo $registrationError; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="register" value="1">
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">Full Name</label>
                                <input type="text" class="form-control rounded-3" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <input type="email" class="form-control rounded-3" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold">Password</label>
                                <input type="password" class="form-control rounded-3" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="user_type" class="form-label fw-semibold">Account Type</label>
                                <select class="form-select rounded-3" id="user_type" name="user_type" required>
                                    <option value="customer">Customer</option>
                                    <option value="vendor" <?php echo (isset($_GET['type']) && $_GET['type'] === 'vendor') ? 'selected' : ''; ?>>Vendor</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2 rounded-3">Register</button>
                        </form>

                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="index.php?view=login">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lottie Animation Column -->
            <div class="col-md-6 col-lg-5 text-center order-1 order-md-2 mb-4 mb-md-0">
                <lottie-player 
                    src="https://assets5.lottiefiles.com/packages/lf20_x62chJ.json"  
                    background="transparent"  
                    speed="1"  
                    loop  
                    autoplay  
                    style="width: 100%; max-width: 400px;">
                </lottie-player>
            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>
<?php endif; ?>
