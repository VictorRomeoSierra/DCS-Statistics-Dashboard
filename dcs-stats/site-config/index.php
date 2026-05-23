<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';
require_once __DIR__ . '/update_channel.php';
require_once __DIR__ . '/version_tracker.php';
require_once dirname(__DIR__) . '/site_features.php';
require_once dirname(__DIR__) . '/api_config_helper.php';

// Require admin login
requireAdmin();
requirePermission('view_dashboard');

// Get current admin
$currentAdmin = getCurrentAdmin();

// Get dashboard statistics
$stats = getDashboardStats();
$features = loadSiteFeatures();
$featureGroups = getFeatureGroups();
$maintenanceConfig = loadMaintenanceConfig();
$updateChannel = getUpdateChannelConfig();
$versionInfo = initializeVersionTracking();
$apiConfigResult = loadApiConfigWithFix();
$apiConfig = $apiConfigResult['config'] ?? [];
$siteConfigFile = dirname(__DIR__) . '/site_config.json';
$siteConfig = file_exists($siteConfigFile) ? (json_decode(file_get_contents($siteConfigFile), true) ?: []) : [];

$featureCount = 0;
$enabledFeatureCount = 0;
foreach ($featureGroups as $groupFeatures) {
    foreach ($groupFeatures as $featureKey => $featureLabel) {
        $featureCount++;
        if (!empty($features[$featureKey])) {
            $enabledFeatureCount++;
        }
    }
}

$apiEnabled = !empty($apiConfig['use_api']);
$apiHost = $apiConfig['api_host'] ?? preg_replace('#^https?://#', '', $apiConfig['api_base_url'] ?? '');
$enabledEndpoints = isset($apiConfig['enabled_endpoints']) && is_array($apiConfig['enabled_endpoints'])
    ? count($apiConfig['enabled_endpoints'])
    : 0;
$installedBuild = $versionInfo['version'] ?? (defined('ADMIN_PANEL_VERSION') ? ADMIN_PANEL_VERSION : 'Unknown');
$installedCommit = !empty($versionInfo['commit_sha']) ? substr($versionInfo['commit_sha'], 0, 12) : 'Unknown';
$backupDataDir = __DIR__ . '/data';
$dataFiles = is_dir($backupDataDir) ? glob($backupDataDir . '/*.json') : [];
$siteName = $siteConfig['site_name'] ?? 'DCS Statistics';

