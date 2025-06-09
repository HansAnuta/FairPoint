<?php
// Digital_Judging_System/add_category.php

require_once 'db_connect.php';

$competition_id = $_GET['competition_id'] ?? null;
$competition_name = '';
$category_name = '';
$message = '';
$message_type = '';

// --- Fetch Competition Name ---
if ($competition_id) {
    try {
        $stmt = $pdo->prepare("SELECT competition_name, competition_type FROM Competition WHERE competition_id = ?");
        $stmt->execute([$competition_id]);
        $comp_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($comp_data) {
            $competition_name = $comp_data['competition_name'];
            // Redirect if trying to add categories to a non-category-based competition
            if ($comp_data['competition_type'] !== 'categories_based') {
                header('Location: /Digital_Judging_System/competition_details.php?competition_id=' . $competition_id . '&status=error&message=' . urlencode('This competition does not support categories.'));
                exit();
            }
        } else {
            header('Location: /Digital_Judging_System/admin_events.php?error=competition_not_found');
            exit();
        }
    } catch (\PDOException $e) {
        $message = "Error fetching competition details: " . $e->getMessage();
        $message_type = 'error';
        error_log("Error in add_category.php (comp fetch): " . $e->getMessage());
    }
} else {
    header('Location: /Digital_Judging_System/admin_events.php?error=no_competition_id');
    exit();
}

// --- Handle Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = trim($_POST['category_name']);

    if (empty($category_name)) {
        $message = "Category Name is required.";
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO Category (competition_id, category_name, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$competition_id, $category_name]);

            $message = "Category '" . htmlspecialchars($category_name) . "' added successfully!";
            $message_type = 'success';
            $category_name = ''; // Clear form field

            // Redirect back to competition_details.php
            header('Location: /Digital_Judging_System/competition_details.php?competition_id=' . $competition_id . '&status=success&message=' . urlencode($message));
            exit();

        } catch (\PDOException $e) {
            $message = "Error adding category: " . $e->getMessage();
            $message_type = 'error';
            error_log("Error in add_category.php: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category to <?php echo htmlspecialchars($competition_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Digital_Judging_System/css/style.css">
    <style>
        /* Reusing form styles from create_event_step1.php */
        .form-container {
            padding: 30px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 40px auto;
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #34495e;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        .form-group input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 1.1em;
        }
        .form-group input[type="text"]:focus {
            border-color: #2ecc71;
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2);
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }
        .form-actions button {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .form-actions .submit-btn {
            background-color: #2ecc71;
            color: white;
        }
        .form-actions .submit-btn:hover {
            background-color: #27ae60;
        }
        .form-actions .cancel-btn {
            background-color: #95a5a6;
            color: white;
        }
        .form-actions .cancel-btn:hover {
            background-color: #7f8c8d;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
            text-align: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>Digital Judging System - Admin</h1>
        <button class="logout-btn" onclick="handleLogout()">Logout</button>
    </header>

    <div class="form-container">
        <h2>Add Category to: <?php echo htmlspecialchars($competition_name); ?></h2>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="/Digital_Judging_System/add_category.php?competition_id=<?php echo htmlspecialchars($competition_id); ?>" method="POST">
            <div class="form-group">
                <label for="category_name">Category Name:</label>
                <input type="text" id="category_name" name="category_name" value="<?php echo htmlspecialchars($category_name); ?>" required autofocus>
            </div>

            <div class="form-actions">
                <button type="button" class="cancel-btn" onclick="location.href='/Digital_Judging_System/competition_details.php?competition_id=<?php echo htmlspecialchars($competition_id); ?>'">Cancel</button>
                <button type="submit" class="submit-btn">Add Category</button>
            </div>
        </form>
    </div>

    <script src="/Digital_Judging_System/js/script.js"></script>
</body>
</html>