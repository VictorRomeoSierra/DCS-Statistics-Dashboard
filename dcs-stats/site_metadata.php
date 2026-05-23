<?php
/**
 * Public site metadata settings.
 */

function getSiteMetadataPath() {
    $path = __DIR__ . '/site-config/data/site_metadata.json';
    $dir = dirname($path);

    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    return $path;
}

function getDefaultSiteMetadata() {
    return [
        'description' => 'DCS Statistics Dashboard for combat data, pilot statistics, server activity, and squadron insights.',
        'keywords' => 'DCS, DCS World, dashboard, statistics, pilots, squadron, server stats',
        'block_search_engines' => false
    ];
}

function loadSiteMetadata() {
    $defaults = getDefaultSiteMetadata();
    $path = getSiteMetadataPath();

    if (file_exists($path)) {
        $saved = json_decode(file_get_contents($path), true);
        if (is_array($saved)) {
            return array_merge($defaults, $saved);
        }
    }

    return $defaults;
}

function saveSiteMetadata($metadata) {
    $path = getSiteMetadataPath();
    $dir = dirname($path);

    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    $clean = [
        'description' => trim((string)($metadata['description'] ?? '')),
        'keywords' => trim((string)($metadata['keywords'] ?? '')),
        'block_search_engines' => !empty($metadata['block_search_engines'])
    ];

    return @file_put_contents($path, json_encode($clean, JSON_PRETTY_PRINT)) !== false;
}
