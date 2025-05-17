<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

try {
    // Check if 'mkid' is provided in the POST request
    if (isset($_POST['mkid'])) {
        // Fetch the latest agent details
        $data = $obj->details_by_cond("tbl_agent", "ag_status='1' ORDER BY ag_id DESC limit 1");

        if (!$data) {
            throw new Exception("Failed to fetch agent details.");
        }

        // Generate customer ID (CUS...)
        if (($data['ag_id'] + 1) < 10) {
            $STD = "CUS000";
        } else if (($data['ag_id'] + 1) < 100) {
            $STD = "CUS000";
        } else if (($data['ag_id'] + 1) < 1000) {
            $STD = "CUS00";
        } else if (($data['ag_id'] + 1) < 10000) {
            $STD = "CUS0";
        } else {
            $STD = "CUS";
        }
        $STD .= $data['ag_id'] + 1;

        // Collect POST data
        $id = $_POST['mkid'];
        $status = $_POST['status'];
        $profile = $_POST['profile'];
        $password = $_POST['password'];
        $name = $_POST['name'];

        // Fetch package price based on the profile
        $packageData = $obj->details_by_cond('tbl_package', 'net_speed ="' . $profile . '"');
        if (!$packageData) {
            throw new Exception("Failed to fetch package data for profile: $profile");
        }
        $packagesPrice = $packageData['bill_amount'];

        // Prepare data for insertion
        $formArray = [
            'cus_id' => $STD++,
            'ip' => $name,
            'queue_password' => $password,
            'ag_name' => $name,
            'type' => 1,
            'ag_mobile_no' => 01,
            'mb' => $profile,
            'int_mb' => intval($profile),
            'taka' => $packagesPrice,
            'ag_status' => ($status == 'false') ? 1 : 0,
            'entry_date' => date('Y-m-d', strtotime('first day of this month')),
            'mikrotik_id' => $id,
            'connection_date' => date('Y-m-d'),
        ];

        // Insert agent data
        $agentId = $obj->Insert_data("tbl_agent", $formArray);
        if (!$agentId) {
            throw new Exception("Failed to insert agent data.");
        }
        
        if ($agentId) {
            $obj->insertData('customer_billing', [
                'agid' => $agentId,
                'cusid' => $STD,
                'monthlybill' => $obj->convertBanglaToEnglishNumbers($packagesPrice),
                'generate_at' => '2024-01-01'
            ]);
        }

        // Success response
        $response = [
            'status' => 'Data Added successfully!',
            'success' => true,
        ];
    } else {
        throw new Exception("Missing required parameter: mkid");
    }
} catch (Exception $e) {
    // Error response
    $response = [
        'status' => $e->getMessage(),
        'success' => false,
    ];
}

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
