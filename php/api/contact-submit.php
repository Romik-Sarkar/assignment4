<?php
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Require login
if (!isLoggedIn()) {
    sendError('You must be logged in to submit a contact form', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Invalid request method', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate comment length
if (empty($data['comment']) || strlen($data['comment']) < 10) {
    sendError('Comment must be at least 10 characters');
}

$user = getCurrentUser();

$result = saveContact(
    $user['phone'],
    $user['firstName'],
    $user['lastName'],
    $user['dateOfBirth'],
    $user['email'],
    $user['gender'],
    $data['comment']
);

if ($result['success']) {
    sendSuccess($result, 'Contact submitted successfully');
} else {
    sendError($result['error']);
}
?>
