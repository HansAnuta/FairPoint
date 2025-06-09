<?php
// create_event_step1.php

require_once 'db_connect.php';

$event_name = '';
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_name = trim($_POST['event_name']);

    if (empty($event_name)) {
        $message = "Event Name is required.";
        $message_type = 'error';
    } else {
        // Assume admin_id = 1 for demonstration. Get this from session in a real app.
        $admin_id = 1;

        try {
            $stmt = $pdo->prepare("INSERT INTO Event (admin_id, event_name, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$admin_id, $event_name]);
            $event_id = $pdo->lastInsertId();

            $message = "Event '" . htmlspecialchars($event_name) . "' created successfully!";
            $message_type = 'success';

            // Redirect to the event details page to add competitions
            header('Location: /Digital_Judging_System/event_details.php?event_id=' . $event_id);
            exit();

        } catch (\PDOException $e) {
            $message = "Error creating event: " . $e->getMessage();
            $message_type = 'error';
            error_log("Error in create_event_step1.php: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Event</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Digital_Judging_System/css/style.css">
    <style>
        /* Specific styling for this simple form */
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
        <h2>Create New Event</h2>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="/Digital_Judging_System/create_event_step1.php" method="POST">
            <div class="form-group">
                <label for="event_name">Event Name:</label>
                <input type="text" id="event_name" name="event_name" value="<?php echo htmlspecialchars($event_name); ?>" required autofocus>
            </div>

            <div class="form-actions">
                <button type="button" class="cancel-btn" onclick="location.href='/Digital_Judging_System/admin_events.php'">Cancel</button>
                <button type="submit" class="submit-btn">Create Event</button>
            </div>
        </form>
    </div>

    <script src="/Digital_Judging_System/js/script.js"></script>
</body>
</html>