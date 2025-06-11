<?php
/**
 * Create Participant Page
 * Handles adding new participants to a competition/category.
 */
session_start();

require_once '../../includes/db_connection.php'; // Adjust path

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$message = '';
$competition_id = $_GET['competition_id'] ?? null;
$category_id = $_GET['category_id'] ?? null; // Optional, for specific categories in segmented judging
$participant_name = '';

// Get competition name for display
$competition_name = 'Unknown Competition';
if ($competition_id) {
    $stmt_comp_name = $conn->prepare("SELECT competition_name FROM competitions WHERE competition_id = ?");
    if ($stmt_comp_name) {
        $stmt_comp_name->bind_param("i", $competition_id);
        $stmt_comp_name->execute();
        $stmt_comp_name->bind_result($name);
        $stmt_comp_name->fetch();
        $stmt_comp_name->close();
        if ($name) $competition_name = $name;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $participant_name = trim($_POST['participant_name'] ?? '');
    $competition_id = $_POST['competition_id'] ?? null;
    $category_id = $_POST['category_id'] ?? null; // Pass if coming from a specific category

    if (empty($participant_name) || $competition_id === null) {
        $message = "Participant name and Competition ID are required.";
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO participants (competition_id, category_id, participant_name, created_at) VALUES (?, ?, ?, NOW())");
        if ($stmt_insert) {
            // Handle category_id which can be null for non-segmented judging
            $category_id_param = ($category_id === null || $category_id === '') ? null : (int)$category_id;
            $stmt_insert->bind_param("iis", $competition_id, $category_id_param, $participant_name);

            if ($stmt_insert->execute()) {
                $success_message = "Participant '" . htmlspecialchars($participant_name) . "' added successfully!";
                // Redirect back to the correct manage competition page, possibly with a tab pre-selected
                if ($category_id_param !== null) {
                    header("Location: ../categories/manage_category.php?category_id=" . $category_id_param . "&message=" . urlencode($success_message));
                } else {
                    header("Location: ../manage_competition.php?competition_id=" . $competition_id . "&subtab=participants&message=" . urlencode($success_message));
                }
                exit();
            } else {
                $message = "Error adding participant: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        } else {
            $message = "Error preparing statement: " . $conn->error;
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
    <title>Add New Participant</title>
    <!-- Your CSS link will go here -->
    <!-- <link rel="stylesheet" href="../../assets/css/style.css"> -->
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
            <h1 class="site-title">Add New Participant for <?php echo htmlspecialchars($competition_name); ?></h1>
        </header>

        <main class="main-content">
            <section class="form-section">
                <div class="form-container">
                    <?php if (!empty($message)): ?>
                        <p class="message"><?php echo htmlspecialchars($message); ?></p>
                    <?php endif; ?>
                    <form action="create_participant.php" method="POST" class="create-participant-form">
                        <input type="hidden" name="competition_id" value="<?php echo htmlspecialchars($competition_id); ?>">
                        <?php if ($category_id !== null): ?>
                            <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category_id); ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="participant_name" class="form-label">Participant Name:</label>
                            <input type="text" id="participant_name" name="participant_name" class="form-input" value="<?php echo htmlspecialchars($participant_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="button button-primary">Add Participant</button>
                        </div>
                    </form>
                    <div class="form-footer-links">
                        <?php if ($category_id !== null): ?>
                            <p><a href="../categories/manage_category.php?category_id=<?php echo htmlspecialchars($category_id); ?>">Back to Category Management</a></p>
                        <?php else: ?>
                            <p><a href="../manage_competition.php?competition_id=<?php echo htmlspecialchars($competition_id); ?>&tab=participants">Back to Competition Management</a></p>
                        <?php endif; ?>
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
