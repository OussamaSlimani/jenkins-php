<?php
// metrics.php
header('Content-Type: text/plain; version=0.0.4; charset=utf-8');

// Define a function to get metrics
function getMetrics() {
    // Example metrics
    $metrics = [
        "# HELP php_requests_total The total number of HTTP requests.",
        "# TYPE php_requests_total counter",
        "php_requests_total{method=\"get\"} 1027",
        "php_requests_total{method=\"post\"} 3",
        "",
        "# HELP php_errors_total The total number of errors.",
        "# TYPE php_errors_total counter",
        "php_errors_total 42",
    ];

    return implode("\n", $metrics);
}

echo getMetrics();
