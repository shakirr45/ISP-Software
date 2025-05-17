<?php

session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

header('Content-Type: application/json');

$allData = $obj->rawSql("SELECT tbl_agent.ag_name,customer_billing.totaldiscount FROM customer_billing LEFT JOIN tbl_agent ON customer_billing.agid = tbl_agent.ag_id");


$data = array();
foreach ($allData as $row) {
	$data[] = $row;
}

echo json_encode($data);
?>