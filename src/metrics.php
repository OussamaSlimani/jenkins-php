<?php
header('Content-Type: application/json');

$metrics = [
    'uptime' => shell_exec('uptime'),
    'memory_usage' => memory_get_usage(),
    // Add more metrics as needed
];

echo json_encode($metrics);