<?php
/**
 * Edit Judge Page
 * Allows admins to edit details of an existing judge.
 */
session_start();

require_once '../includes/db_connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$judge_id = $_GET['judge_id'] ?? null;
$username = '';
$email = '';
$is_active = 1; // Default to active

// Redirect if no judge_id is provided
if ($judge_id === null) {
    header("Location: dashboard.php?tab=judges&message=" . urlencode("No judge selected for editing."));
    exit();
}

// Fetch existing judge and user data
$stmt_fetch = $conn->prepare("SELECT u.user_id, u.username, j.email, j.is_active FROM judges j JOIN users u ON j.user_id = u.user_id WHERE j.judge_id = ?");
if ($stmt_fetch) {
    $stmt_fetch->bind_param("i", $judge_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();

    if ($result_fetch->num_rows === 1) {
        $judge_data = $result_fetch->fetch_assoc();
        $user_id_to_edit = $judge_data['user_id']; // Store the user_id for updating the users table
        $username = htmlspecialchars($judge_data['username']);
        $email = htmlspecialchars($judge_data['email']);
        $is_active = $judge_data['is_active'];
    } else {
        header("Location: dashboard.php?tab=judges&message=" . urlencode("Judge not found."));
        exit();
    }
    $stmt_fetch->close();
} else {
    $message = "Error preparing statement to fetch judge: " . $conn->error;
}


// Handle form submission for updating judge
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = trim($_POST['username'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_is_active = isset($_POST['is_active']) ? 1 : 0; // Checkbox value
    $post_judge_id = $_POST['judge_id'] ?? null;

    if ($post_judge_id != $judge_id) { // Security check
        $message = "Security error: Judge ID mismatch.";
    } elseif (empty($new_username) || empty($new_email)) {
        $message = "Username and Email cannot be empty.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
        // We need to fetch the user_id again as it might not be explicitly passed in the POST
        $stmt_get_user_id = $conn->prepare("SELECT user_id FROM judges WHERE judge_id = ?");
        $stmt_get_user_id->bind_param("i", $judge_id);
        $stmt_get_user_id->execute();
        $stmt_get_user_id->bind_result($user_id_from_db);
        $stmt_get_user_id->fetch();
        $stmt_get_user_id->close();

        if ($user_id_from_db === null) {
            $message = "Error: Associated user not found for this judge.";
        } else {
            $conn->begin_transaction(); // Start transaction

            try {
                // Update 'users' table
                $stmt_update_user = $conn->prepare("UPDATE users SET username = ?, updated_at = NOW() WHERE user_id = ?");
                if (!$stmt_update_user) {
                    throw new Exception("Prepare statement failed for updating user: " . $conn->error);
                }
                $stmt_update_user->bind_param("si", $new_username, $user_id_from_db);
                if (!$stmt_update_user->execute()) {
                    throw new Exception("Execute statement failed for updating user: " . $stmt_update_user->error);
                }

                // Update 'judges' table
                $stmt_update_judge = $conn->prepare("UPDATE judges SET email = ?, is_active = ?, updated_at = NOW() WHERE judge_id = ?");
                if (!$stmt_update_judge) {
                    throw new Exception("Prepare statement failed for updating judge: " . $conn->error);
                }
                $stmt_update_judge->bind_param("sii", $new_email, $new_is_active, $judge_id);
                if (!$stmt_update_judge->execute()) {
                    throw new Exception("Execute statement failed for updating judge: " . $stmt_update_judge->error);
                }

                $conn->commit();
                header("Location: dashboard.php?tab=judges&message=" . urlencode("Judge '" . htmlspecialchars($new_username) . "' updated successfully!"));
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'for key \'users.username\'') !== false) {
                    $message = "Username '" . htmlspecialchars($new_username) . "' already exists. Please choose a different one.";
                } elseif (strpos($e->getMessage(), 'for key \'judges.email\'') !== false) {
                    $message = "Email '" . htmlspecialchars($new_email) . "' already exists. Please use a different one.";
                } else {
                    $message = "Update failed: " . $e->getMessage();
                }
            } finally {
                if (isset($stmt_update_user) && $stmt_update_user) $stmt_update_user->close();
                if (isset($stmt_update_judge) && $stmt_update_judge) $stmt_update_judge->close();
            }
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
    <title>Edit Judge: <?php echo htmlspecialchars($username); ?></title>
    <!-- Your CSS link will go here -->
    <!-- <link rel="stylesheet" href="../assets/css/style.css"> -->
    <style>
        /* Basic styles for responsive structure and no design */
        .page-wrapper { max-width: 960px; margin: 20px auto; padding: 20px; border: 1px solid #eee; }
        .main-header, .main-content, .main-footer { padding: 10px 0; }
        .form-container { border: 1px solid #ccc; padding: 15px; margin-top: 20px; }
        .form-group { margin-bottom: 10px; }
        .form-label { display: block; margin-bottom: 5px; }
        .form-input, .form-checkbox { width: 100%; padding: 8px; box-sizing: border-box; }
        .form-checkbox { width: auto; margin-right: 10px;}
        .button { padding: 8px 15px; background-color: #007bff; color: white; text-decoration: none; border: none; cursor: pointer; }
        .button-primary { background-color: #007bff; }
        .message { padding: 10px; border: 1px solid; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <header class="main-header">
            <h1 class="site-title">Edit Judge</h1>
        </header>

        <main class="main-content">
            <section class="form-section">
                <div class="form-container">
                    <?php if (!empty($message)): ?>
                        <p class="message"><?php echo htmlspecialchars($message); ?></p>
                    <?php endif; ?>
                    <form action="edit_judge.php?judge_id=<?php echo htmlspecialchars($judge_id); ?>" method="POST" class="edit-judge-form">
                        <input type="hidden" name="judge_id" value="<?php echo htmlspecialchars($judge_id); ?>">
                        <div class="form-group">
                            <label for="username" class="form-label">Username:</label>
                            <input type="text" id="username" name="username" class="form-input" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="form-group">
                            <input type="checkbox" id="is_active" name="is_active" class="form-checkbox" <?php echo ($is_active == 1) ? 'checked' : ''; ?>>
                            <label for="is_active" class="form-label" style="display: inline;">Is Active</label>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="button button-primary">Update Judge</button>
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