<?php
session_start();

// Enforce session authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include 'database.php';

header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("SELECT id, content, status, created_at FROM items WHERE user_id = :user_id AND type = 'task' ORDER BY created_at DESC");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($tasks);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
