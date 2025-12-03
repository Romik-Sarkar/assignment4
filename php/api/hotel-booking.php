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

// Calculate number of nights
$checkIn = new DateTime(convertDateToMySQL($data['checkInDate']));
$checkOut = new DateTime(convertDateToMySQL($data['checkOutDate']));
$nights = $checkOut->diff($checkIn)->days;

$totalPrice = $data['numRooms'] * $data['pricePerNight'] * $nights;

$result = bookHotel(
    $data['hotelId'],
    $user['phone'],
    $data['checkInDate'],
    $data['checkOutDate'],
    $data['numRooms'],
    $data['pricePerNight'],
    $totalPrice,
    $data['guests']
);

if ($result['success']) {
    sendSuccess($result, 'Hotel booked successfully');
} else {
    sendError($result['error']);
}
?>
