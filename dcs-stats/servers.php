<?php
// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'header.php';
require_once __DIR__ . '/site_features.php';
include 'nav.php';

$siteFeatures = loadSiteFeatures();
$serverCardVisibility = [];
foreach ($siteFeatures as $featureKey => $enabled) {
    if (strpos($featureKey, 'server_card_') === 0) {
        $serverCardVisibility[$featureKey] = (bool)$enabled;
    }
}

if (!isFeatureEnabled('nav_servers')):
?>
<main>
    <div class="alert" style="text-align: center; padding: 50px;">
        <h2>Server Status Disabled</h2>
        <p>The server status page is currently disabled.</p>
    </div>
</main>
<?php include 'footer.php'; exit; ?>
<?php endif; ?>

<main>
    <div class="dashboard-header">
        <h1>Server Status</h1>
        <p class="dashboard-subtitle">Live DCS server information and player counts</p>
    </div>
    
    <div id="servers-loading" style="text-align: center; padding: 50px;">
        <p>Loading server information...</p>
    </div>
    
    <div id="servers-container" style="display: none;">
        <?php if (isFeatureEnabled('server_live_api_details')): ?>
        <div class="api-section">
            <div class="server-details-grid" id="serverDetailsGrid"></div>
        </div>
        <?php endif; ?>
    </div>
    
    <div id="no-servers" style="display: none; text-align: center; padding: 50px;">
        <p>No server information available.</p>
    </div>
</main>

<script>
const serverCardVisibility = <?php echo json_encode($serverCardVisibility); ?>;

function getServerCardFeatureKey(serverName) {
    let slug = String(serverName || 'unknown_server')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');

    if (!slug) {
        slug = 'unknown_server';
    }

    return `server_card_${slug}`;
}

function isServerCardEnabled(serverName) {
    const featureKey = getServerCardFeatureKey(serverName);
    return serverCardVisibility[featureKey] !== false;
}

async function loadServers() {
    try {
        // Use client-side API
        const result = await window.dcsAPI.getServers();
        
        document.getElementById('servers-loading').style.display = 'none';
        
        // Handle both direct data and wrapped response
        const data = result.data || result;
        
        if (!data || result.error || data.length === 0) {
            document.getElementById('no-servers').style.display = 'block';
            return;
        }
        
        const serverDetailsGrid = document.getElementById('serverDetailsGrid');
        if (!serverDetailsGrid) {
            document.getElementById('no-servers').style.display = 'block';
            return;
        }

        serverDetailsGrid.innerHTML = '';
        
        // Handle both array and object with servers property
        const servers = Array.isArray(data) ? data : (data.servers || []);
        let visibleServerCount = 0;
        
        servers.forEach((server, index) => {
            if (!isServerCardEnabled(server.name || `Server ${index + 1}`)) {
                return;
            }

            visibleServerCount++;

            // Extract mission data if available
            let missionName = 'N/A';
            let theatre = 'N/A';
            let playerCount = 'N/A';
            let uptime = 'N/A';
            let slotSummary = 'N/A';
            
            if (server.mission) {
                missionName = server.mission.name || 'N/A';
                theatre = server.mission.theatre || 'N/A';
                
                // Calculate player counts
                const blueUsed = server.mission.blue_slots_used || 0;
                const blueTotal = server.mission.blue_slots || 0;
                const redUsed = server.mission.red_slots_used || 0;
                const redTotal = server.mission.red_slots || 0;
                const totalUsed = blueUsed + redUsed;
                const totalSlots = blueTotal + redTotal;
                
                playerCount = `${totalUsed}/${totalSlots} (B:${blueUsed}/${blueTotal} R:${redUsed}/${redTotal})`;
                slotSummary = `${totalUsed}/${totalSlots} slots used`;
                
                // Format uptime
                if (server.mission.uptime !== undefined) {
                    const hours = Math.floor(server.mission.uptime / 3600);
                    const minutes = Math.floor((server.mission.uptime % 3600) / 60);
                    uptime = `${hours}h ${minutes}m`;
                }
            }
            
            serverDetailsGrid.appendChild(createServerDetailCard(server, {
                missionName,
                theatre,
                playerCount,
                uptime,
                slotSummary
            }));
        });

        if (visibleServerCount === 0) {
            document.getElementById('no-servers').style.display = 'block';
            document.getElementById('servers-container').style.display = 'none';
            return;
        }
        
        document.getElementById('servers-container').style.display = 'block';
        
    } catch (error) {
        // Error loading servers
        document.getElementById('servers-loading').style.display = 'none';
        document.getElementById('no-servers').style.display = 'block';
    }
}

