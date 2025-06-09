<?php
// Digital_Judging_System/manage_participants.php (UPDATED for edit participant)

require_once 'db_connect.php';

$competition_id = $_GET['competition_id'] ?? null;
$category_id = $_GET['category_id'] ?? null;

$parent_entity_id = null;
$parent_entity_name = '';
$parent_entity_type = '';
$event_id = null;

$participants = [];
$participant_name = '';
$message = '';
$message_type = '';

if ($category_id) {
    $parent_entity_type = 'category';
    $parent_entity_id = $category_id;
    try {
        $stmt = $pdo->prepare("SELECT C.category_name, C.competition_id, Comp.competition_name, Comp.event_id FROM Category AS C JOIN Competition AS Comp ON C.competition_id = Comp.competition_id WHERE C.category_id = ?");
        $stmt->execute([$category_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $parent_entity_name = $data['category_name'];
            $competition_id = $data['competition_id'];
            $event_id = $data['event_id'];
        } else {
            header('Location: /Digital_Judging_System/admin_events.php?error=category_not_found');
            exit();
        }
    } catch (\PDOException $e) {
        $message = "Error fetching category details: " . $e->getMessage();
        $message_type = 'error';
        error_log("Error in manage_participants.php (category fetch): " . $e->getMessage());
    }
} elseif ($competition_id) {
    $parent_entity_type = 'competition';
    $parent_entity_id = $competition_id;
    try {
        $stmt = $pdo->prepare("SELECT competition_name, event_id, competition_type FROM Competition WHERE competition_id = ?");
        $stmt->execute([$competition_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $parent_entity_name = $data['competition_name'];
            $event_id = $data['event_id'];
            if ($data['competition_type'] === 'categories_based') {
                header('Location: /Digital_Judging_System/competition_details.php?competition_id=' . $competition_id . '&status=error&message=' . urlencode('This competition requires participants to be added to categories.'));
                exit();
            }
        } else {
            header('Location: /Digital_Judging_System/admin_events.php?error=competition_not_found');
            exit();
        }
    } catch (\PDOException $e) {
        $message = "Error fetching competition details: " . $e->getMessage();
        $message_type = 'error';
        error_log("Error in manage_participants.php (competition fetch): " . $e->getMessage());
    }
} else {
    header('Location: /Digital_Judging_System/admin_events.php?error=no_parent_id');
    exit();
}

if ($parent_entity_type === 'category') {
    $stmt = $pdo->prepare("SELECT participant_id, participant_name FROM Participant WHERE category_id = ? ORDER BY participant_name ASC");
    $stmt->execute([$category_id]);
} else {
    $stmt = $pdo->prepare("SELECT participant_id, participant_name FROM Participant WHERE competition_id = ? AND category_id IS NULL ORDER BY participant_name ASC");
    $stmt->execute([$competition_id]);
}
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $participant_name = trim($_POST['participant_name']);

    if (empty($participant_name)) {
        $message = "Participant Name is required.";
        $message_type = 'error';
    } else {
        try {
            if ($parent_entity_type === 'category') {
                $stmt = $pdo->prepare("INSERT INTO Participant (competition_id, category_id, participant_name, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$competition_id, $category_id, $participant_name]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO Participant (competition_id, category_id, participant_name, created_at) VALUES (?, NULL, ?, NOW())");
                $stmt->execute([$competition_id, $participant_name]);
            }

            $message = "Participant '" . htmlspecialchars($participant_name) . "' added successfully!";
            $message_type = 'success';
            $participant_name = '';

            // Redirect back to the details page (refresh)
            $redirect_query_params = ($parent_entity_type === 'category' ? 'category_id=' . htmlspecialchars($category_id) : 'competition_id=' . htmlspecialchars($competition_id));
            header('Location: /Digital_Judging_System/manage_participants.php?' . $redirect_query_params . '&status=success&message=' . urlencode($message));
            exit();

        } catch (\PDOException $e) {
            $message = "Error adding participant: " . $e->getMessage();
            $message_type = 'error';
            error_log("Error in manage_participants.php: " . $e->getMessage());
        }
    }
}

$page_heading = ($parent_entity_type === 'category') ? "Manage Participants for Category: " : "Manage Participants for Competition: ";
$back_url = '/Digital_Judging_System/competition_details.php?competition_id=' . htmlspecialchars($competition_id);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_heading . htmlspecialchars($parent_entity_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Digital_Judging_System/css/style.css">
    <style>
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

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        .section-header h3 {
            margin: 0;
            color: #34495e;
            font-size: 1.5em;
        }

        .item-list {
            list-style: none;
            padding: 0;
            margin: 0 0 30px 0;
        }
        .item-list li {
            background-color: #ecf0f1;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .item-list li span {
            font-size: 1.1em;
            color: #34495e;
            font-weight: bold;
            flex-grow: 1;
        }
        .item-list li .actions {
            display: flex;
            gap: 8px;
        }
        .item-list li .actions .btn {
            padding: 5px 10px;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>Digital Judging System - Admin</h1>
        <button class="logout-btn" onclick="handleLogout()">Logout</button>
    </header>

    <div class="container">
        <button class="back-to-events-btn" onclick="location.href='<?php echo $back_url; ?>'">← Back to Competition</button>

        <div class="form-container">
            <h2><?php echo $page_heading . htmlspecialchars($parent_entity_name); ?></h2>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form action="/Digital_Judging_System/manage_participants.php?<?php echo ($parent_entity_type === 'category' ? 'category_id=' . htmlspecialchars($category_id) : 'competition_id=' . htmlspecialchars($competition_id)); ?>" method="POST">
                <div class="form-group">
                    <label for="participant_name">Participant Name:</label>
                    <input type="text" id="participant_name" name="participant_name" value="<?php echo htmlspecialchars($participant_name); ?>" required autofocus>
                </div>

                <div class="form-actions">
                    <button type="button" class="cancel-btn" onclick="location.href='<?php echo $back_url; ?>'">Cancel</button>
                    <button type="submit" class="submit-btn">Add Participant</button>
                </div>
            </form>
        </div>

        <div class="section-box" style="margin-top: 30px;">
            <div class="section-header">
                <h3>Existing Participants</h3>
            </div>
            <?php if (count($participants) > 0): ?>
                <ul class="item-list">
                    <?php foreach ($participants as $participant): ?>
                        <li>
                            <span><?php echo htmlspecialchars($participant['participant_name']); ?></span>
                            <div class="actions">
                                <button class="btn edit-btn" onclick="editParticipant('<?php echo htmlspecialchars($participant['participant_id']); ?>')">Edit</button>
                                <button class="btn delete-btn" onclick="if(confirm('Are you sure you want to delete this participant? This action cannot be undone.')) { location.href='/Digital_Judging_System/delete_participant.php?participant_id=' + <?php echo htmlspecialchars($participant['participant_id']); ?> + '&competition_id=' + <?php echo htmlspecialchars($competition_id); ?>; }">Delete</button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">
                    <p>No participants added yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/Digital_Judging_System/js/script.js"></script>
    <script>
        // Implemented editParticipant function
        function editParticipant(participantId) {
            window.location.href = '/Digital_Judging_System/edit_participant.php?participant_id=' + participantId;
        }
    </script>
</body>
</html>