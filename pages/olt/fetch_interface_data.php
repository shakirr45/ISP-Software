<?php
set_time_limit(0); // Endless run safe, cron friendly
date_default_timezone_set('Asia/Dhaka'); // Logging purpose

require_once __DIR__ . '/../../services/Database.php';

$db = new Database();
$pdo = $db->getConnection();

// Logging function
function logMessage($msg){
    $time = date('Y-m-d H:i:s');
    echo "[$time] $msg" . PHP_EOL;
}

// OLT credentials
$oltIp = "172.35.156.14";
$community = "bsd";

// OIDs for ONU overview
$oids = [
    'interface_name' => "1.3.6.1.2.1.2.2.1.2",
    'oper_status'    => "1.3.6.1.2.1.2.2.1.8", 
    'vendor_id'      => "1.3.6.1.4.1.3320.101.10.1.1.1",
    'serial_number'  => "1.3.6.1.4.1.3320.101.10.1.1.3",
    'uptime'         => "1.3.6.1.2.1.2.2.1.9",
    'onu_status'     => "1.3.6.1.4.1.3320.101.11.4.1.5"
];

// SNMP helper function
function snmpWalkLines($community, $oltIp, $oid){
    $output = shell_exec("snmpwalk -v2c -c $community $oltIp $oid 2>&1");
    return explode("\n", trim($output));
}

// Helper function to convert hex to serial
function hexToSerial($hexString) {
    $hex = preg_replace('/[^0-9A-Fa-f ]/', '', $hexString);
    return strtoupper(str_replace(' ', ':', trim($hex)));
}

// Helper function to format uptime
function formatUptime($ticks) {
    $seconds = (int)($ticks / 100);
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    return "{$days}d {$hours}h {$minutes}m";
}

logMessage("Fetching ONU overview data from OLT: $oltIp");

// Step 1: Fetch all ONU overview data
$data = [];

// Status mapping
$statusMap = [
    1 => 'Connected', 
    2 => 'Down', 
    3 => 'Testing', 
    4 => 'Unknown', 
    5 => 'Dormant', 
    6 => 'Not Present', 
    7 => 'Lower Layer Down'
];

foreach($oids as $key => $oid){
    logMessage("Fetching $key data...");
    $lines = snmpWalkLines($community, $oltIp, $oid);
    
    foreach($lines as $line){
        if($key === 'oper_status'){
            if(preg_match('/\.(\d+) = INTEGER: \w+\((\d+)\)/', $line, $matches)){
                $index = $matches[1];
                $statusCode = (int)$matches[2];
                $data[$index][$key] = $statusMap[$statusCode] ?? 'Unknown';
            }
        }
        elseif($key === 'uptime'){
            if(preg_match('/\.(\d+) = Timeticks: \((\d+)\) (.+)/', $line, $matches)){
                $index = $matches[1];
                $ticks = (int)$matches[2];
                $data[$index][$key] = formatUptime($ticks);
            }
        }
        elseif($key === 'serial_number'){
            if(preg_match('/\.(\d+) = (Hex-STRING|STRING):\s(.+)/', $line, $matches)){
                $index = $matches[1];
                $value = trim($matches[3]);
                $data[$index][$key] = $matches[2] === "Hex-STRING" ? hexToSerial($value) : trim($value, '"');
            }
        }
        else {
            if(preg_match('/\.(\d+) = (?:STRING|INTEGER|Hex-STRING|Gauge32): ?"?(.+?)"?$/', $line, $matches)){
                $index = $matches[1];
                $value = trim($matches[2], '"');
                $data[$index][$key] = $value;
            }
        }
    }
}

// Step 2: Filter EPON ports only
$onuPorts = array_filter($data, function($item){
    return isset($item['interface_name']) && preg_match('/^EPON\d+\/\d+:\d+$/', $item['interface_name']);
});

// Step 3: Sort logically
uasort($onuPorts, function($a, $b){
    preg_match('/EPON(\d+)\/(\d+):(\d+)/', $a['interface_name'], $m1);
    preg_match('/EPON(\d+)\/(\d+):(\d+)/', $b['interface_name'], $m2);
    return [$m1[1], $m1[2], $m1[3]] <=> [$m2[1], $m2[2], $m2[3]];
});

logMessage("Found " . count($onuPorts) . " EPON ports");

// Step 4: Insert/Update database
$inserted = 0;
$updated = 0;

try {
    $pdo->beginTransaction();
    
    foreach($onuPorts as $onu){
        // Check if record exists
        $checkStmt = $pdo->prepare("SELECT id FROM onu_overview WHERE olt_ip = ? AND interface_name = ?");
        $checkStmt->execute([$oltIp, $onu['interface_name']]);
        
        if($checkStmt->fetch()){
            // Update existing record
            $stmt = $pdo->prepare("
                UPDATE onu_overview SET 
                    oper_status = ?,
                    vendor_id = ?,
                    serial_number = ?,
                    uptime = ?,
                    onu_status = ?,
                    last_updated = CURRENT_TIMESTAMP
                WHERE olt_ip = ? AND interface_name = ?
            ");
            $stmt->execute([
                $onu['oper_status'] ?? null,
                $onu['vendor_id'] ?? null, 
                $onu['serial_number'] ?? null,
                $onu['uptime'] ?? null,
                $onu['onu_status'] ?? null,
                $oltIp,
                $onu['interface_name']
            ]);
            $updated++;
        } else {
            // Insert new record
            $stmt = $pdo->prepare("
                INSERT INTO onu_overview (olt_ip, interface_name, oper_status, vendor_id, serial_number, uptime, onu_status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $oltIp,
                $onu['interface_name'],
                $onu['oper_status'] ?? null,
                $onu['vendor_id'] ?? null,
                $onu['serial_number'] ?? null, 
                $onu['uptime'] ?? null,
                $onu['onu_status'] ?? null
            ]);
            $inserted++;
        }
    }
    
    $pdo->commit();
    logMessage("ONU overview data updated successfully. Inserted: $inserted, Updated: $updated");
    
} catch(Exception $e) {
    $pdo->rollBack();
    logMessage("Error updating database: " . $e->getMessage());
}
?>