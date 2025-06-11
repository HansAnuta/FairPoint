<?php
/**
 * Login Page
 * Handles user authentication for only admin users.
 */
session_start(); // Start the PHP session to manage user state

// Include the database connection file
require_once 'includes/db_connection.php';

$error_message = ''; // Initialize an empty error message

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve username and password from the form
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Basic validation: Check if fields are not empty
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        // Prepare a SQL statement to prevent SQL injection and specifically query for admin users
        $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ? AND role = 'admin'");
        $stmt->bind_param("s", $username); // 's' denotes string type
        $stmt->execute();
        $result = $stmt->get_result(); // Get the result set

        // Check if an admin user with the provided username exists
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc(); // Fetch user data

            // Verify the provided password against the hashed password in the database
            if (password_verify($password, $user['password'])) {
                // Password is correct, set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Redirect to admin dashboard as this page is specifically for admin login
                header("Location: admin/dashboard.php");
                exit(); // Always exit after a header redirect
            } else {
                $error_message = "Invalid username or password."; // Incorrect password
            }
        } else {
            $error_message = "Invalid username or password."; // Username not found or not an admin
        }
        $stmt->close(); // Close the prepared statement
    }
}

$conn->close(); // Close the database connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Digital Judging System</title>
    <!-- You will link your CSS file(s) here later -->
    <!-- <link rel="stylesheet" href="assets/css/style.css"> -->
</head>
<body>
    <div class="page-wrapper">
        <header class="main-header">
            <h1 class="site-title">Login</h1>
        </header>

        <main class="main-content">
            <section class="login-form-section">
                <div class="form-container">
                    <?php if (!empty($error_message)): ?>
                        <p class="error-message"><?php echo $error_message; ?></p>
                    <?php endif; ?>

                    <form action="login.php" method="POST" class="login-form">
                        <div class="form-group">
                            <label for="username" class="form-label">Username:</label>
                            <input type="text" id="username" name="username" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="password" class="form-label">Password:</label>
                            <input type="password" id="password" name="password" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="button button-primary">Login</button>
                        </div>
                    </form>
                    <div class="form-footer-links">
                        <p>Don't have an account? <a href="admin_register.php">Register as Admin</a></p>
                        <p><a href="index.html">Back to Home</a></p>
                    </div>
                </div>
            </section>
        </main>

        <footer class="main-footer">
            <p class="footer-text">&copy; 2025 Digital Judging System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
