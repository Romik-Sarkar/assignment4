<?php
require_once '../config.php';
require_once '../admin-functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$result = loadFlightsFromJSON();

if ($result['success']) {
    sendSuccess($result, $result['message']);
} else {
    sendError($result['error']);
}
?>
