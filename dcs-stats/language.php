<?php
/**
 * Lightweight language support for public dashboard text.
 *
 * This is intentionally small: pages can call dcs_t('key') and missing
 * translations fall back to English. More text can be moved into the language
 * files over time without changing the page structure.
 */

function dcs_builtin_languages() {
    return [
        'en' => 'English',
        'de' => 'Deutsch'
    ];
}

function dcs_custom_language_registry_path() {
    return __DIR__ . '/site-config/data/languages.json';
}

function dcs_custom_language_dir() {
    return __DIR__ . '/site-config/data/languages';
}

function dcs_normalize_language_code($language) {
    $language = strtolower(trim((string)$language));
    $language = str_replace('_', '-', $language);
    return preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $language) ? $language : '';
}

function dcs_custom_languages() {
    $path = dcs_custom_language_registry_path();
    if (!file_exists($path)) {
        return [];
    }

    $data = json_decode(@file_get_contents($path), true);
    if (!is_array($data)) {
        return [];
    }

    $languages = [];
    $builtIn = dcs_builtin_languages();
    foreach ($data as $code => $info) {
        $code = dcs_normalize_language_code($code);
        if ($code === '' || isset($builtIn[$code]) || !is_array($info) || empty($info['name'])) {
            continue;
        }

        $file = $info['file'] ?? ($code . '.json');
        if (!preg_match('/^[a-z0-9-]+\.json$/i', $file)) {
            continue;
        }

        $languages[$code] = [
            'name' => (string)$info['name'],
            'file' => $file,
            'uploaded_at' => $info['uploaded_at'] ?? null
        ];
    }

    return $languages;
}

function dcs_supported_languages() {
    $languages = dcs_builtin_languages();
    foreach (dcs_custom_languages() as $code => $info) {
        $languages[$code] = $info['name'];
    }
    return $languages;
}

function dcs_language_code($language = null) {
    $supported = dcs_supported_languages();
    $language = dcs_normalize_language_code($language);
    return isset($supported[$language]) ? $language : 'en';
}

function dcs_default_language() {
    static $language = null;

    if ($language !== null) {
        return $language;
    }

    $configFile = __DIR__ . '/site_config.json';
    $config = [];
    if (file_exists($configFile)) {
        $config = json_decode(@file_get_contents($configFile), true) ?: [];
    }

    $language = dcs_language_code($config['default_language'] ?? 'en');
    return $language;
}

function dcs_load_translations($language) {
    static $cache = [];

    $language = dcs_language_code($language);
    if (isset($cache[$language])) {
        return $cache[$language];
    }

    $translations = [];
    $file = __DIR__ . '/lang/' . $language . '.php';
    if (file_exists($file)) {
        $translations = require $file;
    } else {
        $customLanguages = dcs_custom_languages();
        if (isset($customLanguages[$language])) {
            $customFile = dcs_custom_language_dir() . '/' . $customLanguages[$language]['file'];
            $customData = file_exists($customFile) ? json_decode(@file_get_contents($customFile), true) : [];
            $translations = $customData['translations'] ?? $customData;
        }
    }

    $cache[$language] = is_array($translations) ? $translations : [];
    return $cache[$language];
}

function dcs_t($key, $replace = []) {
    $language = dcs_default_language();
    $translations = dcs_load_translations($language);
    $fallback = $language === 'en' ? [] : dcs_load_translations('en');
    $text = $translations[$key] ?? $fallback[$key] ?? $key;

    foreach ($replace as $name => $value) {
        $text = str_replace('{' . $name . '}', (string)$value, $text);
    }

    return $text;
}

function dcs_language_name($language = null) {
    $language = dcs_language_code($language ?? dcs_default_language());
    $supported = dcs_supported_languages();
    return $supported[$language] ?? $supported['en'];
}
