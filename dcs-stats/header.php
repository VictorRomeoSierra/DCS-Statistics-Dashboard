<?php
// Include path configuration
require_once __DIR__ . '/config_path.php';

// Load site configuration
$siteConfig = [];
$siteConfigFile = __DIR__ . '/site_config.json';
if (file_exists($siteConfigFile)) {
    $content = @file_get_contents($siteConfigFile);
    if ($content) {
        $siteConfig = json_decode($content, true) ?: [];
    }
}

$siteName = $siteConfig['site_name'] ?? 'DCS Statistics';

// Security headers for protection against common web vulnerabilities
header("X-Content-Type-Options: nosniff");
// Allow iframe embedding for theme preview, deny for everything else
if (isset($_GET['preview']) && $_GET['preview'] === '1') {
    header("X-Frame-Options: SAMEORIGIN");
} else {
   header("X-Frame-Options: DENY");
}
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Build dynamic CSP based on API configuration
$cspConnectSrc = "'self'";

// Load API configuration if available
$configFile = __DIR__ . '/api_config.json';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (!empty($config['api_base_url'])) {
        // Parse the API URL to add to CSP
        $parsedUrl = parse_url($config['api_base_url']);
        if ($parsedUrl) {
            $scheme = $parsedUrl['scheme'] ?? 'http';
            $host = $parsedUrl['host'] ?? '';
            $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
            
            if ($host) {
                // Add the specific API URL
                $cspConnectSrc .= " {$scheme}://{$host}{$port}";
                
                // Also add wildcard for subdomains
                $domain = preg_replace('/^[^.]+\./', '*.', $host);
                if ($domain !== $host) {
                    $cspConnectSrc .= " {$scheme}://{$domain}:*";
                }
            }
        }
    }
}

// Always allow localhost for development
$cspConnectSrc .= " http://localhost:* https://localhost:*";

// Build CSP header with frame-ancestors for preview mode
$frameAncestors = (isset($_GET['preview']) && $_GET['preview'] === '1') ? " frame-ancestors 'self';" : " frame-ancestors 'none';";
//header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src {$cspConnectSrc};" . $frameAncestors);

// Handle theme preview parameters
$previewColors = null;
if (isset($_GET['preview']) && $_GET['preview'] === '1') {
    $previewColors = [
        'primary_color' => isset($_GET['primary']) ? '#' . $_GET['primary'] : null,
        'secondary_color' => isset($_GET['secondary']) ? '#' . $_GET['secondary'] : null,
        'background_color' => isset($_GET['background']) ? '#' . $_GET['background'] : null,
        'text_color' => isset($_GET['text']) ? '#' . $_GET['text'] : null,
        'link_color' => isset($_GET['link']) ? '#' . $_GET['link'] : null,
        'border_color' => isset($_GET['border']) ? '#' . $_GET['border'] : null,
    ];
}

// Maintenance mode check
$maintenanceFile = __DIR__ . '/site-config/data/maintenance.json';
if (file_exists($maintenanceFile)) {
    $maintenance = json_decode(file_get_contents($maintenanceFile), true);
    if (!empty($maintenance['enabled'])) {
        $allowed = $maintenance['ip_whitelist'] ?? [];
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!in_array($ip, $allowed)) {
            // Override redirect logic within maintenance.php
            if (!defined('MAINTENANCE_OVERRIDE')) {
                define('MAINTENANCE_OVERRIDE', true);
            }
            require __DIR__ . '/maintenance.php';
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($siteName); ?> Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/hung1001/font-awesome-pro@4cac1a6/css/all.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://community.cloudflare.steamstatic.com/public/shared/javascript/tooltip.js"></script>
    <script>
	document.addEventListener('DOMContentLoaded', function(event) {
		$('.nav').v_tooltip( { 'location': 'bottom', 'offsetY': 5, 'tooltipClass': 'tooltiptext_wrap', 'dataName': 'tooltipText', 'defaultType': 'text', 'replaceExisting': false } );
	});
</script>
  <link rel="stylesheet" href="<?php echo url('styles.php'); ?>" />
  <link rel="stylesheet" href="<?php echo url('styles-mobile.css'); ?>" />
  <?php if (file_exists(__DIR__ . '/custom_theme.css')): ?>
  <link rel="stylesheet" href="<?php echo url('custom_theme.css'); ?>" />
  <?php endif; ?>
  <?php if ($previewColors): ?>
  <style>
    :root {
      <?php foreach ($previewColors as $var => $color): ?>
      <?php if ($color): ?>
      --<?php echo $var; ?>: <?php echo $color; ?> !important;
      <?php endif; ?>
      <?php endforeach; ?>
    }
  </style>
  <?php endif; ?>
  <script>
    // Path configuration for JavaScript
    window.DCS_CONFIG = <?php echo getJsConfig(); ?>;
    
    // XSS Protection function
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
  </script>
  <script src="<?php echo url('js/api-client.js'); ?>"></script>
  <script src="<?php echo url('mobile-enhancements.js'); ?>"></script>
</head>
<body>
<div id="wrapper">
<video playsinline autoplay muted loop poster="bg.jpg" id="vrs-bg-video">
  <source src="video.mp4" type="video/mp4">
</video>
  <header class="main-header">
	<div id="logo"><a href="https://agaar.in/vrs_new/"><img src="logo.png" /></a>
	</div>

	<div id="nav">
		<div id="primary">
			<a class="nav" href="https://map.victorromeosierra.com/">Live Map</a>
			<a class="nav" href="https://tacview.victorromeosierra.com/">Tac View</a>
			<a class="nav active" href="https://stats.victorromeosierra.com/">Stats</a>
			<a class="nav disabled" href="">Wiki</a>
			<a class="nav icon" data-tooltip-text="Discord" href="https://discord.gg/invite/n5XMup5NBF"><i class="fab fa-discord"></i></a>
			<a class="nav icon" data-tooltip-text="Patreon" href=""><i class="fab fa-patreon"></i></a>
			<a class="nav icon" data-tooltip-text="Youtube" href=""><i class="fab fa-youtube"></i></a>
		</div>
    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle navigation menu"><i class="fas fa-bars"></i></button>
	</div>
  </header>
