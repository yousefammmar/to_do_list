<?php
session_start();
// This page doesn't require login, but we check session for navigation
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Team Members</title>
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
        <h1>About Our Team</h1>
        <nav>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="index.php" class="btn btn-primary" aria-label="Go to homepage">Home</a>
                <a href="dashboard.php" class="btn btn-primary" aria-label="Go to dashboard">Dashboard</a>
                <a href="task_history.php" class="btn btn-info" aria-label="View task history">Task History</a>
                <a href="profile.php" class="btn btn-warning" aria-label="Go to profile settings">Profile</a>
                <a href="logout.php" class="btn btn-danger" aria-label="Logout from your account">Logout</a>
            <?php else: ?>
                <a href="index.php" class="btn btn-primary" aria-label="Go to homepage">Home</a>
                <a href="login.php" class="btn btn-info" aria-label="Go to login page">Login</a>
                <a href="register.php" class="btn btn-warning" aria-label="Go to registration page">Register</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <div class="container">
            <div class="about-section">
                <h2>Meet Our Development Team</h2>
                <p class="team-intro">We are a team of dedicated developers who built this To-Do & Notes Manager application.</p>

                <div class="team-grid">

                    <div class="team-member">
                        <div class="team-member-image-container">
                            <img src="team_images/Tasneem.jpeg" alt="Team Member 1" class="team-member-image">
                            <!-- TODO: Add your image here - Replace 'team_member_1.jpg' with your image filename -->
                        </div>
                        <h3 class="team-member-name"><a href="https://yousefammmar.github.io/Tasneem"><strong>Tasneem Ahmad</strong></a></h3>
                        <!-- TODO: Replace 'Team Member 1 Name' above with the actual name -->
                        <p class="team-member-bio">
                            <!-- TODO: Add bio here - Replace this text with the actual bio -->
                            Tasneem handled the JavaScript functionality of the project, focusing on interactivity and user experience, with strong skills in JavaScript and frontend development.
                        </p>
                    </div>


                    <div class="team-member">
                        <div class="team-member-image-container">
                            <img src="team_images/Yousef.png" alt="Team Member 2" class="team-member-image">
                            <!-- TODO: Add your image here - Replace 'team_member_2.jpg' with your image filename -->
                        </div>
                        <h3 class="team-member-name"><strong><a href="https://yousefammmar.github.io/yousef/">Yousef Odeh</a></strong></h3>
                        <!-- TODO: Replace 'Team Member 2 Name' above with the actual name -->
                        <p class="team-member-bio">
                            <!-- TODO: Add bio here - Replace this text with the actual bio -->
                            Yousef handled the database design and authentication functionality of the project, focusing on data management, security, and reliable system access.
                        </p>
                    </div>


                    <div class="team-member">
                        <div class="team-member-image-container">
                            <img src="team_images/Fatema.jpeg" alt="Team Member 3" class="team-member-image">
                            <!-- TODO: Add your image here - Replace 'team_member_3.jpg' with your image filename -->
                        </div>
                        <h3 class="team-member-name"><strong>Fatema Alahmad</strong></h3>
                        <!-- TODO: Replace 'Team Member 3 Name' above with the actual name -->
                        <p class="team-member-bio">
                            <!-- TODO: Add bio here - Replace this text with the actual bio -->
                            Fatema Alahmad handled the HTML and CSS part of the project, focusing on page structure, layout, and responsive design.
                        </p>
                    </div>


                    <div class="team-member">
                        <div class="team-member-image-container">
                            <img src="team_images/Ameen.png" alt="Team Member 4" class="team-member-image">
                            <!-- TODO: Add your image here - Replace 'team_member_4.jpg' with your image filename -->
                        </div>
                        <h3 class="team-member-name"><strong><a href='https://yousefammmar.github.io/Ameen'> Ameen Alrawabdeh</strong></a></h3>
                        <!-- TODO: Replace 'Team Member 4 Name' above with the actual name -->
                        <p class="team-member-bio">
                            <!-- TODO: Add bio here - Replace this text with the actual bio -->
                            Ameen handled the PHP part of the project, focusing on server-side logic, data handling, and backend functionality.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> To-Do & Notes Manager. All rights reserved.</p>
    </footer>
</body>

</html>