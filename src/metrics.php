<?php
require 'vendor/autoload.php';

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;

// Create a new collector registry
$registry = new CollectorRegistry(new InMemory());

// Create a counter metric
$counter = $registry->registerCounter('app', 'http_requests_total', 'Total number of HTTP requests');
$counter->inc(); // Increment the counter

// Create a gauge metric
$gauge = $registry->registerGauge('app', 'memory_usage_bytes', 'Memory usage in bytes');
$gauge->set(memory_get_usage());

// Create a summary metric
$summary = $registry->registerSummary('app', 'request_duration_seconds', 'Duration of HTTP requests in seconds');
$summary->observe(0.5); // Simulate an observation

// Render the metrics in the Prometheus format
$renderer = new RenderTextFormat();
$data = $renderer->render($registry->getMetricFamilySamples());

// Set the content type and output the metrics
header('Content-Type: text/plain; version=0.0.4; charset=utf-8');
echo $data;