function formatWeather(weather) {
    if (!weather) return 'No weather data';
    const parts = [];
    if (weather.temperature !== undefined && weather.temperature !== null) parts.push(`${weather.temperature}C`);
    if (weather.wind_speed !== undefined && weather.wind_speed !== null) parts.push(`${Number(weather.wind_speed).toFixed(1)} m/s wind`);
    if (weather.wind_direction !== undefined && weather.wind_direction !== null) parts.push(`${weather.wind_direction} deg`);
    if (weather.clouds_base !== undefined && weather.clouds_base !== null) parts.push(`cloud base ${weather.clouds_base}m`);
    return parts.length ? parts.join(' | ') : 'No weather data';
}

function formatExtensions(extensions) {
    if (!Array.isArray(extensions) || extensions.length === 0) return '<span class="muted">No extension data</span>';
    return extensions.map(ext => `
        <div class="detail-list-item">
            <strong>${escapeHtml(ext.name || 'Extension')}</strong>
            <span>${escapeHtml(ext.version || '')}</span>
            <small>${escapeHtml(ext.value || '')}</small>
        </div>
    `).join('');
}

function formatPlayers(players) {
    if (!Array.isArray(players) || players.length === 0) return '<span class="muted">No active players</span>';
    return players.slice(0, 8).map(player => `
        <div class="detail-list-item compact">
            <strong>${escapeHtml(player.name || player.nick || 'Unknown')}</strong>
            <span>${escapeHtml(player.side || player.coalition || '')}</span>
        </div>
    `).join('');
}

function createServerDetailCard(server, summary) {
    const card = document.createElement('article');
    card.className = 'server-detail-card';

    const weather = formatWeather(server.weather);
    const extensions = formatExtensions(server.extensions);
    const players = formatPlayers(server.players);
    const restart = server.restart_time ? new Date(server.restart_time).toLocaleString() : 'N/A';
    const status = server.status || 'Unknown';
    const statusClass = `detail-status status-${String(status).toLowerCase()}`;

    card.innerHTML = `
        <div class="server-detail-header">
            <div>
                <h3>${escapeHtml(server.name || 'Unknown Server')}</h3>
                <?php if (isFeatureEnabled('server_detail_description')): ?>
                <p>${escapeHtml(server.description || '')}</p>
                <?php endif; ?>
            </div>
            <?php if (isFeatureEnabled('server_detail_status')): ?>
            <span class="${statusClass}">${escapeHtml(status)}</span>
            <?php endif; ?>
        </div>
        <?php if (isFeatureEnabled('server_detail_mission') || isFeatureEnabled('server_detail_slots') || isFeatureEnabled('server_detail_restart')): ?>
        <div class="detail-metrics">
            <?php if (isFeatureEnabled('server_detail_mission')): ?>
            <div class="mission-metric"><span>Mission</span><strong>${escapeHtml(summary.missionName)}</strong></div>
            <div><span>Theatre</span><strong>${escapeHtml(summary.theatre)}</strong></div>
            <?php endif; ?>
            <?php if (isFeatureEnabled('server_detail_slots')): ?>
            <div><span>Slots</span><strong>${escapeHtml(summary.slotSummary)}</strong></div>
            <?php endif; ?>
            <?php if (isFeatureEnabled('server_detail_restart')): ?>
            <div><span>Restart</span><strong>${escapeHtml(restart)}</strong></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if (isFeatureEnabled('server_detail_weather') || isFeatureEnabled('server_detail_extensions') || isFeatureEnabled('server_detail_active_players')): ?>
        <div class="detail-split">
            <?php if (isFeatureEnabled('server_detail_weather')): ?>
            <section>
                <h4>Weather</h4>
                <p>${escapeHtml(weather)}</p>
            </section>
            <?php endif; ?>
            <?php if (isFeatureEnabled('server_detail_extensions')): ?>
            <section>
                <h4>Extensions</h4>
                ${extensions}
            </section>
            <?php endif; ?>
            <?php if (isFeatureEnabled('server_detail_active_players')): ?>
            <section>
                <h4>Active Players</h4>
                ${players}
            </section>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    `;

    return card;
}

// Load servers on page load
document.addEventListener('DOMContentLoaded', loadServers);

// Refresh every 30 seconds
setInterval(loadServers, 30000);
</script>

<style>
.api-section {
    margin-top: 0;
}

.section-heading {
    margin-bottom: 22px;
    text-align: center;
}

.section-heading h2 {
    color: var(--heading_color);
    font-size: 2rem;
    margin: 0 0 8px;
    text-shadow: 0 0 10px color-mix(in srgb, var(--accent_color) 30%, transparent);
}

.section-heading p {
    color: var(--muted_text_color);
    font-size: 1rem;
    line-height: 1.5;
    margin: 0;
}

.server-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 22px;
}

