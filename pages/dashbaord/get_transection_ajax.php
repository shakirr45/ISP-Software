<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();
$allAgentData = [];
$i = 1;

$sql = "
 SELECT 
        acc.*, 
    agent.ag_name AS customer_name,  
        agent.ag_mobile_no AS phone,
        u.FullName AS create_by
    FROM tbl_account acc
    LEFT JOIN vw_agent agent ON acc.agent_id = agent.ag_id
    LEFT JOIN _createuser u ON acc.entry_by = u.UserId
    ORDER BY acc.acc_id DESC 
    LIMIT 10 OFFSET 0;
";

$allData = $obj->rawSql($sql);


foreach ($allData as $item) {


    $allAgentData[] = [
        'sl' => $i++,
        'customer_name' => $item['customer_name'] ?? 'N/A',
        'phone' => $item['phone'] ?? 'N/A',
        'entry_date' => $item['entry_date'] ?? 'N/A',
        'amount' => $item['acc_amount'] ?? 'N/A',
        'type' => $item['acc_type'],
        'entry_by' => $item['create_by'],
    ];
}

echo json_encode([
    "data" => $allAgentData,
]);

exit();
