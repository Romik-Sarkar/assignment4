<?php
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Invalid request method', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$user = getCurrentUser();

$result = bookFlight(
    $data['flightId'],
    $user['phone'],
    $data['passengers'],
    $data['totalPrice']
);

if ($result['success']) {
    sendSuccess($result, 'Flight booked successfully');
} else {
    sendError($result['error']);
}
?>
