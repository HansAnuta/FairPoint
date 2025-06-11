<?php
/**
 * Manage Competition Page
 * This page dynamically loads different management interfaces based on the
 * judging method associated with the selected competition.
 */
session_start();

require_once '../includes/db_connection.php'; // Adjust path

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$competition_id = $_GET['competition_id'] ?? null;
$competition_name = '';
$judging_method_id = null;
$judging_method_name = '';
$message = ''; // For displaying messages from redirects

// Check for and display messages from redirects
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

// Redirect if no competition_id is provided
if ($competition_id === null) {
    header("Location: dashboard.php?tab=events&message=" . urlencode("No competition selected to manage."));
    exit();
}

// Fetch competition details including its judging method
$stmt_comp = $conn->prepare("
    SELECT c.competition_name, c.judging_method_id, jm.method_name, c.event_id
    FROM competitions c
    JOIN judging_methods jm ON c.judging_method_id = jm.judging_method_id
    WHERE c.competition_id = ?
");
if ($stmt_comp) {
    $stmt_comp->bind_param("i", $competition_id);
    $stmt_comp->execute();
    $stmt_comp->bind_result($competition_name, $judging_method_id, $judging_method_name, $event_id);
    $stmt_comp->fetch();
    $stmt_comp->close();

    if ($competition_name === null) { // If competition_id doesn't exist
        header("Location: dashboard.php?tab=events&message=" . urlencode("Competition not found."));
        exit();
    }
} else {
    // Handle error if statement preparation fails
    error_log("Failed to prepare statement for fetching competition: " . $conn->error);
    $message = "Error fetching competition details.";
}

// Pass connection to partials
// define('INCLUDED_VIA_MANAGE_COMPETITION', true); // Removed this, global $conn is sufficient

// --- Determine which partial to include based on judging_method_id ---
$content_partial = '';
switch ($judging_method_id) {
    case 1: // Ranking
        $content_partial = 'competitions/ranking.php';
        break;
    case 2: // Simple Averaging
        $content_partial = 'competitions/simple_averaging.php';
        break;
    case 3: // Weighted Averaging
        $content_partial = 'competitions/weighted_averaging.php';
        break;
    case 4: // Segmented Judging
        $content_partial = 'competitions/segmented_judging.php';
        break;
    case 5: // Elimination and Bracketing
        $content_partial = 'competitions/elimination_bracketing.php';
        break;
    default:
        $message = "Unknown judging method for this competition.";
        $content_partial = null; // No partial to include
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Competition: <?php echo htmlspecialchars($competition_name); ?></title>
    <!-- Your CSS link will go here -->
    <!-- <link rel="stylesheet" href="../../assets/css/style.css"> -->
    <style>
        /* Basic styles for responsive structure and no design */
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
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .list-container {
            border: 1px solid #ccc;
            padding: 15px;
            margin-top: 15px;
        }
        .item-card {
            border: 1px solid #ddd;
            margin-bottom: 10px;
            padding: 10px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        .item-card > div {
            flex: 1;
            padding: 5px;
        }
        .item-card .card-buttons {
            flex-basis: 100%; /* Take full width on small screens */
            text-align: right; /* Align buttons to the right */
            margin-top: 10px;
        }
        @media (min-width: 768px) {
            .item-card > div {
                flex: none;
            }
            .item-card .card-buttons {
                flex-basis: auto; /* Revert to auto width on larger screens */
                margin-top: 0;
            }
        }
        .message {
            padding: 10px;
            border: 1px solid;
            margin-bottom: 15px;
        }
        .form-container {
            border: 1px solid #ccc;
            padding: 15px;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 10px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
        }
        .form-input, .form-select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        .button {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border: none;
            cursor: pointer;
            display: inline-block;
            margin-top: 10px;
        }
        .button-primary { background-color: #007bff; }
        .button-secondary { background-color: #6c757d; }
        .button-edit { background-color: #ffc107; color: black;}
        .button-delete { background-color: #dc3545; }

        /* Modal Styles (basic structure, no design) - Duplicated for competition level */
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
            <h1 class="site-title">Manage Competition: <?php echo htmlspecialchars($competition_name); ?></h1>
            <p class="competition-method-display">Judging Method: <?php echo htmlspecialchars($judging_method_name); ?></p>
        </header>

        <main class="main-content">
            <?php if (!empty($message)): ?>
                <p class="message"><?php echo $message; ?></p>
            <?php endif; ?>

            <?php
            // Include the specific partial based on the judging method
            if ($content_partial && file_exists($content_partial)) {
                // Pass relevant data to the partial (competition_id and event_id)
                // The partial will then need to declare global $conn;
                // to use the existing database connection.
                require $content_partial;
            } elseif ($content_partial === null) {
                echo "<p>Please contact support: An error occurred with the judging method configuration.</p>";
            } else {
                echo "<p>Management interface for this judging method is not yet implemented.</p>";
            }
            ?>

            <div class="form-footer-links">
                <p><a href="manage_event.php?event_id=<?php echo htmlspecialchars($event_id); ?>">Back to Event Management</a></p>
                <p><a href="dashboard.php?tab=events">Back to Dashboard</a></p>
            </div>
        </main>

        <footer class="main-footer">
            <p class="footer-text">&copy; 2025 Digital Judging System. All rights reserved.</p>
        </footer>
    </div>

    <!-- Custom Delete Confirmation Modal (for items managed within competitions) -->
    <div id="compDeleteConfirmationModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <p id="compModalConfirmMessage">Are you sure you want to delete this item?</p>
            <div class="modal-buttons">
                <button id="compConfirmDeleteButton" class="button button-delete">Confirm Delete</button>
                <button id="compCancelDeleteButton" class="button button-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // JavaScript for tab switching (if tabs are used in partials)
        document.addEventListener('DOMContentLoaded', function() {
            // Function to handle global modal logic for deletion
            function setupDeleteModal(modalId) {
                const deleteModal = document.getElementById(modalId);
                if (!deleteModal) return; // Exit if modal doesn't exist

                const closeButton = deleteModal.querySelector('.close-button');
                const confirmDeleteButton = deleteModal.querySelector('[id$="ConfirmDeleteButton"]'); // Use ends-with selector
                const cancelDeleteButton = deleteModal.querySelector('[id$="CancelDeleteButton"]');
                const modalConfirmMessage = deleteModal.querySelector('[id$="ModalConfirmMessage"]');

                // Function to open the modal
                window.openCompDeleteModal = function(id, type, name, url, confirmMessage) {
                    modalConfirmMessage.innerHTML = confirmMessage;
                    confirmDeleteButton.onclick = function() {
                        window.location.href = url + id; // Redirect to the delete script
                    };
                    deleteModal.style.display = 'flex'; // Use flex to center
                };

                // Function to close the modal
                function closeCompDeleteModal() {
                    deleteModal.style.display = 'none';
                }

                // Event listeners for modal buttons
                closeButton.addEventListener('click', closeCompDeleteModal);
                cancelDeleteButton.addEventListener('click', closeCompDeleteModal);

                // Close modal if user clicks outside of it
                window.addEventListener('click', function(event) {
                    if (event.target == deleteModal) {
                        closeCompDeleteModal();
                    }
                });

                // Attach click listeners to all buttons that open this modal
                document.querySelectorAll('.open-comp-delete-modal').forEach(button => {
                    button.removeEventListener('click', handleOpenModalClick); // Prevent duplicate listeners
                    button.addEventListener('click', handleOpenModalClick);
                });
            }

            function handleOpenModalClick() {
                const id = this.dataset.id;
                const type = this.dataset.type;
                const name = this.dataset.name;
                const url = this.dataset.url;
                const confirmMessage = this.dataset.confirmMessage;
                window.openCompDeleteModal(id, type, name, url, confirmMessage);
            }

            // Setup the competition-level modal
            setupDeleteModal('compDeleteConfirmationModal');


            // Tab switching for competition management (if tabs are used in partials)
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            if (tabButtons.length > 0) { // Only run if tabs exist
                tabButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const targetTabId = this.dataset.tab + '-tab';

                        tabButtons.forEach(btn => btn.classList.remove('active'));
                        tabContents.forEach(content => content.classList.remove('active'));

                        this.classList.add('active');
                        document.getElementById(targetTabId).classList.add('active');

                        // Update URL hash to maintain active tab on refresh (optional but good UX)
                        // history.pushState(null, '', `manage_competition.php?competition_id=<?php echo $competition_id; ?>&subtab=${this.dataset.tab}`);
                    });
                });

                // Initial tab selection based on URL parameter on page load
                const urlParams = new URLSearchParams(window.location.search);
                const initialSubTab = urlParams.get('subtab');
                if (initialSubTab) {
                    const initialButton = document.querySelector(`.tab-button[data-tab="${initialSubTab}"]`);
                    if (initialButton) {
                        initialButton.click();
                    }
                } else {
                    // Default to the first tab if no specific subtab is requested
                    // This logic might need refinement based on which partial gets loaded first.
                    // For now, it won't click if no buttons are found, which is safe.
                    if (tabButtons[0]) tabButtons[0].click();
                }
            }
        });
    </script>
</body>
</html>