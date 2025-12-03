<?php
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Invalid request method', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['phone', 'password', 'confirmPassword', 'firstName', 'lastName', 'dateOfBirth', 'email'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        sendError("Field '$field' is required");
    }
}

// Validate password match
if ($data['password'] !== $data['confirmPassword']) {
    sendError('Passwords do not match');
}

// Validate password length
if (strlen($data['password']) < 8) {
    sendError('Password must be at least 8 characters');
}

// Register user
$result = registerUser(
    $data['phone'],
    $data['password'],
    $data['firstName'],
    $data['lastName'],
    $data['dateOfBirth'],
    $data['email'],
    $data['gender'] ?? null
);

if ($result['success']) {
    sendSuccess($result, 'Registration successful');
} else {
    sendError($result['error']);
}
?>
