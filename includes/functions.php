<?php
// ================================
// AUTH FUNCTIONS
// ================================

// Register new user
function registerUser($pdo, $name, $email, $password, $userType) {
    try {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return "Email is already registered.";
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Vendor accounts are pending approval by default
        $vendorStatus = $userType === 'vendor' ? 'pending' : null;

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, user_type, vendor_status) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword, $userType, $vendorStatus]);

        return true;
    } catch (Exception $e) {
        error_log("Register error: " . $e->getMessage());
        return "An error occurred during registration.";
    }
}

// Login user
function loginUser($pdo, $email, $password) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            return "Invalid email or password.";
        }

        // For vendors: only approved can log in
        if ($user['user_type'] === 'vendor' && $user['vendor_status'] !== 'approved') {
            return "Your vendor account is pending approval.";
        }

        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_type'] = $user['user_type'];

        return true;
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return "An error occurred during login.";
    }
}

// Logout user
function logoutUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_destroy();
    $_SESSION = [];
}

// ================================
// PRODUCT + CART + ORDER FUNCTIONS
// ================================

// Get all products with optional filters
function getProducts($pdo, $category = null, $vendorId = null, $search = null) {
    $sql = "SELECT p.*, u.name as vendor_name 
            FROM products p 
            JOIN users u ON p.vendor_id = u.id 
            WHERE p.status = 'active'";
    
    $params = [];
    
    if ($category) {
        $sql .= " AND p.category = ?";
        $params[] = $category;
    }
    
    if ($vendorId) {
        $sql .= " AND p.vendor_id = ?";
        $params[] = $vendorId;
    }
    
    if ($search) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get product by ID
function getProduct($pdo, $productId) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as vendor_name 
        FROM products p 
        JOIN users u ON p.vendor_id = u.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$productId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Add to cart
function addToCart($productId, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

// Get cart items with details
function getCartItems($pdo) {
    if (empty($_SESSION['cart'])) {
        return [];
    }
    
    $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as vendor_name 
        FROM products p 
        JOIN users u ON p.vendor_id = u.id 
        WHERE p.id IN ($placeholders)
    ");
    $stmt->execute(array_keys($_SESSION['cart']));
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cartItems = [];
    foreach ($products as $product) {
        $product['quantity'] = $_SESSION['cart'][$product['id']];
        
        if ($product['quantity'] > $product['stock']) {
            $product['quantity'] = $product['stock']; // limit to available stock
        }

        $product['subtotal'] = $product['price'] * $product['quantity'];
        $cartItems[] = $product;
    }
    
    return $cartItems;
}

// Get cart total
function getCartTotal($pdo) {
    $cartItems = getCartItems($pdo);
    $total = 0;
    foreach ($cartItems as $item) {
        $total += $item['subtotal'];
    }
    return $total;
}

// Create order
function createOrder($pdo, $customerId, $cartItems) {
    try {
        $pdo->beginTransaction();
        
        // Calculate totals
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item['subtotal'];
        }
        
        $commission = $subtotal * ADMIN_COMMISSION; // e.g. 0.1
        $total = $subtotal + DELIVERY_FEE;          // e.g. 100
        
        // Create order
        $stmt = $pdo->prepare("
            INSERT INTO orders (customer_id, total_amount, commission_amount, delivery_fee, status) 
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$customerId, $total, $commission, DELIVERY_FEE]);
        $orderId = $pdo->lastInsertId();
        
        // Add order items & vendor payouts
        foreach ($cartItems as $item) {
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price, vendor_id) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$orderId, $item['id'], $item['quantity'], $item['price'], $item['vendor_id']]);
            
            // Update product stock
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['id']]);

            // Insert vendor payout (minus commission)
            $vendorAmount = ($item['price'] * $item['quantity']); 
            $vendorAmount -= $vendorAmount * ADMIN_COMMISSION;

            $stmt = $pdo->prepare("
                INSERT INTO vendor_payouts (vendor_id, order_id, amount, status) 
                VALUES (?, ?, ?, 'pending')
            ");
            $stmt->execute([$item['vendor_id'], $orderId, $vendorAmount]);
        }
        
        $pdo->commit();
        return $orderId;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order creation failed: " . $e->getMessage());
        return false;
    }
}
?>
