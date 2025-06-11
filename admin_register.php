<?php
/**
 * Admin Registration Page
 * Handles the registration of new admin users.
 */
session_start(); // Start the PHP session

// Include the database connection file
require_once 'includes/db_connection.php';

$message = ''; // Initialize an empty message (for success or errors)
$username = ''; // Initialize username for the form value attribute
$password = ''; // Initialize password for the form value attribute (though not directly used for display)
$confirm_password = ''; // Initialize confirm_password for the form value attribute (though not directly used for display)


// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    // Role is hardcoded as 'admin' for this registration page
    $role = 'admin';

    // Basic validation
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $message = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (strlen($password) < 6) { // Example: minimum password length
        $message = "Password must be at least 6 characters long.";
    } else {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Start a transaction for atomicity
        $conn->begin_transaction();

        try {
            // 1. Insert into the 'users' table
            $stmt_user = $conn->prepare("INSERT INTO users (username, password, role, created_at) VALUES (?, ?, ?, NOW())");
            if (!$stmt_user) {
                throw new Exception("Prepare statement failed for users: " . $conn->error);
            }
            $stmt_user->bind_param("sss", $username, $hashed_password, $role);
            if (!$stmt_user->execute()) {
                throw new Exception("Execute statement failed for users: " . $stmt_user->error);
            }
            $user_id = $stmt_user->insert_id; // Get the newly inserted user_id

            // 2. Insert into the 'admins' table
            $stmt_admin = $conn->prepare("INSERT INTO admins (username, password_hash, created_at, user_id) VALUES (?, ?, NOW(), ?)");
            if (!$stmt_admin) {
                throw new Exception("Prepare statement failed for admins: " . $conn->error);
            }
            $stmt_admin->bind_param("ssi", $username, $hashed_password, $user_id); // Use hashed_password for admin table as well
            if (!$stmt_admin->execute()) {
                throw new Exception("Execute statement failed for admins: " . $stmt_admin->error);
            }

            $conn->commit(); // Commit the transaction if all operations are successful
            // Redirect to login page after successful registration
            header("Location: login.php");
            exit(); // Important: Always call exit() after header redirects

        } catch (Exception $e) {
            $conn->rollback(); // Rollback transaction on error
            // Check for duplicate username error
            if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'for key \'users.username\'') !== false) {
                $message = "Username already exists. Please choose a different one.";
            } else {
                $message = "Registration failed: " . $e->getMessage();
            }
        } finally {
            if (isset($stmt_user) && $stmt_user) $stmt_user->close();
            if (isset($stmt_admin) && $stmt_admin) $stmt_admin->close();
        }
    }
}

$conn->close(); // Close the database connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - Digital Judging System</title>
    <!-- You will link your CSS file(s) here later -->
    <!-- <link rel="stylesheet" href="assets/css/style.css"> -->
</head>
<body>
    <div class="page-wrapper">
        <header class="main-header">
            <h1 class="site-title">Admin Registration</h1>
        </header>

        <main class="main-content">
            <section class="registration-form-section">
                <div class="form-container">
                    <?php if (!empty($message)): ?>
                        <p class="<?php echo (strpos($message, 'successfully') !== false) ? 'success-message' : 'error-message'; ?>"><?php echo $message; ?></p>
                    <?php endif; ?>

                    <form action="admin_register.php" method="POST" class="registration-form">
                        <div class="form-group">
                            <label for="username" class="form-label">Username:</label>
                            <!-- Added autocomplete="off" to try and prevent browser autofill -->
                            <input type="text" id="username" name="username" class="form-input" value="<?php echo htmlspecialchars($username); ?>" required autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="password" class="form-label">Password:</label>
                            <input type="password" id="password" name="password" class="form-input" required autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" required autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <button type="submit" class="button button-primary">Register</button>
                        </div>
                    </form>
                    <div class="form-footer-links">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
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
