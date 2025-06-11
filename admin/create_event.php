<?php
/**
 * Create Event Page
 * Handles the creation of new events by admin users.
 */
session_start();

require_once '../includes/db_connection.php'; // Adjust path

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$event_name = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_name = trim($_POST['event_name'] ?? '');

    if (empty($event_name)) {
        $message = "Event name cannot be empty.";
    } else {
        // Get the admin_id from the admins table using the user_id from the session
        $admin_id = null;
        $stmt_admin_id = $conn->prepare("SELECT admin_id FROM admins WHERE user_id = ?");
        if ($stmt_admin_id) {
            $stmt_admin_id->bind_param("i", $_SESSION['user_id']);
            $stmt_admin_id->execute();
            $stmt_admin_id->bind_result($admin_id);
            $stmt_admin_id->fetch();
            $stmt_admin_id->close();
        }

        if ($admin_id === null) {
            $message = "Error: Admin ID not found for the current user.";
        } else {
            // Insert new event into the database
            $stmt = $conn->prepare("INSERT INTO events (admin_id, event_name, created_at) VALUES (?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("is", $admin_id, $event_name);
                if ($stmt->execute()) {
                    // Redirect back to the dashboard with the events tab active
                    header("Location: dashboard.php?tab=events&message=" . urlencode("Event created successfully!"));
                    exit();
                } else {
                    $message = "Error creating event: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = "Error preparing statement: " . $conn->error;
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
    <title>Create New Event</title>
    <!-- Your CSS link will go here -->
    <!-- <link rel="stylesheet" href="../assets/css/style.css"> -->
</head>
<body>
    <div class="page-wrapper">
        <header class="main-header">
            <h1 class="site-title">Create New Event</h1>
        </header>

        <main class="main-content">
            <section class="form-section">
                <div class="form-container">
                    <?php if (!empty($message)): ?>
                        <p class="message"><?php echo htmlspecialchars($message); ?></p>
                    <?php endif; ?>
                    <form action="create_event.php" method="POST" class="create-event-form">
                        <div class="form-group">
                            <label for="event_name" class="form-label">Event Name:</label>
                            <input type="text" id="event_name" name="event_name" class="form-input" value="<?php echo htmlspecialchars($event_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="button button-primary">Create Event</button>
                        </div>
                    </form>
                    <div class="form-footer-links">
                        <p><a href="dashboard.php?tab=events">Back to Dashboard</a></p>
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
