<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include RouterOS API class
// require(realpath(__DIR__ . '/pages/diagram/routeros-api.php'));
require(realpath(__DIR__ . '/../../services/routeros-api.php'));

$api = new RouterosAPI();
if ($api->connect('110.11.99.225', 'bsd', 'bsd@123')) {
    $api->write('/ppp/secret/print');
    $interfaces = $api->read();
    // $api->disconnect();
    header('Content-Type: application/json'); // Set content type to JSON

    // Transform the data into a hierarchical format
    $treeData = [];
    foreach ($interfaces as $interface) {
        $treeData[] = [
            'name' => $interface['name'],        // Node name
            'parent' => $interface['parent'] ?? null, // Parent node
            'status' => $interface['status'] ?? 'inactive', // Status
            'bandwidth' => $interface['rate-limit'] ?? 'Unknown', // Bandwidth
        ];
    }
    echo json_encode($treeData);


    // Ensure no extra output before sending JSON

} else {
    // In case of connection failure, return an error message
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unable to connect to Mikrotik']);
}
exit;
