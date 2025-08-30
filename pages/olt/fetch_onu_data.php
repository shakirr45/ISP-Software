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

// OIDs
$oids = [
    'name'     => "1.3.6.1.2.1.2.2.1.2",
    'rx_power' => "1.3.6.1.4.1.3320.101.10.5.1.5",
    'tx_power' => "1.3.6.1.4.1.3320.101.10.5.1.6",
    'distance' => "1.3.6.1.4.1.3320.101.10.1.1.27",
    'serial'   => "1.3.6.1.4.1.3320.101.10.1.1.3",
];
$oidIfHCInOctets  = "1.3.6.1.2.1.31.1.1.1.10";
$oidIfHCOutOctets = "1.3.6.1.2.1.31.1.1.1.6";

// SNMP helper
function snmpWalkLines($community, $oltIp, $oid){
    $output = shell_exec("snmpwalk -v2c -c $community $oltIp $oid 2>&1");
    return explode("\n", trim($output));
}

logMessage("Fetching data from OLT: $oltIp");

// Step 1: Fetch all OLT data
$data = [];
foreach($oids as $key=>$oid){
    $lines = snmpWalkLines($community,$oltIp,$oid);
    foreach($lines as $line){
        if(preg_match('/\.(\d+) = (?:STRING|INTEGER|Hex-STRING|Gauge32): ?"?(.+?)"?$/',$line,$matches)){
            $index = $matches[1];
            $value = $matches[2];
            if(in_array($key,['rx_power','tx_power'])){
                $value=(int)$value;
                if($value==-65535) continue;
                $value=$value/10;
            }
            if($key==='serial'){
                $hex=preg_replace('/[^0-9A-Fa-f ]/','',$value);
                $value=strtoupper(str_replace(' ',':',trim($hex)));
            }
            $data[$index][$key]=$value;
        }
    }
}

// Step 2: Download bytes
foreach(snmpWalkLines($community,$oltIp,$oidIfHCInOctets) as $line){
    if(preg_match('/\.(\d+) = Counter64: (\d+)/',$line,$matches)){
        $index=$matches[1];
        $data[$index]['download_bytes']=(int)$matches[2];
    }
}

// Step 3: Upload bytes
foreach(snmpWalkLines($community,$oltIp,$oidIfHCOutOctets) as $line){
    if(preg_match('/\.(\d+) = Counter64: (\d+)/',$line,$matches)){
        $index=$matches[1];
        $data[$index]['upload_bytes']=(int)$matches[2];
    }
}

// Step 4: Filter EPON only
$onuPorts = array_filter($data,function($item){
    return isset($item['name']) && preg_match('/^EPON\d+\/\d+:\d+$/',$item['name']);
});

// Step 5: Sort logically
uasort($onuPorts,function($a,$b){
    preg_match('/EPON(\d+)\/(\d+):(\d+)/',$a['name'],$m1);
    preg_match('/EPON(\d+)\/(\d+):(\d+)/',$b['name'],$m2);
    return [$m1[1],$m1[2],$m1[3]]<=>[$m2[1],$m2[2],$m2[3]];
});

// Step 6: Insert/Update DB
$inserted = 0;
foreach($onuPorts as $onu){
    $stmt = $pdo->prepare("
        INSERT INTO onu_status (olt_ip,interface_name,serial,distance,tx_power,rx_power,download_bytes,upload_bytes)
        VALUES (:olt_ip,:interface_name,:serial,:distance,:tx_power,:rx_power,:download_bytes,:upload_bytes)
        ON DUPLICATE KEY UPDATE
            serial=:serial, distance=:distance, tx_power=:tx_power, rx_power=:rx_power,
            download_bytes=:download_bytes, upload_bytes=:upload_bytes, last_updated=CURRENT_TIMESTAMP()
    ");
    $stmt->execute([
        ':olt_ip'=>$oltIp,
        ':interface_name'=>$onu['name'],
        ':serial'=>$onu['serial'] ?? null,
        ':distance'=>$onu['distance'] ?? null,
        ':tx_power'=>$onu['tx_power'] ?? null,
        ':rx_power'=>$onu['rx_power'] ?? null,
        ':download_bytes'=>$onu['download_bytes'] ?? null,
        ':upload_bytes'=>$onu['upload_bytes'] ?? null
    ]);
    $inserted++;
}

logMessage("ONU data fetched and updated successfully for {$inserted} ports.");
