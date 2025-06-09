<?php
// Digital_Judging_System/assign_judges.php (UPDATED for unassign judge)

require_once 'db_connect.php';

$competition_id = $_GET['competition_id'] ?? null;
$category_id = $_GET['category_id'] ?? null;

$parent_entity_id = null;
$parent_entity_name = '';
$parent_entity_type = ''; // 'competition' or 'category'
$event_id = null; // Used for linking back

$available_judges = [];
$assigned_judges = [];
$message = '';
$message_type = '';

// Determine context: is it a competition or a category?
if ($category_id) {
    $parent_entity_type = 'category';
    $parent_entity_id = $category_id;
    try {
        $stmt = $pdo->prepare("SELECT Cat.category_name, Comp.competition_id, Comp.competition_name, Comp.event_id FROM Category AS Cat JOIN Competition AS Comp ON Cat.competition_id = Comp.competition_id WHERE Cat.category_id = ?");
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
        error_log("Error in assign_judges.php (category fetch): " . $e->getMessage());
    }
} elseif ($competition_id) {
    $parent_entity_type = 'competition';
    $parent_entity_id = $competition_id;
    try {
        $stmt = $pdo->prepare("SELECT competition_name, event_id FROM Competition WHERE competition_id = ?");
        $stmt->execute([$competition_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $parent_entity_name = $data['competition_name'];
            $event_id = $data['event_id'];
        } else {
            header('Location: /Digital_Judging_System/admin_events.php?error=competition_not_found');
            exit();
        }
    } catch (\PDOException $e) {
        $message = "Error fetching competition details: " . $e->getMessage();
        $message_type = 'error';
        error_log("Error in assign_judges.php (competition fetch): " . $e->getMessage());
    }
} else {
    header('Location: /Digital_Judging_System/admin_events.php?error=no_parent_id');
    exit();
}

// --- Handle Form Submission (Assign/Unassign Judge) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_query_string = ($parent_entity_type === 'category' ? 'category_id=' . htmlspecialchars($category_id) : 'competition_id=' . htmlspecialchars($competition_id));

    if (isset($_POST['assign_judge_id'])) {
        $assign_judge_id = $_POST['assign_judge_id'];

        // Re-fetch assigned judges to check for existing assignment
        $sql_check_assigned = "SELECT judge_id FROM Assignment WHERE competition_id = ?";
        $params_check_assigned = [$competition_id];
        if ($parent_entity_type === 'category') {
            $sql_check_assigned .= " AND category_id = ?";
            $params_check_assigned[] = $category_id;
        } else {
            $sql_check_assigned .= " AND category_id IS NULL";
        }
        $stmt = $pdo->prepare($sql_check_assigned);
        $stmt->execute($params_check_assigned);
        $existing_assignments = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        if (in_array($assign_judge_id, $existing_assignments)) {
            $message = "Judge is already assigned to this " . $parent_entity_type . ".";
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO Assignment (judge_id, competition_id, category_id, event_id, created_at) VALUES (?, ?, ?, ?, NOW())");
                if ($parent_entity_type === 'category') {
                    $stmt->execute([$assign_judge_id, $competition_id, $category_id, $event_id]);
                } else {
                    $stmt->execute([$assign_judge_id, $competition_id, NULL, $event_id]);
                }
                $message = "Judge assigned successfully!";
                $message_type = 'success';
                header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $current_query_string . '&status=success&message=' . urlencode($message));
                exit();
            } catch (\PDOException $e) {
                $message = "Error assigning judge: " . $e->getMessage();
                $message_type = 'error';
                error_log("Error in assign_judges.php (assign): " . $e->getMessage());
            }
        }
    } elseif (isset($_POST['unassign_judge_id'])) {
        $unassign_judge_id = $_POST['unassign_judge_id'];
        try {
            $sql_delete = "DELETE FROM Assignment WHERE judge_id = ? AND competition_id = ?";
            $params_delete = [$unassign_judge_id, $competition_id];
            if ($parent_entity_type === 'category') {
                $sql_delete .= " AND category_id = ?";
                $params_delete[] = $category_id;
            } else {
                $sql_delete .= " AND category_id IS NULL";
            }
            $stmt = $pdo->prepare($sql_delete);
            $stmt->execute($params_delete);
            $message = "Judge unassigned successfully!";
            $message_type = 'success';
            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $current_query_string . '&status=success&message=' . urlencode($message));
            exit();
        } catch (\PDOException $e) {
            $message = "Error unassigning judge: " . $e->getMessage();
            $message_type = 'error';
            error_log("Error in assign_judges.php (unassign): " . $e->getMessage());
        }
    }
}

