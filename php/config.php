<?php
/**
 * CS 6314 Assignment #4
 * Database Configuration
 */

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_USER', 'root');  // Change this to your MySQL username
define('DB_PASS', '');      // Change this to your MySQL password
define('DB_NAME', 'travel_deals');

// Admin hardcoded phone number
define('ADMIN_PHONE', '222-222-2222');

// Date range for bookings
define('START_DATE', '2024-09-01');
define('END_DATE', '2024-12-01');

// Pricing multipliers
define('CHILD_MULTIPLIER', 0.70);  // 70% of adult price
define('INFANT_MULTIPLIER', 0.10); // 10% of adult price

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection function
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            return null;
        }

        $conn->set_charset("utf8mb4");
        return $conn;

    } catch (Exception $e) {
        error_log("Database connection exception: " . $e->getMessage());
        return null;
    }
}

// Close database connection
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user']) && isset($_SESSION['user']['phone']);
}

// Check if user is admin
function isAdmin() {
    return isLoggedIn() && $_SESSION['user']['phone'] === ADMIN_PHONE;
}

// Get current logged-in user
function getCurrentUser() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

// Require login (redirect to login page if not logged in)
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Require admin (redirect if not admin)
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
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

// Validate phone format (ddd-ddd-dddd)
function validatePhone($phone) {
    $pattern = '/^\d{3}-\d{3}-\d{4}$/';
    return preg_match($pattern, $phone);
}

// Validate email
function validateEmail($email) {
    return strpos($email, '@') !== false && strpos($email, '.com') !== false;
}

// Validate date format (MM-DD-YYYY)
function validateDateFormat($date) {
    $pattern = '/^\d{2}-\d{2}-\d{4}$/';
    if (!preg_match($pattern, $date)) {
        return false;
    }

    // Validate actual date values
    list($month, $day, $year) = explode('-', $date);
    return checkdate($month, $day, $year);
}

// Convert MM-DD-YYYY to YYYY-MM-DD for MySQL
function convertDateToMySQL($date) {
    if (empty($date)) return null;
    list($month, $day, $year) = explode('-', $date);
    return "$year-$month-$day";
}

// Convert YYYY-MM-DD to MM-DD-YYYY for display
function convertDateFromMySQL($date) {
    if (empty($date)) return '';
    list($year, $month, $day) = explode('-', $date);
    return "$month-$day-$year";
}

// Generate unique ID with prefix
function generateUniqueID($prefix = 'ID') {
    return $prefix . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

// Send JSON response
function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Send error response
function sendError($message, $statusCode = 400) {
    sendJSON(['success' => false, 'error' => $message], $statusCode);
}

// Send success response
function sendSuccess($data, $message = 'Success') {
    sendJSON(['success' => true, 'message' => $message, 'data' => $data]);
}

// Error handler
function handleError($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
    return true;
}

set_error_handler('handleError');

// Set timezone
date_default_timezone_set('America/Chicago');
?>
