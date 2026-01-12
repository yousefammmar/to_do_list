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

    if (empty($id) || !is_numeric($id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid ID']);
        exit;
    }

    try {
        // Delete only if the item belongs to the logged-in user
        $stmt = $conn->prepare("DELETE FROM items WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $user_id
        ]);

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

