<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'asekosigo_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create database connection
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Initialize database tables
function initDatabase($pdo) {
    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            user_type ENUM('admin','vendor','customer') NOT NULL,
            vendor_status ENUM('pending','approved','rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vendor_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            category VARCHAR(100) NOT NULL,
            image_url VARCHAR(255),
            stock INT DEFAULT 0,
            status ENUM('active','inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (vendor_id) REFERENCES users(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            commission_amount DECIMAL(10,2) NOT NULL,
            delivery_fee DECIMAL(10,2) NOT NULL,
            status ENUM('pending','paid','shipped','delivered','cancelled') DEFAULT 'pending',
            mpesa_receipt VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES users(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            vendor_id INT NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id),
            FOREIGN KEY (product_id) REFERENCES products(id),
            FOREIGN KEY (vendor_id) REFERENCES users(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS vendor_payouts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vendor_id INT NOT NULL,
            order_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending','paid') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (vendor_id) REFERENCES users(id),
            FOREIGN KEY (order_id) REFERENCES orders(id)
        )"
    ];
    
    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
        } catch(PDOException $e) {
            // Log error but don't stop execution
            error_log("Database initialization error: " . $e->getMessage());
        }
    }
    
    // Create default admin user if not exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@asekosigo.com']);
    if ($stmt->rowCount() == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, 'admin')");
        $stmt->execute(['System Admin', 'admin@asekosigo.com', $hashedPassword]);
    }
}

// Initialize database
initDatabase($pdo);
?>