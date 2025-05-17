<?php
session_start();
// require_once './config.php';

// Instantiate and connect to the database
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);


        // Prepare SQL statement
        $stmt = $obj->insertData("items", $data);

        // Bind parameters

        echo json_encode([
            'message' => 'Item added successfully'
        ]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to add node: ' . $e->getMessage(),
        ], JSON_PRETTY_PRINT);
    }
}
