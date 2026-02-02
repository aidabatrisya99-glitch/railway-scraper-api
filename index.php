<?php

// Simple routing for Railway deployment
require __DIR__ . '/vendor/autoload.php';

use App\ScraperService;

// Load environment variables (if .env exists, otherwise use getenv)
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get request path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Route: POST /api/scrape
if ($path === '/api/scrape' || $path === '/scrape') {
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate API key
    if (!isset($input['api_key']) || $input['api_key'] !== $_ENV['SCRAPER_API_KEY']) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized', 'success' => false]);
        exit;
    }
    
    // Validate matric number
    if (!isset($input['matric']) || empty($input['matric'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Matric number required', 'success' => false]);
        exit;
    }
    
    try {
        // Run scraper
        $scraper = new ScraperService();
        $result = $scraper->scrape($input['matric']);
        
        http_response_code(200);
        echo json_encode($result);
        
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage(),
            'success' => false
        ]);
    }
    
} elseif ($path === '/' || $path === '') {
    // Health check
    echo json_encode([
        'service' => 'UiTM Schedule Scraper API',
        'status' => 'running',
        'version' => '1.0.0'
    ]);
    
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}
