<?php

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirect to login if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../public/login.php");
        exit();
    }
}

// Redirect to login if not admin
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: ../public/login.php");
        exit();
    }
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate password
function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 special character
    return preg_match('/^(?=.*[A-Z])(?=.*[!@#$%^&*])(.{8,})$/', $password);
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generate random token
function generateToken() {
    return bin2hex(random_bytes(32));
}

// Send email (basic function - can be enhanced with PHPMailer)
function sendEmail($to, $subject, $message) {
    $headers = "From: noreply@tendergate.com\r\n";
    $headers .= "Reply-To: noreply@tendergate.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Upload file
function uploadFile($file, $uploadDir) {
    $targetDir = $uploadDir;
    $fileName = basename($file["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
    // Allow certain file formats
    $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'pdf', 'doc', 'docx');
    
    if(in_array($fileType, $allowTypes)){
        // Upload file to server
        if(move_uploaded_file($file["tmp_name"], $targetFilePath)){
            return $fileName;
        }
    }
    
    return false;
}

?>

