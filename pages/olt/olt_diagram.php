<?php
set_time_limit(0);
date_default_timezone_set('Asia/Dhaka');
require_once __DIR__ . '/../../services/Database.php';
$db = new Database();
$pdo = $db->getConnection();

$stmt = $pdo->query("SELECT * FROM onu_status ORDER BY interface_name ASC");
$onuPorts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusStmt = $pdo->query("SELECT interface_name, oper_status FROM onu_overview");
$statusData = $statusStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$eponTree = [];
foreach($onuPorts as $item){
    $name = $item['interface_name'];
    $item['status'] = $statusData[$name] ?? 'Unknown';

    if(preg_match('/^EPON0\/(\d+):(\d+)$/', $name, $m)){
        $port = "EPON0/".$m[1];
        $eponTree[$port]['onus'][] = $item;
    } elseif(preg_match('/^EPON0\/(\d+)$/', $name)){
        $eponTree[$name]['onus'] = $eponTree[$name]['onus'] ?? [];
    }
}

$links = [];
$nodesColor = []; 

foreach($eponTree as $port => $data){
    $links[] = ['OLT', $port];
    $nodesColor[$port] = '#007bff'; 

    foreach($data['onus'] ?? [] as $onu){
        $links[] = [$port, $onu['interface_name']];

        $status = strtolower($onu['status']);
        $color = match($status){
            'connected'=>'green',
            'down'=>'red',
            default=>'orange'
        };
        $nodesColor[$onu['interface_name']] = $color;
    }
}
$nodesColor['OLT'] = '#000000'; 
?>

<div id="container" style="height: 600px;"></div>

<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/networkgraph.js"></script>

<script>
Highcharts.chart('container', {
    chart: {
        type: 'networkgraph',
        marginTop: 80
    },
    title: {
        text: 'OLT → EPON → ONU Network Diagram'
    },
    plotOptions: {
        networkgraph: {
            keys: ['from', 'to'],
            layoutAlgorithm: {
                enableSimulation: false,
                integration: 'verlet',
                linkLength: 100
            }
        }
    },
    series: [{
        marker: { radius: 10 },
        dataLabels: { enabled: true },
        data: <?php echo json_encode($links); ?>,
        nodes: <?php 
            $nodes = [];
            foreach($nodesColor as $id => $color){
                $nodes[] = ['id'=>$id, 'color'=>$color];
            }
            echo json_encode($nodes);
        ?>
    }]
});
</script>
