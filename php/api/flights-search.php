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
if (empty($data['origin']) || empty($data['destination']) || empty($data['departureDate'])) {
    sendError('Origin, destination, and departure date are required');
}

$numPassengers = ($data['adults'] ?? 0) + ($data['children'] ?? 0) + ($data['infants'] ?? 0);

if ($numPassengers == 0) {
    sendError('At least one passenger is required');
}

$result = searchFlights(
    $data['origin'],
    $data['destination'],
    $data['departureDate'],
    $numPassengers,
    3 // ±3 days flexibility
);

if ($result['success']) {
    sendSuccess($result['flights'], 'Flights found');
} else {
    sendError($result['error']);
}
?>
