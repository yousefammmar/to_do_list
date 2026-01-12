<?php
session_start();

// Enforce session authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'] ?? '';
    $user_id = $_SESSION['user_id'];
    $content = isset($_POST['content']) ? trim($_POST['content']) : null;
    $status = $_POST['status'] ?? null;

    if (empty($id) || !is_numeric($id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid ID']);
        exit;
    }

    try {
        // Build update query dynamically based on what's provided
        $updates = [];
        $params = [':id' => $id, ':user_id' => $user_id];

        if ($content !== null) {
            $updates[] = "content = :content";
            $params[':content'] = $content;
        }

        if ($status !== null) {
            // Normalize status values
            $normalized_status = strtolower($status);
            if ($normalized_status === 'in progress') {
                $normalized_status = 'in_progress';
            }
            
            // Validate status against allowed values to prevent SQL injection
            $allowed_statuses = ['pending', 'in_progress', 'completed', 'done'];
            if (!in_array($normalized_status, $allowed_statuses)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid status value']);
                exit;
            }
            
            $updates[] = "status = :status";
            $params[':status'] = $normalized_status;
        }

        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit;
        }

        $sql = "UPDATE items SET " . implode(', ', $updates) . " WHERE id = :id AND user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Item not found or unauthorized']);
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>

