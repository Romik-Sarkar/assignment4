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

if (empty($data['city'])) {
    sendError('City is required');
}

$result = searchHotels($data['city']);

if ($result['success']) {
    sendSuccess($result['hotels'], 'Hotels found');
} else {
    sendError($result['error']);
}
?>
