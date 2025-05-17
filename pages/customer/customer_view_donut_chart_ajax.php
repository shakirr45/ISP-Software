<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();


$currentYear = date("Y");
$previousYear = $currentYear - 1;
$pakageCountData = [];


$pakageCount = $obj->rawSql("SELECT COUNT(mb) AS mb_count FROM tbl_agent GROUP BY mb order by mb desc");
$mbbsCountData = $obj->rawSql("SELECT mb FROM tbl_agent GROUP BY mb order by mb desc");


foreach ($pakageCount as $data) {
    $pakageCountData[] = $data["mb_count"];
}


echo json_encode([
    "pakageCountData" => $pakageCountData,
    "mbbsCountData" => $mbbsCountData,

]);
exit();
