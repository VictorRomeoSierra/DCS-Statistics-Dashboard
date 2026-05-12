<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

// Include security functions and API client
require_once __DIR__ . '/security_functions.php';
require_once __DIR__ . '/api_client_enhanced.php';

// Rate limiting: 120 requests per minute
if (!checkRateLimit(120, 60)) {
    exit;
}

try {
    // Load API configuration
    $configFile = __DIR__ . '/api_config.json';
    $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
    
    // Initialize API client
    $apiClient = new DCSServerBotAPIClient($config);
    
    // Check which sorting is requested
    $sortBy = $_GET['sort'] ?? 'kills';
    
    // Get data from appropriate endpoint
    if ($sortBy === 'kdr') {
        $topPlayers = $apiClient->getTopKDR();
    } else {
        $topPlayers = $apiClient->getTopKills();
    }
    
    // Transform API response to match our existing format
    $stats = [];
    foreach ($topPlayers as $index => $player) {
        $stats[] = [
            'rank' => $index + 1,
            'name' => htmlspecialchars($player['nick'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
            'kills' => $player['kills'] ?? 0,
            'deaths' => $player['deaths'] ?? 0,
            'kd_ratio' => $player['kdr'] ?? 0,
            // These fields are not available in the current API
            'sorties' => 0,
            'flight_hours' => 0,
            'takeoffs' => 0,
            'landings' => 0,
            'crashes' => 0,
            'ejections' => 0,
            'most_used_aircraft' => 'N/A'
        ];
    }
    
    // Return response
    echo json_encode([
        'data' => $stats,
        'source' => 'api',
        'count' => count($stats),
        'generated' => date('c')
    ]);
    
} catch (Exception $e) {
    
    // Return error response
    echo json_encode([
        'error' => 'Service temporarily unavailable',
        'data' => [],
        'source' => 'api',
        'count' => 0
    ]);
}