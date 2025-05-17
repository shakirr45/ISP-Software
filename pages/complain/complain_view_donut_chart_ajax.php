<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json'); // Ensure JSON response

$obj = new Model();

try {
    $pakageCount = $obj->rawSql("SELECT c.complain_type, t.template, COUNT(c.id) AS count 
                                 FROM tbl_complains c
                                 JOIN tbl_complain_templates t ON c.complain_type = t.id
                                 GROUP BY c.complain_type, t.template 
                                 ORDER BY c.complain_type DESC");

    if (!$pakageCount) {
        throw new Exception("Failed to fetch data.");
    }

    $pakageCountData = [];
    foreach ($pakageCount as $data) {
        $pakageCountData[] = [
            'template' => $data['template'],
            'count' => $data['count']
        ];
    }

    echo json_encode(['pakageCountData' => $pakageCountData]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
exit();
