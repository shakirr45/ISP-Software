<?php
require_once __DIR__ . '/../../services/Database.php'; // fetch_onu_data.php
$db=new Database();
$pdo=$db->getConnection();

$stmt=$pdo->query("SELECT * FROM onu_status ORDER BY 
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(interface_name,'/',1),'EPON',-1) AS UNSIGNED),
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(interface_name,':',1),'/',-1) AS UNSIGNED),
    CAST(SUBSTRING_INDEX(interface_name,':',-1) AS UNSIGNED)
");
$onuPorts=$stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="badge bg-info text-dark me-3">Total ONUs: <?= count($onuPorts) ?></span>
        <button onclick="location.reload()" class="btn btn-sm btn-outline-primary">🔄 Refresh</button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-primary text-center">
                <tr>
                    <th>SL</th>
                    <th>OLT</th>
                    <th>Interface</th>
                    <!-- <th>Serial</th> -->
                    <th>Distance</th>
                    <th>Tx Power (dBm)</th>
                    <th>Rx Power (dBm)</th>
                    <th>Download (GB)</th>
                    <th>Upload (GB)</th>
                    <!-- <th>Last Update</th> -->
                </tr>
            </thead>
            <tbody class="text-center">
                <?php $sl=1; foreach($onuPorts as $onu): ?>
                <tr>
                    <td><?= $sl++ ?></td>
                    <td><?= htmlspecialchars($onu['olt_ip']) ?></td>
                    <td><?= htmlspecialchars($onu['interface_name']) ?></td>
                    <!-- <td><?= htmlspecialchars($onu['serial']??'-') ?></td> -->
                    <td><?= $onu['distance'] ? $onu['distance'].' m' : '-' ?></td>
                    <td><?= $onu['tx_power'] ? $onu['tx_power'].' dBm' : '-' ?></td>
                    <td><?= $onu['rx_power'] ? $onu['rx_power'].' dBm' : '-' ?></td>
                    <td><?= $onu['download_bytes'] ? round($onu['download_bytes']/1073741824,2).' GB' : '-' ?></td>
                    <td><?= $onu['upload_bytes'] ? round($onu['upload_bytes']/1073741824,2).' GB' : '-' ?></td>
                    <!-- <td><?= $onu['last_updated'] ?></td> -->
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
