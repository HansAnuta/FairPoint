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

$conn->close();

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
    <title>Manage <?php echo htmlspecialchars($competition_name); ?></title>
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
                // Pass relevant data to the partial
                require $content_partial;
            } elseif ($content_partial === null) {
                echo "<p>Please contact support: An error occurred with the judging method configuration.</p>";
            } else {
                echo "<p>Management interface for this judging method is not yet implemented.</p>";
            }
            ?>

            <div class="form-footer-links">
                <p><a href="manage_event.php?event_id=<?php echo $event_id; ?>">Back to Event Management</a></p>
                <p><a href="dashboard.php?tab=events">Back to Dashboard</a></p>
            </div>
        </main>

        <footer class="main-footer">
            <p class="footer-text">&copy; 2025 Digital Judging System. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // JavaScript for tab switching (if tabs are used in partials)
        document.addEventListener('DOMContentLoaded', function() {
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

                        // Optionally update URL hash for sub-tabs
                        // history.pushState(null, '', `manage_competition.php?competition_id=<?php echo $competition_id; ?>&subtab=${this.dataset.tab}`);
                    });
                });

                // Activate initial tab if a subtab parameter is present
                // const urlParams = new URLSearchParams(window.location.search);
                // const initialSubTab = urlParams.get('subtab');
                // if (initialSubTab) {
                //     const initialButton = document.querySelector(`.tab-button[data-tab="${initialSubTab}"]`);
                //     if (initialButton) {
                //         initialButton.click();
                //     }
                // } else {
                //     // Default to the first tab if no specific subtab is requested
                //     tabButtons[0].click();
                // }
            }
        });
    </script>
</body>
</html>
