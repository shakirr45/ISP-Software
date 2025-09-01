<?php
require_once __DIR__ . '/../../services/Database.php';

$db = new Database();
$pdo = $db->getConnection();

// Fetch data with proper sorting
$stmt = $pdo->query("
    SELECT * FROM onu_overview
    ORDER BY
        CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(interface_name,'/',1),'EPON',-1) AS UNSIGNED),
        CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(interface_name,':',1),'/',-1) AS UNSIGNED),
        CAST(SUBSTRING_INDEX(interface_name,':',-1) AS UNSIGNED)
");
$onuPorts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count statistics
$totalOnus = count($onuPorts);
$connectedOnus = count(array_filter($onuPorts, fn($onu) => $onu['oper_status'] === 'Connected'));
$downOnus = count(array_filter($onuPorts, fn($onu) => $onu['oper_status'] === 'Down'));
$otherOnus = $totalOnus - $connectedOnus - $downOnus;

// Helper function for status badges
function getStatusBadge($status) {
    switch($status) {
        case 'Connected': return 'bg-success';
        case 'Down': return 'bg-danger';
        case 'Testing': return 'bg-warning text-dark';
        case 'Dormant': return 'bg-secondary';
        case 'Not Present': return 'bg-dark';
        default: return 'bg-secondary';
    }
}
?>

<div class="container py-4">
    <!-- Simple Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="text-primary mb-0">🖧 Interface State Monitor</h5>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-info text-dark">Total: <?= $totalOnus ?></span>
            <span class="badge bg-success">Connected: <?= $connectedOnus ?></span>
            <span class="badge bg-danger">Down: <?= $downOnus ?></span>
            <?php if($otherOnus > 0): ?>
                <span class="badge bg-secondary">Other: <?= $otherOnus ?></span>
            <?php endif; ?>
            <button onclick="location.reload()" class="btn btn-sm btn-outline-primary">🔄 Refresh</button>
        </div>
    </div>

    <!-- Simple Professional Table -->
    <div class="table-responsive border rounded">
        <table class="table table-striped table-hover table-bordered align-middle mb-0">
            <thead class="table-primary">
                <tr class="text-center">
                    <th style="width: 5%;">SL</th>
                    <th style="width: 15%;">Interface</th>
                    <th style="width: 20%;">Serial Number</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 15%;">Vendor</th>
                    <th style="width: 15%;">Uptime</th>
                    <th style="width: 15%;">Last Updated</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <?php $sl = 1; foreach($onuPorts as $onu): ?>
                <tr>
                    <td class="fw-semibold"><?= $sl++ ?></td>

                    <td>
                        <code class="bg-light px-2 py-1 rounded">
                            <?= htmlspecialchars($onu['interface_name']) ?>
                        </code>
                    </td>

                    <td>
                        <span class="font-monospace text-muted small">
                            <?= htmlspecialchars($onu['serial_number'] ?? '-') ?>
                        </span>
                    </td>

                    <td>
                        <span class="badge <?= getStatusBadge($onu['oper_status'] ?? 'Unknown') ?> px-3">
                            <?= htmlspecialchars($onu['oper_status'] ?? 'Unknown') ?>
                        </span>
                    </td>

                    <td>
                        <small class="text-muted">
                            <?= htmlspecialchars($onu['vendor_id'] ?? '-') ?>
                        </small>
                    </td>

                    <td>
                        <?php if($onu['uptime']): ?>
                            <span class="badge bg-info text-dark">
                                <?= htmlspecialchars($onu['uptime']) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <small class="text-muted">
                            <?= $onu['last_updated'] ? date('M j, H:i', strtotime($onu['last_updated'])) : '-' ?>
                        </small>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Simple Footer -->
    <div class="mt-3 text-center">
        <small class="text-muted">
            Auto-refresh every minute | Last update: 
            <?= $onuPorts ? date('M j, Y H:i:s', strtotime($onuPorts[0]['last_updated'] ?? 'now')) : 'Never' ?>
        </small>
    </div>
</div>