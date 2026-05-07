<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'projectnihanz');
define('DB_USER', 'root');
define('DB_PASS', '');

// Encryption key for sensitive data
define('ENCRYPTION_KEY', 'your-secret-key-here-change-this-in-production');

// Start session
session_start();

// PDO connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Function to encrypt data
function encryptData($data) {
    $key = ENCRYPTION_KEY;
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

// Function to decrypt data
function decryptData($data) {
    $key = ENCRYPTION_KEY;
    $data = base64_decode($data);
    $iv_length = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $iv_length);
    $encrypted = substr($data, $iv_length);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
}

// Function to log audit
function logAudit($user_id, $action, $status) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, timestamp, status) VALUES (?, ?, NOW(), ?)");
    $stmt->execute([$user_id, $action, $status]);
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] == $role;
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Function to require role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: dashboard.php');
        exit;
    }
}

// Input validation functions
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number, and no spaces
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[^\s]{8,}$/', $password);
}
?>