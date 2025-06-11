<?php
/**
 * Admin Dashboard Page
 * Displays various administrative sections (Events, Judges, Results)
 * and provides navigation and data management capabilities.
 */
session_start(); // Start the PHP session

// Include the database connection file
require_once '../includes/db_connection.php'; // Adjust path as this is in admin/

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // If not logged in or not an admin, redirect to login page
    header("Location: ../login.php");
    exit();
}

$current_username = htmlspecialchars($_SESSION['username']); // Get logged-in username

// Determine active tab based on GET parameter, default to 'events'
$active_tab = $_GET['tab'] ?? 'events';

// --- Data Fetching for Tabs ---

// Fetch Events data
$events = [];
$stmt_events = $conn->prepare("SELECT event_id, event_name, created_at FROM events ORDER BY created_at DESC");
if ($stmt_events) {
    $stmt_events->execute();
    $result_events = $stmt_events->get_result();
    while ($row = $result_events->fetch_assoc()) {
        $events[] = $row;
    }
    $stmt_events->close();
} else {
    // Handle error if statement preparation fails
    error_log("Failed to prepare statement for fetching events: " . $conn->error);
}


// Fetch Judges data (joining with users table to get username/email and created_at from users table)
$judges = [];
// Corrected: Removed 'j.created_at' as it does not exist in the 'judges' table.
// 'u.created_at' is used instead, assuming judge creation time is when their user account was created.
$stmt_judges = $conn->prepare("SELECT j.judge_id, u.username, j.email, j.is_active, u.created_at AS user_created_at
                                 FROM judges j
                                 JOIN users u ON j.user_id = u.user_id
                                 ORDER BY u.username ASC");
if ($stmt_judges) {
    $stmt_judges->execute();
    $result_judges = $stmt_judges->get_result();
    while ($row = $result_judges->fetch_assoc()) {
        $judges[] = $row;
    }
    $stmt_judges->close();
} else {
    // Handle error if statement preparation fails
    error_log("Failed to prepare statement for fetching judges: " . $conn->error);
}

$conn->close(); // Close the database connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Digital Judging System</title>
    <!-- Your CSS link will go here -->
    <!-- <link rel="stylesheet" href="../assets/css/style.css"> -->
    <style>
        /* Basic styles for responsive structure and tab switching (no design) */
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .tabs button { padding: 10px 15px; cursor: pointer; }
        .tabs button.active { font-weight: bold; }
        .event-card, .judge-card { border: 1px solid #ccc; margin-bottom: 10px; padding: 10px; }
        .card-buttons a, .card-buttons button { margin-right: 5px; }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <header class="main-header">
            <h1 class="site-title">Admin Dashboard</h1>
            <nav class="main-nav">
                <div class="user-info">
                    <span>Welcome, <?php echo $current_username; ?></span>
                </div>
                <div class="nav-links">
                    <a href="../logout.php" class="button button-logout">Logout</a>
                </div>
            </nav>
        </header>

        <main class="main-content">
            <div class="dashboard-tabs">
                <div class="tabs">
                    <button class="tab-button <?php echo ($active_tab === 'events') ? 'active' : ''; ?>" data-tab="events">Events</button>
                    <button class="tab-button <?php echo ($active_tab === 'judges') ? 'active' : ''; ?>" data-tab="judges">Judges</button>
                    <button class="tab-button <?php echo ($active_tab === 'results') ? 'active' : ''; ?>" data-tab="results">Results</button>
                </div>

                <div id="events-tab" class="tab-content <?php echo ($active_tab === 'events') ? 'active' : ''; ?>">
                    <h2 class="tab-heading">Events Management</h2>
                    <div class="action-bar">
                        <a href="create_event.php" class="button button-primary">Create New Event</a>
                    </div>
                    <div class="events-list">
                        <?php if (empty($events)): ?>
                            <p>No events found. Click "Create New Event" to add one.</p>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                                <div class="event-card">
                                    <h3 class="event-name"><?php echo htmlspecialchars($event['event_name']); ?></h3>
                                    <p class="event-meta">Created: <?php echo htmlspecialchars($event['created_at']); ?></p>
                                    <div class="card-buttons">
                                        <a href="manage_event.php?event_id=<?php echo $event['event_id']; ?>" class="button button-secondary">Manage</a>
                                        <a href="edit_event.php?event_id=<?php echo $event['event_id']; ?>" class="button button-edit">Edit</a>
                                        <a href="delete_event.php?event_id=<?php echo $event['event_id']; ?>" class="button button-delete">Delete</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="judges-tab" class="tab-content <?php echo ($active_tab === 'judges') ? 'active' : ''; ?>">
                    <h2 class="tab-heading">Judges Management</h2>
                    <div class="action-bar">
                        <a href="add_judge.php" class="button button-primary">Add New Judge</a>
                    </div>
                    <div class="judges-list">
                        <?php if (empty($judges)): ?>
                            <p>No judges found. Click "Add New Judge" to add one.</p>
                        <?php else: ?>
                            <?php foreach ($judges as $judge): ?>
                                <div class="judge-card">
                                    <h3 class="judge-username"><?php echo htmlspecialchars($judge['username']); ?></h3>
                                    <p class="judge-email">Email: <?php echo htmlspecialchars($judge['email']); ?></p>
                                    <p class="judge-meta">Active: <?php echo $judge['is_active'] ? 'Yes' : 'No'; ?></p>
                                    <!-- Corrected: Displaying user creation timestamp from the 'users' table -->
                                    <p class="judge-meta">User Created: <?php echo htmlspecialchars($judge['user_created_at']); ?></p>
                                    <div class="card-buttons">
                                        <!-- Add buttons for managing/editing/deleting judges here if needed later -->
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="results-tab" class="tab-content <?php echo ($active_tab === 'results') ? 'active' : ''; ?>">
                    <h2 class="tab-heading">Competition Results</h2>
                    <p>Results will be displayed here.</p>
                    <!-- Content for displaying results will go here -->
                </div>
            </div>
        </main>

        <footer class="main-footer">
            <p class="footer-text">&copy; 2025 Digital Judging System. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // JavaScript for tab switching
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetTabId = this.dataset.tab + '-tab'; // e.g., 'events-tab'

                    // Remove 'active' class from all buttons and content
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    // Add 'active' class to the clicked button
                    this.classList.add('active');

                    // Add 'active' class to the target content
                    document.getElementById(targetTabId).classList.add('active');

                    // Update URL hash to maintain active tab on refresh (optional but good UX)
                    history.pushState(null, '', `dashboard.php?tab=${this.dataset.tab}`);
                });
            });

            // Initial tab selection based on URL parameter on page load
            const urlParams = new URLSearchParams(window.location.search);
            const initialTab = urlParams.get('tab');
            if (initialTab) {
                const initialButton = document.querySelector(`.tab-button[data-tab="${initialTab}"]`);
                if (initialButton) {
                    initialButton.click(); // Simulate click to activate tab
                }
            } else {
                // Default to events tab if no parameter is present
                document.querySelector('.tab-button[data-tab="events"]').click();
            }
        });
    </script>
</body>
</html>
