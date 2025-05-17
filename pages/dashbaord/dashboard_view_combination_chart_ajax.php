<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();


$currentYear = date("Y");

$CurrentYear = $obj->rawSql("SELECT count(ag_id)as id,sum(taka) as amount, MONTH(entry_date) AS month FROM tbl_agent WHERE YEAR(entry_date)='$currentYear' AND ag_status='1' GROUP BY MONTH(entry_date)");

$currentYearData = array_fill(0, 12, 0); // Default all months to 0

$currentYearData = array_fill(0, 12, 0); // Default all months to 0


foreach ($CurrentYear as $data) {
    $currentYearData[$data['month'] - 1] =  [
        'countId' => $data["id"],
        'total_taka' => $data['amount']
    ];
}

echo json_encode([
    "totalData" => $currentYearData,
]);
