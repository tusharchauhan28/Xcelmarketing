<?php
// delete_user.php
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
    
    // Prevent deleting the last superadmin
    $checkStmt = $conn->prepare("SELECT role FROM $tableName WHERE id = ?");
    $checkStmt->bind_param("i", $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if ($user['role'] === 'superadmin') {
            $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM $tableName WHERE role = 'superadmin'");
            $countStmt->execute();
            $count = $countStmt->get_result()->fetch_assoc()['count'];
            
            if ($count <= 1) {
                $response['message'] = 'Cannot delete the last superadmin';
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }
        }
        
        // Proceed with deletion
        $stmt = $conn->prepare("DELETE FROM $tableName WHERE id = ?");
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'User deleted successfully'];
        } else {
            $response['message'] = 'Error deleting user: ' . $stmt->error;
        }
    } else {
        $response['message'] = 'User not found';
    }
} else {
    $response['message'] = 'Invalid request';
}

header('Content-Type: application/json');
echo json_encode($response);
?>