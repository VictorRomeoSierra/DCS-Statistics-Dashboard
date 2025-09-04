<?php  
header_remove('X-Frame-Options');
header("Content-Security-Policy: frame-ancestors 'self' https://agaar.in");  
?>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>VRS Test Statistics Dashboard</title>
  <link rel="stylesheet" href="/styles.php" />
  <link rel="stylesheet" href="/styles-mobile.css" />
      <script>
    // Path configuration for JavaScript
    window.DCS_CONFIG = {"basePath":"","baseUrl":"http:\/\/stats-test.victorromeosierra.com"};
    
    // XSS Protection function
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
  </script>
  <script src="/js/api-client.js"></script>
  <script src="/mobile-enhancements.js"></script>
</head>
<?php
// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/site_features.php';
require_once __DIR__ . '/table-responsive.php';

if (!isFeatureEnabled('nav_servers')):
?>
<main>
    <div class="alert" style="text-align: center; padding: 50px;">
        <h2>Server Status Disabled</h2>
        <p>The server status page is currently disabled.</p>
    </div>
</main>
<?php endif; ?>

<main>   
    <div id="servers-loading" style="text-align: center; padding: 50px;">
        <p>Loading server information...</p>
    </div>
    
    <div id="servers-container" style="display: none;">
        <div class="table-wrapper">
            <table id="serversTable">
                <tbody id="serversTableBody"></tbody>
            </table>
        </div>
        
        <!-- Mobile Cards Container -->
        <div class="mobile-cards" id="serversCards"></div>
    </div>
    
    <div id="no-servers" style="display: none; text-align: center; padding: 50px;">
        <p>No server information available.</p>
    </div>
</main>

<script>
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
        
        const tbody = document.getElementById('serversTableBody');
        const serversCards = document.getElementById('serversCards');
        tbody.innerHTML = '';
        if (serversCards) serversCards.innerHTML = '';
        
        // Handle both array and object with servers property
        const servers = Array.isArray(data) ? data : (data.servers || []);
        
        servers.forEach((server, index) => {
            
            const row = document.createElement('tr');
            
            // Determine status class
            const statusClass = server.status ? `status-${server.status.toLowerCase()}` : 'status-unknown';
            
            // Extract mission data if available
            let missionName = 'N/A';
            let theatre = 'N/A';
            let playerCount = 'N/A';
            let uptime = 'N/A';
            
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
                
                // Format uptime
                if (server.mission.uptime !== undefined) {
                    const hours = Math.floor(server.mission.uptime / 3600);
                    const minutes = Math.floor((server.mission.uptime % 3600) / 60);
                    uptime = `${hours}h ${minutes}m`;
                }
            }
            
            // Check if password protected
            const passwordStatus = server.password ? '🔒 Yes' : '🔓 No';
            
            // Create cells individually to ensure proper count
            const cells = [
                `<td>${escapeHtml(server.name || 'Unknown')}</td>`,
                `<td><span class="${statusClass}">${escapeHtml(server.status || 'Unknown')}</span></td>`,
                `<td class="hide-mobile">${escapeHtml(server.address || 'N/A')}</td>`,
                `<td class="hide-mobile">${passwordStatus}</td>`,
                `<td>${escapeHtml(missionName)}</td>`,
                `<td class="hide-mobile">${escapeHtml(theatre)}</td>`,
                `<td>${escapeHtml(playerCount)}</td>`,
                `<td class="hide-mobile">${escapeHtml(uptime)}</td>`
            ];
            
            row.innerHTML = cells.join('');
            tbody.appendChild(row);
            
            // Create mobile card
            if (serversCards) {
                const card = document.createElement('div');
                card.className = 'mobile-card server-card';
                
                const statusLower = (server.status || 'unknown').toLowerCase();
                const statusClass = statusLower === 'running' || statusLower === 'online' ? 'online' : 'offline';
                
                card.innerHTML = `
                    <div class="server-card-header">
                        <div>
                            <div class="server-card-name">${escapeHtml(server.name || 'Unknown')}</div>
                            <div class="server-card-mission">${escapeHtml(missionName)}</div>
                        </div>
                        <div class="server-card-status ${statusClass}">
                            ${escapeHtml(server.status || 'Unknown')}
                        </div>
                    </div>
                    <div class="server-card-info">
                        <div class="server-card-info-item">
                            <strong>Players</strong>
                            ${escapeHtml(playerCount)}
                        </div>
                        <div class="server-card-info-item">
                            <strong>Theatre</strong>
                            ${escapeHtml(theatre)}
                        </div>
                        <div class="server-card-info-item">
                            <strong>Password</strong>
                            ${passwordStatus}
                        </div>
                        <div class="server-card-info-item">
                            <strong>Uptime</strong>
                            ${escapeHtml(uptime)}
                        </div>
                    </div>
                `;
                
                serversCards.appendChild(card);
            }
        });
        
        document.getElementById('servers-container').style.display = 'block';
        
    } catch (error) {
        // Error loading servers
        document.getElementById('servers-loading').style.display = 'none';
        document.getElementById('no-servers').style.display = 'block';
    }
}

