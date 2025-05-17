<?php
session_start();


require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    try {

        // ডাটাবেস থেকে নোড ডেটা নেওয়া
        $rows = $obj->rawSql("SELECT id, name, parent_id,user_id FROM nodes");


        // রিকার্সিভ ফাংশন - গাছ তৈরি করা
        function buildTree(array $elements, $parentId = null)
        {
            $branch = [];
            foreach ($elements as $element) {
                if ($element['parent_id'] == $parentId) {
                    $children = buildTree($elements, $element['id']);

                    $node = ['name' => $element['name'], 'id' => $element['id'], 'description' => $element['description'] ?? "N/A", 'user_id' => $element['user_id'] ?? null];
                    if ($children) {
                        $node['children'] = $children;
                    }
                    $branch[] = $node;
                }
            }
            return $branch;
        }

        // Root node ডাটাবেস থেকে খোঁজা (যে নোডটির parent_id NULL)
        $rootNodes = buildTree($rows, null);  // root nodes যেগুলোর parent_id NULL

        // যদি রুট নোড পাওয়া যায়
        if (!empty($rootNodes)) {
            $output = $rootNodes[0]; // Root node-টির প্রথমটি নেয়া হচ্ছে
        } else {
            $output = [
                'status' => 'error',
                'message' => 'Root node not found in the database.',
            ];
        }

        // JSON আউটপুট
        header('Content-Type: application/json');
        echo json_encode($output, JSON_PRETTY_PRINT);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to fetch data: ' . $e->getMessage(),
        ], JSON_PRETTY_PRINT);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);


        // Prepare SQL statement
        $stmt = $obj->insertData("nodes", $data);

        $obj->notificationStore('Node added successfully.', 'success');
        echo json_encode([
            'message' => 'Node added successfully'
        ]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        $obj->notificationStore('Node added Failed.', 'error');
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to add node: ' . $e->getMessage(),
        ], JSON_PRETTY_PRINT);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        // Retrieve the node ID
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? $_GET['id'] ?? null;

        if (!$id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Node ID is required.',
            ]);
            exit;
        }

        // Pass conditions as an array of arrays
        $conditions = [
            ['id', '=', $id], // Matches the expected format of deleteData
        ];

        // Call the deleteData method
        $stmt = $obj->deleteData("nodes", $conditions);


        $obj->notificationStore('Node deleted successfully.', 'success');
        echo json_encode([
            'status' => 'success',
            'message' => 'Node deleted successfully',
        ]);
    } catch (PDOException $e) {
        $obj->notificationStore('Node deletion failed.', 'error');

        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete node: ' . $e->getMessage(),
        ], JSON_PRETTY_PRINT);
    }
}