.server-detail-card {
    background: linear-gradient(135deg, var(--card_color) 0%, var(--card_alt_color) 100%);
    border: 1px solid color-mix(in srgb, var(--accent_color) 36%, transparent);
    border-left: 4px solid color-mix(in srgb, var(--accent_color) 75%, transparent);
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    color: var(--card_text_color);
    padding: 22px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.server-detail-card:hover {
    border-color: var(--accent_color);
    box-shadow: 0 12px 40px color-mix(in srgb, var(--accent_color) 25%, transparent);
    transform: translateY(-2px);
}

.server-detail-header {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
    border-bottom: 1px solid color-mix(in srgb, var(--border_color) 55%, transparent);
    padding-bottom: 16px;
    margin-bottom: 16px;
}

.server-detail-header h3 {
    color: var(--card_heading_color);
    font-size: 1.15rem;
    line-height: 1.3;
    margin: 0 0 8px;
    text-shadow: 0 0 10px color-mix(in srgb, var(--accent_color) 28%, transparent);
}

.server-detail-header p {
    color: var(--card_muted_text_color);
    font-size: 0.92rem;
    margin: 0;
    line-height: 1.4;
}

.detail-status {
    background: color-mix(in srgb, var(--warning_color) 12%, transparent);
    border: 1px solid color-mix(in srgb, var(--warning_color) 35%, transparent);
    border-radius: 999px;
    color: var(--warning_color);
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    padding: 6px 10px;
    text-transform: uppercase;
    white-space: nowrap;
}

.detail-metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}

.detail-metrics div {
    background: color-mix(in srgb, var(--secondary_color) 84%, transparent);
    border: 1px solid color-mix(in srgb, var(--border_color) 55%, transparent);
    border-radius: 6px;
    padding: 12px;
}

.detail-metrics span {
    display: block;
    color: var(--card_heading_color);
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.detail-metrics strong {
    display: block;
    color: var(--card_text_color);
    font-size: 0.98rem;
    line-height: 1.35;
    margin-top: 6px;
    overflow-wrap: anywhere;
    word-break: normal;
}

.detail-metrics .mission-metric {
    grid-column: 1 / -1;
}

.detail-metrics .mission-metric strong {
    font-size: clamp(0.86rem, 1.2vw, 0.98rem);
    line-height: 1.45;
    white-space: normal;
}

.detail-split {
    display: grid;
    gap: 14px;
}

.detail-split section {
    background: color-mix(in srgb, var(--secondary_color) 84%, transparent);
    border: 1px solid color-mix(in srgb, var(--border_color) 55%, transparent);
    border-radius: 6px;
    padding: 14px;
}

.detail-split h4 {
    color: var(--card_heading_color);
    font-size: 0.95rem;
    letter-spacing: 0.03em;
    margin: 0 0 10px;
    text-shadow: 0 0 8px color-mix(in srgb, var(--accent_color) 25%, transparent);
}

.detail-split p {
    margin: 0;
    color: var(--card_text_color);
}

.detail-list-item {
    display: grid;
    gap: 3px;
    padding: 9px 0;
    border-top: 1px solid color-mix(in srgb, var(--border_color) 55%, transparent);
}

.detail-list-item:first-of-type {
    border-top: 0;
}

.detail-list-item strong {
    color: var(--card_heading_color);
    font-size: 0.95rem;
}

.detail-list-item span,
.detail-list-item small,
.muted {
    color: var(--card_muted_text_color);
}

.detail-list-item small {
    white-space: pre-line;
}

.detail-status.status-online,
.detail-status.status-running {
    background: color-mix(in srgb, var(--success_color) 12%, transparent);
    border-color: color-mix(in srgb, var(--success_color) 42%, transparent);
    color: var(--success_color);
}

.detail-status.status-offline,
.detail-status.status-shutdown {
    background: color-mix(in srgb, var(--danger_color) 12%, transparent);
    border-color: color-mix(in srgb, var(--danger_color) 42%, transparent);
    color: var(--danger_color);
}

.detail-status.status-starting,
.detail-status.status-paused {
    background: color-mix(in srgb, var(--warning_color) 12%, transparent);
    border-color: color-mix(in srgb, var(--warning_color) 42%, transparent);
    color: var(--warning_color);
}

.detail-status.status-unknown {
    background: color-mix(in srgb, var(--muted_text_color) 12%, transparent);
    border-color: color-mix(in srgb, var(--muted_text_color) 35%, transparent);
    color: var(--card_muted_text_color);
}

/* Mobile Responsive Styles */
@media screen and (max-width: 768px) {
    /* Dashboard header mobile */
    .dashboard-header h1 {
        font-size: 1.8rem;
    }
    
    .dashboard-subtitle {
        font-size: 0.9rem;
        padding: 0 10px;
    }
    
    /* Loading and no-servers messages */
    #servers-loading,
    #no-servers {
        padding: 30px 15px !important;
        font-size: 1rem;
    }

    .server-details-grid {
        grid-template-columns: 1fr;
    }

    .server-detail-header {
        display: grid;
    }

    .detail-status {
        justify-self: start;
    }

    .detail-metrics {
        grid-template-columns: 1fr;
    }
}

/* Ensure hide-mobile works */
@media screen and (max-width: 768px) {
    .hide-mobile {
        display: none !important;
    }
}
</style>

<?php include 'footer.php'; ?>
