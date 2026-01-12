<?php
session_start();

// Enforce session authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include 'database.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Validate ID is numeric
    if (empty($id) || !is_numeric($id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid ID']);
        exit;
    }

    try {
        // Update task status (only if task belongs to user)
        $stmt = $conn->prepare("UPDATE tasks SET status='in progress' WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $id, ':user_id' => $user_id]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID required']);
}
?>