// Load servers on page load
document.addEventListener('DOMContentLoaded', loadServers);

// Refresh every 30 seconds
setInterval(loadServers, 30000);
</script>

<style>
body {
    background: transparent !important;
    overflow: hidden;
}
main {
    width: 100% !important;
}
.table-responsive {
    overflow-x: auto;
    margin: 20px 0;
}

#serversTable {
    width: 100%;
    border-collapse: collapse;
    background-color: #2c2c2c;
    color: #fff;
    table-layout: auto;
}

#serversTable th {
    background-color: #1e1e1e;
    padding: 12px;
    text-align: left;
    border-bottom: 2px solid #ed145b;
    color: #ed145b;
    white-space: nowrap;
}

#serversTable td {
    padding: 10px;
    border-bottom: 1px solid #444;
    vertical-align: middle;
}

/* Ensure all content is left-aligned */
#serversTable th, #serversTable td {
    text-align: left;
}

#serversTable tr:hover {
    background-color: #3a3a3a;
}

.status-online, .status-running {
    color: #4CAF50;
    font-weight: bold;
}

.status-offline, .status-shutdown {
    color: #f44336;
    font-weight: bold;
}

.status-starting, .status-paused {
    color: #ff9800;
    font-weight: bold;
}

.status-unknown {
    color: #9e9e9e;
    font-weight: bold;
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
    
    /* Table adjustments */
    #serversTable {
        font-size: 0.85rem;
    }
    
    #serversTable th,
    #serversTable td {
        padding: 8px 5px;
        white-space: nowrap;
    }
    
    /* Server name wrapping on mobile */
    #serversTable td:first-child {
        white-space: normal;
        max-width: 150px;
        word-wrap: break-word;
    }
    
    /* Status column */
    #serversTable td:nth-child(2) {
        min-width: 60px;
    }
    
    /* Mission name wrapping */
    #serversTable td:nth-child(5) {
        white-space: normal;
        max-width: 120px;
        word-wrap: break-word;
    }
    
    /* Player count smaller */
    #serversTable td:nth-child(7) {
        font-size: 0.8rem;
    }
    
    /* Loading and no-servers messages */
    #servers-loading,
    #no-servers {
        padding: 30px 15px !important;
        font-size: 1rem;
    }
}

/* Very small devices - show only essential columns */
@media screen and (max-width: 480px) {
    #serversTable {
        font-size: 0.8rem;
    }
    
    #serversTable th,
    #serversTable td {
        padding: 6px 3px;
    }
    
    /* Even smaller server name on very small screens */
    #serversTable td:first-child {
        max-width: 100px;
        font-size: 0.85rem;
    }
    
    /* Simplify player count display */
    #serversTable td:nth-child(7) {
        font-size: 0.75rem;
    }
    
    /* Hide detailed player counts, show only total */
    @media screen and (max-width: 480px) {
        #serversTable td:nth-child(7) {
            text-overflow: ellipsis;
            overflow: hidden;
            max-width: 50px;
        }
    }
}

/* Ensure hide-mobile works */
@media screen and (max-width: 768px) {
    .hide-mobile {
        display: none !important;
    }
}

/* Touch-friendly hover states */
@media (hover: none) and (pointer: coarse) {
    #serversTable tr:hover {
        background-color: transparent;
    }
    
    #serversTable tr:active {
        background-color: #3a3a3a;
    }
}
</style>

<?php tableResponsiveStyles(); ?>