<?php
// Digital_Judging_System/competition_details.php (UPDATED for delete category/participant)

require_once 'db_connect.php';

$competition_id = $_GET['competition_id'] ?? null;
$competition = null;
$categories = [];
$participants = [];
$judges_assigned = [];

$message = '';
$message_type = '';

if (!$competition_id) {
    header('Location: /Digital_Judging_System/admin_events.php');
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT C.competition_id, C.competition_name, C.competition_type, JM.method_name, E.event_name, E.event_id
                           FROM Competition AS C
                           JOIN JudgingMethod AS JM ON C.judging_method_id = JM.judging_method_id
                           JOIN Event AS E ON C.event_id = E.event_id
                           WHERE C.competition_id = ?");
    $stmt->execute([$competition_id]);
    $competition = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$competition) {
        header('Location: /Digital_Judging_System/admin_events.php?error=competition_not_found');
        exit();
    }

    if ($competition['competition_type'] === 'categories_based') {
        $stmt = $pdo->prepare("SELECT category_id, category_name FROM Category WHERE competition_id = ? ORDER BY category_name ASC");
        $stmt->execute([$competition_id]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT participant_id, participant_name FROM Participant WHERE competition_id = ? AND category_id IS NULL ORDER BY participant_name ASC");
        $stmt->execute([$competition_id]);
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmt = $pdo->prepare("SELECT DISTINCT J.email
                           FROM Assignment AS A
                           JOIN Judge AS J ON A.judge_id = J.judge_id
                           WHERE A.competition_id = ?");
    $stmt->execute([$competition_id]);
    $judges_assigned = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (\PDOException $e) {
    $message = "Error loading competition details: " . $e->getMessage();
    $message_type = 'error';
    error_log("Error in competition_details.php: " . $e->getMessage());
}

if (isset($_GET['status']) && isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['status']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Competition: <?php echo htmlspecialchars($competition['competition_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Digital_Judging_System/css/style.css">
    <style>
        .comp-header {
            background-color: #34495e;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .comp-header h2 {
            margin: 0;
            font-size: 1.8em;
        }
        .comp-header p {
            margin: 0;
            font-size: 0.9em;
            opacity: 0.8;
        }
        .comp-header .back-btn {
            background-color: #95a5a6;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
        }
        .comp-header .back-btn:hover {
            background-color: #7f8c8d;
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
        .section-header .add-btn {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .section-header .add-btn:hover {
            background-color: #27ae60;
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
        .section-box {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>Digital Judging System - Admin</h1>
        <button class="logout-btn" onclick="handleLogout()">Logout</button>
    </header>

    <div class="container">
        <div class="comp-header">
            <div>
                <button class="back-btn" onclick="location.href='/Digital_Judging_System/event_details.php?event_id=<?php echo htmlspecialchars($competition['event_id']); ?>'">← Back to Event</button>
                <h2>Competition: <?php echo htmlspecialchars($competition['competition_name']); ?></h2>
                <p>Event: <?php echo htmlspecialchars($competition['event_name']); ?> | Judging Method: <?php echo htmlspecialchars($competition['method_name']); ?> | Type: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $competition['competition_type']))); ?></p>
            </div>
            <div>
                <button class="btn edit-btn">Edit Comp</button>
                <button class="btn delete-btn">Delete Comp</button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="section-box">
            <div class="section-header">
                <h3>Assigned Judges</h3>
                <button class="add-btn" onclick="location.href='/Digital_Judging_System/assign_judges.php?competition_id=<?php echo htmlspecialchars($competition_id); ?>'">Assign Judges</button>
            </div>
            <?php if (count($judges_assigned) > 0): ?>
                <ul class="item-list">
                    <?php foreach ($judges_assigned as $judge): ?>
                        <li>
                            <span><?php echo htmlspecialchars($judge['email']); ?></span>
                            <div class="actions">
                                <button class="btn delete-btn">Unassign</button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">
                    <p>No judges assigned to this competition yet.</p>
                </div>
            <?php endif; ?>
        </div>


        <?php if ($competition['competition_type'] === 'categories_based'): ?>
            <div class="section-box">
                <div class="section-header">
                    <h3>Categories</h3>
                    <button class="add-btn" onclick="location.href='/Digital_Judging_System/add_category.php?competition_id=<?php echo htmlspecialchars($competition_id); ?>'">+ Add Category</button>
                </div>
                <?php if (count($categories) > 0): ?>
                    <ul class="item-list">
                        <?php foreach ($categories as $category): ?>
                            <li>
                                <span><?php echo htmlspecialchars($category['category_name']); ?></span>
                                <div class="actions">
                                    <button class="btn view-btn" onclick="location.href='/Digital_Judging_System/manage_participants.php?category_id=<?php echo htmlspecialchars($category['category_id']); ?>'">Participants</button>
                                    <button class="btn view-btn" onclick="location.href='/Digital_Judging_System/assign_judges.php?competition_id=<?php echo htmlspecialchars($competition_id); ?>&category_id=<?php echo htmlspecialchars($category['category_id']); ?>'">Judges</button>
                                    <button class="btn edit-btn">Edit</button>
                                    <button class="btn delete-btn" onclick="if(confirm('Are you sure you want to delete this category and ALL its associated participants and judges assignments? This action cannot be undone.')) { location.href='/Digital_Judging_System/delete_category.php?category_id=' + <?php echo htmlspecialchars($category['category_id']); ?> + '&competition_id=' + <?php echo htmlspecialchars($competition_id); ?>; }">Delete</button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No categories added to this competition yet. Add categories and then participants within them.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: // direct_participants ?>
            <div class="section-box">
                <div class="section-header">
                    <h3>Participants</h3>
                    <button class="add-btn" onclick="location.href='/Digital_Judging_System/manage_participants.php?competition_id=<?php echo htmlspecialchars($competition_id); ?>'">+ Add Participant</button>
                </div>
                <?php if (count($participants) > 0): ?>
                    <ul class="item-list">
                        <?php foreach ($participants as $participant): ?>
                            <li>
                                <span><?php echo htmlspecialchars($participant['participant_name']); ?></span>
                                <div class="actions">
                                    <button class="btn edit-btn">Edit</button>
                                    <button class="btn delete-btn">Delete</button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No participants added to this competition yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

    <script src="/Digital_Judging_System/js/script.js"></script>
</body>
</html>