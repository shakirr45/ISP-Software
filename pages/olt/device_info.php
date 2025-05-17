<?php
require 'vendor/autoload.php'; // FreeDSx SNMP

$ip = '172.35.156.14';
$community = 'bsd';

$error = null;
$device = [];

try {
    // SNMP GET using FreeDSx
    $snmp = new \FreeDSx\Snmp\SnmpClient([
        'host' => $ip,
        'version' => 2,
        'community' => $community,
    ]);

    $sysDescr = $snmp->getValue('1.3.6.1.2.1.1.1.0');
    $sysName = $snmp->getValue('1.3.6.1.2.1.1.5.0');
    $cpuUsage = $snmp->getValue('1.3.6.1.2.1.4.2.0'); // Optional, may not be supported

    // System uptime
    $uptimeRaw = shell_exec("snmpwalk -v2c -c $community $ip 1.3.6.1.2.1.1.3.0");
    preg_match('/Timeticks:.*\((\d+)\)\s+(.+)/', $uptimeRaw, $uptimeMatches);
    $sysUpTime = $uptimeMatches[2] ?? 'N/A';

    // Memory usage
    $oid = "1.3.6.1.4.1.3320.9.48.1";
    $output = shell_exec("snmpwalk -v2c -c $community $ip $oid");
    $lines = explode("\n", trim($output));
    $memoryUsage = 'N/A';
    if (isset($lines[0])) {
        preg_match('/INTEGER:\s*(\d+)/', $lines[0], $matches);
        $memoryUsage = $matches[1] ?? 'N/A';
    }

    $device = [
        'sysDescr' => $sysDescr,
        'sysName' => $sysName,
        'sysUpTime' => $sysUpTime,
        'cpuUsage' => $cpuUsage,
        'memoryUsage' => $memoryUsage,
    ];

} catch (Exception $e) {
    $error = 'SNMP query failed: ' . $e->getMessage();
}
?>


<div class="container">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-2xl">
        <h2 class="text-2xl font-bold mb-4 text-center">Device Info</h2>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 gap-4">
                <div><strong>System Description:</strong></div>
                <div><?= htmlspecialchars($device['sysDescr'] ?? 'N/A') ?></div>

                <div><strong>System Name:</strong></div>
                <div><?= htmlspecialchars($device['sysName'] ?? 'N/A') ?></div>

                <div><strong>System Uptime:</strong></div>
                <div><?= htmlspecialchars($device['sysUpTime'] ?? 'N/A') ?></div>

                <div><strong>CPU Usage:</strong></div>
                <div><?= htmlspecialchars($device['cpuUsage'] ?? 'N/A') ?></div>

                <div><strong>Memory Usage:</strong></div>
                <div><?= htmlspecialchars($device['memoryUsage'] ?? 'N/A') ?> %</div>
            </div>
        <?php endif; ?>
    </div>

</div>