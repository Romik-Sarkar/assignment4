<?php
/**
 * API Router - Routes API calls to the actual PHP files
 * This file bridges the public directory with the php/api directory
 */

// Get the request URI path
$request = $_SERVER['REQUEST_URI'];

// Extract the endpoint after /api-router.php/
$endpoint = str_replace('/api-router.php/', '', parse_url($request, PHP_URL_PATH));

// Map endpoints to actual PHP files
$apiFile = __DIR__ . '/../php/api/' . $endpoint;

// Check if file exists
if (file_exists($apiFile) && pathinfo($apiFile, PATHINFO_EXTENSION) === 'php') {
    // Change working directory to php/api so relative includes work
    chdir(__DIR__ . '/../php/api');

    // Include and execute the API file
    require $apiFile;
} else {
    // API endpoint not found
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'API endpoint not found: ' . $endpoint]);
}
?>
