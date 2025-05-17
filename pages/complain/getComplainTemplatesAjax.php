<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

$wherecond = '';
$allTemplateData = [];
$i = 1;

// Pagination (start and length)
$start = $_GET['start'] ?? 0;
$length = $_GET['length'] ?? 10;

// get query
$sql = "
    SELECT *
    FROM tbl_complain_templates
    WHERE deleted_at IS NULL
    ORDER BY id DESC 
    LIMIT $length OFFSET $start;
";

$allData = $obj->rawSql($sql);

foreach ($allData as $item) {
    $complainCount = $obj->raw_sql("SELECT COUNT(complain_type) AS total_complaints
                        FROM tbl_complains
                        WHERE complain_type = $item[id] AND deleted_at IS NULL;");

    $allTemplateData[] = [
        'sl' => $i++,
        'id' => $item['id'],
        'template' => [
            "template" => $item['template'] ?? 'N/A',
            'totalComplaints' => $complainCount[0]["total_complaints"] ?? 'N/A'
        ]
    ];
}

// Return or use $allTemplateData as required
echo json_encode([
    "data" => $allTemplateData,
    "sadi" => "Tanvir Hossain Sadi"
]);

exit();
