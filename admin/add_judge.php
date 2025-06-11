<?php
/**
 * Add New Judge Page
 * Handles the addition of new judge users by admin.
 * A new user entry with role 'judge' is created, and then a linked judge entry.
 */
session_start();

require_once '../includes/db_connection.php'; // Adjust path

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$username = ''; // For judge's login username
$email = '';    // For judge's email

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    // For simplicity, generate a random password for new judges
    // In a real system, you might generate a token and send an email for password setup.
    $generated_password = bin2hex(random_bytes(8)); // Generates a 16-char hex string
    $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
    $role = 'judge';

    if (empty($username) || empty($email)) {
        $message = "Please fill in all required fields (Username, Email).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
        $conn->begin_transaction(); // Start transaction

        try {
            // 1. Insert into 'users' table for the judge's login
            $stmt_user = $conn->prepare("INSERT INTO users (username, password, role, created_at) VALUES (?, ?, ?, NOW())");
            if (!$stmt_user) {
                throw new Exception("Prepare statement failed for users: " . $conn->error);
            }
            $stmt_user->bind_param("sss", $username, $hashed_password, $role);
            if (!$stmt_user->execute()) {
                throw new Exception("Execute statement failed for users: " . $stmt_user->error);
            }
            $user_id = $stmt_user->insert_id; // Get the newly created user_id

            // 2. Insert into 'judges' table
            $stmt_judge = $conn->prepare("INSERT INTO judges (email, is_active, user_id, created_at) VALUES (?, ?, ?, NOW())");
            if (!$stmt_judge) {
                throw new Exception("Prepare statement failed for judges: " . $conn->error);
            }
            $is_active = 1; // Default to active
            $stmt_judge->bind_param("sii", $email, $is_active, $user_id);
            if (!$stmt_judge->execute()) {
                throw new Exception("Execute statement failed for judges: " . $stmt_judge->error);
            }

            $conn->commit(); // Commit transaction
            $message = "Judge '" . htmlspecialchars($username) . "' added successfully! Initial Password: <strong>" . htmlspecialchars($generated_password) . "</strong>. Please note this down.";
            // Clear form fields
            $username = '';
            $email = '';

        } catch (Exception $e) {
            $conn->rollback(); // Rollback transaction on error
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'for key \'users.username\'') !== false) {
                    $message = "Username '" . htmlspecialchars($username) . "' already exists. Please choose a different one.";
                } elseif (strpos($e->getMessage(), 'for key \'judges.email\'') !== false) {
                    $message = "Email '" . htmlspecialchars($email) . "' already exists. Please use a different one.";
                } else {
                    $message = "Error adding judge: Duplicate entry detected.";
                }
            } else {
                $message = "Error adding judge: " . $e->getMessage();
            }
        } finally {
            if (isset($stmt_user) && $stmt_user) $stmt_user->close();
            if (isset($stmt_judge) && $stmt_judge) $stmt_judge->close();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Judge</title>
    <!-- Your CSS link will go here -->
    <!-- <link rel="stylesheet" href="../assets/css/style.css"> -->
</head>
<body>
    <div class="page-wrapper">
        <header class="main-header">
            <h1 class="site-title">Add New Judge</h1>
        </header>

        <main class="main-content">
            <section class="form-section">
                <div class="form-container">
                    <?php if (!empty($message)): ?>
                        <p class="message"><?php echo $message; ?></p>
                    <?php endif; ?>
                    <form action="add_judge.php" method="POST" class="add-judge-form">
                        <div class="form-group">
                            <label for="username" class="form-label">Judge Username:</label>
                            <input type="text" id="username" name="username" class="form-input" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Judge Email:</label>
                            <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="button button-primary">Add Judge</button>
                        </div>
                    </form>
                    <div class="form-footer-links">
                        <p><a href="dashboard.php?tab=judges">Back to Dashboard</a></p>
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
