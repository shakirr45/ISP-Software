<?php
set_time_limit(0);
date_default_timezone_set('Asia/Dhaka');

require_once '/var/www/html/olt6/services/Database.php';
$db = new Database();
$pdo = $db->getConnection();

// ====== User activity update ======
$page = basename(__FILE__, '.php');
$stmt = $pdo->prepare("
    INSERT INTO user_activity (page, last_active) VALUES (?, NOW())
    ON DUPLICATE KEY UPDATE last_active = NOW()
");
$stmt->execute([$page]);

// ===== AJAX request: live stats =====
if(isset($_GET['action']) && $_GET['action'] === 'live_stats'){
    $latest = $pdo->query("SELECT MAX(last_updated) AS last_updated FROM onu_status")->fetchColumn();
    $total = $pdo->query("SELECT COUNT(*) FROM onu_status")->fetchColumn();
    $active = $pdo->query("SELECT COUNT(*) FROM onu_status WHERE (rx_power > -30 OR tx_power > -30)")->fetchColumn();

    header('Content-Type: application/json');
    echo json_encode([
        'total' => $total ?: 0,
        'active' => $active ?: 0,
        'last_updated' => $latest ?: null
    ]);
    exit;
}

// ===== Fetch data for table =====
$stmt = $pdo->query("
    SELECT * FROM onu_status
    ORDER BY
        CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(interface_name,'/',1),'EPON',-1) AS UNSIGNED),
        CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(interface_name,':',1),'/',-1) AS UNSIGNED),
        CAST(SUBSTRING_INDEX(interface_name,':',-1) AS UNSIGNED)
");
$onuPorts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === Find the latest update timestamp from the initial data load ===
$initialLastUpdated = '';
if (!empty($onuPorts)) {
    // Get all 'last_updated' values and find the maximum one
    $initialLastUpdated = max(array_column($onuPorts, 'last_updated'));
}


// ===== Helper functions =====
function formatBytes($bytes){
    if(!$bytes || $bytes <= 0) return '-';
    return round($bytes / 1073741824, 2) . ' GB';
}

function getPowerClass($power){
    if($power === null) return 'text-muted';
    if($power >= -20) return 'text-success fw-bold';
    if($power >= -25) return 'text-info fw-bold';
    if($power >= -30) return 'text-warning fw-bold';
    return 'text-danger fw-bold';
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="text-primary mb-0">📡 Device Condition Monitor</h5>
        <div class="d-flex gap-2 align-items-center">

            <div id="liveSpinner" class="spinner-border text-primary" role="status"></div>
            <span id="liveStatusText" class="text-muted small">Live data fetching...</span>

            <span class="badge bg-primary" id="totalOnus">Total: 0</span>
            <span class="badge bg-success" id="activeOnus">Active: 0</span>

            <span class="text-primary small" id="lastUpdated">-</span>

            <button id="refreshButton" onclick="location.reload()" class="btn btn-sm btn-outline-primary d-none">🔄 Refresh</button>
        </div>
    </div>

    <div class="table-responsive border rounded">
        <table class="table table-striped table-hover align-middle mb-0" data-initial-last-updated="<?= htmlspecialchars($initialLastUpdated) ?>">
            <thead class="table-primary">
                <tr class="text-center">
                    <th>SL</th>
                    <th>OLT</th>
                    <th>Interface</th>
                    <th>Serial</th>
                    <th>Distance</th>
                    <th>Tx Power (dBm)</th>
                    <th>Rx Power (dBm)</th>
                    <th>Download</th>
                    <th>Upload</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <?php $sl=1; foreach($onuPorts as $onu): ?>
                <tr>
                    <td class="fw-semibold"><?= $sl++ ?></td>
                    <td class="font-monospace small"><?= htmlspecialchars($onu['olt_ip']) ?></td>
                    <td><code class="bg-light px-2 py-1 rounded small"><?= htmlspecialchars($onu['interface_name']) ?></code></td>
                    <td class="font-monospace small text-muted"><?= htmlspecialchars($onu['serial'] ?? '-') ?></td>
                    <td><?= $onu['distance'] ? "<span class='fw-semibold'>{$onu['distance']} m</span>" : "<span class='text-muted'>-</span>" ?></td>
                    <td class="<?= getPowerClass($onu['tx_power']) ?>"><?= $onu['tx_power'] ? $onu['tx_power'].' dBm' : '-' ?></td>
                    <td class="<?= getPowerClass($onu['rx_power']) ?>"><?= $onu['rx_power'] ? $onu['rx_power'].' dBm' : '-' ?></td>
                    <td class="text-success fw-semibold"><?= formatBytes($onu['download_bytes']) ?></td>
                    <td class="text-primary fw-semibold"><?= formatBytes($onu['upload_bytes']) ?></td>
                    <td class="text-primary"><?= $onu['last_updated'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3 text-center">
        <small class="text-muted">
            Data refreshed every minute | Last update:
            <span id="footerLastUpdated">-</span>
        </small>
    </div>
</div>

<script>
// Select elements once outside the function for better performance
const totalOnusEl = document.getElementById('totalOnus');
const activeOnusEl = document.getElementById('activeOnus');
const lastUpdatedEl = document.getElementById('lastUpdated');
const footerLastUpdatedEl = document.getElementById('footerLastUpdated');
const spinner = document.getElementById('liveSpinner');
const refreshBtn = document.getElementById('refreshButton');
const statusText = document.getElementById('liveStatusText');
const table = document.querySelector('table');
const initialLastUpdated = table.dataset.initialLastUpdated;

function updateLiveStats(){
    // Using ?action=live_stats to target the AJAX logic in the same file
    fetch('/olt6/pages/olt/live_onu_stats.php')
    .then(res => {
        if (!res.ok) {
            throw new Error('Network response was not ok');
        }
        return res.json();
    })
    .then(data => {
        // Update the stats display
        totalOnusEl.textContent = "Total: " + data.total;
        activeOnusEl.textContent = "Active: " + data.active;
        
        let formattedTime = data.last_updated ? new Date(data.last_updated).toLocaleString() : '-';
        lastUpdatedEl.textContent = formattedTime;
        footerLastUpdatedEl.textContent = formattedTime;

        // Core Logic: Compare timestamps and toggle visibility
        const liveLastUpdated = data.last_updated;

        if (liveLastUpdated && initialLastUpdated && liveLastUpdated !== initialLastUpdated) {
            // If timestamps do NOT match, data is stale. Show refresh button.
            spinner.classList.add('d-none');
            refreshBtn.classList.remove('d-none');
            statusText.textContent = 'New data available!';
        } else {
            // If timestamps match, data is current. Show spinner.
            spinner.classList.remove('d-none');
            refreshBtn.classList.add('d-none');
            statusText.textContent = 'Live data fetching...';
        }
    })
    .catch(err => {
        console.error('Error fetching live stats:', err);
        statusText.textContent = 'Error fetching data.';
        spinner.classList.add('d-none'); // Hide spinner on error
    });
}

// Auto update every 5 seconds
setInterval(updateLiveStats, 5000);
updateLiveStats(); // Initial call to get data immediately
</script>