<?php
session_start();

// Protect page - only logged-in users can access
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'database.php';
$user_id = $_SESSION['user_id'];

// Fetch current user data
try {
    $stmt = $conn->prepare("SELECT id, name, email, profile_image FROM users WHERE id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    $error = "Error loading profile data.";
}

$success = '';
$error = '';

// Handle image upload FIRST (before profile form)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['profile_image'];

    // Check for upload errors
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
    ];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = isset($upload_errors[$file['error']]) ? $upload_errors[$file['error']] : "Upload error code: " . $file['error'];
    } else {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            $error = "Invalid file type. Only JPEG, PNG, and GIF images are allowed. Your file type: " . $file['type'];
        } elseif ($file['size'] > $max_size) {
            $error = "File size too large. Maximum size is 5MB. Your file size: " . round($file['size'] / 1024 / 1024, 2) . "MB";
        } else {
            // Create uploads directory if it doesn't exist (use absolute path)
            $upload_dir = __DIR__ . '/uploads/';
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    $error = "Failed to create uploads directory. Please check permissions.";
                }
            }

            // Ensure directory is writable
            if (empty($error) && !is_writable($upload_dir)) {
                // Try to make it writable
                @chmod($upload_dir, 0777);
                if (!is_writable($upload_dir)) {
                    $error = "Uploads directory is not writable. Please set permissions to 777 for 'uploads/' folder.";
                }
            }

            if (empty($error)) {
                // Generate unique filename
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                // Debug: Check if temp file exists and is readable
                if (!is_uploaded_file($file['tmp_name'])) {
                    $error = "Uploaded file is not valid or was not uploaded via POST.";
                } elseif (!file_exists($file['tmp_name'])) {
                    $error = "Temporary file does not exist. Upload may have failed.";
                } elseif (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Store relative path in database for web access
                    $relative_path = 'uploads/' . $new_filename;

                    // Delete old profile image if exists (use absolute path for deletion)
                    if (!empty($user['profile_image'])) {
                        $old_image_path = __DIR__ . '/' . $user['profile_image'];
                        if (file_exists($old_image_path)) {
                            @unlink($old_image_path);
                        }
                    }

                    // Update database
                    try {
                        $stmt = $conn->prepare("UPDATE users SET profile_image = :profile_image WHERE id = :user_id");
                        $stmt->execute([
                            ':profile_image' => $relative_path,
                            ':user_id' => $user_id
                        ]);

                        // Refresh user data
                        $stmt = $conn->prepare("SELECT id, name, email, profile_image FROM users WHERE id = :user_id");
                        $stmt->execute([':user_id' => $user_id]);
                        $user = $stmt->fetch();

                        $success = "Profile image updated successfully!";
                    } catch (PDOException $e) {
                        // Delete uploaded file if database update fails
                        @unlink($upload_path);
                        $error = "Error updating profile image in database: " . $e->getMessage();
                    }
                } else {
                    $error = "Failed to move uploaded file. ";
                    $error .= "Directory: " . $upload_dir . " ";
                    $error .= "Writable: " . (is_writable($upload_dir) ? 'Yes' : 'No') . " ";
                    $error .= "Temp file exists: " . (file_exists($file['tmp_name']) ? 'Yes' : 'No') . ". ";
                    $error .= "Please check directory permissions for 'uploads/' folder (should be 777).";
                }
            }
        }
    }
}

// Handle profile form submission (only if image wasn't uploaded)
if ($_SERVER["REQUEST_METHOD"] == "POST" && (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] === UPLOAD_ERR_NO_FILE)) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Validate input
    if (empty($name) || empty($email)) {
        $error = "Name and email are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        try {
            // Check if email is already taken by another user
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
            $stmt->execute([':email' => $email, ':user_id' => $user_id]);
            if ($stmt->fetch()) {
                $error = "Email already taken by another user!";
            } else {
                // Update user info
                $stmt = $conn->prepare("UPDATE users SET name = :name, email = :email WHERE id = :user_id");
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':user_id' => $user_id
                ]);

                // Update session name
                $_SESSION['name'] = $name;

                $success = "Profile updated successfully!";
                // Refresh user data
                $stmt = $conn->prepare("SELECT id, name, email, profile_image FROM users WHERE id = :user_id");
                $stmt->execute([':user_id' => $user_id]);
                $user = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings</title>
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
            <h1 style="margin: 0; flex: 1;">Profile Settings</h1>
            <a href="about.php" class="btn btn-secondary" style="margin-left: auto; margin-right: 0.5rem;" aria-label="About our team">About Us</a>
        </div>
        <nav>
            <a href="index.php" class="btn btn-primary" aria-label="Go to homepage">Home</a>
            <a href="dashboard.php" class="btn btn-primary" aria-label="Go back to dashboard">Dashboard</a>
            <a href="task_history.php" class="btn btn-info" aria-label="View task history">Task History</a>
            <a href="logout.php" class="btn btn-danger" aria-label="Logout from your account">Logout</a>
        </nav>
    </header>

    <main>
        <div class="container">
            <div class="profile-section">
                <h2>Edit Your Profile</h2>

                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Profile Image Section -->
                <div class="profile-image-section">
                    <h3>Profile Picture</h3>
                    <div class="profile-image-container">
                        <?php
                        $image_path = '';
                        if (!empty($user['profile_image'])) {
                            // Check if it's an absolute path or relative path
                            if (file_exists($user['profile_image'])) {
                                $image_path = $user['profile_image'];
                            } elseif (file_exists(__DIR__ . '/' . $user['profile_image'])) {
                                $image_path = $user['profile_image'];
                            }
                        }
                        if (!empty($image_path)): ?>
                            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Profile picture" class="profile-image" id="profileImagePreview">
                        <?php else: ?>
                            <div class="profile-image-placeholder" id="profileImagePreview">
                                <span>No Image</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="" enctype="multipart/form-data" class="image-upload-form">
                        <input type="file" name="profile_image" id="profileImageInput" accept="image/jpeg,image/jpg,image/png,image/gif" aria-label="Upload profile image">
                        <button type="submit" class="btn btn-primary" aria-label="Upload profile image">Upload Image</button>
                    </form>
                    <p class="text-muted">Accepted formats: JPEG, PNG, GIF. Maximum size: 5MB</p>
                </div>

                <!-- Profile Information Section -->
                <div class="profile-info-section">
                    <h3>Personal Information</h3>
                    <form method="POST" action="" id="profileForm">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required aria-required="true" aria-label="Enter your name">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required aria-required="true" aria-label="Enter your email address">
                        </div>
                        <button type="submit" class="btn btn-primary w-100" aria-label="Update profile information">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> To-Do & Notes Manager. All rights reserved.</p>
    </footer>

    <script>
        // Preview image before upload
        document.getElementById('profileImageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('profileImagePreview');
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        // Replace placeholder with image
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Profile picture preview';
                        img.className = 'profile-image';
                        img.id = 'profileImagePreview';
                        preview.parentNode.replaceChild(img, preview);
                    }
                };
                reader.readAsDataURL(file);
            }
        });

        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (name === '') {
                e.preventDefault();
                alert('Name is required!');
                return false;
            }

            if (email === '') {
                e.preventDefault();
                alert('Email is required!');
                return false;
            }

            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Invalid email format!');
                return false;
            }
        });
    </script>
</body>

</html>