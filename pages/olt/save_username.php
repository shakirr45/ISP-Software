<?php
require_once __DIR__ . '/../../services/Database.php'; // আপনার ডাটাবেস ফাইল

header('Content-Type: application/json');

$db = new Database();
$pdo = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mac = $_POST['mac'] ?? null;
    $username = trim($_POST['username'] ?? '');

    if (empty($mac) || empty($username)) {
        echo json_encode(['success' => false, 'message' => 'MAC address and username are required.']);
        exit;
    }

    try {
        $sql = "INSERT INTO onu_users (mac_address, user_name) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE user_name = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$mac, $username, $username]);

        echo json_encode(['success' => true, 'message' => 'Username saved successfully!']);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
