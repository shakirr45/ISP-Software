<?php
session_start();
// require_once './config.php';

// Instantiate and connect to the database
// $database = new DatabaseConnection();
// $pdo = $database->connect();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

if (isset($_GET['id'])) {

    try {
        $id = $_GET['id'];

        $data = $obj->rawSql("SELECT * FROM items WHERE node_id = $id");


        header('Content-Type: application/json');
        echo json_encode($data);
    } catch (PDOException $e) {
        echo "Fetch data failed: " . $e->getMessage();
    }
} else {
    // যদি ID সেট না থাকে, তাহলে একটি এরর রিটার্ন করুন
    http_response_code(400);
    echo json_encode(['error' => 'ID parameter is missing.']);
}
