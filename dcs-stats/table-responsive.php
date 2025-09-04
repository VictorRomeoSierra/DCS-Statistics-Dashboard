<?php
/**
 * Utility functions for making tables responsive with mobile card support
 */

function tableResponsiveStart($includeCards = true, $cardId = '') {
    echo '<div class="table-wrapper">';
}

function tableResponsiveEnd($includeCards = true, $cardId = '') {
    echo '</div>';
    
    // Add mobile cards container if requested
    if ($includeCards && $cardId) {
        echo "\n<!-- Mobile Cards Container -->\n";
        echo '<div class="mobile-cards" id="' . htmlspecialchars($cardId) . '"></div>';
    }
}

// CSS for responsive tables with card layout support
function tableResponsiveStyles() {
    
}

// Helper function to create a mobile card
function createMobileCard($content, $classes = '') {
    return '<div class="mobile-card ' . htmlspecialchars($classes) . '">' . $content . '</div>';
}

// Helper function to escape HTML
function tableResponsiveEscape($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>