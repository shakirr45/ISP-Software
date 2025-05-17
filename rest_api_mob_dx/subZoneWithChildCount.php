<?php
include("dbconfig_robotisp.php");
$parent_id = $_GET['parent_id'];
$sql = "SELECT 
            z1.zone_id AS main_zone_id, 
            z1.zone_name AS main_zone_name, 
            COUNT(z2.zone_id) AS sub_zone_count
        FROM 
            tbl_zone z1
        LEFT JOIN 
            tbl_zone z2 
        ON 
            z1.zone_id = z2.parent_id
        WHERE 
            z1.parent_id = $parent_id
        GROUP BY 
            z1.zone_id, z1.zone_name;";

$result = mysqli_query($con, $sql);

if ($result) {
    $zones = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $zones[] = [
            'zone_id' => $row['main_zone_id'],
            'zone_name' => $row['main_zone_name'],
            'area_count' => $row['sub_zone_count']
        ];
    }
    echo json_encode($zones);
} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_close($con);
?>