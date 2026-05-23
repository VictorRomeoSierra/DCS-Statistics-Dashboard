<?php
/**
 * Privacy and SEO Settings
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';
require_once dirname(__DIR__) . '/site_metadata.php';

requireAdmin();
requirePermission('manage_features');

$currentAdmin = getCurrentAdmin();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = ERROR_MESSAGES['csrf_invalid'];
        $messageType = 'error';
    } else {
        $metadata = [
            'description' => trim($_POST['description'] ?? ''),
            'keywords' => trim($_POST['keywords'] ?? ''),
            'block_search_engines' => isset($_POST['block_search_engines']),
            'show_privacy_link' => isset($_POST['show_privacy_link']),
            'privacy_notice' => trim($_POST['privacy_notice'] ?? '')
        ];

        if (strlen($metadata['description']) > 320) {
            $message = 'Description should be 320 characters or fewer';
            $messageType = 'error';
        } elseif (strlen($metadata['keywords']) > 500) {
            $message = 'Keywords should be 500 characters or fewer';
            $messageType = 'error';
        } elseif (strlen($metadata['privacy_notice']) > 5000) {
            $message = 'Privacy notice should be 5000 characters or fewer';
            $messageType = 'error';
        } elseif (saveSiteMetadata($metadata)) {
            logAdminActivity('METADATA_UPDATE', $_SESSION['admin_id'], 'settings', 'metadata', [
                'description_length' => strlen($metadata['description']),
                'keywords_length' => strlen($metadata['keywords']),
                'block_search_engines' => $metadata['block_search_engines'],
                'show_privacy_link' => $metadata['show_privacy_link'],
                'privacy_notice_length' => strlen($metadata['privacy_notice'])
            ]);
            $message = 'Privacy and SEO settings saved successfully';
            $messageType = 'success';
        } else {
            $message = 'Failed to save website metadata';
            $messageType = 'error';
        }
    }
}

$metadata = loadSiteMetadata();
$pageTitle = 'Privacy & SEO';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Carrier Air Wing Command</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .metadata-help {
            background-color: rgba(76, 175, 80, 0.08);
            border: 1px solid rgba(76, 175, 80, 0.28);
            border-radius: 6px;
            color: var(--text-muted);
            line-height: 1.55;
            margin-bottom: 20px;
            padding: 15px;
        }

        .character-count {
            color: var(--text-muted);
            display: block;
            font-size: 12px;
            margin-top: 6px;
        }

        textarea.form-control {
            min-height: 110px;
            resize: vertical;
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
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'error' ?>">
                    <?= e($message) ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Search Metadata</h2>
                </div>

                <div class="metadata-help">
                    Manage search metadata, crawler visibility, and the public privacy notice from one place.
                </div>

                <form method="POST">
                    <?= csrfField() ?>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" maxlength="320"><?= e($metadata['description'] ?? '') ?></textarea>
                        <span class="character-count">Recommended: 120-160 characters. Maximum: 320.</span>
                    </div>

                    <div class="form-group">
                        <label for="keywords">Keywords</label>
                        <textarea id="keywords" name="keywords" class="form-control" maxlength="500"><?= e($metadata['keywords'] ?? '') ?></textarea>
                        <span class="character-count">Comma-separated keywords, maximum 500 characters.</span>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="block_search_engines" value="1" <?= !empty($metadata['block_search_engines']) ? 'checked' : '' ?>>
                            Block search engines from crawling/indexing this site
                        </label>
                    </div>

                    <div class="card" style="margin-top: 24px;">
                        <div class="card-header">
                            <h3 class="card-title">Privacy Notice</h3>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="show_privacy_link" value="1" <?= !empty($metadata['show_privacy_link']) ? 'checked' : '' ?>>
                                Show Privacy link in the public footer
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="privacy_notice">Privacy Notice Text</label>
                            <textarea id="privacy_notice" name="privacy_notice" class="form-control" maxlength="5000"><?= e($metadata['privacy_notice'] ?? '') ?></textarea>
                            <span class="character-count">This appears on the public Privacy page. Maximum: 5000 characters.</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Privacy & SEO Settings</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