// --- Fetch all available judges (after potential assignment) ---
try {
    $stmt = $pdo->query("SELECT judge_id, email FROM Judge WHERE is_active = TRUE ORDER BY email ASC");
    $available_judges = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $message = "Error fetching available judges: " . $e->getMessage();
    $message_type = 'error';
    error_log("Error in assign_judges.php (available judges fetch): " . $e->getMessage());
}

// --- Fetch already assigned judges (after potential assignment) ---
try {
    $sql_assigned = "SELECT J.judge_id, J.email FROM Assignment AS A JOIN Judge AS J ON A.judge_id = J.judge_id WHERE A.competition_id = ?";
    $params_assigned = [$competition_id];

    if ($parent_entity_type === 'category') {
        $sql_assigned .= " AND A.category_id = ?";
        $params_assigned[] = $category_id;
    } else {
        $sql_assigned .= " AND A.category_id IS NULL";
    }
    $sql_assigned .= " ORDER BY J.email ASC";

    $stmt = $pdo->prepare($sql_assigned);
    $stmt->execute($params_assigned);
    $assigned_judges = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $assigned_judge_ids = array_column($assigned_judges, 'judge_id'); // Re-create for display filtering

} catch (\PDOException $e) {
    $message = "Error fetching assigned judges: " . $e->getMessage();
    $message_type = 'error';
    error_log("Error in assign_judges.php (assigned judges fetch): " . $e->getMessage());
}


// Determine heading and back button URL
$page_heading = "Assign Judges for " . ucwords($parent_entity_type) . ": ";
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
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 1.1em;
        }
        .form-group select:focus {
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

            <form action="/Digital_Judging_System/assign_judges.php?<?php echo ($parent_entity_type === 'category' ? 'category_id=' . htmlspecialchars($category_id) : 'competition_id=' . htmlspecialchars($competition_id)); ?>" method="POST">
                <div class="form-group">
                    <label for="assign_judge_id">Select Judge to Assign:</label>
                    <select id="assign_judge_id" name="assign_judge_id" required>
                        <option value="">Select a Judge</option>
                        <?php foreach ($available_judges as $judge): ?>
                            <?php if (!in_array($judge['judge_id'], $assigned_judge_ids)): ?>
                                <option value="<?php echo htmlspecialchars($judge['judge_id']); ?>">
                                    <?php echo htmlspecialchars($judge['email']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="cancel-btn" onclick="location.href='<?php echo $back_url; ?>'">Cancel</button>
                    <button type="submit" class="submit-btn">Assign Judge</button>
                </div>
            </form>
        </div>

        <div class="section-box" style="margin-top: 30px;">
            <div class="section-header">
                <h3>Currently Assigned Judges</h3>
            </div>
            <?php if (count($assigned_judges) > 0): ?>
                <ul class="item-list">
                    <?php foreach ($assigned_judges as $judge): ?>
                        <li>
                            <span><?php echo htmlspecialchars($judge['email']); ?></span>
                            <div class="actions">
                                <form action="/Digital_Judging_System/assign_judges.php?<?php echo ($parent_entity_type === 'category' ? 'category_id=' . htmlspecialchars($category_id) : 'competition_id=' . htmlspecialchars($competition_id)); ?>" method="POST" style="display:inline;">
                                    <input type="hidden" name="unassign_judge_id" value="<?php echo htmlspecialchars($judge['judge_id']); ?>">
                                    <button type="submit" class="btn delete-btn" onclick="return confirm('Are you sure you want to unassign <?php echo htmlspecialchars($judge['email']); ?> from this <?php echo $parent_entity_type; ?>?');">Unassign</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">
                    <p>No judges currently assigned to this <?php echo $parent_entity_type; ?>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/Digital_Judging_System/js/script.js"></script>
</body>
</html>