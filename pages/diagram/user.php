<?php

session_start();


require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);


        // Prepare SQL statement
        $stmt = $obj->insertData("nodes", $data);

        echo json_encode([
            'message' => 'User added successfully'
        ]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to add node: ' . $e->getMessage(),
        ], JSON_PRETTY_PRINT);
    }
}
