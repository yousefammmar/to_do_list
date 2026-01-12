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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST['type'] ?? '';
    $content = trim($_POST['content'] ?? '');
    $user_id = $_SESSION['user_id'];

    // Validate input
    if (empty($type) || empty($content)) {
        http_response_code(400);
        echo json_encode(['error' => 'Type and content are required']);
        exit;
    }

    if (!in_array($type, ['task', 'note'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid type']);
        exit;
    }
    try {
        // Insert task or note into items table
        // For notes, we'll use 'pending' as status (it's required by ENUM, but won't be displayed)
        $stmt = $conn->prepare("INSERT INTO items (user_id, type, content, status) VALUES (:user_id, :type, :content, :status)");
        $status = ($type === 'task') ? 'pending' : 'pending';
        $stmt->execute([
            ':user_id' => $user_id,
            ':type' => $type,
            ':content' => $content,
            ':status' => $status
        ]);

        $item_id = $conn->lastInsertId();

        http_response_code(200);
        echo json_encode(['success' => true, 'id' => $item_id]);
    } catch(PDOException $e) {
        http_response_code(500);
        // Return more detailed error for debugging (remove in production)
        error_log('Database error in save.php: ' . $e->getMessage());
        echo json_encode(['error' => 'Database error. Please try again.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>

