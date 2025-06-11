<?php
/**
 * Create Criteria Page
 * Handles adding new criteria to a specific category.
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
$competition_id = $_GET['competition_id'] ?? null; // For redirection back to correct competition
$criteria_name = '';
$weight = '';

// Get category name for display
$category_name = 'Unknown Category';
if ($category_id) {
    $stmt_cat_name = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
    if ($stmt_cat_name) {
        $stmt_cat_name->bind_param("i", $category_id);
        $stmt_cat_name->execute();
        $stmt_cat_name->bind_result($name);
        $stmt_cat_name->fetch();
        $stmt_cat_name->close();
        if ($name) $category_name = $name;
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $criteria_name = trim($_POST['criteria_name'] ?? '');
    $weight = trim($_POST['weight'] ?? '');
    $category_id_post = $_POST['category_id'] ?? null; // Get from POST
    $competition_id_post = $_POST['competition_id'] ?? null; // Get from POST

    if (empty($criteria_name) || $category_id_post === null || empty($weight) || !is_numeric($weight)) {
        $message = "Please fill in all required fields with valid values.";
    } elseif ($weight < 0) { // Basic validation for weight
        $message = "Weight cannot be negative.";
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO criteria (category_id, criteria_name, weight, created_at) VALUES (?, ?, ?, NOW())");
        if ($stmt_insert) {
            $stmt_insert->bind_param("isd", $category_id_post, $criteria_name, $weight); // 'd' for double/decimal
            if ($stmt_insert->execute()) {
                $success_message = "Criteria '" . htmlspecialchars($criteria_name) . "' added successfully!";
                // Redirect back to the correct management page based on context
                if ($competition_id_post && $category_id_post) {
                    // Coming from segmented judging -> manage_category.php
                    header("Location: ../categories/manage_category.php?category_id=" . $category_id_post . "&message=" . urlencode($success_message));
                } else if ($competition_id_post) {
                    // Coming from direct competition (ranking/simple/weighted) -> manage_competition.php
                    header("Location: ../manage_competition.php?competition_id=" . $competition_id_post . "&subtab=criteria&message=" . urlencode($success_message));
                } else {
                    // Fallback, though ideally shouldn't happen if links are correct
                    header("Location: ../dashboard.php?tab=events&message=" . urlencode($success_message));
                }
                exit();
            } else {
                $message = "Error adding criteria: " . $stmt_insert->error;
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
    <title>Add New Criteria</title>
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
            <h1 class="site-title">Add New Criteria for <?php echo htmlspecialchars($category_name); ?></h1>
        </header>

        <main class="main-content">
            <section class="form-section">
                <div class="form-container">
                    <?php if (!empty($message)): ?>
                        <p class="message"><?php echo htmlspecialchars($message); ?></p>
                    <?php endif; ?>
                    <form action="create_criteria.php" method="POST" class="create-criteria-form">
                        <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category_id); ?>">
                        <input type="hidden" name="competition_id" value="<?php echo htmlspecialchars($competition_id); ?>">
                        <div class="form-group">
                            <label for="criteria_name" class="form-label">Criteria Name:</label>
                            <input type="text" id="criteria_name" name="criteria_name" class="form-input" value="<?php echo htmlspecialchars($criteria_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="weight" class="form-label">Weight (e.g., 25.00 for 25% or 1 for non-weighted):</label>
                            <input type="number" step="0.01" id="weight" name="weight" class="form-input" value="<?php echo htmlspecialchars($weight); ?>" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="button button-primary">Add Criteria</button>
                        </div>
                    </form>
                    <div class="form-footer-links">
                        <?php if ($category_id !== null && $competition_id !== null): ?>
                            <p><a href="../categories/manage_category.php?category_id=<?php echo htmlspecialchars($category_id); ?>">Back to Category Management</a></p>
                        <?php elseif ($competition_id !== null): ?>
                            <p><a href="../manage_competition.php?competition_id=<?php echo htmlspecialchars($competition_id); ?>&tab=criteria">Back to Competition Management</a></p>
                        <?php else: ?>
                            <p><a href="../dashboard.php?tab=events">Back to Dashboard</a></p>
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
