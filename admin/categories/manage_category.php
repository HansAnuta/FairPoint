<?php
/**
 * Manage Category Page (for Segmented Judging)
 * Allows admins to configure participants and criteria for a specific category.
 */
session_start();

require_once '../../includes/db_connection.php'; // Adjust path

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$message = '';
$category_id = $_GET['category_id'] ?? null;
$category_name = '';
$competition_id = null; // To redirect back to the correct competition management page

// Check for and display messages from redirects
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

// Redirect if no category_id is provided
if ($category_id === null) {
    // Attempt to get competition_id to redirect gracefully
    $temp_comp_id = $_GET['competition_id'] ?? null;
    if ($temp_comp_id) {
         header("Location: ../manage_competition.php?competition_id=" . $temp_comp_id . "&subtab=categories&message=" . urlencode("No category selected to manage."));
    } else {
        header("Location: ../dashboard.php?tab=events&message=" . urlencode("No category selected to manage."));
    }
    exit();
}

// Fetch category details and its parent competition_id
$stmt_cat = $conn->prepare("SELECT category_name, competition_id FROM categories WHERE category_id = ?");
if ($stmt_cat) {
    $stmt_cat->bind_param("i", $category_id);
    $stmt_cat->execute();
    $stmt_cat->bind_result($category_name, $competition_id);
    $stmt_cat->fetch();
    $stmt_cat->close();
    if ($category_name === null) { // If category_id doesn't exist
        header("Location: ../dashboard.php?tab=events&message=" . urlencode("Category not found."));
        exit();
    }
} else {
    error_log("Failed to prepare statement for fetching category: " . $conn->error);
    $message = "Error fetching category details.";
}

// Determine active sub-tab for this page (Participants or Criteria)
$active_subtab = $_GET['subtab'] ?? 'participants';


// Fetch Participants for this category
$participants = [];
$stmt_participants = $conn->prepare("SELECT participant_id, participant_name FROM participants WHERE category_id = ? ORDER BY participant_name ASC");
if ($stmt_participants) {
    $stmt_participants->bind_param("i", $category_id);
    $stmt_participants->execute();
    $result_participants = $stmt_participants->get_result();
    while ($row = $result_participants->fetch_assoc()) {
        $participants[] = $row;
    }
    $stmt_participants->close();
} else {
    error_log("Failed to prepare statement for fetching participants: " . $conn->error);
}

// Fetch Criteria for this category
$criteria = [];
$stmt_criteria = $conn->prepare("SELECT criteria_id, criteria_name, weight FROM criteria WHERE category_id = ? ORDER BY criteria_name ASC");
if ($stmt_criteria) {
    $stmt_criteria->bind_param("i", $category_id);
    $stmt_criteria->execute();
    $result_criteria = $stmt_criteria->get_result();
    while ($row = $result_criteria->fetch_assoc()) {
        $criteria[] = $row;
    }
    $stmt_criteria->close();
} else {
    error_log("Failed to prepare statement for fetching criteria: " . $conn->error);
}


