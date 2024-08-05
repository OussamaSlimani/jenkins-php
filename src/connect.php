<?php

// Function to parse .env file and set environment variables
function loadEnv($filePath) {
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; 
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (!array_key_exists($key, $_ENV)) {
            putenv("$key=$value"); 
            $_ENV[$key] = $value;
        }
    }
}

// Load .env file
$envFilePath = __DIR__ . '/.env';
if (file_exists($envFilePath)) {
    loadEnv($envFilePath);
} else {
    die('.env file not found');
}

// Function to get database connection
function getConnection() {
    $host = $_ENV['DB_HOST'];
    $port = $_ENV['DB_PORT'];
    $user = $_ENV['DB_USER'];
    $password = $_ENV['DB_PASSWORD'];
    $database = $_ENV['DB_DATABASE'];
    echo  $host;
    $conn = new mysqli($host, $user, $password, $database, $port);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}
?>
