<?php
// event_details.php (UPDATED for edit competition)

require_once 'db_connect.php';

$event_id = $_GET['event_id'] ?? null;
$event_name = '';
$competitions = [];
$message = '';
$message_type = '';

if (!$event_id) {
    header('Location: /Digital_Judging_System/admin_events.php');
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT event_id, event_name, created_at FROM Event WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        header('Location: /Digital_Judging_System/admin_events.php?error=event_not_found');
        exit();
    }
    $event_name = $event['event_name'];

    $stmt = $pdo->prepare("SELECT
                                C.competition_id,
                                C.competition_name,
                                C.competition_type,
                                JM.method_name
                            FROM
                                Competition AS C
                            JOIN
                                JudgingMethod AS JM ON C.judging_method_id = JM.judging_method_id
                            WHERE
                                C.event_id = ?
                            ORDER BY
                                C.created_at ASC");
    $stmt->execute([$event_id]);
    $competitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (\PDOException $e) {
    $message = "Error loading event details: " . $e->getMessage();
    $message_type = 'error';
    error_log("Error in event_details.php: " . $e->getMessage());
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
    <title>Manage Event: <?php echo htmlspecialchars($event_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Digital_Judging_System/css/style.css">
    <style>
        .event-header {
            background-color: #34495e;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .event-header h2 {
            margin: 0;
            font-size: 1.8em;
        }
        .event-header p {
            margin: 0;
            font-size: 0.9em;
            opacity: 0.8;
        }

        .competition-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .competition-item {
            background-color: #ecf0f1;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .competition-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .competition-details h3 {
            margin: 0 0 5px 0;
            color: #34495e;
            font-size: 1.2em;
        }
        .competition-details p {
            margin: 0;
            font-size: 0.9em;
            color: #7f8c8d;
        }
        .competition-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .competition-actions .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
            transition: background-color 0.3s ease;
        }
        .add-competition-btn {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .add-competition-btn:hover {
            background-color: #27ae60;
        }
        .back-to-events-btn {
            background-color: #95a5a6;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
            margin-bottom: 20px;
        }
        .back-to-events-btn:hover {
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

    <div class="container">
        <button class="back-to-events-btn" onclick="location.href='/Digital_Judging_System/admin_events.php'">← Back to All Events</button>

        <div class="event-header">
            <div>
                <h2>Event: <?php echo htmlspecialchars($event_name); ?></h2>
                <p>Created on: <?php echo date('Y-m-d', strtotime($event['created_at'])); ?></p>
            </div>
            <button class="add-competition-btn" onclick="location.href='/Digital_Judging_System/create_competition.php?event_id=<?php echo htmlspecialchars($event_id); ?>'">+ Add New Competition</button>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <h3>Competitions in This Event:</h3>
        <ul class="competition-list">
            <?php if (count($competitions) > 0): ?>
                <?php foreach ($competitions as $comp): ?>
                    <li class="competition-item">
                        <div class="competition-details">
                            <h3><?php echo htmlspecialchars($comp['competition_name']); ?></h3>
                            <p>Judging Method: <?php echo htmlspecialchars($comp['method_name']); ?></p>
                            <p>Type: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $comp['competition_type']))); ?></p>
                        </div>
                        <div class="competition-actions">
                            <button class="btn view-btn" onclick="location.href='/Digital_Judging_System/competition_details.php?competition_id=<?php echo htmlspecialchars($comp['competition_id']); ?>'">Manage</button>
                            <button class="btn edit-btn" onclick="editCompetition('<?php echo htmlspecialchars($comp['competition_id']); ?>')">Edit</button>
                            <button class="btn delete-btn" onclick="if(confirm('Are you sure you want to delete this competition and ALL its associated data (categories, participants, judges assignments)? This action cannot be undone.')) { location.href='/Digital_Judging_System/delete_competition.php?competition_id=' + <?php echo htmlspecialchars($comp['competition_id']); ?> + '&event_id=' + <?php echo htmlspecialchars($event_id); ?>; }">Delete</button>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No competitions added to this event yet. Click "Add New Competition" to create one!</p>
                </div>
            <?php endif; ?>
        </ul>
    </div>

    <script src="/Digital_Judging_System/js/script.js"></script>
    <script>
        // Implemented editCompetition function
        function editCompetition(competitionId) {
            window.location.href = '/Digital_Judging_System/edit_competition.php?competition_id=' + competitionId;
        }
    </script>
</body>
</html>