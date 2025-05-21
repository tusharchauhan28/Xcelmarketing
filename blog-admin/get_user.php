<?php
// get_user.php
session_start();
include 'config.php';

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die(json_encode(['success' => false, 'message' => 'Invalid CSRF token']));
}

// Define valid systems
$systems = [
    'fabmediteach' => 'fab_user',
    'xcel' => 'xcel_user',
    'rcs' => 'rcs_user'
];

$response = ['success' => false];

if (isset($_POST['id']) && isset($_POST['system']) && array_key_exists($_POST['system'], $systems)) {
    $userId = intval($_POST['id']);
    $tableName = $systems[$_POST['system']];
    
    $stmt = $conn->prepare("SELECT id, name, email, role FROM $tableName WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response = [
            'success' => true,
            'data' => $result->fetch_assoc()
        ];
    } else {
        $response['message'] = 'User not found';
    }
} else {
    $response['message'] = 'Invalid request';
}

header('Content-Type: application/json');
echo json_encode($response);
?>