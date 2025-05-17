<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Decode JSON input
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate input
        $complain_id = $data['complain_id'] ?? null;

        // Additional input validation
        if (!$complain_id) {
            throw new Exception('Invalid input: Missing ID or status');
        }

        // Prepare form_data as an associative array
        $form_data = ['deleted_at' => date('Y-m-d H:i:m A')];

        // Prepare where condition as an associative array
        $where_cond = ['id' => $complain_id];

        // Update data
        $query = $obj->updateData("tbl_complains", $form_data, $where_cond);

        // Return success response
        if ($query === true) {
            echo json_encode([
                'success' => true,
                'message' => 'complain deleted successfully'
            ]);
        } else {
            throw new Exception('Update failed: No rows affected');
        }
    } catch (Exception $e) {
        // Return error response
        http_response_code(400); // Bad request
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    // Handle non-POST requests
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
