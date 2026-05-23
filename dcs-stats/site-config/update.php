<?php
/**
 * Update Dashboard from GitHub branches
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';
require_once __DIR__ . '/update_channel.php';

requireAdmin();
requirePermission('manage_updates');

$currentAdmin = getCurrentAdmin();
$updateChannel = getUpdateChannelConfig();

$pageTitle = 'Update Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Carrier Air Wing Command</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .update-log {
            background: #111;
            color: #0f0;
            padding: 10px;
            height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
            font-family: monospace;
        }
        .warning-box {
            background-color: rgba(255, 152, 0, 0.1);
            border: 1px solid var(--accent-warning);
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
        }
        .row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .col-md-6 {
            flex: 1;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-primary {
            background-color: var(--accent-primary);
            color: white;
        }
        .badge-warning {
            background-color: var(--accent-warning);
            color: #000;
        }
        .btn-block {
            width: 100%;
            display: block;
        }
        .btn-info {
            background-color: var(--accent-info);
            color: white;
        }
        .btn-info:hover {
            background-color: #1976D2;
        }
        .mb-2 {
            margin-bottom: 10px;
        }
        .badge-info {
            background-color: #2196F3;
            color: white;
        }
        .badge-success {
            background-color: #4CAF50;
            color: white;
        }
        .mt-2 {
            margin-top: 10px;
        }
        .version-summary-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            margin-bottom: 16px;
        }
        .version-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 12px;
        }
        .version-label {
            color: var(--text-muted);
            display: block;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .version-value {
            color: var(--text-primary);
            display: block;
            font-size: 18px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }
        .version-details {
            display: grid;
            gap: 8px;
            margin: 0 0 14px;
        }
        .version-detail-row {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: space-between;
        }
        .version-detail-row strong {
            color: var(--text-muted);
        }
        .version-status-panel {
            background: rgba(33, 150, 243, 0.08);
            border: 1px solid rgba(33, 150, 243, 0.22);
            border-radius: 6px;
            margin-top: 14px;
            padding: 12px;
        }
        .version-status-title {
            color: var(--text-primary);
            font-weight: 700;
            margin-bottom: 6px;
        }
        .version-status-meta {
            color: var(--text-muted);
            display: grid;
            gap: 4px;
            font-size: 13px;
        }
    </style>
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
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">System Information</h3>
                        </div>
                        <div class="card-content">
                            <?php
                            require_once __DIR__ . '/version_tracker.php';
                            require_once dirname(__DIR__) . '/dev_mode.php';
                            $versionInfo = initializeVersionTracking();
                            $dashboardVersion = defined('ADMIN_PANEL_VERSION') ? ADMIN_PANEL_VERSION : 'Unknown';
                            $currentBranch = $versionInfo['branch'];
                            $isDev = isDevMode();
                            $installedBuild = $versionInfo['version'] ?? 'Unknown';
                            if (empty($versionInfo['commit_sha']) && preg_match('/^V?\d+\.\d+\.\d+/i', $installedBuild)) {
                                $installedBuild .= ' (legacy - refreshes after next update)';
                            }
                            $installedCommit = !empty($versionInfo['commit_sha']) ? substr($versionInfo['commit_sha'], 0, 12) : 'Unknown';
                            $installedDate = !empty($versionInfo['commit_date']) ? date('Y-m-d H:i:s', strtotime($versionInfo['commit_date'])) : 'Unknown';
                            $lastUpdated = $versionInfo['updated_at'] ?? 'Unknown';
                            $supportInfo = [
                                'Dashboard Version' => $dashboardVersion,
                                'Installed Build' => $installedBuild,
                                'Current Branch' => $currentBranch,
                                'Update Channel' => $updateChannel['channel'],
                                'GitHub Branch' => $updateChannel['branch'],
                                'Installed Commit' => $installedCommit,
                                'Installed Date' => $installedDate,
                                'PHP Version' => PHP_VERSION,
                                'Last Updated' => $lastUpdated
                            ];
                            ?>
                            <div class="version-summary-grid">
                                <div class="version-card">
                                    <span class="version-label">Dashboard Version</span>
                                    <span class="version-value"><?= e($dashboardVersion) ?></span>
                                </div>
                                <div class="version-card">
                                    <span class="version-label">Installed Build</span>
                                    <span class="version-value"><?= e($installedBuild) ?></span>
                                </div>
                                <div class="version-card">
                                    <span class="version-label">Update Channel</span>
                                    <span class="badge badge-<?= $updateChannel['is_dev'] ? 'warning' : 'primary' ?>"><?= e($updateChannel['channel']) ?></span>
                                </div>
                            </div>

                            <div class="version-details">
                                <div class="version-detail-row">
                                    <strong>Current Branch</strong>
                                    <span class="badge badge-<?= $currentBranch === 'Dev' ? 'warning' : 'primary' ?>"><?= e($currentBranch) ?></span>
                                </div>
                                <div class="version-detail-row">
                                    <strong>GitHub Source</strong>
                                    <code><?= e($updateChannel['repo'] . ':' . $updateChannel['branch']) ?></code>
                                </div>
                                <div class="version-detail-row">
                                    <strong>Installed Commit</strong>
                                    <code><?= e($installedCommit) ?></code>
                                </div>
                                <div class="version-detail-row">
                                    <strong>Installed Date</strong>
                                    <span><?= e($installedDate) ?></span>
                                </div>
                                <div class="version-detail-row">
                                    <strong>Last Updated</strong>
                                    <span><?= e($lastUpdated) ?></span>
                                </div>
                                <div class="version-detail-row">
                                    <strong>PHP Version</strong>
                                    <span><?= e(PHP_VERSION) ?></span>
                                </div>
                            </div>

                            <button class="btn btn-secondary btn-small" type="button" onclick="copySupportInfo()" style="margin-top: 8px;">
                                Copy Support Info
                            </button>
                            <pre id="support-info" class="update-log" style="display: none; height: auto; max-height: 180px; margin-top: 10px;"><?php foreach ($supportInfo as $label => $value): ?><?= e($label . ': ' . $value) . "\n" ?><?php endforeach; ?></pre>
                            
                            <div class="version-status-panel">
                                <div class="version-status-title" id="update-status-title">Checking GitHub source...</div>
                                <div class="version-status-meta">
                                    <span id="update-status">Checking for updates...</span>
                                    <span>Latest GitHub commit: <code id="remote-commit">Checking...</code></span>
                                    <span>Latest GitHub date: <span id="remote-date">Checking...</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div class="card-content">
                            <button class="btn btn-secondary btn-block mb-2" onclick="createBackup()">
                                <span class="nav-icon">💾</span> Create Manual Backup
                            </button>
                            <button class="btn btn-warning btn-block mb-2" onclick="showDowngradeModal()">
                                <span class="nav-icon">⬇️</span> Downgrade Version
                            </button>
                            <button class="btn btn-info btn-block mb-2" onclick="checkForUpdates()">
                                <span class="nav-icon">🔍</span> Check for Updates
                            </button>
                            <button class="btn btn-primary btn-block mb-2" onclick="performUpdate()">
                                <span class="nav-icon">⬆️</span> Update Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Update Log</h3>
                </div>
                <pre id="log" class="update-log"></pre>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Backup Management</h3>
                </div>
                <div id="backup-list" class="card-content">
                    <p class="text-muted">Loading backups...</p>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Restore Modal -->
<div id="restoreModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Restore from Backup</h3>
            <button class="modal-close" onclick="closeModal('restoreModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Select a backup to restore:</p>
            <div id="restore-backup-list">Loading backups...</div>
        </div>
    </div>
</div>

<!-- Downgrade Modal -->
<div id="downgradeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Downgrade Version</h3>
            <button class="modal-close" onclick="closeModal('downgradeModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="downgrade-form">
                <div class="form-group">
                    <label for="downgrade-version">Select Version</label>
                    <select id="downgrade-version" class="form-control">
                        <option value="">Loading versions...</option>
                    </select>
                </div>
                <div class="form-group">
                    <p class="text-muted">✓ Automatic backup will be created before downgrading</p>
                </div>
                <button type="submit" class="btn btn-warning">Downgrade</button>
            </form>
        </div>
    </div>
</div>

<script>
// Check for updates on page load
let updateAvailable = false;
let latestVersion = null;

function copySupportInfo() {
    const supportInfo = document.getElementById('support-info');
    if (!supportInfo) return;

    supportInfo.style.display = 'block';
    navigator.clipboard.writeText(supportInfo.textContent.trim())
        .then(() => {
            const log = document.getElementById('log');
            log.textContent = 'Support info copied. Paste it into the issue report.\n\n' + supportInfo.textContent.trim();
        })
        .catch(() => {
            const range = document.createRange();
            range.selectNodeContents(supportInfo);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
        });
}

function checkUpdateStatus() {
    fetch('api/check_updates.php')
        .then(response => response.text())
        .then(data => {
            const statusDiv = document.getElementById('update-status');
            const statusTitle = document.getElementById('update-status-title');
            const remoteCommit = document.getElementById('remote-commit');
            const remoteDate = document.getElementById('remote-date');
            const latestCommitMatch = data.match(/Latest Commit: ([^\n]+)/);
            const latestDateMatch = data.match(/Latest Date: ([^\n]+)/);
            const branchMatch = data.match(/GitHub Branch: ([^\n]+)/);
            const sourceLabel = branchMatch ? branchMatch[1].trim() : 'selected branch';

            if (remoteCommit) {
                remoteCommit.textContent = latestCommitMatch ? latestCommitMatch[1].trim() : 'Unavailable';
            }
            if (remoteDate) {
                remoteDate.textContent = latestDateMatch ? latestDateMatch[1].trim() : 'Unavailable';
            }
            
            // Parse the response to check if update is available
            if (data.includes('✅ Update Available!')) {
                updateAvailable = true;
                // Extract version from response
                const versionMatch = data.match(/Latest Release: (v?[\d.]+)/);
                if (versionMatch) {
                    latestVersion = versionMatch[1];
                }
                statusTitle.textContent = 'Update Ready';
                statusDiv.innerHTML = `Latest code is available from ${sourceLabel}. <button class="btn btn-primary btn-small" onclick="performUpdate()" style="margin-left: 10px;">Update Now</button>`;
            } else if (data.includes('✓ You are running the latest')) {
                statusTitle.textContent = 'Up to Date';
                statusDiv.innerHTML = '<span class="text-success">System is running the latest selected branch build.</span>';
            } else if (data.includes('Could not fetch branch information')) {
                statusTitle.textContent = 'GitHub Check Failed';
                statusDiv.innerHTML = 'Could not check the selected GitHub branch. Use Check for Updates below to view the full response.';
            } else {
                statusTitle.textContent = 'Update Status Unknown';
                statusDiv.innerHTML = 'GitHub returned an unexpected response. Use Check for Updates below to view the full response.';
            }
            
            // Also populate versions for downgrade
            const versions = [];
            const versionMatches = data.matchAll(/- (v?[\d.]+)/g);
            for (const match of versionMatches) {
                versions.push(match[1]);
            }
            populateVersionSelect(versions);
        })
        .catch(error => {
            document.getElementById('update-status').innerHTML = '<p class="text-danger">Failed to check updates</p>';
        });
}

function performUpdate() {
    const formData = new FormData();
    
    const log = document.getElementById('log');
    log.textContent = 'Starting update from <?= e($updateChannel['channel']) ?> channel...\n';
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/update.php');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onprogress = function() {
        log.textContent = xhr.responseText;
        log.scrollTop = log.scrollHeight;
    };
    xhr.onload = function() {
        log.textContent = xhr.responseText;
        log.scrollTop = log.scrollHeight;
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    };
    xhr.send(formData);
}

// Load backup list
function loadBackups() {
    fetch('api/list_backups.php')
        .then(response => response.json())
        .then(data => {
            const backupList = document.getElementById('backup-list');
            if (data.backups && data.backups.length > 0) {
                let html = '<div class="data-table-wrapper"><table class="data-table">';
                html += '<thead><tr><th>Backup Date</th><th>Version</th><th>Branch</th><th>Size</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
                data.backups.forEach((backup, index) => {
                    const branchClass = backup.branch === 'Dev' ? 'badge-warning' : 'badge-primary';
                    const isProtected = index < 5;
                    const statusHtml = isProtected 
                        ? '<span class="badge badge-success">Protected</span>' 
                        : '<span class="badge badge-warning">Will be auto-deleted</span>';
                    html += `<tr>
                        <td>${backup.date}</td>
                        <td>${backup.version}</td>
                        <td><span class="badge ${branchClass}">${backup.branch}</span></td>
                        <td>${backup.size}</td>
                        <td>${statusHtml}</td>
                        <td>
                            <button class="btn btn-small btn-secondary" onclick="restoreBackup('${backup.name}')">Restore</button>
                            <button class="btn btn-small btn-danger" onclick="deleteBackup('${backup.name}')">Delete</button>
                        </td>
                    </tr>`;
                });
                html += '</tbody></table></div>';
                html += '<p class="text-muted mt-2">ℹ️ System keeps the 5 most recent backups. Older backups are automatically deleted.</p>';
                backupList.innerHTML = html;
            } else {
                backupList.innerHTML = '<p class="text-muted">No backups found.</p>';
            }
        })
        .catch(error => {
            document.getElementById('backup-list').innerHTML = '<p class="text-danger">Failed to load backups.</p>';
        });
}

function restoreBackup(filename) {
    if (!confirm('Are you sure you want to restore this backup? This will overwrite current files.')) {
        return;
    }
    
    const log = document.getElementById('log');
    log.textContent = 'Starting restore...\n';
    
    fetch('api/restore_backup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ backup: filename })
    })
    .then(response => response.text())
    .then(data => {
        log.textContent += data;
        loadBackups();
    })
    .catch(error => {
        log.textContent += 'Restore failed: ' + error.message;
    });
}

function deleteBackup(filename) {
    if (!confirm('Are you sure you want to delete this backup?')) {
        return;
    }
    
    fetch('api/delete_backup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ backup: filename })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadBackups();
        } else {
            alert('Failed to delete backup: ' + data.error);
        }
    })
    .catch(error => {
        alert('Failed to delete backup: ' + error.message);
    });
}

// Create manual backup
function createBackup() {
    const log = document.getElementById('log');
    log.textContent = 'Creating backup...\n';
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/create_backup.php');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onprogress = function() {
        log.textContent = xhr.responseText;
        log.scrollTop = log.scrollHeight;
    };
    xhr.onload = function() {
        log.textContent = xhr.responseText;
        log.scrollTop = log.scrollHeight;
        loadBackups();
    };
    xhr.send();
}

// Check for updates
function checkForUpdates() {
    const log = document.getElementById('log');
    log.textContent = 'Checking for updates...\n';
    
    fetch('api/check_updates.php')
        .then(response => response.text())
        .then(data => {
            log.textContent = data;
        })
        .catch(error => {
            log.textContent = 'Failed to check for updates: ' + error.message;
        });
}


// Modal functions
function showRestoreModal() {
    document.getElementById('restoreModal').classList.add('active');
    loadBackupsForRestore();
}

function showDowngradeModal() {
    document.getElementById('downgradeModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Load backups for restore modal
function loadBackupsForRestore() {
    fetch('api/list_backups.php')
        .then(response => response.json())
        .then(data => {
            const restoreList = document.getElementById('restore-backup-list');
            if (data.backups && data.backups.length > 0) {
                let html = '<div class="backup-list">';
                data.backups.forEach(backup => {
                    const branchClass = backup.branch === 'Dev' ? 'badge-warning' : 'badge-primary';
                    html += `
                        <div class="backup-item" style="padding: 10px; border: 1px solid var(--border-color); margin-bottom: 10px; border-radius: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong>${backup.date}</strong><br>
                                    Version: ${backup.version} | <span class="badge ${branchClass}">${backup.branch}</span> | Size: ${backup.size}
                                </div>
                                <button class="btn btn-secondary btn-small" onclick="restoreBackup('${backup.name}'); closeModal('restoreModal');">
                                    Restore
                                </button>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                restoreList.innerHTML = html;
            } else {
                restoreList.innerHTML = '<p class="text-muted">No backups available.</p>';
            }
        });
}

// Populate version select
function populateVersionSelect(versions) {
    const select = document.getElementById('downgrade-version');
    let html = '<option value="">Select a version</option>';
    versions.forEach(version => {
        if (version !== '<?= ADMIN_PANEL_VERSION ?>') {
            html += `<option value="${version}">${version}</option>`;
        }
    });
    select.innerHTML = html;
}

// Handle downgrade form
document.getElementById('downgrade-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const version = document.getElementById('downgrade-version').value;
    if (!version) {
        alert('Please select a version');
        return;
    }
    
    closeModal('downgradeModal');
    
    const formData = new FormData();
    formData.append('version', version);
    
    const log = document.getElementById('log');
    log.textContent = `Downgrading to version ${version}...\n`;
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/update.php');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onprogress = function() {
        log.textContent = xhr.responseText;
        log.scrollTop = log.scrollHeight;
    };
    xhr.onload = function() {
        log.textContent = xhr.responseText;
        log.scrollTop = log.scrollHeight;
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    };
    xhr.send(formData);
});

// Load backups on page load
loadBackups();
checkUpdateStatus();

</script>
</body>
</html>
