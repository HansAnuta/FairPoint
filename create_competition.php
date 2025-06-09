<?php
// create_competition.php

require_once 'db_connect.php';

$event_id = $_GET['event_id'] ?? null;
$event_name = '';
$competition_name = '';
$selected_judging_method_id = '';
$selected_competition_type = 'categories_based'; // Default value
$message = '';
$message_type = '';

// --- Fetch Event Name ---
if ($event_id) {
    try {
        $stmt = $pdo->prepare("SELECT event_name FROM Event WHERE event_id = ?");
        $stmt->execute([$event_id]);
        $event_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($event_data) {
            $event_name = $event_data['event_name'];
        } else {
            // Event not found, redirect
            header('Location: /Digital_Judging_System/admin_events.php?error=event_not_found');
            exit();
        }
    } catch (\PDOException $e) {
        $message = "Error fetching event details: " . $e->getMessage();
        $message_type = 'error';
        error_log("Error in create_competition.php (event fetch): " . $e->getMessage());
    }
} else {
    // No event_id provided, redirect
    header('Location: /Digital_Judging_System/admin_events.php?error=no_event_id');
    exit();
}

// --- Fetch Judging Methods for dropdown ---
$judging_methods = [];
try {
    $stmt = $pdo->query("SELECT judging_method_id, method_name FROM JudgingMethod ORDER BY method_name ASC");
    $judging_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $message = "Error fetching judging methods: " . $e->getMessage();
    $message_type = 'error';
    error_log("Error in create_competition.php (judging methods fetch): " . $e->getMessage());
}

// --- Handle Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $competition_name = trim($_POST['competition_name']);
    $selected_judging_method_id = $_POST['judging_method_id'] ?? null;
    $selected_competition_type = $_POST['competition_type'] ?? 'categories_based';

    if (empty($competition_name) || empty($selected_judging_method_id) || empty($selected_competition_type)) {
        $message = "All fields are required.";
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO Competition (event_id, judging_method_id, competition_name, competition_type, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$event_id, $selected_judging_method_id, $competition_name, $selected_competition_type]);
            $competition_id = $pdo->lastInsertId();

            $message = "Competition '" . htmlspecialchars($competition_name) . "' created successfully!";
            $message_type = 'success';

            // Redirect back to event_details.php with success message
            header('Location: /Digital_Judging_System/event_details.php?event_id=' . $event_id . '&status=success&message=' . urlencode($message));
            exit();

        } catch (\PDOException $e) {
            $message = "Error creating competition: " . $e->getMessage();
            $message_type = 'error';
            error_log("Error in create_competition.php: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Competition to <?php echo htmlspecialchars($event_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Digital_Judging_System/css/style.css">
    <style>
        /* Reusing form styles from create_event_step1.php, can be moved to style.css */
        .form-container {
            padding: 30px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
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
        .form-group input[type="text"],
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 1.1em;
        }
        .form-group input[type="text"]:focus,
        .form-group select:focus {
            border-color: #2ecc71;
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2);
        }
        .form-group.radio-group label {
            display: inline-block;
            margin-right: 20px;
            font-weight: normal; /* Override bold for radio labels */
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
        <h2>Add Competition to: <?php echo htmlspecialchars($event_name); ?></h2>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="/Digital_Judging_System/create_competition.php?event_id=<?php echo htmlspecialchars($event_id); ?>" method="POST">
            <div class="form-group">
                <label for="competition_name">Competition Name:</label>
                <input type="text" id="competition_name" name="competition_name" value="<?php echo htmlspecialchars($competition_name); ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="judging_method_id">Judging Method:</label>
                <select id="judging_method_id" name="judging_method_id" required>
                    <option value="">Select a method</option>
                    <?php foreach ($judging_methods as $method): ?>
                        <option value="<?php echo htmlspecialchars($method['judging_method_id']); ?>"
                            <?php echo ($selected_judging_method_id == $method['judging_method_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($method['method_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Competition Type:</label><br>
                <div class="radio-group">
                    <input type="radio" id="type_categories" name="competition_type" value="categories_based"
                        <?php echo ($selected_competition_type === 'categories_based') ? 'checked' : ''; ?> required>
                    <label for="type_categories">Requires Categories (e.g., Pageants, where participants compete in multiple categories)</label><br>

                    <input type="radio" id="type_direct_participants" name="competition_type" value="direct_participants"
                        <?php echo ($selected_competition_type === 'direct_participants') ? 'checked' : ''; ?> required>
                    <label for="type_direct_participants">Direct Participants (e.g., Sports tournaments, single category competitions)</label>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="cancel-btn" onclick="location.href='/Digital_Judging_System/event_details.php?event_id=<?php echo htmlspecialchars($event_id); ?>'">Cancel</button>
                <button type="submit" class="submit-btn">Create Competition</button>
            </div>
        </form>
    </div>

    <script src="/Digital_Judging_System/js/script.js"></script>
</body>
</html>