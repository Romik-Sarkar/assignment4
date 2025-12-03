<?php
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$result = logoutUser();
sendSuccess(null, 'Logged out successfully');
?>
