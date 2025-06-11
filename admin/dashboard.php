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

// Check for and display messages from redirects
$message = '';
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

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
        .page-wrapper {
            max-width: 960px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #eee;
        }
        .main-header, .main-content, .main-footer {
            padding: 10px 0;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .tabs button { padding: 10px 15px; cursor: pointer; }
        .tabs button.active { font-weight: bold; }
        .event-card, .judge-card {
            border: 1px solid #ccc;
            margin-bottom: 10px;
            padding: 10px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        .event-card > div, .judge-card > div {
            flex: 1;
            padding: 5px;
        }
        .card-buttons {
            flex-basis: 100%; /* Take full width on small screens */
            text-align: right; /* Align buttons to the right */
            margin-top: 10px;
        }
        .card-buttons a, .card-buttons button { margin-right: 5px; display: inline-block; padding: 8px 12px; text-decoration: none; color: white; border-radius: 4px; }
        .button-primary { background-color: #007bff; }
        .button-secondary { background-color: #6c757d; }
        .button-edit { background-color: #ffc107; color: black; }
        .button-delete { background-color: #dc3545; }
        .button-logout { background-color: #f44336; }

        @media (min-width: 768px) {
            .event-card > div, .judge-card > div {
                flex: none;
            }
            .card-buttons {
                flex-basis: auto; /* Revert to auto width on larger screens */
                margin-top: 0;
            }
        }
        .message {
            padding: 10px;
            border: 1px solid;
            margin-bottom: 15px;
            background-color: #e9f7ef; /* Example for success */
            border-color: #28a745;
            color: #28a745;
        }
        .error-message {
            background-color: #f8d7da; /* Example for error */
            border-color: #dc3545;
            color: #dc3545;
        }
        .user-info {
            float: right;
            padding: 10px;
        }

        /* Modal Styles (basic structure, no design) */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            justify-content: center; /* Center content horizontally */
            align-items: center; /* Center content vertically */
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more responsive */
            max-width: 500px;
            box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19);
            text-align: center;
        }
        .modal-buttons {
            margin-top: 20px;
        }
        .modal-buttons button {
            margin: 0 10px;
            padding: 10px 20px;
            cursor: pointer;
        }
        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
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
            <?php if (!empty($message)): ?>
                <p class="message <?php echo (strpos($message, 'successfully') !== false) ? '' : 'error-message'; ?>"><?php echo $message; ?></p>
            <?php endif; ?>

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
                                    <div class="event-details">
                                        <h3 class="event-name"><?php echo htmlspecialchars($event['event_name']); ?></h3>
                                        <p class="event-meta">Created: <?php echo htmlspecialchars($event['created_at']); ?></p>
                                    </div>
                                    <div class="card-buttons">
                                        <a href="manage_event.php?event_id=<?php echo $event['event_id']; ?>" class="button button-secondary">Manage</a>
                                        <a href="edit_event.php?event_id=<?php echo $event['event_id']; ?>" class="button button-edit">Edit</a>
                                        <!-- Changed onclick to open custom modal -->
                                        <button type="button" class="button button-delete open-delete-modal"
                                                data-id="<?php echo $event['event_id']; ?>"
                                                data-type="event"
                                                data-name="<?php echo htmlspecialchars($event['event_name']); ?>"
                                                data-url="delete_event.php?event_id="
                                                data-confirm-message="Are you sure you want to delete the event '<?php echo htmlspecialchars($event['event_name']); ?>'? This will also delete all associated competitions, categories, criteria, participants, and scores. This action cannot be undone.">
                                            Delete
                                        </button>
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
                                    <div class="judge-details">
                                        <h3 class="judge-username"><?php echo htmlspecialchars($judge['username']); ?></h3>
                                        <p class="judge-email">Email: <?php echo htmlspecialchars($judge['email']); ?></p>
                                        <p class="judge-meta">Active: <?php echo $judge['is_active'] ? 'Yes' : 'No'; ?></p>
                                        <p class="judge-meta">User Created: <?php echo htmlspecialchars($judge['user_created_at']); ?></p>
                                    </div>
                                    <div class="card-buttons">
                                        <a href="edit_judge.php?judge_id=<?php echo $judge['judge_id']; ?>" class="button button-edit">Edit</a>
                                        <!-- Changed onclick to open custom modal -->
                                        <button type="button" class="button button-delete open-delete-modal"
                                                data-id="<?php echo $judge['judge_id']; ?>"
                                                data-type="judge"
                                                data-name="<?php echo htmlspecialchars($judge['username']); ?>"
                                                data-url="delete_judge.php?judge_id="
                                                data-confirm-message="Are you sure you want to delete the judge '<?php echo htmlspecialchars($judge['username']); ?>'? This will also delete their associated user account, assignments, and scores. This action cannot be undone.');">
                                            Delete
                                        </button>
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

    <!-- Custom Delete Confirmation Modal -->
    <div id="deleteConfirmationModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <p id="modalConfirmMessage">Are you sure you want to delete this item?</p>
            <div class="modal-buttons">
                <button id="confirmDeleteButton" class="button button-delete">Confirm Delete</button>
                <button id="cancelDeleteButton" class="button button-secondary">Cancel</button>
            </div>
        </div>
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

            // --- Custom Delete Modal Logic ---
            const deleteModal = document.getElementById('deleteConfirmationModal');
            const closeButton = document.querySelector('.close-button');
            const confirmDeleteButton = document.getElementById('confirmDeleteButton');
            const cancelDeleteButton = document.getElementById('cancelDeleteButton');
            const modalConfirmMessage = document.getElementById('modalConfirmMessage');

            // Function to open the modal
            function openDeleteModal(id, type, name, url, confirmMessage) {
                modalConfirmMessage.innerHTML = confirmMessage; // Use innerHTML to allow strong tags etc.
                confirmDeleteButton.onclick = function() {
                    window.location.href = url + id; // Redirect to the delete script
                };
                deleteModal.style.display = 'flex'; // Use flex to center
            }

            // Function to close the modal
            function closeDeleteModal() {
                deleteModal.style.display = 'none';
            }

            // Event listeners for delete buttons
            document.querySelectorAll('.open-delete-modal').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const type = this.dataset.type;
                    const name = this.dataset.name;
                    const url = this.dataset.url;
                    const confirmMessage = this.dataset.confirmMessage;
                    openDeleteModal(id, type, name, url, confirmMessage);
                });
            });

            // Event listeners for modal buttons
            closeButton.addEventListener('click', closeDeleteModal);
            cancelDeleteButton.addEventListener('click', closeDeleteModal);

            // Close modal if user clicks outside of it
            window.addEventListener('click', function(event) {
                if (event.target == deleteModal) {
                    closeDeleteModal();
                }
            });
        });
    </script>
</body>
</html>