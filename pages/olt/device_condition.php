<?php
set_time_limit(300);

// OLT credentials
$oltIp = "172.35.156.14";
$community = "bsd";

// OIDs for ONU info
$oids = [
    'name'     => "1.3.6.1.2.1.2.2.1.2",
    'rx_power' => "1.3.6.1.4.1.3320.101.10.5.1.5",
    'tx_power' => "1.3.6.1.4.1.3320.101.10.5.1.6",
    'distance' => "1.3.6.1.4.1.3320.101.10.1.1.27",
    'serial'   => "1.3.6.1.4.1.3320.101.10.1.1.3",
];

// OIDs for traffic (Counter64)
$oidIfHCInOctets  = "1.3.6.1.2.1.31.1.1.1.10"; // Download bytes
$oidIfHCOutOctets = "1.3.6.1.2.1.31.1.1.1.6";  // Upload bytes

// Function to run snmpwalk and return lines array
function snmpWalkLines($community, $oltIp, $oid) {
    $output = shell_exec("snmpwalk -v2c -c $community $oltIp $oid");
    return explode("\n", trim($output));
}

// Step 1: Fetch ONU basic info (name, power, distance, serial)
$data = [];
foreach ($oids as $key => $oid) {
    $lines = snmpWalkLines($community, $oltIp, $oid);
    foreach ($lines as $line) {
        if (preg_match('/\.(\d+) = (?:STRING|INTEGER|Hex-STRING|Gauge32): ?"?(.+?)"?$/', $line, $matches)) {
            $index = $matches[1];
            $value = $matches[2];

            if (in_array($key, ['rx_power', 'tx_power'])) {
                $value = (int)$value;
                if ($value == -65535) continue;
                $value = $value / 10;
            }

            if ($key === 'serial' && str_starts_with($line, "SNMPv2-SMI")) {
                $hex = preg_replace('/[^0-9A-Fa-f ]/', '', $value);
                $value = strtoupper(str_replace(' ', ':', trim($hex)));
            }

            $data[$index][$key] = $value;
        }
    }
}

// Step 2: Fetch Download Traffic (ifHCInOctets)
$downloadLines = snmpWalkLines($community, $oltIp, $oidIfHCInOctets);
foreach ($downloadLines as $line) {
    if (preg_match('/\.(\d+) = Counter64: (\d+)/', $line, $matches)) {
        $index = $matches[1];
        $data[$index]['download_bytes'] = (int)$matches[2];
    }
}

// Step 3: Fetch Upload Traffic (ifHCOutOctets)
$uploadLines = snmpWalkLines($community, $oltIp, $oidIfHCOutOctets);
foreach ($uploadLines as $line) {
    if (preg_match('/\.(\d+) = Counter64: (\d+)/', $line, $matches)) {
        $index = $matches[1];
        $data[$index]['upload_bytes'] = (int)$matches[2];
    }
}

// Step 4: Filter EPON ONU interfaces only
$onuPorts = array_filter($data, function ($item) {
    return isset($item['name']) && preg_match('/^EPON\d+\/\d+:\d+$/', $item['name']);
});

// Step 5: Sort EPON interfaces logically
uasort($onuPorts, function ($a, $b) {
    preg_match('/EPON(\d+)\/(\d+):(\d+)/', $a['name'], $m1);
    preg_match('/EPON(\d+)\/(\d+):(\d+)/', $b['name'], $m2);
    return [$m1[1], $m1[2], $m1[3]] <=> [$m2[1], $m2[2], $m2[3]];
});
?>

<!-- HTML Output -->
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="badge bg-info text-dark me-3">Total ONUs: <?= count($onuPorts) ?></span>
        <button onclick="location.reload()" class="btn btn-sm btn-outline-primary">🔄 Refresh</button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-primary text-center">
                <tr>
                    <th scope="col">SL</th>
                    <th scope="col">Interface</th>
                    <th scope="col">Distance</th>
                    <th scope="col">Tx Power (dBm)</th>
                    <th scope="col">Rx Power (dBm)</th>
                    <th scope="col">Download (bytes)</th>
                    <th scope="col">Upload (bytes)</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <?php $sl = 1; ?>
                <?php foreach ($onuPorts as $onu): ?>
                    <tr>
                        <td><?= $sl++ ?></td>
                        <td><?= htmlspecialchars($onu['name'] ?? '-') ?></td>
                        <td><?= isset($onu['distance']) ? $onu['distance'] . ' m' : '-' ?></td>
                        <td><?= isset($onu['tx_power']) ? $onu['tx_power'] . ' dBm' : '-' ?></td>
                        <td><?= isset($onu['rx_power']) ? $onu['rx_power'] . ' dBm' : '-' ?></td>
                        <td><?= isset($onu['download_bytes']) ? number_format($onu['download_bytes']) : '-' ?></td>
                        <td><?= isset($onu['upload_bytes']) ? number_format($onu['upload_bytes']) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
