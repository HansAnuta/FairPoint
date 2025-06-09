<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Events</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Digital_Judging_System/css/style.css">
</head>
<body>
    <header class="header">
        <h1>Digital Judging System - Admin</h1>
        <button class="logout-btn" onclick="handleLogout()">Logout</button>
    </header>

    <div class="container">
        <h2>Manage Events</h2>

        <div class="action-bar">
            <button class="create-event-btn" onclick="location.href='/Digital_Judging_System/create_event_step1.php'">+ Create New Event</button>
        </div>

        <ul class="event-list" id="eventList">
            <?php
            require_once 'db_connect.php';

            // Assume admin_id = 1 for demonstration. Replace with session management in a real app.
            $admin_id_for_demo = 1;

            try {
                // Fetch events managed by this admin
                // Note: The query for fetching events now does NOT join JudgingMethod directly,
                // as judging method is on Competition level. If you need to display
                // judging methods for *all* competitions within an event here, it would be more complex.
                $stmt = $pdo->prepare("SELECT
                                            E.event_id,
                                            E.event_name,
                                            E.created_at
                                        FROM
                                            Event AS E
                                        WHERE
                                            E.admin_id = ?
                                        ORDER BY
                                            E.created_at DESC");
                $stmt->execute([$admin_id_for_demo]);
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($events) > 0) {
                    foreach ($events as $event) {
                        echo '<li class="event-item">';
                        echo '<div class="event-details">';
                        echo '<h3>' . htmlspecialchars($event['event_name']) . '</h3>';
                        // Removed judging method display here as it's now per competition
                        echo ' <p>Created: ' . date('Y-m-d', strtotime($event['created_at'])) . '</p>';
                        echo '</div>';
                        echo '<div class="event-actions">';
                        // Updated View button to go to event_details.php
                        echo '<button class="btn view-btn" onclick="location.href=\'/Digital_Judging_System/event_details.php?event_id=' . htmlspecialchars($event['event_id']) . '\'">View/Manage</button>';
                        echo '<button class="btn edit-btn" onclick="editEvent(\'' . htmlspecialchars($event['event_id']) . '\')">Edit Event</button>';
                        echo '<button class="btn delete-btn" onclick="deleteEvent(\'' . htmlspecialchars($event['event_id']) . '\')">Delete Event</button>';
                        echo '</div>';
                        echo '</li>';
                    }
                } else {
                    echo '<div class="empty-state">';
                    echo '<p>No events found. Click "Create New Event" to get started!</p>';
                    echo '</div>';
                }
            } catch (\PDOException $e) {
                echo '<div class="empty-state" style="color: red;">';
                echo '<p>Error loading events. Please try again later.</p>';
                error_log("Error fetching events in admin_events.php: " . $e->getMessage());
                echo '</div>';
            }
            ?>
        </ul>
    </div>

    <script src="/Digital_Judging_System/js/script.js"></script>
</body>
</html>