// Page title
$pageTitle = 'Flight Deck Operations';
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
        .data-table { width: 100%; table-layout: fixed; }
        .data-table td { word-wrap: break-word; overflow-wrap: break-word; }
        
        /* Bridge Log / Activity List Styles */
        .activity-list { max-width: 100%; }
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #444;
            word-wrap: break-word;
            overflow-wrap: break-word;
            transition: background-color 0.2s;
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-item:hover { background-color: #333; }
        .activity-time {
            font-size: 12px;
            color: #888;
            margin-bottom: 5px;
            font-style: italic;
        }
        .activity-action {
            margin-bottom: 5px;
            word-break: break-word;
            line-height: 1.6;
        }
        .activity-action strong {
            color: #4CAF50;
            margin-right: 5px;
        }
        .activity-details {
            font-size: 12px;
            color: #aaa;
            background: #1a1a1a;
            padding: 8px;
            border-radius: 4px;
            margin-top: 8px;
            word-break: break-all;
            max-width: 100%;
            overflow-x: auto;
            border-left: 3px solid #4CAF50;
        }
        code {
            background: #1a1a1a;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #ff9800;
            word-break: break-all;
            display: inline-block;
            max-width: 100%;
        }
        .text-muted {
            color: #888;
            font-size: 13px;
        }
        .overview-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            margin-bottom: 28px;
        }
        .overview-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 18px;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .overview-card-header {
            align-items: flex-start;
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
        }
        .overview-icon {
            align-items: center;
            background: rgba(76, 175, 80, 0.14);
            border-radius: 6px;
            color: var(--accent-primary);
            display: flex;
            flex: 0 0 40px;
            font-size: 22px;
            height: 40px;
            justify-content: center;
            width: 40px;
        }
        .overview-title {
            color: var(--text-primary);
            font-size: 16px;
            font-weight: 700;
            line-height: 1.25;
            margin: 0;
        }
        .overview-subtitle {
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 3px;
        }
        .overview-value {
            color: var(--text-primary);
            font-size: 24px;
            font-weight: 700;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }
        .overview-meta {
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 6px;
            overflow-wrap: anywhere;
        }
        .status-pill {
            align-self: flex-start;
            border-radius: 999px;
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            margin-top: 12px;
            padding: 4px 9px;
        }
        .status-pill.good {
            background: rgba(76, 175, 80, 0.18);
            color: #7bd97f;
        }
        .status-pill.warn {
            background: rgba(255, 152, 0, 0.18);
            color: #ffc266;
        }
        .status-pill.info {
            background: rgba(33, 150, 243, 0.18);
            color: #78c3ff;
        }
        .quick-action-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        }
        @media (max-width: 768px) {
            .admin-sidebar { display: none; }
            .admin-wrapper { flex-direction: column; }
            .admin-content { padding: 15px; }
            .activity-item { padding: 10px; }
            .activity-details { font-size: 11px; padding: 5px; }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'nav.php'; ?>
        
        <!-- Main Content -->
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
                <!-- Welcome Message -->
                <div class="alert alert-info">
                    Welcome aboard, <?= e($currentAdmin['username']) ?>! 
                    Last watch: <?= formatDate($currentAdmin['last_login']) ?>
                </div>

                <!-- Admin Overview -->
                <div class="overview-grid">
                    <div class="overview-card">
                        <div>
                            <div class="overview-card-header">
                                <div class="overview-icon">⚙️</div>
                                <div>
                                    <h2 class="overview-title">Site Setup</h2>
                                    <div class="overview-subtitle">Public dashboard identity</div>
                                </div>
                            </div>
                            <div class="overview-value"><?= e($siteName) ?></div>
                            <div class="overview-meta">Theme: <?= e($siteConfig['theme'] ?? 'dark') ?></div>
                        </div>
                        <span class="status-pill good">Configured</span>
                    </div>

                    <div class="overview-card">
                        <div>
                            <div class="overview-card-header">
                                <div class="overview-icon">🔌</div>
                                <div>
                                    <h2 class="overview-title">API Connection</h2>
                                    <div class="overview-subtitle">DCSServerBot source</div>
                                </div>
                            </div>
                            <div class="overview-value"><?= $apiEnabled ? 'Enabled' : 'Disabled' ?></div>
                            <div class="overview-meta"><?= $apiHost ? e($apiHost) : 'No API host set' ?></div>
                            <div class="overview-meta"><?= number_format($enabledEndpoints) ?> endpoints enabled</div>
                        </div>
                        <span class="status-pill <?= $apiEnabled && $apiHost ? 'good' : 'warn' ?>"><?= $apiEnabled && $apiHost ? 'Ready' : 'Needs Setup' ?></span>
                    </div>

                    <div class="overview-card">
                        <div>
                            <div class="overview-card-header">
                                <div class="overview-icon">🎛️</div>
                                <div>
                                    <h2 class="overview-title">Site Features</h2>
                                    <div class="overview-subtitle">Visible sections and controls</div>
                                </div>
                            </div>
                            <div class="overview-value"><?= number_format($enabledFeatureCount) ?> / <?= number_format($featureCount) ?></div>
                            <div class="overview-meta">Configured feature toggles enabled</div>
                        </div>
                        <span class="status-pill info">Customisable</span>
                    </div>

                    <div class="overview-card">
                        <div>
                            <div class="overview-card-header">
                                <div class="overview-icon">🔄</div>
                                <div>
                                    <h2 class="overview-title">Update Status</h2>
                                    <div class="overview-subtitle">Installed build and channel</div>
                                </div>
                            </div>
                            <div class="overview-value"><?= e($installedBuild) ?></div>
                            <div class="overview-meta"><?= e($updateChannel['channel']) ?> channel: <?= e($updateChannel['branch']) ?></div>
                            <div class="overview-meta">Commit: <?= e($installedCommit) ?></div>
                        </div>
                        <span class="status-pill <?= $updateChannel['is_dev'] ? 'warn' : 'good' ?>"><?= $updateChannel['is_dev'] ? 'Dev' : 'Stable' ?></span>
                    </div>

                    <div class="overview-card">
                        <div>
                            <div class="overview-card-header">
                                <div class="overview-icon">🛠️</div>
                                <div>
                                    <h2 class="overview-title">Maintenance</h2>
                                    <div class="overview-subtitle">Public access mode</div>
                                </div>
                            </div>
                            <div class="overview-value"><?= !empty($maintenanceConfig['enabled']) ? 'On' : 'Off' ?></div>
                            <div class="overview-meta"><?= number_format(count($maintenanceConfig['ip_whitelist'] ?? [])) ?> allowed IPs</div>
                        </div>
                        <span class="status-pill <?= !empty($maintenanceConfig['enabled']) ? 'warn' : 'good' ?>"><?= !empty($maintenanceConfig['enabled']) ? 'Restricted' : 'Public' ?></span>
                    </div>

                    <div class="overview-card">
                        <div>
                            <div class="overview-card-header">
                                <div class="overview-icon">💾</div>
                                <div>
                                    <h2 class="overview-title">Local Settings</h2>
                                    <div class="overview-subtitle">Admin data files</div>
                                </div>
                            </div>
                            <div class="overview-value"><?= number_format(count($dataFiles)) ?></div>
                            <div class="overview-meta">JSON settings files found</div>
                        </div>
                        <span class="status-pill info">Backup Ready</span>
                    </div>
                </div>
                
                <!-- Statistics Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Bridge Officers</div>
                        <div class="stat-value"><?= number_format($stats['total_admins']) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Active Pilots</div>
                        <div class="stat-value"><?= number_format($stats['total_players']) ?></div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Bridge Log</h2>
                        <a href="logs.php" class="btn btn-primary btn-small">View All</a>
                    </div>
                    
                    <?php if (empty($stats['recent_activity'])): ?>
                        <p class="text-muted">No recent activity to display.</p>
                    <?php else: ?>
                        <div class="activity-list">
                            <?php foreach ($stats['recent_activity'] as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-time"><?= formatDate($activity['created_at']) ?></div>
                                    <div class="activity-action">
                                        <strong><?= e($activity['admin_username']) ?></strong>
                                        <?= e(LOG_ACTIONS[$activity['action']] ?? $activity['action']) ?>
                                        <?php if ($activity['target_type']): ?>
                                            <div style="margin-top: 5px;">
                                                <span class="text-muted">Target: <?= e($activity['target_type']) ?></span>
                                                <?php if ($activity['target_id']): ?>
                                                    <code style="font-size: 11px;"><?= e(substr($activity['target_id'], 0, 50)) ?><?= strlen($activity['target_id']) > 50 ? '...' : '' ?></code>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($activity['details'] && !empty($activity['details'])): ?>
                                        <div class="activity-details">
                                            <?php 
                                            $details = is_array($activity['details']) ? json_encode($activity['details']) : $activity['details'];
                                            echo e(substr($details, 0, 100)) . (strlen($details) > 100 ? '...' : '');
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Quick Actions</h2>
                    </div>
                    <div class="quick-action-grid">
                        <?php if (hasPermission('manage_admins')): ?>
                            <a href="admins.php" class="btn btn-primary">Manage Admins</a>
                        <?php endif; ?>
                        <?php if (hasPermission('manage_features')): ?>
                            <a href="settings.php" class="btn btn-secondary">Site Features</a>
                            <a href="settings_backup.php" class="btn btn-secondary">Settings Backup</a>
                        <?php endif; ?>
                        <?php if (hasPermission('manage_api')): ?>
                            <a href="api_settings.php" class="btn btn-secondary">API Settings</a>
                        <?php endif; ?>
                        <?php if (hasPermission('manage_themes')): ?>
                            <a href="themes.php" class="btn btn-secondary">Themes</a>
                        <?php endif; ?>
                        <?php if (hasPermission('manage_updates')): ?>
                            <a href="update.php" class="btn btn-secondary">Updates</a>
                        <?php endif; ?>
                        <?php if (hasPermission('view_logs')): ?>
                            <a href="logs.php" class="btn btn-secondary">View Logs</a>
                        <?php endif; ?>
                        <?php if (hasPermission('export_data')): ?>
                            <a href="export.php" class="btn btn-secondary">Export Data</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">System Information</h2>
                    </div>
                    <table class="data-table">
                        <tr>
                            <td>Admin Panel Version</td>
                            <td><?= ADMIN_PANEL_VERSION ?></td>
                        </tr>
                        <tr>
                            <td>PHP Version</td>
                            <td><?= phpversion() ?></td>
                        </tr>
                        <tr>
                            <td>Storage Mode</td>
                            <td><?= USE_DATABASE ? 'Database' : 'File-based' ?></td>
                        </tr>
                        <tr>
                            <td>Data Directory</td>
                            <td title="<?= htmlspecialchars(ADMIN_DATA_DIR) ?>"><?= basename(rtrim(ADMIN_DATA_DIR, '/')) ?>/</td>
                        </tr>
                        <tr>
                            <td>Log Retention</td>
                            <td><?= LOG_RETENTION_DAYS ?> days</td>
                        </tr>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Auto-refresh activity every 30 seconds
        setInterval(() => {
            // In a real implementation, this would fetch new activity via AJAX
        }, 30000);
    </script>
</body>
</html>
