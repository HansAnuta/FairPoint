<?php
/**
 * Create Category Page
 * Handles adding new categories to a competition (specifically for Segmented Judging).
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
$category_name = '';

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
    $category_name = trim($_POST['category_name'] ?? '');
    $competition_id_post = $_POST['competition_id'] ?? null;

    if (empty($category_name) || $competition_id_post === null) {
        $message = "Category name and Competition ID are required.";
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO categories (competition_id, category_name, created_at) VALUES (?, ?, NOW())");
        if ($stmt_insert) {
            $stmt_insert->bind_param("is", $competition_id_post, $category_name);
            if ($stmt_insert->execute()) {
                $success_message = "Category '" . htmlspecialchars($category_name) . "' added successfully!";
                // Redirect back to segmented judging competition manage page, categories tab
                header("Location: ../manage_competition.php?competition_id=" . $competition_id_post . "&subtab=categories&message=" . urlencode($success_message));
                exit();
            } else {
                $message = "Error adding category: " . $stmt_insert->error;
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
    <title>Add New Category</title>
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
            <h1 class="site-title">Add New Category for <?php echo htmlspecialchars($competition_name); ?></h1>
        </header>

        <main class="main-content">
            <section class="form-section">
                <div class="form-container">
                    <?php if (!empty($message)): ?>
                        <p class="message"><?php echo htmlspecialchars($message); ?></p>
                    <?php endif; ?>
                    <form action="create_category.php" method="POST" class="create-category-form">
                        <input type="hidden" name="competition_id" value="<?php echo htmlspecialchars($competition_id); ?>">
                        <div class="form-group">
                            <label for="category_name" class="form-label">Category Name:</label>
                            <input type="text" id="category_name" name="category_name" class="form-input" value="<?php echo htmlspecialchars($category_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="button button-primary">Add Category</button>
                        </div>
                    </form>
                    <div class="form-footer-links">
                        <p><a href="../manage_competition.php?competition_id=<?php echo htmlspecialchars($competition_id); ?>&tab=categories">Back to Competition Management</a></p>
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
