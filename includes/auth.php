<?php
// Handle user registration
function registerUser($pdo, $name, $email, $password, $userType) {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        return "Email already registered";
    }
    
    // Hash password and create user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $vendorStatus = $userType == 'vendor' ? 'pending' : null;
    
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, user_type, vendor_status) VALUES (?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$name, $email, $hashedPassword, $userType, $vendorStatus])) {
        return true;
    } else {
        return "Registration failed. Please try again.";
    }
}

// Handle user login
function loginUser($pdo, $email, $password) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Check if vendor is approved
            if ($user['user_type'] == 'vendor' && $user['vendor_status'] != 'approved') {
                return "Your vendor account is pending approval";
            }
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['user_name'] = $user['name'];
            
            return true;
        }
    }
    
    return "Invalid email or password";
}

// Handle user logout
function logoutUser() {
    session_destroy();
    header('Location: ../index.php');
    exit();
}
?>