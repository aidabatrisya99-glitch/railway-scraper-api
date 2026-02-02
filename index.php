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
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Route: GET / (health check)
if (($path === '/' || $path === '') && $_SERVER['REQUEST_METHOD'] === 'GET') {
    error_log('[RAILWAY] Health check accessed');
    echo json_encode([
        'status' => 'ok',
        'message' => 'Railway Scraper API is running',
        'version' => '1.0.0',
        'chromedriver_running' => file_exists('/tmp/chromedriver.log')
    ]);
    exit;
}

// Route: POST /api/scrape
if (($path === '/api/scrape' || $path === '/scrape') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    error_log('[RAILWAY] Scrape endpoint accessed');
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    error_log('[RAILWAY] Input data: ' . json_encode($input));
    
    // Validate API key
    $apiKey = $_ENV['SCRAPER_API_KEY'] ?? getenv('SCRAPER_API_KEY');
    error_log('[RAILWAY] Expected API key: ' . substr($apiKey, 0, 5) . '...');
    error_log('[RAILWAY] Received API key: ' . substr($input['api_key'] ?? '', 0, 5) . '...');
    
    if (!isset($input['api_key']) || $input['api_key'] !== $apiKey) {
        error_log('[RAILWAY] Unauthorized: API key mismatch');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized', 'success' => false]);
        exit;
    }
    
    // Validate matric number
    if (!isset($input['matric']) || empty($input['matric'])) {
        error_log('[RAILWAY] Invalid input: missing matric number');
        http_response_code(400);
        echo json_encode(['error' => 'Matric number required', 'success' => false]);
        exit;
    }
    
    try {
        error_log('[RAILWAY] Starting scrape for matric: ' . $input['matric']);
        
        // Run scraper
        $scraper = new ScraperService();
        $result = $scraper->scrape($input['matric']);
        
        error_log('[RAILWAY] Scrape completed. Success: ' . ($result['success'] ? 'true' : 'false'));
        
        http_response_code(200);
        echo json_encode($result);
        
    } catch (\Exception $e) {
        error_log('[RAILWAY] Exception: ' . $e->getMessage());
        error_log('[RAILWAY] Trace: ' . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage(),
            'success' => false
        ]);
    }
    exit;
}

// 404 - Route not found
http_response_code(404);
echo json_encode(['error' => 'Not found']);
