<?php
set_time_limit(600);

$oltIp = "103.89.26.227:1200";
$community = "bsd";

// OIDs
$oids = [
    'mac'      => "1.3.6.1.4.1.34592.1.3.1.1.2.1.1.2.1.2",
    'vendor'   => "1.3.6.1.4.1.17409.2.3.4.1.1.25",   // Correct ONU Vendor OID
    'status'   => "1.3.6.1.4.1.17409.2.3.4.1.1.8",    // Up/Down OID
    'rx_power' => "1.3.6.1.4.1.17409.2.3.4.2.1.4",
    'tx_power' => "1.3.6.1.4.1.17409.2.3.4.2.1.5",
    'distance' => "1.3.6.1.4.1.17409.2.3.4.2.1.6",   // New: ONU distance
];

/**
 * Helper: Convert Hex-STRING to ASCII
 */
function hexToAscii($hexStr) {
    $hexStr = trim(str_replace([' ', "\n", "\r"], '', $hexStr));
    $ascii = '';
    for ($i = 0; $i < strlen($hexStr); $i += 2) {
        $char = hexdec(substr($hexStr, $i, 2));
        if ($char > 31 && $char < 127) { // printable chars only
            $ascii .= chr($char);
        }
    }
    return $ascii ?: $hexStr;
}

/**
 * Fetches all OID data using snmpbulkwalk
 */
function fetchOnuDataFast($community, $host, $oids) {
    $data = [];

    foreach ($oids as $key => $oid) {
        $cmd = "snmpbulkwalk -v2c -c $community -t 10 -r 2 -Cr50 -O n $host $oid 2>&1";
        $output = shell_exec($cmd);
        if (!$output) continue;

        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (strpos($line, '=') === false) continue;

            list($fullOid, $valueStr) = explode(' = ', $line, 2);
            $index = substr($fullOid, strlen($oid) + 1);
            $index = preg_replace('/\.0\.0$/', '', $index);

            if (preg_match('/(STRING|Hex-STRING|INTEGER): ?"?(.+?)"?$/', $valueStr, $matches)) {
                $val = trim($matches[2]);

                // Vendor Hex fix
                if ($key === 'vendor' && $matches[1] === 'Hex-STRING') {
                    $val = hexToAscii($val);
                }

                $data[$index][$key] = $val;
            }
        }
    }
    return $data;
}

// Fetch all data
$allOnuData = fetchOnuDataFast($community, $oltIp, $oids);

// Helper functions
function formatMac($hexStr) {
    $hexStr = str_replace([' ', '0x'], '', $hexStr);
    if (strlen($hexStr) < 12) return $hexStr;
    return strtoupper(implode(':', str_split($hexStr, 2)));
}

function formatPower($val) {
    if (!is_numeric($val) || $val == -32768) return '-';
    return number_format((float)$val / 100, 2) . " dBm";
}

function formatStatus($val) {
    if ($val == 1) return "<span class='badge bg-success'>Up</span>";
    if ($val == 2) return "<span class='badge bg-danger'>Down</span>";
    return "<span class='badge bg-secondary'>Unknown</span>";
}

// Vendor simple output
function formatVendor($vendor) {
    return htmlspecialchars($vendor);
}

// Format distance
function formatDistance($val) {
    if (!is_numeric($val)) return '-';
    return $val . " m";
}
?>

<div class="container py-4">
    <h4>CData OLT ONU Status</h4>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span>Total ONU: <?= count($allOnuData) ?></span>
        <button onclick="location.reload()" class="btn btn-sm btn-outline-primary">Refresh</button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-primary text-center">
                <tr>
                    <th>SL</th>
                    <th>MAC Address</th>
                    <th>Vendor / Model</th>
                    <th>Status</th>
                    <th>Rx Power</th>
                    <th>Tx Power</th>
                    <th>Distance</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <?php
                if (empty($allOnuData)) {
                    echo '<tr><td colspan="7">No data fetched. Check OLT or community string.</td></tr>';
                } else {
                    $sl = 1;
                    foreach ($allOnuData as $onu) {
                        if (empty($onu['mac'])) continue;
                        ?>
                        <tr>
                            <td><?= $sl++ ?></td>
                            <td><?= htmlspecialchars(formatMac($onu['mac'])) ?></td>
                            <td><?= isset($onu['vendor']) ? formatVendor($onu['vendor']) : '-' ?></td>
                            <td><?= isset($onu['status']) ? formatStatus($onu['status']) : '-' ?></td>
                            <td><?= htmlspecialchars(formatPower($onu['rx_power'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars(formatPower($onu['tx_power'] ?? '-')) ?></td>
                            <td><?= isset($onu['distance']) ? formatDistance($onu['distance']) : '-' ?></td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
