<?php
require_once __DIR__ . '/../../services/Database.php';

$db = new Database();
$pdo = $db->getConnection();

// = Fetch data from database with proper sorting
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
?>

<div class="container py-4">
    <!-- Header Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-primary fw-bold">
                    🖧 ONU Port Overview
                </h5>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-info text-dark">Total: <?= $totalOnus ?></span>
                    <span class="badge bg-success">Connected: <?= $connectedOnus ?></span>
                    <span class="badge bg-danger">Down: <?= $downOnus ?></span>
                    <button onclick="location.reload()" class="btn btn-sm btn-outline-primary">
                        🔄 Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-bordered align-middle mb-0">
                    <thead class="table-primary text-center">
                        <tr>
                            <th scope="col" style="width: 5%;">SL</th>
                            <th scope="col" style="width: 15%;">Interface</th>
                            <th scope="col" style="width: 20%;">MAC Address</th>
                            <th scope="col" style="width: 15%;">Connection State</th>
                            <th scope="col" style="width: 15%;">Vendor</th>
                            <th scope="col" style="width: 15%;">Uptime</th>
                            <th scope="col" style="width: 15%;">Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sl = 1; foreach($onuPorts as $onu): ?>
                            <tr class="text-center">
                                <td class="fw-semibold"><?= $sl++ ?></td>
                                
                                <td>
                                    <code class="bg-light px-2 py-1 rounded">
                                        <?= htmlspecialchars($onu['interface_name']) ?>
                                    </code>
                                </td>
                                
                                <td>
                                    <span class="font-monospace text-muted">
                                        <?= $onu['serial_number'] ?? '-' ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <?php
                                        $status = $onu['oper_status'] ?? 'Unknown';
                                        $badgeClass = match ($status) {
                                            'Connected' => 'bg-success',
                                            'Down' => 'bg-danger',
                                            'Testing' => 'bg-warning text-dark',
                                            'Dormant', 'Lower Layer Down' => 'bg-secondary',
                                            'Not Present' => 'bg-dark',
                                            default => 'bg-outline-secondary',
                                        };
                                        $icon = match ($status) {
                                            'Connected' => '',
                                            'Down' => '',
                                            'Testing' => '',
                                            default => '',
                                        };
                                    ?>
                                    <span class="badge <?= $badgeClass ?> px-3">
                                        <?= $icon ?> <?= $status ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($onu['vendor_id'] ?? '-') ?>
                                    </small>
                                </td>
                                
                                <td>
                                    <small class="font-monospace">
                                        <?= $onu['uptime'] ?? '-' ?>
                                    </small>
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
        </div>
    </div>

    <!-- Footer Info -->
    <div class="mt-3">
        <small class="text-muted">
            <i class="bi bi-info-circle"></i> 
            Data refreshed every minute via cron job. Last refresh: 
            <?= $onuPorts ? date('M j, Y H:i:s', strtotime($onuPorts[0]['last_updated'] ?? 'now')) : 'Never' ?>
        </small>
    </div>
</div>