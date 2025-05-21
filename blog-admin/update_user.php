<?php
// update_user.php
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

if (isset($_POST['id']) && isset($_POST['system']) && isset($_POST['username']) && 
    isset($_POST['email']) && isset($_POST['role']) && array_key_exists($_POST['system'], $systems)) {
    
    $userId = intval($_POST['id']);
    $tableName = $systems[$_POST['system']];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    
    // Basic validation
    if (empty($username) || empty($email) || empty($role)) {
        $response['message'] = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format';
    } else {
        // Check if email is already used by another user
        $checkStmt = $conn->prepare("SELECT id FROM $tableName WHERE email = ? AND id != ?");
        $checkStmt->bind_param("si", $email, $userId);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            $response['message'] = 'Email already exists in the system';
        } else {
            // Update user
            $stmt = $conn->prepare("UPDATE $tableName SET name = ?, email = ?, role = ? WHERE id = ?");
            $stmt->bind_param("sssi", $username, $email, $role, $userId);
            
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'User updated successfully'];
            } else {
                $response['message'] = 'Error updating user: ' . $stmt->error;
            }
        }
    }
} else {
    $response['message'] = 'Invalid request';
}

header('Content-Type: application/json');
echo json_encode($response);
?>