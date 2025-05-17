<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();
$allAgentData = [];
$i = 1;

$sql = "
    SELECT 
        c.*, 
        a.ag_name AS customer_name,  
        a.ag_mobile_no AS phone,
        t.template AS complain_name
    FROM tbl_complains c
    LEFT JOIN vw_agent a ON c.customer_id = a.ag_id
    LEFT JOIN tbl_complain_templates t ON c.complain_type = t.id
    WHERE c.deleted_at IS NULL 
    ORDER BY c.id DESC 
    LIMIT 5 OFFSET 0;
";
$allData = $obj->rawSql($sql);


foreach ($allData as $item) {


    $allAgentData[] = [
        'sl' => $i++,
        'customer_name' => $item['customer_name'] ?? 'N/A',
        'phone' => $item['phone'] ?? 'N/A',
        'id' => $item['id'] ?? 'N/A',
        'complain_date' => $item['complain_date'] ?? 'N/A',
        'complain_name' => $item['complain_name'] ?? 'N/A',
        'status_name' => $item['status'],
    ];
}

echo json_encode([
    "data" => $allAgentData,
]);

exit();
