<?php
/**
 * Admin API Settings Page - Configure DCSServerBot API
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';
require_once __DIR__ . '/../api_config_helper.php';

// Require admin login and permission
requireAdmin();
requirePermission('manage_api');

// Get current admin
$currentAdmin = getCurrentAdmin();

// Load current API configuration with auto-fixing
$configResult = loadApiConfigWithFix();
$apiConfig = $configResult['config'];
$configFile = $configResult['config_path'];
$autoFixMessage = '';

// Show any auto-fix messages
if (isset($configResult['fixed']) && $configResult['fixed'] && !empty($configResult['changes'])) {
    $autoFixMessage = 'Configuration auto-fixed: ' . implode(', ', $configResult['changes']);
}

// Show config location if not standard
if ($configFile !== dirname(__DIR__) . '/api_config.json') {
    $autoFixMessage .= ($autoFixMessage ? ' | ' : '') . 'Config location: ' . $configFile;
}

// Handle form submission
$message = '';
$messageType = '';
$testResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = ERROR_MESSAGES['csrf_invalid'];
        $messageType = 'error';
    } else {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'save':
                    // Get form inputs
                    $apiHost = trim($_POST['api_host'] ?? '');
                    $timeout = intval($_POST['timeout'] ?? 30);
                    $cacheTtl = intval($_POST['cache_ttl'] ?? 300);
                    $refreshInterval = intval($_POST['refresh_interval'] ?? 300);
                    $useApi = isset($_POST['use_api']);
                    
                    // Use helper to create complete config
                    $saveResult = createApiConfigFromHost($apiHost, $configFile);
                    
                    if ($saveResult['success']) {
                        // Update with form values
                        $apiConfig = $saveResult['config'];
                        $apiConfig['timeout'] = $timeout;
                        $apiConfig['cache_ttl'] = $cacheTtl;
                        $apiConfig['refresh_interval'] = in_array($refreshInterval, [300, 600, 1800, 3600], true) ? $refreshInterval : 300;
                        $apiConfig['use_api'] = $useApi;
                        
                        // Save again with all settings
                        if (file_put_contents($configFile, json_encode($apiConfig, JSON_PRETTY_PRINT))) {
                            logAdminActivity('API_CONFIG_CHANGE', $_SESSION['admin_id'], 'settings', 'api_config', $apiConfig);
                            $message = 'API configuration saved successfully with all endpoints enabled';
                            $messageType = 'success';
                        }
                    } else {
                        $message = $saveResult['message'];
                        $messageType = 'error';
                    }
                    break;
                    
                case 'test':
                    // Test API connection using enhanced client with auto-detection
                    require_once dirname(__DIR__) . '/api_client_enhanced.php';
                    
                    try {
                        // Create enhanced client
                        $client = createEnhancedAPIClient();
                        
                        // Try to make a simple request
                        $result = $client->request('/servers', null, 'GET');
                        
                        if ($result !== null) {
                            $protocol = $client->getDetectedProtocol();
                            $detectedUrl = $client->getApiBaseUrl();
                            
                            // Update config with detected protocol
                            $apiHost = $apiConfig['api_host'] ?? preg_replace('#^https?://#', '', $apiConfig['api_base_url']);
                            $apiConfig['api_host'] = $apiHost;
                            $apiConfig['api_base_url'] = $detectedUrl;
                            
                            // Save the configuration with detected protocol
                            $saveConfig = $apiConfig;
                            $saveConfig['api_base_url'] = $detectedUrl;
                            
                            if (@file_put_contents($configFile, json_encode($saveConfig, JSON_PRETTY_PRINT))) {
                                $testResult = [
                                    'success' => true, 
                                    'message' => "API connection successful! Detected and saved {$protocol}:// protocol.",
                                    'protocol' => $protocol
                                ];
                            } else {
                                $testResult = [
                                    'success' => true, 
                                    'message' => "API connection successful using {$protocol}://",
                                    'protocol' => $protocol
                                ];
                            }
                        } else {
                            $testResult = ['success' => false, 'message' => 'No response from API'];
                        }
                    } catch (Exception $e) {
                        $errorMsg = $e->getMessage();
                        
                        // The enhanced client should have handled protocol detection
                        // If we still get an error, it's a real connection issue
                        $testResult = [
                            'success' => false, 
                            'message' => 'Unable to connect to API: ' . $errorMsg
                        ];
                    }
                    break;
            }
        }
    }
}

// Page title
$pageTitle = 'API Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Carrier Air Wing Command</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .api-form {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--accent-primary);
            font-weight: bold;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 10px;
            background-color: var(--bg-tertiary);
            border: 1px solid var(--border-primary);
            border-radius: 4px;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group select:focus {
            border-color: var(--accent-primary);
            outline: none;
        }
        
        .form-group .help-text {
            margin-top: 5px;
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
        }
        
        .test-section {
            background-color: var(--bg-tertiary);
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .test-result {
            margin-top: 15px;
            padding: 15px;
            border-radius: 4px;
        }
        
        .test-result.success {
            background-color: rgba(76, 175, 80, 0.2);
            border: 1px solid var(--accent-success);
            color: var(--accent-success);
        }
        
        .test-result.error {
            background-color: rgba(244, 67, 54, 0.2);
            border: 1px solid var(--accent-danger);
            color: var(--accent-danger);
        }
        
        .endpoints-info {
            background-color: var(--bg-tertiary);
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .endpoints-info h4 {
            margin-bottom: 15px;
            color: var(--accent-primary);
        }
        
        .endpoints-list {
            font-family: monospace;
            font-size: 13px;
            line-height: 1.8;
        }

        .endpoints-list ul {
            margin: 8px 0 18px;
            padding-left: 20px;
        }

        .endpoints-list code {
            color: var(--accent-primary);
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        /* Critical inline CSS for layout */
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; overflow-x: hidden; }
        .admin-wrapper { display: flex; min-height: 100vh; width: 100%; overflow-x: hidden; }
        .admin-sidebar { width: 250px; flex-shrink: 0; background: #2a2a2a; }
        .admin-main { flex: 1; min-width: 0; overflow-x: hidden; }
        .admin-content { padding: 30px; max-width: 100%; overflow-x: hidden; }
        .card { max-width: 100%; overflow-x: auto; }
    </style>
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <?php include __DIR__ . '/nav.php'; ?>
        
        <main class="admin-main">
            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($autoFixMessage): ?>
                    <div class="alert alert-info">
                        <?= e($autoFixMessage) ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">DCSServerBot API Configuration</h2>
                    </div>
                    
                    <form method="POST" action="" class="api-form">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="save">
                        
                        <div class="form-group">
                            <label for="api_host">API Host</label>
                            <input type="text" 
                                   id="api_host" 
                                   name="api_host" 
                                   value="<?= e($apiConfig['api_host'] ?? preg_replace('#^https?://#', '', $apiConfig['api_base_url'])) ?>"
                                   placeholder="localhost:8080"
                                   pattern="[a-zA-Z0-9.-]+:[0-9]+">
                            <div class="help-text">Enter domain:port (e.g., dcs1.example.com:9876). Protocol will be auto-detected.</div>
                        </div>
                        
                        
                        <div class="form-group">
                            <label for="timeout">Request Timeout (seconds)</label>
                            <input type="number" 
                                   id="timeout" 
                                   name="timeout" 
                                   value="<?= $apiConfig['timeout'] ?>"
                                   min="5" 
                                   max="300">
                            <div class="help-text">Maximum time to wait for API responses</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="refresh_interval">Dashboard Refresh Rate</label>
                            <?php $refreshInterval = (int)($apiConfig['refresh_interval'] ?? 300); ?>
                            <select id="refresh_interval" name="refresh_interval">
                                <option value="300" <?= $refreshInterval === 300 ? 'selected' : '' ?>>5 minutes</option>
                                <option value="600" <?= $refreshInterval === 600 ? 'selected' : '' ?>>10 minutes</option>
                                <option value="1800" <?= $refreshInterval === 1800 ? 'selected' : '' ?>>30 minutes</option>
                                <option value="3600" <?= $refreshInterval === 3600 ? 'selected' : '' ?>>1 hour</option>
                            </select>
                            <div class="help-text">How often live dashboard pages automatically refresh API data. Default for new installs is 5 minutes.</div>
                        </div>

                        <div class="form-group">
                            <label for="cache_ttl">Cache TTL (seconds)</label>
                            <input type="number" 
                                   id="cache_ttl" 
                                   name="cache_ttl" 
                                   value="<?= $apiConfig['cache_ttl'] ?>"
                                   min="0" 
                                   max="3600">
                            <div class="help-text">Advanced: how long API responses may be cached where caching is used. Existing default is 300 seconds.</div>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" 
                                   id="use_api" 
                                   name="use_api" 
                                   value="1"
                                   <?= $apiConfig['use_api'] ? 'checked' : '' ?>>
                            <label for="use_api">Enable API Integration</label>
                        </div>
                        
                        
                        <div class="button-group">
                            <button type="submit" class="btn btn-primary">Save Configuration</button>
                            <button type="submit" class="btn btn-secondary" name="action" value="test">Test Connection</button>
                            <a href="api_health.php" class="btn btn-secondary">API Health / Debug</a>
                        </div>
                    </form>
                </div>
                
                <?php if ($testResult): ?>
                    <div class="test-section">
                        <h3>Connection Test Result</h3>
                        <div class="test-result <?= $testResult['success'] ? 'success' : 'error' ?>">
                            <?= e($testResult['message']) ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="endpoints-info">
                    <h4>Available Endpoints</h4>
                    <div class="endpoints-list">
                        <strong>DCSServerBot REST API endpoints:</strong>
                        <ul>
                            <li><code>GET /servers</code> - Live server status, mission, weather, slots, extensions, and players</li>
                            <li><code>GET /serverstats</code> - Server-wide totals for players, playtime, sorties, kills, deaths, and activity</li>
                            <li><code>GET /server_attendance</code> - Current players, 24h/7d/30d attendance, top theatres, missions, and modules</li>
                            <li><code>GET /leaderboard</code> - Ranked player data by kills, credits, playtime, and other supported metrics</li>
                            <li><code>GET /highscore</code> - High score leaderboard data</li>
                            <li><code>GET /trueskill</code> - TrueSkill ranking data</li>
                            <li><code>POST /player_info</code> - Detailed individual player profile data</li>
                            <li><code>POST /stats</code> - Enhanced individual player statistics</li>
                            <li><code>POST /getuser</code> - Legacy individual user statistics</li>
                            <li><code>POST /credits</code> - Player credits lookup</li>
                            <li><code>POST /modulestats</code> - Module usage statistics</li>
                            <li><code>POST /traps</code> - Carrier trap statistics</li>
                            <li><code>POST /weaponpk</code> - Weapon probability of kill statistics</li>
                            <li><code>GET /squadrons</code> - Squadron list and overview data</li>
                            <li><code>POST /player_squadrons</code> - Squadrons for an individual player</li>
                            <li><code>POST /squadron_members</code> - Members for a squadron</li>
                            <li><code>POST /squadron_credits</code> - Squadron credits data</li>
                            <li><code>GET /current_server</code> - Current server selection/status</li>
                            <li><code>GET /airbases</code>, <code>GET /airbase</code>, <code>GET /airbase/atis</code>, <code>GET /airbase/warehouse</code> - Airbase data</li>
                            <li><code>GET /convertCoordinates</code> - Coordinate conversion helper</li>
                            <li><code>GET /mission/group/waypoints</code> - Mission group waypoint data</li>
                        </ul>

                        <strong>Dashboard PHP endpoints:</strong>
                        <ul>
                            <li><code>get_servers_api.php</code> / <code>get_servers.php</code> - Server status page data</li>
                            <li><code>get_server_stats.php</code> - Homepage server statistics, attendance, and top API lists</li>
                            <li><code>get_leaderboard_api.php</code> / <code>get_leaderboard.php</code> - Leaderboard page data</li>
                            <li><code>get_player_stats_api.php</code> / <code>get_player_stats.php</code> - Individual player statistics</li>
                            <li><code>get_credits_api.php</code> / <code>get_credits.php</code> - Credits leaderboard data</li>
                            <li><code>get_missionstats_api.php</code> / <code>get_missionstats.php</code> - Mission statistics</li>
                            <li><code>get_squadrons_api.php</code> / <code>get_squadrons.php</code> - Squadron overview data</li>
                            <li><code>get_squadron_members_api.php</code> / <code>get_squadron_members.php</code> - Squadron member data</li>
                            <li><code>get_squadron_credits_api.php</code> / <code>get_squadron_credits.php</code> - Squadron credits data</li>
                            <li><code>get_api_config.php</code> - Client-side API configuration</li>
                            <li><code>get_leaderboard_client.php</code> - Client-side leaderboard bridge</li>
                        </ul>

                        <strong>Note:</strong> The dashboard now uses the expanded API routes where available.
                    </div>
                </div>

<script>
    // Handle test button - remove the complex AJAX functionality since test_api_connection.php doesn't exist
    // The test is handled server-side when the form is submitted with action=test
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form.api-form');
        const actionInput = form.querySelector('input[name="action"]');
        const testBtn = document.querySelector('button[value="test"]');
        
        if (testBtn) {
            testBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const apiHostInput = document.getElementById('api_host');
                if (!apiHostInput.value.trim()) {
                    alert('Please enter an API host first');
                    return;
                }
                
                // Set the action to test and submit the form
                actionInput.value = 'test';
                form.submit();
            });
        }
    });
</script>
            </div>
        </main>
    </div>
</body>
</html>
