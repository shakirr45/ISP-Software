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
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="text-primary mb-0">📡 Device Condition Monitor</h5>
        <div class="d-flex gap-2 align-items-center">

            <!-- Spinner -->
            <div class="spinner-border text-primary" role="status"></div>
            <span class="text-muted small">Live data fetching...</span>

            <!-- Stats badges -->
            <span class="badge bg-primary" id="totalOnus">Total: 0</span>
            <span class="badge bg-success" id="activeOnus">Active: 0</span>

            <!-- Last updated -->
            <span class="text-primary small" id="lastUpdated">-</span>

            <!-- Refresh button -->
            <button onclick="location.reload()" class="btn btn-sm btn-outline-primary d-none">🔄 Refresh</button>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive border rounded">
        <table class="table table-striped table-hover align-middle mb-0">
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

    <!-- Footer -->
    <div class="mt-3 text-center">
        <small class="text-muted">
            Data refreshed every minute | Last update:
            <span id="footerLastUpdated">-</span>
        </small>
    </div>
</div>

<!-- JS: Live Update -->
<script>
function updateLiveStats(){
    fetch('/olt6/pages/olt/live_onu_stats.php')
    .then(res => res.json())
    .then(data => {
        document.querySelector('#totalOnus').textContent = "Total: "+data.total;
        document.querySelector('#activeOnus').textContent = "Active: "+data.active;
        let formatted = data.last_updated ? new Date(data.last_updated).toLocaleString() : '-';
        document.querySelector('#lastUpdated').textContent = formatted;
        document.querySelector('#footerLastUpdated').textContent = formatted;
    })
    .catch(err => console.error('Error fetching live stats:', err));
}

// Auto update every 5 seconds
setInterval(updateLiveStats, 5000);
updateLiveStats(); // initial call
</script>
