<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Simple chatbot API
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = $input['message'] ?? '';
    
    // Process the message and generate response
    $response = processChatbotMessage($message);
    
    echo json_encode([
        'response' => $response,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

function processChatbotMessage($message) {
    $message = strtolower(trim($message));
    
    // Greetings
    if (preg_match('/hello|hi|hey|greetings|howdy/', $message)) {
        return "Hello! Welcome to AsekosiGo. How can I help you today?";
    }
    
    // Help
    if (preg_match('/help|support|assistance/', $message)) {
        return "I can help you with: 
        - Finding products
        - Order status
        - Payment questions
        - Delivery information
        - Returns and refunds
        What do you need help with?";
    }
    
    // Products
    if (preg_match('/product|item|thing|buy|purchase|shop/', $message)) {
        return "We have thousands of products from various vendors. You can use the search bar or browse by category to find what you're looking for.";
    }
    
    // Price
    if (preg_match('/price|cost|how much|expensive|cheap/', $message)) {
        return "Product prices are set by our vendors and vary by item. You can see the price on each product page. We also have regular sales and discounts!";
    }
    
    // Delivery
    if (preg_match('/delivery|shipping|when arrive|how long|dispatch/', $message)) {
        return "We charge a flat delivery fee of KSh 200. Most orders are delivered within 2-5 business days depending on your location. You'll receive tracking information once your order ships.";
    }
    
    // Payment
    if (preg_match('/payment|pay|mpesa|cash|credit|debit/', $message)) {
        return "We accept M-Pesa payments. At checkout, you'll enter your M-Pesa phone number and receive a prompt to complete payment. Your payment is secure and encrypted.";
    }
    
    // Returns
    if (preg_match('/return|refund|exchange|wrong item|broken|damaged/', $message)) {
        return "We have a 7-day return policy. If you're not satisfied with your purchase, you can request a return through your order history. The vendor will process your return or exchange.";
    }
    
    // Account
    if (preg_match('/account|login|sign in|password|username|register/', $message)) {
        return "You can create an account or login using the buttons in the top right corner. If you've forgotten your password, use the 'Forgot Password' link on the login page.";
    }
    
    // Default response
    return "I'm sorry, I didn't understand that. I can help you with product searches, order status, payment questions, delivery information, and returns. What would you like to know?";
}

// For GET requests, return API info
echo json_encode([
    'name' => 'AsekosiGo Chatbot API',
    'version' => '1.0',
    'description' => 'Chatbot service for AsekosiGo marketplace',
    'endpoints' => [
        'POST /api/chatbot.php' => 'Process chatbot messages'
    ]
]);
?>