$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Category: <?php echo htmlspecialchars($category_name); ?></title>
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
            flex-basis: 100%;
            text-align: right;
            margin-top: 10px;
        }
        @media (min-width: 768px) {
            .item-card > div { flex: none; }
            .item-card .card-buttons { flex-basis: auto; margin-top: 0; }
        }
        .message { padding: 10px; border: 1px solid; margin-bottom: 15px; }
        .button { padding: 8px 15px; background-color: #007bff; color: white; text-decoration: none; border: none; cursor: pointer; display: inline-block; margin-top: 10px; }
        .button-primary { background-color: #007bff; }
        .button-secondary { background-color: #6c757d; }
        .button-edit { background-color: #ffc107; color: black;}
        .button-delete { background-color: #dc3545; }

        /* Modal Styles (basic structure, no design) - Duplicated for category level */
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
            <h1 class="site-title">Manage Category: <?php echo htmlspecialchars($category_name); ?></h1>
            <p>Parent Competition ID: <?php echo htmlspecialchars($competition_id); ?></p>
        </header>

        <main class="main-content">
            <?php if (!empty($message)): ?>
                <p class="message"><?php echo $message; ?></p>
            <?php endif; ?>

            <div class="category-tabs">
                <div class="tabs">
                    <button class="tab-button <?php echo ($active_subtab === 'participants') ? 'active' : ''; ?>" data-tab="participants">Participants</button>
                    <button class="tab-button <?php echo ($active_subtab === 'criteria') ? 'active' : ''; ?>" data-tab="criteria">Criteria</button>
                </div>

                <div id="participants-tab" class="tab-content <?php echo ($active_subtab === 'participants') ? 'active' : ''; ?>">
                    <div class="section-header">
                        <h2 class="tab-heading">Participants</h2>
                        <a href="../participants/create_participant.php?competition_id=<?php echo htmlspecialchars($competition_id); ?>&category_id=<?php echo htmlspecialchars($category_id); ?>" class="button button-primary">Add New Participant</a>
                    </div>
                    <div class="list-container">
                        <?php if (empty($participants)): ?>
                            <p>No participants found for this category.</p>
                        <?php else: ?>
                            <?php foreach ($participants as $participant): ?>
                                <div class="item-card">
                                    <div class="item-details">
                                        <h3 class="item-name"><?php echo htmlspecialchars($participant['participant_name']); ?></h3>
                                    </div>
                                    <div class="card-buttons">
                                        <a href="../participants/edit_participant.php?participant_id=<?php echo htmlspecialchars($participant['participant_id']); ?>&competition_id=<?php echo htmlspecialchars($competition_id); ?>&category_id=<?php echo htmlspecialchars($category_id); ?>" class="button button-edit">Edit</a>
                                        <button type="button" class="button button-delete open-cat-delete-modal"
                                                data-id="<?php echo htmlspecialchars($participant['participant_id']); ?>"
                                                data-type="participant"
                                                data-name="<?php echo htmlspecialchars($participant['participant_name']); ?>"
                                                data-url="../participants/delete_participant.php?participant_id="
                                                data-extra-params="&competition_id=<?php echo htmlspecialchars($competition_id); ?>&category_id=<?php echo htmlspecialchars($category_id); ?>"
                                                data-confirm-message="Are you sure you want to delete the participant '<?php echo htmlspecialchars($participant['participant_name']); ?>'?">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="criteria-tab" class="tab-content <?php echo ($active_subtab === 'criteria') ? 'active' : ''; ?>">
                    <div class="section-header">
                        <h2 class="tab-heading">Criteria</h2>
                        <a href="../criteria/create_criteria.php?competition_id=<?php echo htmlspecialchars($competition_id); ?>&category_id=<?php echo htmlspecialchars($category_id); ?>" class="button button-primary">Add New Criteria</a>
                    </div>
                    <div class="list-container">
                        <?php if (empty($criteria)): ?>
                            <p>No criteria found for this category.</p>
                        <?php else: ?>
                            <?php foreach ($criteria as $criterion): ?>
                                <div class="item-card">
                                    <div class="item-details">
                                        <h3 class="item-name"><?php echo htmlspecialchars($criterion['criteria_name']); ?></h3>
                                        <p class="item-weight">Weight: <?php echo htmlspecialchars($criterion['weight']); ?></p>
                                    </div>
                                    <div class="card-buttons">
                                        <a href="../criteria/edit_criteria.php?criteria_id=<?php echo htmlspecialchars($criterion['criteria_id']); ?>&competition_id=<?php echo htmlspecialchars($competition_id); ?>&category_id=<?php echo htmlspecialchars($category_id); ?>" class="button button-edit">Edit</a>
                                        <button type="button" class="button button-delete open-cat-delete-modal"
                                                data-id="<?php echo htmlspecialchars($criterion['criteria_id']); ?>"
                                                data-type="criteria"
                                                data-name="<?php echo htmlspecialchars($criterion['criteria_name']); ?>"
                                                data-url="../criteria/delete_criteria.php?criteria_id="
                                                data-extra-params="&competition_id=<?php echo htmlspecialchars($competition_id); ?>&category_id=<?php echo htmlspecialchars($category_id); ?>"
                                                data-confirm-message="Are you sure you want to delete the criteria '<?php echo htmlspecialchars($criterion['criteria_name']); ?>'?">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-footer-links">
                <p><a href="../manage_competition.php?competition_id=<?php echo htmlspecialchars($competition_id); ?>&subtab=categories">Back to Competition Management</a></p>
                <p><a href="../dashboard.php?tab=events">Back to Dashboard</a></p>
            </div>
        </main>

        <footer class="main-footer">
            <p class="footer-text">&copy; 2025 Digital Judging System. All rights reserved.</p>
        </footer>
    </div>

    <!-- Custom Delete Confirmation Modal (for items managed within categories) -->
    <div id="catDeleteConfirmationModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <p id="catModalConfirmMessage">Are you sure you want to delete this item?</p>
            <div class="modal-buttons">
                <button id="catConfirmDeleteButton" class="button button-delete">Confirm Delete</button>
                <button id="catCancelDeleteButton" class="button button-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // JavaScript for tab switching within this page
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            if (tabButtons.length > 0) {
                tabButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const targetTabId = this.dataset.tab + '-tab';

                        tabButtons.forEach(btn => btn.classList.remove('active'));
                        tabContents.forEach(content => content.classList.remove('active'));

                        this.classList.add('active');
                        document.getElementById(targetTabId).classList.add('active');

                        // Update URL hash to maintain active tab on refresh (optional but good UX)
                        history.pushState(null, '', `manage_category.php?category_id=<?php echo $category_id; ?>&subtab=${this.dataset.tab}`);
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
                    // Default to participants tab if no parameter is present
                    document.querySelector('.tab-button[data-tab="participants"]').click();
                }
            }

            // --- Custom Delete Modal Logic for Category Level ---
            function setupCategoryDeleteModal(modalId) {
                const deleteModal = document.getElementById(modalId);
                if (!deleteModal) return;

                const closeButton = deleteModal.querySelector('.close-button');
                const confirmDeleteButton = deleteModal.querySelector('[id$="ConfirmDeleteButton"]');
                const cancelDeleteButton = deleteModal.querySelector('[id$="CancelDeleteButton"]');
                const modalConfirmMessage = deleteModal.querySelector('[id$="ModalConfirmMessage"]');

                window.openCatDeleteModal = function(id, type, name, url, extraParams, confirmMessage) {
                    modalConfirmMessage.innerHTML = confirmMessage;
                    confirmDeleteButton.onclick = function() {
                        window.location.href = url + id + (extraParams || ''); // Append extra parameters
                    };
                    deleteModal.style.display = 'flex';
                };

                function closeCatDeleteModal() {
                    deleteModal.style.display = 'none';
                }

                document.querySelectorAll('.open-cat-delete-modal').forEach(button => {
                    button.removeEventListener('click', handleOpenCatModalClick); // Prevent duplicate
                    button.addEventListener('click', handleOpenCatModalClick);
                });

                closeButton.addEventListener('click', closeCatDeleteModal);
                cancelDeleteButton.addEventListener('click', closeCatDeleteModal);

                window.addEventListener('click', function(event) {
                    if (event.target == deleteModal) {
                        closeCatDeleteModal();
                    }
                });
            }

            function handleOpenCatModalClick() {
                const id = this.dataset.id;
                const type = this.dataset.type;
                const name = this.dataset.name;
                const url = this.dataset.url;
                const extraParams = this.dataset.extraParams; // Get extra parameters
                const confirmMessage = this.dataset.confirmMessage;
                window.openCatDeleteModal(id, type, name, url, extraParams, confirmMessage);
            }

            setupCategoryDeleteModal('catDeleteConfirmationModal');
        });
    </script>
</body>
</html>