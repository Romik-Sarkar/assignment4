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

if (empty($data['phone']) || empty($data['password'])) {
    sendError('Phone and password are required');
}

$result = loginUser($data['phone'], $data['password']);

if ($result['success']) {
    sendSuccess($result['user'], 'Login successful');
} else {
    sendError($result['error']);
}
?>
