<?php
require_once __DIR__ . '/../../services/Database.php';

$db = new Database();
$pdo = $db->getConnection();

// Fetch data with proper sorting
$stmt = $pdo->query("
    SELECT * FROM onu_status 
    ORDER BY 
        CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(interface_name,'/',1),'EPON',-1) AS UNSIGNED),
        CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(interface_name,':',1),'/',-1) AS UNSIGNED),
        CAST(SUBSTRING_INDEX(interface_name,':',-1) AS UNSIGNED)
");
$onuPorts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$totalOnus = count($onuPorts);
$activeOnus = count(array_filter($onuPorts, fn($onu) => 
    ($onu['rx_power'] && $onu['rx_power'] > -30) || 
    ($onu['tx_power'] && $onu['tx_power'] > -30)
));

// Helper functions
function formatBytes($bytes) {
    if(!$bytes || $bytes <= 0) return '-';
    return round($bytes / 1073741824, 2) . ' GB';
}

function getPowerClass($power) {
    if($power === null) return 'text-muted';
    if($power >= -20) return 'text-success fw-bold';
    if($power >= -25) return 'text-info fw-bold';
    if($power >= -30) return 'text-warning fw-bold';
    return 'text-danger fw-bold';
}
?>

<div class="container py-4">
    <!-- Simple Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="text-primary mb-0">📡 Device Condition Monitor</h5>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-primary">Total: <?= $totalOnus ?></span>
            <span class="badge bg-success">Active: <?= $activeOnus ?></span>
            <button onclick="location.reload()" class="btn btn-sm btn-outline-primary">🔄 Refresh</button>
        </div>
    </div>

    <!-- Simple Professional Table -->
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
                </tr>
            </thead>
            <tbody class="text-center">
                <?php $sl = 1; foreach($onuPorts as $onu): ?>
                <tr>
                    <td class="fw-semibold"><?= $sl++ ?></td>
                    <td class="font-monospace small"><?= htmlspecialchars($onu['olt_ip']) ?></td>
                    <td>
                        <code class="bg-light px-2 py-1 rounded small">
                            <?= htmlspecialchars($onu['interface_name']) ?>
                        </code>
                    </td>
                    <td class="font-monospace small text-muted">
                        <?= htmlspecialchars($onu['serial'] ?? '-') ?>
                    </td>
                    <td>
                        <?php if($onu['distance']): ?>
                            <span class="fw-semibold"><?= $onu['distance'] ?> m</span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="<?= getPowerClass($onu['tx_power']) ?>">
                        <?= $onu['tx_power'] ? $onu['tx_power'] . ' dBm' : '-' ?>
                    </td>
                    <td class="<?= getPowerClass($onu['rx_power']) ?>">
                        <?= $onu['rx_power'] ? $onu['rx_power'] . ' dBm' : '-' ?>
                    </td>
                    <td class="text-success fw-semibold">
                        <?= formatBytes($onu['download_bytes']) ?>
                    </td>
                    <td class="text-primary fw-semibold">
                        <?= formatBytes($onu['upload_bytes']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Simple Footer -->
    <div class="mt-3 text-center">
        <small class="text-muted">
            Data refreshed every minute | Last update: 
            <?= $onuPorts ? date('M j, Y H:i:s', strtotime($onuPorts[0]['last_updated'] ?? 'now')) : 'Never' ?>
        </small>
    </div>
</div>