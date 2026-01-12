<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

include 'database.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Email and password are required!";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, name, password_hash FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid email or password!";
            }
        } catch (PDOException $e) {
            $error = "Login failed, please try again!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
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
            <h1 style="margin: 0; flex: 1;">Login to Your Account</h1>
            <a href="about.php" class="btn btn-secondary" style="margin-left: auto;" aria-label="About our team">About Us</a>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="form-container-in">
                <h2>Login</h2>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required aria-required="true" aria-label="Enter your email address">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required aria-required="true" aria-label="Enter your password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100" aria-label="Submit login form">Login</button>
                </form>
                <p class="mt-3 text-center">Don't have an account? <a href="register.php">Register here</a></p>

                <?php if ($error): ?>
                    <div class="alert alert-danger mt-3" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> To-Do & Notes Manager. All rights reserved.</p>
    </footer>
</body>

</html>