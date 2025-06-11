<?php
/**
 * Edit Event Page
 * Allows admins to edit details of an existing event.
 */
session_start();

require_once '../includes/db_connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$event_id = $_GET['event_id'] ?? null;
$event_name = '';

// Redirect if no event_id is provided
if ($event_id === null) {
    header("Location: dashboard.php?tab=events&message=" . urlencode("No event selected for editing."));
    exit();
}

// Fetch existing event data
$stmt_fetch = $conn->prepare("SELECT event_name FROM events WHERE event_id = ?");
if ($stmt_fetch) {
    $stmt_fetch->bind_param("i", $event_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();

    if ($result_fetch->num_rows === 1) {
        $event_data = $result_fetch->fetch_assoc();
        $event_name = htmlspecialchars($event_data['event_name']);
    } else {
        header("Location: dashboard.php?tab=events&message=" . urlencode("Event not found."));
        exit();
    }
    $stmt_fetch->close();
} else {
    $message = "Error preparing statement to fetch event: " . $conn->error;
}


// Handle form submission for updating event
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_event_name = trim($_POST['event_name'] ?? '');
    $post_event_id = $_POST['event_id'] ?? null; // Ensure event_id is also passed via POST

    if ($post_event_id != $event_id) { // Security check: ensure ID from GET matches ID from POST
        $message = "Security error: Event ID mismatch.";
    } elseif (empty($new_event_name)) {
        $message = "Event name cannot be empty.";
    } else {
        $stmt_update = $conn->prepare("UPDATE events SET event_name = ?, updated_at = NOW() WHERE event_id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("si", $new_event_name, $event_id);
            if ($stmt_update->execute()) {
                header("Location: dashboard.php?tab=events&message=" . urlencode("Event '" . htmlspecialchars($new_event_name) . "' updated successfully!"));
                exit();
            } else {
                $message = "Error updating event: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $message = "Error preparing update statement: " . $conn->error;
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
    <title>Edit Event: <?php echo htmlspecialchars($event_name); ?></title>
    <!-- Your CSS link will go here -->
    <!-- <link rel="stylesheet" href="../assets/css/style.css"> -->
    <style>
        /* Basic styles for responsive structure and no design */
        .page-wrapper { max-width: 960px; margin: 20px auto; padding: 20px; border: 1px solid #eee; }
        .main-header, .main-content, .main-footer { padding: 10px 0; }
        .form-container { border: 1px solid #ccc; padding: 15px; margin-top: 20px; }
        .form-group { margin-bottom: 10px; }
        .form-label { display: block; margin-bottom: 5px; }
        .form-input { width: 100%; padding: 8px; box-sizing: border-box; }
        .button { padding: 8px 15px; background-color: #007bff; color: white; text-decoration: none; border: none; cursor: pointer; }
        .button-primary { background-color: #007bff; }
        .message { padding: 10px; border: 1px solid; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <header class="main-header">
            <h1 class="site-title">Edit Event</h1>
        </header>

        <main class="main-content">
            <section class="form-section">
                <div class="form-container">
                    <?php if (!empty($message)): ?>
                        <p class="message"><?php echo htmlspecialchars($message); ?></p>
                    <?php endif; ?>
                    <form action="edit_event.php?event_id=<?php echo htmlspecialchars($event_id); ?>" method="POST" class="edit-event-form">
                        <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event_id); ?>">
                        <div class="form-group">
                            <label for="event_name" class="form-label">Event Name:</label>
                            <input type="text" id="event_name" name="event_name" class="form-input" value="<?php echo htmlspecialchars($event_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="button button-primary">Update Event</button>
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
