<?php
/**
 * Themes Management Page
 * Allows super admins to customize site appearance
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';
require_once __DIR__ . '/../config_path.php';
require_once __DIR__ . '/../chart_theme.php';

// Require admin login and permission
requireAdmin();
requirePermission('manage_themes');

// Get current admin to check specific permissions
$currentAdmin = getCurrentAdmin();
$isAirBoss = ($currentAdmin['role'] === ROLE_AIR_BOSS);

$message = '';
$error = '';

function getDefaultHeaderImageSettings() {
    return [
        'image' => 'dcs-header-image.jpg',
        'position_x' => 50,
        'position_y' => 50
    ];
}

function getHeaderImageSettingsPath() {
    return __DIR__ . '/data/header_image.json';
}

function loadHeaderImageSettings() {
    $settings = getDefaultHeaderImageSettings();
    $path = getHeaderImageSettingsPath();
    if (file_exists($path)) {
        $saved = json_decode(file_get_contents($path), true);
        if (is_array($saved)) {
            $settings = array_merge($settings, array_intersect_key($saved, $settings));
        }
    }

    $imagePath = __DIR__ . '/../' . ltrim($settings['image'], '/');
    if (empty($settings['image']) || !file_exists($imagePath)) {
        $settings['image'] = getDefaultHeaderImageSettings()['image'];
    }

    $settings['position_x'] = max(0, min(100, (int)$settings['position_x']));
    $settings['position_y'] = max(0, min(100, (int)$settings['position_y']));
    return $settings;
}

function saveHeaderImageSettings($settings) {
    $settings = array_merge(getDefaultHeaderImageSettings(), $settings);
    $settings['position_x'] = max(0, min(100, (int)$settings['position_x']));
    $settings['position_y'] = max(0, min(100, (int)$settings['position_y']));

    $dataDir = __DIR__ . '/data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    $imagePath = __DIR__ . '/../' . ltrim($settings['image'], '/');
    if (empty($settings['image']) || !file_exists($imagePath)) {
        $settings['image'] = getDefaultHeaderImageSettings()['image'];
    }

    $imageUrl = str_replace(["\\", "'"], ['/', "\\'"], $settings['image']);
    $css = ".header-background {\n";
    $css .= "    background-image: url('{$imageUrl}') !important;\n";
    $css .= "    background-size: cover !important;\n";
    $css .= "    background-position: {$settings['position_x']}% {$settings['position_y']}% !important;\n";
    $css .= "    background-repeat: no-repeat !important;\n";
    $css .= "}\n";

    return file_put_contents(getHeaderImageSettingsPath(), json_encode($settings, JSON_PRETTY_PRINT)) !== false
        && file_put_contents(__DIR__ . '/../header_custom.css', $css) !== false;
}

function getDefaultThemeColors() {
    return [
        'primary_color' => '#1a1a1a',
        'secondary_color' => '#2a2a2a',
        'background_color' => '#121212',
        'surface_color' => '#2c2c2c',
        'surface_dark_color' => '#1e1e1e',
        'card_color' => '#2c2c2c',
        'card_alt_color' => '#1e1e1e',
        'card_heading_color' => '#4CAF50',
        'card_text_color' => '#ffffff',
        'card_muted_text_color' => '#cccccc',
        'text_color' => '#ffffff',
        'muted_text_color' => '#cccccc',
        'heading_color' => '#4CAF50',
        'link_color' => '#4a9eff',
        'accent_color' => '#4CAF50',
        'accent_hover_color' => '#7ad77d',
        'border_color' => '#556b2f',
        'nav_background_color' => '#1a1a1a',
        'nav_text_color' => '#ffffff',
        'nav_hover_color' => '#4CAF50',
        'header_text_color' => '#ffffff',
        'header_title_gradient_color' => '#4CAF50',
        'header_subtitle_color' => '#e0e0e0',
        'footer_background_color' => '#2a2a2a',
        'footer_text_color' => '#e0e0e0',
        'success_color' => '#4CAF50',
        'warning_color' => '#ff9800',
        'danger_color' => '#f44336',
        'info_color' => '#2196F3',
        'table_header_color' => '#1e1e1e',
        'table_header_text_color' => '#4CAF50',
        'table_row_color' => '#2c2c2c',
        'table_text_color' => '#ffffff',
        'table_player_name_color' => '#e0e0e0',
        'table_hover_color' => '#3a3a3a'
    ];
}

function getThemeColorGroups() {
    return [
        'Page & Text' => [
            'background_color' => 'Page Background',
            'text_color' => 'Main Text',
            'muted_text_color' => 'Muted Text',
            'heading_color' => 'Headings',
            'link_color' => 'Links'
        ],
        'Brand & Accents' => [
            'accent_color' => 'Main Accent',
            'accent_hover_color' => 'Accent Hover',
            'border_color' => 'Borders',
            'success_color' => 'Success',
            'warning_color' => 'Warning',
            'danger_color' => 'Danger',
            'info_color' => 'Info'
        ],
        'Panels & Cards' => [
            'surface_color' => 'Panel Top',
            'surface_dark_color' => 'Panel Bottom',
            'card_color' => 'Card Top',
            'card_alt_color' => 'Card Bottom',
            'card_heading_color' => 'Card Headings',
            'card_text_color' => 'Card Text',
            'card_muted_text_color' => 'Card Muted Text',
            'secondary_color' => 'Secondary Surface'
        ],
        'Header & Navigation' => [
            'primary_color' => 'Primary Background',
            'nav_background_color' => 'Nav Background',
            'nav_text_color' => 'Nav Text',
            'nav_hover_color' => 'Nav Hover',
            'header_text_color' => 'Header Title',
            'header_title_gradient_color' => 'Header Title Gradient End',
            'header_subtitle_color' => 'Header Subtitle'
        ],
        'Footer' => [
            'footer_background_color' => 'Footer Background',
            'footer_text_color' => 'Footer Text'
        ],
        'Tables' => [
            'table_header_color' => 'Table Header',
            'table_header_text_color' => 'Table Header Text',
            'table_row_color' => 'Table Row',
            'table_text_color' => 'Table Text',
            'table_player_name_color' => 'Table Player Names',
            'table_hover_color' => 'Table Hover'
        ]
    ];
}

function getDefaultThemeOptions() {
    return [
        'header_title_gradient_enabled' => false
    ];
}

function loadThemeOptionsFromCss($content) {
    $options = getDefaultThemeOptions();
    if (preg_match('/--header_title_gradient_enabled:\s*(0|1);/', $content, $match)) {
        $options['header_title_gradient_enabled'] = $match[1] === '1';
    }
    return $options;
}

function loadThemeColorsFromCss($customCSS) {
    $colors = getDefaultThemeColors();
    if (!file_exists($customCSS)) {
        return $colors;
    }

    $content = file_get_contents($customCSS);
    preg_match_all('/--([a-z_]+):\s*(#[0-9a-fA-F]{6});/', $content, $matches);
    if (!empty($matches[1]) && !empty($matches[2])) {
        foreach ($matches[1] as $i => $varName) {
            if (isset($colors[$varName])) {
                $colors[$varName] = $matches[2][$i];
            }
        }
    }

    return $colors;
}

function loadThemeOptionsFile($customCSS) {
    if (!file_exists($customCSS)) {
        return getDefaultThemeOptions();
    }

    return loadThemeOptionsFromCss(file_get_contents($customCSS));
}

function cleanThemeColors($colors) {
    $clean = [];
    foreach (getDefaultThemeColors() as $key => $defaultValue) {
        $value = $colors[$key] ?? $defaultValue;
        $clean[$key] = is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? $value : $defaultValue;
    }
    return $clean;
}

function cleanThemeOptions($options) {
    return [
        'header_title_gradient_enabled' => !empty($options['header_title_gradient_enabled'])
    ];
}

function buildThemeSettingsBackup($menuConfigFile) {
    $customCSS = __DIR__ . '/../custom_theme.css';

    return [
        'type' => 'dcs_statistics_dashboard_theme_settings',
        'version' => 1,
        'created_at' => date('c'),
        'theme_colors' => loadThemeColorsFromCss($customCSS),
        'theme_options' => loadThemeOptionsFile($customCSS),
        'chart_colors' => loadChartTheme(),
        'header_image' => loadHeaderImageSettings(),
        'menu' => file_exists($menuConfigFile) ? json_decode(file_get_contents($menuConfigFile), true) : null
    ];
}

function importThemeSettingsBackup($backup, $menuConfigFile) {
    if (!is_array($backup) || ($backup['type'] ?? '') !== 'dcs_statistics_dashboard_theme_settings') {
        return false;
    }

    $colors = cleanThemeColors($backup['theme_colors'] ?? []);
    $options = cleanThemeOptions($backup['theme_options'] ?? []);
    if (file_put_contents(__DIR__ . '/../custom_theme.css', buildCustomThemeCss($colors, $options)) === false) {
        return false;
    }

    if (isset($backup['chart_colors']) && is_array($backup['chart_colors'])) {
        saveChartTheme($backup['chart_colors']);
    }

    if (isset($backup['header_image']) && is_array($backup['header_image'])) {
        saveHeaderImageSettings($backup['header_image']);
    }

    if (isset($backup['menu']) && is_array($backup['menu'])) {
        file_put_contents($menuConfigFile, json_encode($backup['menu'], JSON_PRETTY_PRINT));
    }

    return true;
}

function buildCustomThemeCss($colors, $options = []) {
    $options = array_merge(getDefaultThemeOptions(), $options);
    $cssVars = ":root {\n";
    foreach ($colors as $key => $value) {
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
            $cssVars .= "    --{$key}: {$value};\n";
        }
    }
    $gradientEnabled = !empty($options['header_title_gradient_enabled']);
    $cssVars .= "    --header_title_gradient_enabled: " . ($gradientEnabled ? "1" : "0") . ";\n";
    if ($gradientEnabled) {
        $cssVars .= "    --header_title_background: linear-gradient(135deg, var(--header_text_color) 0%, var(--header_title_gradient_color) 100%);\n";
        $cssVars .= "    --header_title_fill: transparent;\n";
    } else {
        $cssVars .= "    --header_title_background: none;\n";
        $cssVars .= "    --header_title_fill: var(--header_text_color);\n";
    }
    $cssVars .= "}\n\n";

    return $cssVars;

    $cssVars .= <<<'CSS'
body {
    background-color: var(--background_color) !important;
    color: var(--text_color) !important;
}

a,
.player-name a,
.credits-link {
    color: var(--link_color) !important;
}

.main-header {
    border-bottom-color: var(--accent_color) !important;
}

.header-overlay {
    background: linear-gradient(135deg, color-mix(in srgb, var(--primary_color) 82%, transparent) 0%, color-mix(in srgb, var(--accent_color) 14%, transparent) 50%, color-mix(in srgb, var(--primary_color) 90%, transparent) 100%) !important;
}

.site-title,
.dashboard-header h1,
.section-heading h2,
.leaderboard-chart-header h2,
.stat-content h3,
.credits-box h2 {
    color: var(--heading_color) !important;
    text-shadow: 0 0 10px color-mix(in srgb, var(--accent_color) 35%, transparent) !important;
}

.site-subtitle,
.dashboard-subtitle,
.section-heading p,
.server-detail-header p,
.detail-metrics span,
.detail-list-item span,
.detail-list-item small,
.muted,
.text-muted {
    color: var(--muted_text_color) !important;
}

.site-title {
    background: var(--header_title_background, none) !important;
    color: var(--header_text_color) !important;
    -webkit-background-clip: initial !important;
    -webkit-text-fill-color: var(--header_title_fill, var(--header_text_color)) !important;
    background-clip: initial !important;
}

.site-subtitle {
    color: var(--header_subtitle_color) !important;
}

nav,
.main-nav,
.navbar {
    background-color: var(--nav_background_color) !important;
}

nav a,
.main-nav a,
.navbar a {
    color: var(--nav_text_color) !important;
}

nav a:hover,
.main-nav a:hover,
.navbar a:hover {
    color: var(--nav_hover_color) !important;
}

.stat-card,
.leaderboard-chart-panel,
.server-detail-card,
.credits-box,
.trophy-box,
.chart-container,
.table-wrapper {
    background: linear-gradient(135deg, var(--card_color) 0%, var(--card_alt_color) 100%) !important;
    border-color: color-mix(in srgb, var(--accent_color) 36%, transparent) !important;
}

.stat-card:hover,
.leaderboard-chart-panel:hover,
.server-detail-card:hover,
.trophy-box:hover {
    border-color: var(--accent_color) !important;
    box-shadow: 0 12px 40px color-mix(in srgb, var(--accent_color) 25%, transparent) !important;
}

.detail-metrics div,
.detail-split section,
.credits-list a {
    background: color-mix(in srgb, var(--surface_dark_color) 78%, transparent) !important;
    border-color: color-mix(in srgb, var(--border_color) 55%, transparent) !important;
}

table,
#leaderboardTable,
#serversTable {
    background-color: var(--table_row_color) !important;
    color: var(--table_text_color) !important;
    border-color: var(--border_color) !important;
}

th,
#leaderboardTable th,
#serversTable th {
    background-color: var(--table_header_color) !important;
    color: var(--table_header_text_color) !important;
    border-color: var(--accent_color) !important;
}

td,
#leaderboardTable td,
#serversTable td {
    color: var(--table_text_color) !important;
    border-color: color-mix(in srgb, var(--border_color) 65%, transparent) !important;
}

.player-name,
.player-name a,
#leaderboardTable .player-name,
#leaderboardTable .player-name a {
    color: var(--table_player_name_color) !important;
}

tr:hover,
#leaderboardTable tbody tr:hover,
#serversTable tr:hover {
    background-color: var(--table_hover_color) !important;
}

.status-dot,
.detail-status.status-online,
.detail-status.status-running {
    background-color: var(--success_color) !important;
    border-color: color-mix(in srgb, var(--success_color) 50%, transparent) !important;
    color: var(--success_color) !important;
}

.detail-status.status-paused,
.detail-status.status-starting,
.admin-button,
.warning-box {
    border-color: color-mix(in srgb, var(--warning_color) 50%, transparent) !important;
    color: var(--warning_color) !important;
}

.detail-status.status-offline,
.detail-status.status-shutdown,
.alert-error {
    border-color: color-mix(in srgb, var(--danger_color) 50%, transparent) !important;
    color: var(--danger_color) !important;
}

button,
.btn,
input[type="submit"] {
    border-color: var(--border_color);
}

.btn-primary {
    background-color: var(--accent_color) !important;
    border-color: var(--accent_color) !important;
}

.btn-primary:hover,
.credits-link:hover,
.credits-list a:hover {
    color: var(--accent_hover_color) !important;
    border-color: var(--accent_hover_color) !important;
}

footer {
    background-color: var(--footer_background_color) !important;
    color: var(--footer_text_color) !important;
    border-color: var(--border_color) !important;
}
CSS;

    return $cssVars;
}

// Get writable path for menu configuration
function getMenuConfigPath() {
    // Try primary location with data directory
    $primaryPath = __DIR__ . '/data/menu_config.json';
    $primaryDir = dirname($primaryPath);
    
    // Check if directory exists and is writable
    if (is_dir($primaryDir) && is_writable($primaryDir)) {
        return $primaryPath;
    }
    
    // Try to create directory with proper permissions
    if (!is_dir($primaryDir)) {
        @mkdir($primaryDir, 0777, true);
        @chmod($primaryDir, 0777);
        if (is_dir($primaryDir) && is_writable($primaryDir)) {
            return $primaryPath;
        }
    }
    
    // Try alternative location in parent directory
    $altPath = __DIR__ . '/../menu_config.json';
    $altDir = dirname($altPath);
    if (is_writable($altDir)) {
        return $altPath;
    }
    
    // Fall back to temp directory
    $tempDir = sys_get_temp_dir() . '/dcs_stats';
    if (!is_dir($tempDir)) {
        @mkdir($tempDir, 0777, true);
    }
    
    return $tempDir . '/menu_config.json';
}

// Load site features to check if Discord/Squadron links are enabled
require_once __DIR__ . '/../site_features.php';

// Load menu configuration
$menuConfigFile = getMenuConfigPath();
$defaultMenuItems = [
    ['name' => 'Home', 'url' => 'index.php', 'enabled' => true, 'type' => 'page'],
    ['name' => 'Leaderboard', 'url' => 'leaderboard.php', 'enabled' => true, 'type' => 'page'],
    ['name' => 'Pilot Statistics', 'url' => 'pilot_statistics.php', 'enabled' => true, 'type' => 'page'],
    ['name' => 'Pilot Credits', 'url' => 'pilot_credits.php', 'enabled' => true, 'type' => 'page'],
    ['name' => 'Squadrons', 'url' => 'squadrons.php', 'enabled' => true, 'type' => 'page'],
    ['name' => 'Servers', 'url' => 'servers.php', 'enabled' => true, 'type' => 'page']
];

// Add Discord if enabled
if (isFeatureEnabled('show_discord_link')) {
    $defaultMenuItems[] = [
        'name' => 'Discord',
        'url' => getFeatureValue('discord_link_url', 'https://discord.gg/DNENf6pUNX'),
        'enabled' => true,
        'type' => 'discord'
    ];
}

// Add Squadron Homepage if enabled
if (isFeatureEnabled('show_squadron_homepage') && !empty(getFeatureValue('squadron_homepage_url'))) {
    $defaultMenuItems[] = [
        'name' => getFeatureValue('squadron_homepage_text', 'Squadron'),
        'url' => getFeatureValue('squadron_homepage_url'),
        'enabled' => true,
        'type' => 'squadron_homepage'
    ];
}

$menuItems = $defaultMenuItems;
if (file_exists($menuConfigFile)) {
    $savedMenu = json_decode(file_get_contents($menuConfigFile), true);
    if ($savedMenu && is_array($savedMenu)) {
        // Merge with default items to pick up any new Discord/Squadron links
        $savedUrls = array_column($savedMenu, 'url');
        foreach ($defaultMenuItems as $defaultItem) {
            $found = false;
            foreach ($savedMenu as &$savedItem) {
                if ($savedItem['url'] === $defaultItem['url'] || 
                    (isset($savedItem['type']) && isset($defaultItem['type']) && $savedItem['type'] === $defaultItem['type'])) {
                    $found = true;
                    // Update URL for Discord/Squadron links in case they changed
                    if (in_array($defaultItem['type'] ?? '', ['discord', 'squadron_homepage'])) {
                        $savedItem['url'] = $defaultItem['url'];
                        $savedItem['name'] = $defaultItem['name']; // Update name too for squadron
                    }
                    break;
                }
            }
            if (!$found && in_array($defaultItem['type'] ?? '', ['discord', 'squadron_homepage'])) {
                // Add new Discord/Squadron link that wasn't in saved config
                $savedMenu[] = $defaultItem;
            }
        }
        $menuItems = $savedMenu;
    }
}

// Handle theme actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid request token';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'export_theme_settings':
                $backup = buildThemeSettingsBackup($menuConfigFile);
                $fileName = 'dcs-theme-settings-' . date('Y-m-d-H-i-s') . '.json';
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                header('Cache-Control: no-store');
                echo json_encode($backup, JSON_PRETTY_PRINT);
                exit;

            case 'import_theme_settings':
                if (!isset($_FILES['theme_settings_file']) || $_FILES['theme_settings_file']['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Please select a theme settings backup file';
                    break;
                }

                $uploadedFile = $_FILES['theme_settings_file'];
                if ($uploadedFile['size'] > 1048576) {
                    $error = 'Theme settings backup must be less than 1MB';
                    break;
                }

                $backup = json_decode(file_get_contents($uploadedFile['tmp_name']), true);
                if (importThemeSettingsBackup($backup, $menuConfigFile)) {
                    $message = 'Theme settings restored successfully';
                    if (function_exists('logActivity')) {
                        logActivity('THEME_SETTINGS_IMPORT', 'Imported theme settings backup');
                    }
                } else {
                    $error = 'Invalid theme settings backup file';
                }
                break;

            case 'update_menu':
                // Handle menu updates
                $newMenuItems = [];
                $menuNames = $_POST['menu_names'] ?? [];
                $menuUrls = $_POST['menu_urls'] ?? [];
                $menuEnabled = $_POST['menu_enabled'] ?? [];
                $menuOrder = $_POST['menu_order'] ?? [];
                $menuTypes = $_POST['menu_types'] ?? [];
                
                // Rebuild menu items based on posted data
                foreach ($menuOrder as $index) {
                    if (isset($menuNames[$index]) && isset($menuUrls[$index])) {
                        $newMenuItems[] = [
                            'name' => $menuNames[$index],
                            'url' => $menuUrls[$index],
                            'enabled' => isset($menuEnabled[$index]),
                            'type' => $menuTypes[$index] ?? 'page'
                        ];
                    }
                }
                
                // Save to file
                $result = @file_put_contents($menuConfigFile, json_encode($newMenuItems, JSON_PRETTY_PRINT));
                if ($result === false) {
                    $error = 'Failed to save menu configuration. Please check file permissions.';
                } else {
                    $menuItems = $newMenuItems;
                    $message = 'Menu configuration updated successfully';
                    if (function_exists('logActivity')) {
                        logActivity('MENU_UPDATE', 'Updated navigation menu configuration');
                    }
                }
                break;
                
            case 'upload_css':
                // Only Air Boss can upload CSS
                if (!$isAirBoss) {
                    $error = 'Only Air Boss can upload custom CSS files';
                    break;
                }
                // Handle CSS file upload
                if (isset($_FILES['css_file']) && $_FILES['css_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadedFile = $_FILES['css_file'];
                    $fileName = $uploadedFile['name'];
                    $fileTmp = $uploadedFile['tmp_name'];
                    $fileSize = $uploadedFile['size'];
                    
                    // Validate file type
                    $allowedTypes = ['text/css', 'text/plain'];
                    $fileType = mime_content_type($fileTmp);
                    
                    if (!in_array($fileType, $allowedTypes) || !preg_match('/\.css$/i', $fileName)) {
                        $error = 'Please upload a valid CSS file';
                    } elseif ($fileSize > 1048576) { // 1MB limit
                        $error = 'CSS file size must be less than 1MB';
                    } else {
                        // Backup current CSS
                        $currentCSS = __DIR__ . '/../styles.css';
                        $backupDir = __DIR__ . '/theme_backups';
                        
                        if (!is_dir($backupDir)) {
                            mkdir($backupDir, 0755, true);
                        }
                        
                        $backupFile = $backupDir . '/styles_' . date('Y-m-d_H-i-s') . '.css';
                        copy($currentCSS, $backupFile);
                        
                        // Upload new CSS
                        if (move_uploaded_file($fileTmp, $currentCSS)) {
                            $message = 'CSS file uploaded successfully. Previous version backed up.';
                            if (function_exists('logActivity')) {
                                logActivity('THEME_UPLOAD', 'Uploaded new CSS file: ' . $fileName);
                            }
                        } else {
                            $error = 'Failed to upload CSS file';
                        }
                    }
                } else {
                    $error = 'Please select a CSS file to upload';
                }
                break;
                
            case 'update_colors':
                // Handle color updates
                $colors = [];
                foreach (getDefaultThemeColors() as $key => $defaultValue) {
                    $colors[$key] = $_POST[$key] ?? $defaultValue;
                }
                $themeOptions = [
                    'header_title_gradient_enabled' => isset($_POST['header_title_gradient_enabled'])
                ];
                
                // Save to custom theme file
                $customCSS = __DIR__ . '/../custom_theme.css';
                file_put_contents($customCSS, buildCustomThemeCss($colors, $themeOptions));
                
                $message = 'Color theme updated successfully';
                if (function_exists('logActivity')) {
                    logActivity('THEME_COLORS', 'Updated theme colors');
                }
                break;

            case 'update_header_image':
                $headerSettings = loadHeaderImageSettings();
                $headerSettings['position_x'] = (int)($_POST['position_x'] ?? 50);
                $headerSettings['position_y'] = (int)($_POST['position_y'] ?? 50);
                if (isset($_POST['use_default_header_image'])) {
                    $headerSettings['image'] = getDefaultHeaderImageSettings()['image'];
                }

                if (!isset($_POST['use_default_header_image']) && isset($_FILES['header_image']) && $_FILES['header_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadedFile = $_FILES['header_image'];
                    $fileTmp = $uploadedFile['tmp_name'];
                    $fileSize = $uploadedFile['size'];
                    $fileType = mime_content_type($fileTmp);
                    $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
                    $allowedTypes = [
                        'jpg' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'png' => 'image/png',
                        'webp' => 'image/webp'
                    ];

                    if (!isset($allowedTypes[$extension]) || $allowedTypes[$extension] !== $fileType) {
                        $error = 'Please upload a JPG, PNG, or WebP header image';
                        break;
                    }

                    if ($fileSize > 5242880) {
                        $error = 'Header image must be less than 5MB';
                        break;
                    }

                    $uploadDir = __DIR__ . '/../uploads';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $targetName = 'header-image.' . ($extension === 'jpeg' ? 'jpg' : $extension);
                    $targetPath = $uploadDir . '/' . $targetName;
                    if (!move_uploaded_file($fileTmp, $targetPath)) {
                        $error = 'Failed to upload header image';
                        break;
                    }

                    $headerSettings['image'] = 'uploads/' . $targetName;
                }

                if (saveHeaderImageSettings($headerSettings)) {
                    $message = 'Header image settings updated successfully';
                    if (function_exists('logActivity')) {
                        logActivity('HEADER_IMAGE_UPDATE', 'Updated header image settings');
                    }
                } else {
                    $error = 'Failed to save header image settings';
                }
                break;

            case 'update_chart_colors':
                $chartTheme = [];
                foreach (getDefaultChartTheme() as $key => $defaultValue) {
                    $chartTheme[$key] = $_POST[$key] ?? $defaultValue;
                }

                if (saveChartTheme($chartTheme)) {
                    $message = 'Leaderboard chart colours updated successfully';
                    if (function_exists('logActivity')) {
                        logActivity('CHART_THEME_COLORS', 'Updated leaderboard chart colours');
                    }
                } else {
                    $error = 'Failed to save leaderboard chart colours';
                }
                break;
                
            case 'restore_backup':
                // Only Air Boss can restore backups
                if (!$isAirBoss) {
                    $error = 'Only Air Boss can restore theme backups';
                    break;
                }
                // Restore from backup
                $backupFile = $_POST['backup_file'] ?? '';
                $backupPath = __DIR__ . '/theme_backups/' . basename($backupFile);
                
                if (file_exists($backupPath)) {
                    $currentCSS = __DIR__ . '/../styles.css';
                    copy($backupPath, $currentCSS);
                    $message = 'Theme restored from backup';
                    if (function_exists('logActivity')) {
                        logActivity('THEME_RESTORE', 'Restored theme from: ' . $backupFile);
                    }
                } else {
                    $error = 'Backup file not found';
                }
                break;
        }
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Get list of backup files
$backupDir = __DIR__ . '/theme_backups';
$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if (preg_match('/^styles_.*\.css$/', $file)) {
            $backups[] = [
                'filename' => $file,
                'date' => filemtime($backupDir . '/' . $file),
                'size' => filesize($backupDir . '/' . $file)
            ];
        }
    }
    // Sort by date descending
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

// Load current custom colors if they exist
$customCSS = __DIR__ . '/../custom_theme.css';
$customColors = loadThemeColorsFromCss($customCSS);
$themeOptions = loadThemeOptionsFile($customCSS);

$chartColors = loadChartTheme();
$headerImageSettings = loadHeaderImageSettings();
$headerPreviewImage = '../' . ltrim($headerImageSettings['image'], '/');

// Page title
$pageTitle = 'Theme Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Carrier Air Wing Command</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        /* Critical inline CSS for layout */
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; overflow-x: hidden; }
        .admin-wrapper { display: flex; min-height: 100vh; width: 100%; overflow-x: hidden; }
        .admin-sidebar { width: 250px; flex-shrink: 0; background: #2a2a2a; }
        .admin-main { flex: 1; min-width: 0; overflow-x: hidden; }
        .admin-content { padding: 30px; max-width: 100%; overflow-x: hidden; }
        .card { max-width: 100%; overflow-x: auto; }
        
        .theme-section {
            background: var(--bg-secondary);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .color-inputs {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
            max-width: 900px;
        }

        .color-fieldset {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin: 22px 0;
            padding: 16px;
        }

        .color-fieldset legend {
            color: var(--accent-primary);
            font-weight: 700;
            padding: 0 8px;
        }
        
        .color-input-group {
            display: flex;
            align-items: center;
            background: var(--bg-tertiary);
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }
        
        .color-input-group:hover {
            border-color: var(--accent-primary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .color-input-group label {
            flex: 1;
            font-size: 0.95em;
            font-weight: 500;
            cursor: pointer;
        }
        
        .color-input-group input[type="color"] {
            width: 60px;
            height: 40px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            cursor: pointer;
            padding: 2px;
            background: var(--bg-secondary);
            transition: all 0.2s ease;
        }
        
        .color-input-group input[type="color"]:hover {
            border-color: var(--accent-primary);
            transform: scale(1.05);
        }
        
        .color-input-group input[type="color"]::-webkit-color-swatch {
            border-radius: 4px;
            border: none;
        }
        
        .color-input-group input[type="color"]::-moz-color-swatch {
            border-radius: 4px;
            border: none;
        }
        
        @media (max-width: 600px) {
            .color-inputs {
                grid-template-columns: 1fr;
            }
        }
        
        .upload-section {
            margin-top: 20px;
        }

        .header-image-preview {
            align-items: flex-end;
            aspect-ratio: 24 / 5;
            background-color: #111;
            background-repeat: no-repeat;
            background-size: cover;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            display: flex;
            margin: 20px 0;
            max-width: 960px;
            min-height: 150px;
            overflow: hidden;
            padding: 16px;
        }

        .header-image-preview span {
            background: rgba(0, 0, 0, 0.68);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 6px;
            color: #fff;
            font-weight: 700;
            padding: 8px 12px;
        }

        .header-position-controls {
            display: grid;
            gap: 16px;
            margin-top: 20px;
            max-width: 720px;
        }

        .header-position-controls label {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            display: grid;
            font-weight: 600;
            gap: 8px;
            padding: 14px 16px;
        }

        .header-position-controls input[type="range"] {
            width: 100%;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-button {
            display: inline-block;
            padding: 10px 20px;
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-input-button:hover {
            background: var(--bg-primary);
        }
        
        .backup-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin-bottom: 5px;
            background: var(--bg-tertiary);
            border-radius: 4px;
        }
        
        .backup-info {
            flex: 1;
        }
        
        .preview-frame {
            width: 100%;
            height: 500px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: #fff;
        }
        
        .theme-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .theme-tab {
            padding: 10px 20px;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }
        
        .theme-tab.active {
            color: var(--text-primary);
            border-bottom-color: var(--accent-primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Menu Configuration Styles */
        .menu-items {
            margin-top: 20px;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            margin-bottom: 10px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .menu-item.dragging {
            opacity: 0.5;
        }
        
        .menu-item.drag-over {
            border-color: var(--accent-primary);
            border-style: dashed;
        }
        
        .menu-item-handle {
            cursor: grab;
            font-size: 20px;
            color: var(--text-muted);
            user-select: none;
        }
        
        .menu-item-handle:active {
            cursor: grabbing;
        }
        
        .menu-item-fields {
            display: flex;
            gap: 15px;
            flex: 1;
            align-items: center;
        }
        
        .menu-item-fields input[type="text"] {
            flex: 1;
            padding: 8px 12px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-primary);
        }
        
        .menu-item-fields input[type="text"]:focus {
            border-color: var(--accent-primary);
            outline: none;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
        }
        
        .checkbox-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
    </style>
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <?php include __DIR__ . '/nav.php'; ?>
        
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <h1><?= $pageTitle ?></h1>
                <div class="admin-user-menu">
                    <div class="admin-user-info">
                        <div class="admin-username"><?= e($currentAdmin['username']) ?></div>
                        <div class="admin-role"><?= getRoleBadge($currentAdmin['role']) ?></div>
                    </div>
                    <a href="logout.php" class="btn btn-secondary btn-small">Logout</a>
                </div>
            </header>
            
            <!-- Content -->
            <div class="admin-content">
                <div class="card">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <!-- Theme Preview Section -->
                <div class="theme-section">
                    <h2>Theme Preview</h2>
                    <p>Preview how the site looks with current theme settings:</p>
                    
                    <?php
                    // Build initial preview URL with current colors
                    $previewParams = [
                        'preview' => '1',
                    ];
                    foreach ($customColors as $colorKey => $colorValue) {
                        $previewParams[$colorKey] = substr($colorValue, 1);
                    }
                    $previewParams['header_title_gradient_enabled'] = !empty($themeOptions['header_title_gradient_enabled']) ? '1' : '0';
                    // Build URL to parent directory
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                    $host = $_SERVER['HTTP_HOST'];
                    $currentPath = dirname($_SERVER['SCRIPT_NAME']);
                    $parentPath = dirname($currentPath);
                    $previewUrl = $protocol . $host . ($parentPath === '/' ? '' : $parentPath) . '/index.php?' . http_build_query($previewParams);
                    ?>
                    <iframe src="<?= $previewUrl ?>" class="preview-frame" id="preview-frame"></iframe>
                    
                    <div style="margin-top: 10px;">
                        <span id="preview-status" style="color: var(--text-muted); font-size: 0.9em;"></span>
                    </div>
                </div>
                
                <!-- Theme Tabs -->
                <div class="theme-tabs">
                    <button class="theme-tab active" onclick="switchTab('simple')">Simple Customization</button>
                    <button class="theme-tab" onclick="switchTab('header-image')">Header Image</button>
                    <button class="theme-tab" onclick="switchTab('menu')">Menu Configuration</button>
                    <button class="theme-tab" onclick="switchTab('charts')">Chart Colours</button>
                    <?php if ($isAirBoss): ?>
                    <button class="theme-tab" onclick="switchTab('advanced')">Advanced CSS Upload</button>
                    <button class="theme-tab" onclick="switchTab('backups')">Backup & Restore</button>
                    <?php endif; ?>
                </div>
                
                <!-- Simple Customization Tab -->
                <div id="simple-tab" class="tab-content active">
                    <div class="theme-section">
                        <h2>Colour Customization</h2>
                        <p>Control the site colours so each squadron can match its own branding.</p>
                        <p style="font-size: 0.9em; color: var(--text-muted); margin-top: 10px;">
                            Tip: Click any colour swatch to change it. Use the preview above before saving.
                        </p>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="update_colors">
                            
                        <?php foreach (getThemeColorGroups() as $groupName => $fields): ?>
                            <fieldset class="color-fieldset">
                                <legend><?= htmlspecialchars($groupName) ?></legend>
                                <?php if ($groupName === 'Header & Navigation'): ?>
                                    <div class="color-input-group" style="margin-bottom: 14px;">
                                        <label for="header_title_gradient_enabled">Header Title Soft Gradient:</label>
                                        <input type="checkbox" id="header_title_gradient_enabled" name="header_title_gradient_enabled"
                                               <?= !empty($themeOptions['header_title_gradient_enabled']) ? 'checked' : '' ?>>
                                    </div>
                                <?php endif; ?>
                                <div class="color-inputs">
                                        <?php foreach ($fields as $fieldKey => $label): ?>
                                            <div class="color-input-group">
                                                <label for="<?= htmlspecialchars($fieldKey) ?>"><?= htmlspecialchars($label) ?>:</label>
                                                <input type="color" id="<?= htmlspecialchars($fieldKey) ?>" name="<?= htmlspecialchars($fieldKey) ?>" 
                                                       value="<?= htmlspecialchars($customColors[$fieldKey]) ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>
                            <?php endforeach; ?>
                            
                            <div style="margin-top: 20px; display: flex; gap: 10px;">
                                <button type="submit" class="btn btn-primary">Update Colours</button>
                                <button type="button" class="btn btn-secondary" onclick="restoreDefaultColors()">Restore Defaults</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="header-image-tab" class="tab-content">
                    <div class="theme-section">
                        <h2>Header Image</h2>
                        <p>Upload a wide header image and choose which part stays in view. The image keeps its proportions and is cropped to fit the header.</p>
                        <p style="font-size: 0.9em; color: var(--text-muted); margin-top: 10px;">
                            Best fit: 2400 x 500 pixels or wider, JPG/PNG/WebP, under 5MB.
                        </p>

                        <div class="header-image-preview"
                             style="background-image: url('<?= htmlspecialchars($headerPreviewImage) ?>'); background-position: <?= (int)$headerImageSettings['position_x'] ?>% <?= (int)$headerImageSettings['position_y'] ?>%;">
                            <span>Current Header Framing</span>
                        </div>

                        <form method="POST" action="" enctype="multipart/form-data" class="upload-section">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="update_header_image">

                            <div class="file-input-wrapper">
                                <input type="file" name="header_image" id="header_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                <label for="header_image" class="file-input-button">Choose Header Image</label>
                            </div>
                            <span id="header-image-file-name" style="margin-left: 10px;">No new image selected</span>

                            <div class="color-input-group" style="margin-top: 18px; max-width: 420px;">
                                <label for="use_default_header_image">Use Default Header Image:</label>
                                <input type="checkbox" id="use_default_header_image" name="use_default_header_image">
                            </div>

                            <div class="header-position-controls">
                                <label for="position_x">
                                    Horizontal Position
                                    <input type="range" id="position_x" name="position_x" min="0" max="100" value="<?= (int)$headerImageSettings['position_x'] ?>">
                                </label>
                                <label for="position_y">
                                    Vertical Position
                                    <input type="range" id="position_y" name="position_y" min="0" max="100" value="<?= (int)$headerImageSettings['position_y'] ?>">
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary" style="margin-top: 20px;">Update Header Image</button>
                        </form>
                    </div>
                </div>
                
                <!-- Chart Colours Tab -->
                <div id="charts-tab" class="tab-content">
                    <div class="theme-section">
                        <h2>Chart Colours</h2>
                        <p>Customize the colours used by the leaderboard and homepage charts.</p>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="update_chart_colors">
                            
                            <fieldset class="color-fieldset">
                                <legend>Leaderboard Chart</legend>
                                <div class="color-inputs">
                                    <div class="color-input-group">
                                        <label for="chart_primary_color" title="Main chart fill colour">Chart Primary:</label>
                                        <input type="color" id="chart_primary_color" name="chart_primary_color" 
                                               value="<?= htmlspecialchars($chartColors['chart_primary_color']) ?>">
                                    </div>
                                    
                                    <div class="color-input-group">
                                        <label for="chart_secondary_color" title="Line and border colour">Chart Secondary:</label>
                                        <input type="color" id="chart_secondary_color" name="chart_secondary_color" 
                                               value="<?= htmlspecialchars($chartColors['chart_secondary_color']) ?>">
                                    </div>
                                    
                                    <div class="color-input-group">
                                        <label for="chart_grid_color" title="Chart grid line colour">Grid Lines:</label>
                                        <input type="color" id="chart_grid_color" name="chart_grid_color" 
                                               value="<?= htmlspecialchars($chartColors['chart_grid_color']) ?>">
                                    </div>
                                    
                                    <div class="color-input-group">
                                        <label for="chart_text_color" title="Chart label and legend colour">Chart Text:</label>
                                        <input type="color" id="chart_text_color" name="chart_text_color" 
                                               value="<?= htmlspecialchars($chartColors['chart_text_color']) ?>">
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset class="color-fieldset">
                                <legend>Homepage: Top 5 Pilots</legend>
                                <div class="color-inputs">
                                    <div class="color-input-group">
                                        <label for="home_top_pilots_color" title="Top pilots bar colour">Bars:</label>
                                        <input type="color" id="home_top_pilots_color" name="home_top_pilots_color" 
                                               value="<?= htmlspecialchars($chartColors['home_top_pilots_color']) ?>">
                                    </div>

                                    <div class="color-input-group">
                                        <label for="home_top_pilots_grid_color" title="Top pilots grid line colour">Grid Lines:</label>
                                        <input type="color" id="home_top_pilots_grid_color" name="home_top_pilots_grid_color" 
                                               value="<?= htmlspecialchars($chartColors['home_top_pilots_grid_color']) ?>">
                                    </div>

                                    <div class="color-input-group">
                                        <label for="home_top_pilots_text_color" title="Top pilots labels and tooltip text colour">Text:</label>
                                        <input type="color" id="home_top_pilots_text_color" name="home_top_pilots_text_color" 
                                               value="<?= htmlspecialchars($chartColors['home_top_pilots_text_color']) ?>">
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset class="color-fieldset">
                                <legend>Homepage: Combat Statistics</legend>
                                <div class="color-inputs">
                                    <div class="color-input-group">
                                        <label for="home_combat_kills_color" title="Combat kills slice colour">Kills:</label>
                                        <input type="color" id="home_combat_kills_color" name="home_combat_kills_color" 
                                               value="<?= htmlspecialchars($chartColors['home_combat_kills_color']) ?>">
                                    </div>

                                    <div class="color-input-group">
                                        <label for="home_combat_deaths_color" title="Combat deaths slice colour">Deaths:</label>
                                        <input type="color" id="home_combat_deaths_color" name="home_combat_deaths_color" 
                                               value="<?= htmlspecialchars($chartColors['home_combat_deaths_color']) ?>">
                                    </div>

                                    <div class="color-input-group">
                                        <label for="home_combat_text_color" title="Combat chart legend and tooltip text colour">Text:</label>
                                        <input type="color" id="home_combat_text_color" name="home_combat_text_color" 
                                               value="<?= htmlspecialchars($chartColors['home_combat_text_color']) ?>">
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset class="color-fieldset">
                                <legend>Homepage: Top Squadrons</legend>
                                <div class="color-inputs">
                                    <div class="color-input-group">
                                        <label for="home_squadrons_color" title="Top squadrons bar colour">Bars:</label>
                                        <input type="color" id="home_squadrons_color" name="home_squadrons_color" 
                                               value="<?= htmlspecialchars($chartColors['home_squadrons_color']) ?>">
                                    </div>

                                    <div class="color-input-group">
                                        <label for="home_squadrons_grid_color" title="Top squadrons grid line colour">Grid Lines:</label>
                                        <input type="color" id="home_squadrons_grid_color" name="home_squadrons_grid_color" 
                                               value="<?= htmlspecialchars($chartColors['home_squadrons_grid_color']) ?>">
                                    </div>

                                    <div class="color-input-group">
                                        <label for="home_squadrons_text_color" title="Top squadrons labels and tooltip text colour">Text:</label>
                                        <input type="color" id="home_squadrons_text_color" name="home_squadrons_text_color" 
                                               value="<?= htmlspecialchars($chartColors['home_squadrons_text_color']) ?>">
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset class="color-fieldset">
                                <legend>Homepage: Player Activity</legend>
                                <div class="color-inputs">
                                    <div class="color-input-group">
                                        <label for="home_activity_color" title="Player activity line and fill colour">Line and Fill:</label>
                                        <input type="color" id="home_activity_color" name="home_activity_color" 
                                               value="<?= htmlspecialchars($chartColors['home_activity_color']) ?>">
                                    </div>

                                    <div class="color-input-group">
                                        <label for="home_activity_grid_color" title="Player activity grid line colour">Grid Lines:</label>
                                        <input type="color" id="home_activity_grid_color" name="home_activity_grid_color" 
                                               value="<?= htmlspecialchars($chartColors['home_activity_grid_color']) ?>">
                                    </div>

                                    <div class="color-input-group">
                                        <label for="home_activity_text_color" title="Player activity labels and tooltip text colour">Text:</label>
                                        <input type="color" id="home_activity_text_color" name="home_activity_text_color" 
                                               value="<?= htmlspecialchars($chartColors['home_activity_text_color']) ?>">
                                    </div>
                                </div>
                            </fieldset>
                            
                            <div style="margin-top: 20px; display: flex; gap: 10px;">
                                <button type="submit" class="btn btn-primary">Update Chart Colours</button>
                                <button type="button" class="btn btn-secondary" onclick="restoreDefaultChartColors()">Restore Defaults</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Menu Configuration Tab -->
                <div id="menu-tab" class="tab-content">
                    <div class="theme-section">
                        <h2>Navigation Menu Configuration</h2>
                        <p>Customize the navigation menu by renaming items, changing their order, or hiding them.</p>
                        
                        <form method="POST" action="" id="menu-form">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="update_menu">
                            
                            <div class="menu-items" id="menu-items">
                                <?php foreach ($menuItems as $index => $item): ?>
                                <div class="menu-item" data-index="<?= $index ?>">
                                    <div class="menu-item-handle">☰</div>
                                    <input type="hidden" name="menu_order[]" value="<?= $index ?>">
                                    <input type="hidden" name="menu_types[<?= $index ?>]" value="<?= htmlspecialchars($item['type'] ?? 'page') ?>">
                                    <div class="menu-item-fields">
                                        <input type="text" name="menu_names[<?= $index ?>]" value="<?= htmlspecialchars($item['name']) ?>" placeholder="Menu Name" required>
                                        <?php if (in_array($item['type'] ?? 'page', ['discord', 'squadron_homepage'])): ?>
                                            <input type="text" name="menu_urls[<?= $index ?>]" value="<?= htmlspecialchars($item['url']) ?>" placeholder="URL" required title="External URL">
                                        <?php else: ?>
                                            <input type="text" name="menu_urls[<?= $index ?>]" value="<?= htmlspecialchars($item['url']) ?>" placeholder="URL" required readonly>
                                        <?php endif; ?>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="menu_enabled[<?= $index ?>]" <?= $item['enabled'] ? 'checked' : '' ?>>
                                            <span>Enabled</span>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="margin-top: 20px;">Save Menu Configuration</button>
                            <button type="button" class="btn btn-secondary" onclick="resetMenu()" style="margin-top: 20px;">Reset to Default</button>
                        </form>
                    </div>
                </div>
                
                <?php if ($isAirBoss): ?>
                <!-- Advanced CSS Upload Tab -->
                <div id="advanced-tab" class="tab-content">
                    <div class="theme-section">
                        <h2>Upload Custom CSS</h2>
                        <div class="alert alert-warning">
                            <strong>Warning:</strong> Uploading a new CSS file will completely replace the current site styling. 
                            Make sure to backup the current theme first!
                        </div>
                        
                        <form method="POST" action="" enctype="multipart/form-data" class="upload-section">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="upload_css">
                            
                            <div class="file-input-wrapper">
                                <input type="file" name="css_file" id="css_file" accept=".css">
                                <label for="css_file" class="file-input-button">Choose CSS File</label>
                            </div>
                            <span id="file-name" style="margin-left: 10px;">No file selected</span>
                            
                            <div style="margin-top: 20px;">
                                <button type="submit" class="btn btn-primary">Upload CSS</button>
                            </div>
                        </form>
                        
                        <div style="margin-top: 30px;">
                            <h3>CSS Guidelines</h3>
                            <ul>
                                <li>Maximum file size: 1MB</li>
                                <li>Use CSS variables for easy color management</li>
                                <li>Test thoroughly before uploading</li>
                                <li>Ensure mobile responsiveness</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Backup & Restore Tab -->
                <div id="backups-tab" class="tab-content">
                    <div class="theme-section">
                        <h2>Theme Settings Backup</h2>
                        <p>Download the current theme settings, then upload the same file later to restore them.</p>

                        <div class="backup-list">
                            <div class="backup-item">
                                <div class="backup-info">
                                    <strong>Current Theme Settings</strong><br>
                                    <small>Includes colour controls, chart colours, header image framing, and menu configuration.</small>
                                </div>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="export_theme_settings">
                                    <button type="submit" class="btn btn-primary btn-sm">Download Backup</button>
                                </form>
                            </div>
                        </div>

                        <form method="POST" action="" enctype="multipart/form-data" class="upload-section">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="import_theme_settings">

                            <div class="file-input-wrapper">
                                <input type="file" name="theme_settings_file" id="theme_settings_file" accept=".json,application/json">
                                <label for="theme_settings_file" class="file-input-button">Choose Settings Backup</label>
                            </div>
                            <span id="theme-settings-file-name" style="margin-left: 10px;">No file selected</span>

                            <div style="margin-top: 20px;">
                                <button type="submit" class="btn btn-primary"
                                        onclick="return confirm('Restore these theme settings? This will replace the current theme configuration.')">
                                    Upload and Restore
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="theme-section">
                        <h2>Advanced CSS Backups</h2>
                        <p>These are automatic backups made when replacing the raw CSS file.</p>
                        
                        <?php if (empty($backups)): ?>
                            <p>No backups found.</p>
                        <?php else: ?>
                            <div class="backup-list">
                                <?php foreach ($backups as $backup): ?>
                                    <div class="backup-item">
                                        <div class="backup-info">
                                            <strong><?= htmlspecialchars($backup['filename']) ?></strong><br>
                                            <small>
                                                Created: <?= date('Y-m-d H:i:s', $backup['date']) ?> | 
                                                Size: <?= number_format($backup['size'] / 1024, 2) ?> KB
                                            </small>
                                        </div>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="action" value="restore_backup">
                                            <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['filename']) ?>">
                                            <button type="submit" class="btn btn-sm" 
                                                    onclick="return confirm('Are you sure you want to restore this backup?')">
                                                Restore
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Tab switching
        function switchTab(tabName) {
            // Remove active class from all tabs and contents
            document.querySelectorAll('.theme-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to selected tab and content
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }
        
        // File input handling
        const cssFileInput = document.getElementById('css_file');
        if (cssFileInput) {
            cssFileInput.addEventListener('change', function(e) {
                const fileName = e.target.files[0]?.name || 'No file selected';
                document.getElementById('file-name').textContent = fileName;
            });
        }

        const headerImageInput = document.getElementById('header_image');
        const headerImagePreview = document.querySelector('.header-image-preview');
        const positionX = document.getElementById('position_x');
        const positionY = document.getElementById('position_y');
        const useDefaultHeaderImage = document.getElementById('use_default_header_image');
        const defaultHeaderImageUrl = '../dcs-header-image.jpg';

        function updateHeaderImagePreview() {
            if (!headerImagePreview || !positionX || !positionY) return;
            headerImagePreview.style.backgroundPosition = `${positionX.value}% ${positionY.value}%`;
        }

        if (headerImageInput) {
            headerImageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                document.getElementById('header-image-file-name').textContent = file?.name || 'No new image selected';
                if (file && headerImagePreview) {
                    if (useDefaultHeaderImage) useDefaultHeaderImage.checked = false;
                    headerImagePreview.style.backgroundImage = `url('${URL.createObjectURL(file)}')`;
                    updateHeaderImagePreview();
                }
            });
        }

        const themeSettingsInput = document.getElementById('theme_settings_file');
        if (themeSettingsInput) {
            themeSettingsInput.addEventListener('change', function(e) {
                const fileName = e.target.files[0]?.name || 'No file selected';
                document.getElementById('theme-settings-file-name').textContent = fileName;
            });
        }

        if (useDefaultHeaderImage) {
            useDefaultHeaderImage.addEventListener('change', function() {
                if (this.checked && headerImagePreview) {
                    headerImagePreview.style.backgroundImage = `url('${defaultHeaderImageUrl}')`;
                    document.getElementById('header-image-file-name').textContent = 'Default image selected';
                    if (headerImageInput) headerImageInput.value = '';
                    updateHeaderImagePreview();
                }
            });
        }

        [positionX, positionY].forEach(input => {
            if (input) {
                input.addEventListener('input', updateHeaderImagePreview);
            }
        });
        
        
        // Debounce function to prevent too many updates
        let updateTimeout;
        function debounce(func, wait) {
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(updateTimeout);
                    func(...args);
                };
                clearTimeout(updateTimeout);
                updateTimeout = setTimeout(later, wait);
            };
        }
        
        // Live color preview
        function updatePreviewColors() {
            const iframe = document.getElementById('preview-frame');
            
            // Build URL with preview parameters
            const params = new URLSearchParams();
            params.set('preview', '1');
            document.querySelectorAll('#simple-tab input[type="color"]').forEach(input => {
                params.set(input.id, input.value.replace('#', ''));
            });
            const titleGradient = document.getElementById('header_title_gradient_enabled');
            if (titleGradient) {
                params.set('header_title_gradient_enabled', titleGradient.checked ? '1' : '0');
            }
            
            // Update iframe source with preview parameters
            // Build URL to parent directory  
            const protocol = window.location.protocol;
            const host = window.location.host;
            const currentPath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
            const parentPath = currentPath.substring(0, currentPath.lastIndexOf('/'));
            const baseUrl = protocol + '//' + host + parentPath + '/index.php';
            iframe.src = baseUrl + '?' + params.toString();
        }
        
        // Debounced version of updatePreviewColors
        const debouncedUpdate = debounce(updatePreviewColors, 500);
        
        // Add event listeners for real-time updates
        document.querySelectorAll('input[type="color"]').forEach(input => {
            input.addEventListener('input', function() {
                // Show status message immediately
                const status = document.getElementById('preview-status');
                status.textContent = '⏳ Updating preview...';
                status.style.color = '#ff9800';
                
                // Update preview with debounce
                debouncedUpdate();
            });
            
            input.addEventListener('change', function() {
                // Show completed message
                const status = document.getElementById('preview-status');
                status.textContent = '✨ Preview updated';
                status.style.color = '#4CAF50';
                setTimeout(() => {
                    status.textContent = '';
                }, 2000);
            });
        });

        const titleGradientToggle = document.getElementById('header_title_gradient_enabled');
        if (titleGradientToggle) {
            titleGradientToggle.addEventListener('change', function() {
                updatePreviewColors();
                const status = document.getElementById('preview-status');
                status.textContent = '✨ Preview updated';
                status.style.color = '#4CAF50';
                setTimeout(() => {
                    status.textContent = '';
                }, 2000);
            });
        }
        
        // Menu drag and drop functionality
        let draggedElement = null;
        
        function initMenuDragDrop() {
            const menuItems = document.querySelectorAll('.menu-item');
            
            menuItems.forEach(item => {
                item.draggable = true;
                
                item.addEventListener('dragstart', function(e) {
                    draggedElement = this;
                    this.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/html', this.innerHTML);
                });
                
                item.addEventListener('dragend', function() {
                    this.classList.remove('dragging');
                });
                
                item.addEventListener('dragover', function(e) {
                    if (e.preventDefault) {
                        e.preventDefault();
                    }
                    e.dataTransfer.dropEffect = 'move';
                    this.classList.add('drag-over');
                    return false;
                });
                
                item.addEventListener('dragleave', function() {
                    this.classList.remove('drag-over');
                });
                
                item.addEventListener('drop', function(e) {
                    if (e.stopPropagation) {
                        e.stopPropagation();
                    }
                    
                    this.classList.remove('drag-over');
                    
                    if (draggedElement !== this) {
                        const container = document.getElementById('menu-items');
                        const allItems = Array.from(container.querySelectorAll('.menu-item'));
                        const draggedIndex = allItems.indexOf(draggedElement);
                        const targetIndex = allItems.indexOf(this);
                        
                        if (draggedIndex < targetIndex) {
                            this.parentNode.insertBefore(draggedElement, this.nextSibling);
                        } else {
                            this.parentNode.insertBefore(draggedElement, this);
                        }
                        
                        // Update order inputs
                        updateMenuOrder();
                    }
                    
                    return false;
                });
            });
        }
        
        function updateMenuOrder() {
            const menuItems = document.querySelectorAll('.menu-item');
            menuItems.forEach((item, index) => {
                const orderInput = item.querySelector('input[name="menu_order[]"]');
                orderInput.value = index;
                
                // Update field names to match new order
                const nameInput = item.querySelector('input[name^="menu_names"]');
                const urlInput = item.querySelector('input[name^="menu_urls"]');
                const enabledInput = item.querySelector('input[name^="menu_enabled"]');
                const typeInput = item.querySelector('input[name^="menu_types"]');
                
                nameInput.name = `menu_names[${index}]`;
                urlInput.name = `menu_urls[${index}]`;
                enabledInput.name = `menu_enabled[${index}]`;
                typeInput.name = `menu_types[${index}]`;
            });
        }
        
        function resetMenu() {
            if (confirm('Are you sure you want to reset the menu to default settings?')) {
                // Create a form to submit reset action
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="update_menu">
                    <?php 
                    // Rebuild default menu items for reset
                    $resetMenuItems = [
                        ['name' => 'Home', 'url' => 'index.php', 'enabled' => true, 'type' => 'page'],
                        ['name' => 'Leaderboard', 'url' => 'leaderboard.php', 'enabled' => true, 'type' => 'page'],
                        ['name' => 'Pilot Statistics', 'url' => 'pilot_statistics.php', 'enabled' => true, 'type' => 'page'],
                        ['name' => 'Pilot Credits', 'url' => 'pilot_credits.php', 'enabled' => true, 'type' => 'page'],
                        ['name' => 'Squadrons', 'url' => 'squadrons.php', 'enabled' => true, 'type' => 'page'],
                        ['name' => 'Servers', 'url' => 'servers.php', 'enabled' => true, 'type' => 'page']
                    ];
                    if (isFeatureEnabled('show_discord_link')) {
                        $resetMenuItems[] = ['name' => 'Discord', 'url' => getFeatureValue('discord_link_url', 'https://discord.gg/DNENf6pUNX'), 'enabled' => true, 'type' => 'discord'];
                    }
                    if (isFeatureEnabled('show_squadron_homepage') && !empty(getFeatureValue('squadron_homepage_url'))) {
                        $resetMenuItems[] = ['name' => getFeatureValue('squadron_homepage_text', 'Squadron'), 'url' => getFeatureValue('squadron_homepage_url'), 'enabled' => true, 'type' => 'squadron_homepage'];
                    }
                    foreach ($resetMenuItems as $index => $item): ?>
                    <input type="hidden" name="menu_order[]" value="<?= $index ?>">
                    <input type="hidden" name="menu_names[<?= $index ?>]" value="<?= htmlspecialchars($item['name']) ?>">
                    <input type="hidden" name="menu_urls[<?= $index ?>]" value="<?= htmlspecialchars($item['url']) ?>">
                    <input type="hidden" name="menu_types[<?= $index ?>]" value="<?= htmlspecialchars($item['type']) ?>">
                    <?php if ($item['enabled']): ?>
                    <input type="hidden" name="menu_enabled[<?= $index ?>]" value="1">
                    <?php endif; ?>
                    <?php endforeach; ?>
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Restore default colors
        function restoreDefaultColors() {
            const defaults = <?= json_encode(getDefaultThemeColors()) ?>;
            
            // Set the color inputs to default values
            for (const [id, value] of Object.entries(defaults)) {
                const input = document.getElementById(id);
                if (input) input.value = value;
            }
            const titleGradient = document.getElementById('header_title_gradient_enabled');
            if (titleGradient) titleGradient.checked = false;
            
            // Update preview immediately
            updatePreviewColors();
            
            // Show status message
            const status = document.getElementById('preview-status');
            status.textContent = '🔄 Colors restored to defaults';
            status.style.color = '#2196F3';
            setTimeout(() => {
                status.textContent = '';
            }, 3000);
        }

        function restoreDefaultChartColors() {
            const defaults = <?= json_encode(getDefaultChartTheme()) ?>;

            for (const [id, value] of Object.entries(defaults)) {
                const input = document.getElementById(id);
                if (input) input.value = value;
            }
        }
        
        // Initialize drag and drop when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initMenuDragDrop();
            
            // Initialize preview with current colors
            updatePreviewColors();
        });
    </script>
</body>
</html>
