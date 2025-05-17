<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();


$agCountData = [];
$zoneCountData = [];


$agCount = $obj->rawSql("SELECT COUNT(ag_id) AS ag_count FROM tbl_agent GROUP BY zone order by zone desc");
$zoneData = $obj->rawSql("SELECT DISTINCT a.zone, z.zone_name FROM tbl_agent a JOIN tbl_zone z ON a.zone = z.zone_id GROUP BY a.zone ORDER BY a.zone DESC");

// Loop through the result set and collect zone names
foreach ($zoneData as $data) {
    $zoneCountData[] = $data['zone_name'];
}
foreach ($agCount as $data) {
    $agCountData[] = $data["ag_count"];
}


echo json_encode([
    "agCountData" => $agCountData,
    "zoneCountData" => $zoneCountData,

]);
exit();
