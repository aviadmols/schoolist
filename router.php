<?php
// Router script for PHP built-in server
// This file routes all requests to index.php

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestedFile = __DIR__ . $uri;

// Serve static files directly if they exist (let PHP server handle them)
// But exclude PHP files and routes that should go through index.php
if ($uri !== '/' && 
    !str_starts_with($uri, '/api/') &&
    !str_starts_with($uri, '/setup') &&
    !str_starts_with($uri, '/login') &&
    !str_starts_with($uri, '/dashboard') &&
    !str_starts_with($uri, '/admin') &&
    !str_starts_with($uri, '/p/') &&
    !str_starts_with($uri, '/q/') &&
    !str_starts_with($uri, '/link/') &&
    !str_starts_with($uri, '/verify') &&
    !str_starts_with($uri, '/redeem') &&
    file_exists($requestedFile) && 
    is_file($requestedFile) &&
    !str_ends_with($requestedFile, '.php')) {
    return false; // Let PHP server handle it
}

// Route everything else to index.php
require __DIR__ . '/index.php';











