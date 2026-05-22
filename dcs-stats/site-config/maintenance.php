<?php
/**
 * Maintenance Mode Settings
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';

// Require admin login and permission
requireAdmin();
requirePermission('manage_maintenance');

// Current admin
$currentAdmin = getCurrentAdmin();

// Load current configuration
$maintenance = loadMaintenanceConfig();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = ERROR_MESSAGES['csrf_invalid'];
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? 'update';
        $maintenance['ip_whitelist'] = $maintenance['ip_whitelist'] ?? [];

        if ($action === 'remove_ip') {
            $ipToRemove = trim($_POST['ip'] ?? '');
            $originalCount = count($maintenance['ip_whitelist']);
            $maintenance['ip_whitelist'] = array_values(array_filter(
                $maintenance['ip_whitelist'],
                static fn($ip) => $ip !== $ipToRemove
            ));

            if ($ipToRemove === '' || count($maintenance['ip_whitelist']) === $originalCount) {
                $message = 'IP address was not found in the whitelist';
                $messageType = 'error';
            } else {
                saveMaintenanceConfig($maintenance);
                logAdminActivity('MAINTENANCE_IP_REMOVE', $_SESSION['admin_id'], 'settings', 'maintenance', ['ip' => $ipToRemove]);
                $message = 'IP address removed from whitelist';
                $messageType = 'success';
            }
        } else {
            // Update maintenance mode
            $maintenance['enabled'] = isset($_POST['enabled']);

            // Add IP to whitelist if provided
            $ip = trim($_POST['ip_address'] ?? '');
            if ($ip !== '') {
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    if (!in_array($ip, $maintenance['ip_whitelist'])) {
                        $maintenance['ip_whitelist'][] = $ip;
                    }
                } else {
                    $message = 'Invalid IP address';
                    $messageType = 'error';
                }
            }

            if ($messageType !== 'error') {
                saveMaintenanceConfig($maintenance);
                logAdminActivity('MAINTENANCE_UPDATE', $_SESSION['admin_id'], 'settings', 'maintenance', $maintenance);
                $message = 'Maintenance settings updated';
                $messageType = 'success';
            }
        }
    }
}

$pageTitle = 'Maintenance Whitelist';
$currentIP = $_SERVER['REMOTE_ADDR'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Carrier Air Wing Command</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="admin-wrapper">
    <?php include 'nav.php'; ?>
    <main class="admin-main">
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
        <div class="admin-content">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'error' ?>">
                    <?= e($message) ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="enabled" <?= $maintenance['enabled'] ? 'checked' : '' ?>>
                        Enable Maintenance Mode
                    </label>
                </div>
                <div class="form-group">
                    <label for="ip_address">Whitelist IP</label>
                    <div class="d-flex gap-1">
                        <input type="text" name="ip_address" id="ip_address" class="form-control" placeholder="127.0.0.1">
                        <button type="button" class="btn btn-secondary" onclick="autofillIP()">Use My IP</button>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
            <?php if (!empty($maintenance['ip_whitelist'])): ?>
                <div class="card mt-2">
                    <div class="card-header">
                        <h2 class="card-title">Current Whitelist</h2>
                    </div>
                    <div class="card-content">
                        <ul class="maintenance-whitelist">
                            <?php foreach ($maintenance['ip_whitelist'] as $ip): ?>
                                <li class="maintenance-whitelist-item">
                                    <span><?= e($ip) ?></span>
                                    <form method="POST" class="maintenance-whitelist-remove">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="remove_ip">
                                        <input type="hidden" name="ip" value="<?= e($ip) ?>">
                                        <button type="submit" class="btn btn-danger btn-small">Remove</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script>
function autofillIP() {
    document.getElementById('ip_address').value = '<?= $currentIP ?>';
}
</script>
<style>
.maintenance-whitelist {
    list-style: none;
    margin: 0;
    padding: 0;
}

.maintenance-whitelist-item {
    align-items: center;
    display: flex;
    gap: 1rem;
    justify-content: space-between;
    padding: 0.5rem 0;
}

.maintenance-whitelist-remove {
    margin: 0;
}
</style>
</body>
</html>
