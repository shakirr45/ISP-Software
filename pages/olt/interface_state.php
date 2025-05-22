<?php

set_time_limit(300);

$oltIp = "172.35.156.14";
$community = "bsd";

// SNMP OIDs
$oidIfDescr     = "1.3.6.1.2.1.2.2.1.2";
$oidIfOperStatus = "1.3.6.1.2.1.2.2.1.8";
$oidVendorId    = "1.3.6.1.4.1.3320.101.10.1.1.1";
$oidSerial      = "1.3.6.1.4.1.3320.101.10.1.1.3";
$oidOnuUpTime     = "1.3.6.1.2.1.2.2.1.9"; 

// SNMP Fetching
function snmpFetch($oid) {
    global $oltIp, $community;
    return explode("\n", trim(shell_exec("snmpwalk -v2c -c $community $oltIp $oid")));
}

$descrLines     = snmpFetch($oidIfDescr);
$statusLines    = snmpFetch($oidIfOperStatus);
$vendorIdLines  = snmpFetch($oidVendorId);
$serialLines    = snmpFetch($oidSerial);
$upTimeLines    = snmpFetch($oidOnuUpTime);


$interfaceData = [];

function hexToSerial($hexString) {
    $parts = explode(' ', $hexString);
    return strtoupper(implode(':', $parts));
}

// Interface Name
foreach ($descrLines as $line) {
    if (preg_match('/\.(\d+) = STRING: (.+)/', $line, $matches)) {
        $interfaceData[$matches[1]] = ['name' => $matches[2]];
    }
}

// Status
$statusMap = [1 => 'Connected', 2 => 'Down', 3 => 'Testing', 4 => 'Unknown', 5 => 'Dormant', 6 => 'Not Present', 7 => 'Lower Layer Down'];
foreach ($statusLines as $line) {
    if (preg_match('/\.(\d+) = INTEGER: \w+\((\d+)\)/', $line, $matches)) {
        $interfaceData[$matches[1]]['status'] = $statusMap[$matches[2]] ?? 'Unknown';
    }
}

// Vendor ID
foreach ($vendorIdLines as $line) {
    if (preg_match('/\.(\d+) = STRING: "?(.+?)"?$/', $line, $matches)) {
        $interfaceData[$matches[1]]['vendor_id'] = $matches[2];
    }
}


// Serial Number
foreach ($serialLines as $line) {
    if (preg_match('/\.(\d+) = (Hex-STRING|STRING):\s(.+)/', $line, $matches)) {
        $index = $matches[1];
        $value = trim($matches[3]);

        $interfaceData[$index]['serial_number'] = $matches[2] === "Hex-STRING" ? hexToSerial($value) : trim($value, '"');
    }
}

    // ONU Uptime (Timeticks to readable format)
    foreach ($upTimeLines as $line) {
        if (preg_match('/\.(\d+) = Timeticks: \((\d+)\) (.+)/', $line, $matches)) {
            $index = $matches[1];
            $ticks = (int)$matches[2];
            $seconds = (int)($ticks / 100); // Cast to int before modulo operations
            $days = floor($seconds / 86400);
            $hours = floor(($seconds % 86400) / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            $formatted = "{$days}d {$hours}h {$minutes}m";
            $interfaceData[$index]['uptime'] = $formatted;
        }
    }


// Filter ONU Ports
$onuPorts = array_filter($interfaceData, function ($data) {
    return isset($data['name']) && preg_match('/^EPON\d+\/\d+:\d+$/', $data['name']);
});

// Sort logically
uasort($onuPorts, function ($a, $b) {
    preg_match('/EPON(\d+)\/(\d+):(\d+)/', $a['name'], $aMatch);
    preg_match('/EPON(\d+)\/(\d+):(\d+)/', $b['name'], $bMatch);
    return [$aMatch[1], $aMatch[2], $aMatch[3]] <=> [$bMatch[1], $bMatch[2], $bMatch[3]];
});

?>



<div class="container py-4">

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-primary fw-bold">
                🖧 ONU Port Overview
            </h5>
            <div>
                <span class="badge bg-info text-dark me-3">Total ONUs: <?= count($onuPorts) ?></span>
                <button onclick="location.reload()" class="btn btn-sm btn-outline-primary">
                    🔄 Refresh
                </button>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-bordered align-middle mb-0">
                    <thead class="table-primary text-center">
                        <tr>
                            <th scope="col">SL</th>
                            <th scope="col">Interface</th>
                            <th scope="col">MAC Address</th>
                            <th scope="col">Status</th>
                            <th scope="col">Vendor</th>
                            <th scope="col">Uptime</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sl = 1; foreach ($onuPorts as $onu): ?>
                            <tr class="text-center">
                                <td><?= $sl++ ?></td>
                                <td><code><?= htmlspecialchars($onu['name']) ?></code></td>
                                <td><span class="text-monospace"><?= $onu['serial_number'] ?? '-' ?></span></td>
                                <td>
                                    <?php
                                        $status = $onu['status'] ?? 'Unknown';
                                        $badgeClass = match ($status) {
                                            'Connected' => 'bg-success',
                                            'Down' => 'bg-danger',
                                            'Testing' => 'bg-warning text-dark',
                                            'Dormant', 'Lower Layer Down' => 'bg-secondary',
                                            default => 'bg-dark',
                                        };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= $status ?></span>
                                </td>
                                <td><?= $onu['vendor_id'] ?? '-' ?></td>
                                <td><?= $onu['uptime'] ?? '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
