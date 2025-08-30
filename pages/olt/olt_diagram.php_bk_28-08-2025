<?php
set_time_limit(300);

$oltIp = "172.35.156.14";
$community = "bsd";

// SNMP OIDs
$oidIfDescr       = "1.3.6.1.2.1.2.2.1.2";
$oidRxPower       = "1.3.6.1.4.1.3320.101.10.5.1.5";
$oidTxPower       = "1.3.6.1.4.1.3320.101.10.5.1.6";
$oidIfOperStatus  = "1.3.6.1.2.1.2.2.1.8";

// Run SNMP commands
$descrOutput        = shell_exec("snmpwalk -v2c -c {$community} {$oltIp} {$oidIfDescr}");
$rxPowerOutput      = shell_exec("snmpwalk -v2c -c {$community} {$oltIp} {$oidRxPower}");
$txPowerOutput      = shell_exec("snmpwalk -v2c -c {$community} {$oltIp} {$oidTxPower}");
$statusOutput       = shell_exec("snmpwalk -v2c -c {$community} {$oltIp} {$oidIfOperStatus}");

$interfaceData = [];

// Parse Interface Descriptions
foreach (explode("\n", trim($descrOutput)) as $line) {
    if (preg_match('/\.(\d+) = STRING: (.+)/', $line, $m)) {
        $interfaceData[$m[1]]['name'] = trim($m[2]);
    }
}

// Parse Rx Power
foreach (explode("\n", trim($rxPowerOutput)) as $line) {
    if (preg_match('/\.(\d+) = INTEGER: (-?\d+)/', $line, $m) && $m[2] != -65535) {
        $interfaceData[$m[1]]['rx_power'] = $m[2] / 10;
    }
}

// Parse Tx Power
foreach (explode("\n", trim($txPowerOutput)) as $line) {
    if (preg_match('/\.(\d+) = INTEGER: (-?\d+)/', $line, $m) && $m[2] != -65535) {
        $interfaceData[$m[1]]['tx_power'] = $m[2] / 10;
    }
}

// Parse Interface Status
foreach (explode("\n", trim($statusOutput)) as $line) {
    if (preg_match('/\.(\d+) = INTEGER: \w+\((\d+)\)/', $line, $matches)) {
        $index = $matches[1];
        $statusCode = $matches[2];
        $statusMap = [
            1 => 'Connected',
            2 => 'Down',
            3 => 'Testing',
            4 => 'Unknown',
            5 => 'Dormant',
            6 => 'Not Present',
            7 => 'Lower Layer Down'
        ];
        $interfaceData[$index]['status'] = $statusMap[$statusCode] ?? 'Unknown';
    }
}

// Build EPON Tree
$eponTree = [];

foreach ($interfaceData as $index => $item) {
    $name = $item['name'] ?? '';

    if (preg_match('/^EPON0\/(\d+):(\d+)$/', $name, $match)) {
        $port = "EPON0/" . $match[1];
        $eponTree[$port]['onus'][] = [
            'name'     => $name,
            'rx_power' => $item['rx_power'] ?? null,
            'tx_power' => $item['tx_power'] ?? null,
            'status'   => $item['status'] ?? 'Unknown',
        ];
    } elseif (preg_match('/^EPON0\/(\d+)$/', $name)) {
        $eponTree[$name]['onus'] = $eponTree[$name]['onus'] ?? [];
    }
}

// Function to get status class for styling
function getStatusClass($status) {
    $status = strtolower($status);
    return match($status) {
        'connected' => 'up',
        'down' => 'down',
        default => 'other',
    };
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>OLT to ONU Topology</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #f0f2f5;
        }

        ul.tree, ul.tree ul {
            list-style: none;
            padding-left: 20px;
            position: relative;
        }

        ul.tree ul::before {
            content: '';
            border-left: 1px solid #ccc;
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
        }

        ul.tree li {
            margin: 0;
            padding: 8px 5px;
            position: relative;
        }

        ul.tree li::before {
            content: '';
            position: absolute;
            top: 12px;
            left: -18px;
            width: 20px;
            height: 1px;
            background: #ccc;
        }

        .olt {
            font-weight: bold;
            color: #000;
            font-size: 1.1em;
        }

        .epon {
            font-weight: bold;
            color: #007bff;
            font-size: 1em;
        }

        .onu {
            color: #333;
            font-size: 0.9em;
        }

        .power {
            font-size: 0.7em;
            color: #666;
            margin-left: 18px;
        }

        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .up { background-color: green; }
        .down { background-color: red; }
        .other { background-color: orange; }

        .up-text {
            color: green;
            font-weight: bold;
            font-size: 0.8em;
        }

        .down-text {
            color: red;
            font-weight: bold;
            font-size: 0.8em;
        }

        .other-text {
            color: orange;
            font-weight: bold;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <ul class="tree">
        <li class="olt">OLT
            <ul>
                <?php foreach ($eponTree as $port => $data): ?>
                    <li class="epon"><?= htmlspecialchars($port) ?>
                        <ul>
                            <?php foreach ($data['onus'] ?? [] as $onu): 
                                $statusClass = getStatusClass($onu['status'] ?? 'Unknown');
                            ?>
                                <li class="onu">
                                    <span class="status-dot <?= $statusClass ?>"></span>
                                    <?= htmlspecialchars($onu['name']) ?>
                                    <div class="power">
                                        Rx: <?= htmlspecialchars($onu['rx_power'] ?? 'N/A') ?> dBm |
                                        Tx: <?= htmlspecialchars($onu['tx_power'] ?? 'N/A') ?> dBm |
                                        Status: <span class="<?= $statusClass ?>-text"><?= htmlspecialchars($onu['status'] ?? 'Unknown') ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>
        </li>
    </ul>
</body>
</html>
