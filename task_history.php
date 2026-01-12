<?php
session_start();

// Protect page - only logged-in users can access
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'database.php';
$user_name = $_SESSION['name'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task History - Completed Tasks</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="background-particles">
        <div class="particle p1"></div>
        <div class="particle p2"></div>
        <div class="particle p3"></div>
        <div class="particle p4"></div>
        <div class="particle p5"></div>
        <div class="particle p6"></div>
    </div>
    <header>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <h1 style="margin: 0; flex: 1;">Task History</h1>
            <a href="about.php" class="btn btn-secondary" style="margin-left: auto; margin-right: 0.5rem;" aria-label="About our team">About Us</a>
        </div>
        <nav>
            <a href="index.php" class="btn btn-primary" aria-label="Go to homepage">Home</a>
            <a href="dashboard.php" class="btn btn-primary" aria-label="Go back to dashboard">Dashboard</a>
            <a href="profile.php" class="btn btn-info" aria-label="Go to profile page">Profile</a>
            <a href="logout.php" class="btn btn-danger" aria-label="Logout from your account">Logout</a>
        </nav>
    </header>

    <main>
        <div class="container">
            <div class="task-history-section">
                <h2>Completed Tasks</h2>
                <p>Here are all your completed tasks.</p>

                <ul class="task-list" id="completedTasksList">
                    <?php
                    try {
                        $stmt = $conn->prepare("SELECT id, content, status, created_at FROM items WHERE user_id = :user_id AND type = 'task' AND status = 'completed' ORDER BY created_at DESC");
                        $stmt->execute([':user_id' => $_SESSION['user_id']]);
                        $completed_tasks = $stmt->fetchAll();

                        if (empty($completed_tasks)) {
                            echo '<li class="placeholder">No completed tasks yet. Complete some tasks to see them here!</li>';
                        } else {
                            foreach ($completed_tasks as $task) {
                                $date = date('M d, Y', strtotime($task['created_at']));
                                echo '<li class="task-item completed-task">';
                                echo '<div class="task-content">';
                                echo '<div class="task-text">';
                                echo '<span>' . htmlspecialchars($task['content']) . '</span>';
                                echo '<span class="task-date">Completed on: ' . htmlspecialchars($date) . '</span>';
                                echo '</div>';
                                echo '<span class="status-badge status-completed">Completed</span>';
                                echo '</div>';
                                echo '</li>';
                            }
                        }
                    } catch (PDOException $e) {
                        echo '<li class="error">Error loading completed tasks.</li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> To-Do & Notes Manager. All rights reserved.</p>
    </footer>
</body>

</html>