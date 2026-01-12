<?php
session_start();
include 'database.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Server-side validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $error = "Email already registered!";
            } else {
                // Hash password and insert user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)");
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password_hash' => $password_hash
                ]);
                header("Location: dashboard.php");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Registration failed, please try again!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
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
            <h1 style="margin: 0; flex: 1;">Create Your Account</h1>
            <a href="about.php" class="btn btn-secondary" style="margin-left: auto;" aria-label="About our team">About Us</a>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="form-container-in">
                <h2>Register</h2>
                <form method="POST" action="" id="registerForm" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required aria-required="true" aria-label="Enter your full name">
                        <span id="nameError" class="error-message" role="alert"></span>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required aria-required="true" aria-label="Enter your email address">
                        <span id="emailError" class="error-message" role="alert"></span>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required aria-required="true" aria-label="Enter your password (minimum 6 characters)">
                        <span id="passwordError" class="error-message" role="alert"></span>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" aria-label="Submit registration form">Register</button>
                </form>
                <p class="mt-3 text-center">Already have an account? <a href="login.php">Login here</a></p>

                <?php if ($error): ?>
                    <div class="alert alert-danger mt-3" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> To-Do & Notes Manager. All rights reserved.</p>
    </footer>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            let isValid = true;
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            // Clear previous errors
            document.getElementById('nameError').textContent = '';
            document.getElementById('emailError').textContent = '';
            document.getElementById('passwordError').textContent = '';

            // Validate name
            if (name === '') {
                document.getElementById('nameError').textContent = 'Name is required';
                isValid = false;
            }

            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email === '') {
                document.getElementById('emailError').textContent = 'Email is required';
                isValid = false;
            } else if (!emailRegex.test(email)) {
                document.getElementById('emailError').textContent = 'Invalid email format';
                isValid = false;
            }

            // Validate password length
            if (password === '') {
                document.getElementById('passwordError').textContent = 'Password is required';
                isValid = false;
            } else if (password.length < 6) {
                document.getElementById('passwordError').textContent = 'Password must be at least 6 characters long';
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
</body>

</html>