<?php
/**
 * Manage Event Page
 * Allows admins to configure competitions and judging methods for a specific event.
 */
session_start();

require_once '../includes/db_connection.php'; // Adjust path

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$event_id = $_GET['event_id'] ?? null;
$event_name = '';
$competitions = []; // To store existing competitions for this event
$judging_methods = []; // To store available judging methods

// Check for and display messages from redirects
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}


// Redirect if no event_id is provided
if ($event_id === null) {
    header("Location: dashboard.php?tab=events&message=" . urlencode("No event selected to manage."));
    exit();
}

// Fetch event details
$stmt_event = $conn->prepare("SELECT event_name FROM events WHERE event_id = ?");
if ($stmt_event) {
    $stmt_event->bind_param("i", $event_id);
    $stmt_event->execute();
    $stmt_event->bind_result($event_name);
    $stmt_event->fetch();
    $stmt_event->close();
    if ($event_name === null) { // If event_id doesn't exist
        header("Location: dashboard.php?tab=events&message=" . urlencode("Event not found."));
        exit();
    }
} else {
    $message = "Error preparing statement to fetch event details: " . $conn->error;
}


// Fetch existing competitions for this event, including judging method name
$stmt_competitions = $conn->prepare("
    SELECT c.competition_id, c.competition_name, c.judging_method_id, jm.method_name
    FROM competitions c
    JOIN judging_methods jm ON c.judging_method_id = jm.judging_method_id
    WHERE c.event_id = ?
    ORDER BY c.created_at DESC
");
if ($stmt_competitions) {
    $stmt_competitions->bind_param("i", $event_id);
    $stmt_competitions->execute();
    $result_competitions = $stmt_competitions->get_result();
    while ($row = $result_competitions->fetch_assoc()) {
        $competitions[] = $row;
    }
    $stmt_competitions->close();
} else {
    error_log("Failed to prepare statement for fetching competitions: " . $conn->error);
}


// Fetch all judging methods for the dropdown
$stmt_methods = $conn->prepare("SELECT judging_method_id, method_name FROM judging_methods ORDER BY method_name ASC");
if ($stmt_methods) {
    $stmt_methods->execute();
    $result_methods = $stmt_methods->get_result();
    while ($row = $result_methods->fetch_assoc()) {
        $judging_methods[] = $row;
    }
    $stmt_methods->close();
} else {
    error_log("Failed to prepare statement for fetching judging methods: " . $conn->error);
}

// Handle form submission for adding new competition
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $competition_name = trim($_POST['competition_name'] ?? '');
    $judging_method_id = $_POST['judging_method'] ?? '';

    if (empty($competition_name) || empty($judging_method_id)) {
        $message = "Please fill in all fields.";
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO competitions (event_id, judging_method_id, competition_name, created_at) VALUES (?, ?, ?, NOW())");
        if ($stmt_insert) {
            $stmt_insert->bind_param("iis", $event_id, $judging_method_id, $competition_name);
            if ($stmt_insert->execute()) {
                // Redirect back to the same page with a success message to refresh the list
                header("Location: manage_event.php?event_id=" . $event_id . "&message=" . urlencode("Competition '" . htmlspecialchars($competition_name) . "' added successfully!"));
                exit();
            } else {
                $message = "Error adding competition: " . $stmt_insert->error;
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
    <title>Manage Event: <?php echo htmlspecialchars($event_name); ?></title>
    <!-- Your CSS link will go here -->
    <!-- <link rel="stylesheet" href="../assets/css/style.css"> -->
    <style>
        /* Basic styles for responsive structure and no design */
        .page-wrapper {
            max-width: 960px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #eee;
        }
        .main-header, .main-content, .main-footer {
            padding: 10px 0;
        }
        .form-container, .competitions-list {
            border: 1px solid #ccc;
            padding: 15px;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 10px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
        }
        .form-input, .form-select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        .button {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border: none;
            cursor: pointer;
            display: inline-block;
            margin-top: 10px;
        }
        .button-primary { background-color: #007bff; }
        .button-secondary { background-color: #6c757d; }
        .button-edit { background-color: #ffc107; color: black;}
        .button-delete { background-color: #dc3545; }
        .competition-card {
            border: 1px solid #ddd;
            margin-bottom: 10px;
            padding: 10px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        .competition-card > div {
            flex: 1;
            padding: 5px;
        }
        .competition-card .card-buttons {
            flex-basis: 100%; /* Take full width on small screens */
            text-align: right; /* Align buttons to the right */
            margin-top: 10px;
        }
        @media (min-width: 768px) {
            .competition-card > div {
                flex: none;
            }
            .competition-card .card-buttons {
                flex-basis: auto; /* Revert to auto width on larger screens */
                margin-top: 0;
            }
        }
        .message {
            padding: 10px;
            border: 1px solid;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <header class="main-header">
            <h1 class="site-title">Manage Event: <?php echo htmlspecialchars($event_name); ?></h1>
        </header>

        <main class="main-content">
            <section class="add-competition-section">
                <div class="form-container">
                    <h2>Add New Competition</h2>
                    <?php if (!empty($message)): ?>
                        <p class="message"><?php echo htmlspecialchars($message); ?></p>
                    <?php endif; ?>
                    <form action="manage_event.php?event_id=<?php echo $event_id; ?>" method="POST" class="add-competition-form">
                        <div class="form-group">
                            <label for="competition_name" class="form-label">Competition Name:</label>
                            <input type="text" id="competition_name" name="competition_name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="judging_method" class="form-label">Judging Method:</label>
                            <select id="judging_method" name="judging_method" class="form-select" required>
                                <option value="">Select Method</option>
                                <?php foreach ($judging_methods as $method): ?>
                                    <option value="<?php echo htmlspecialchars($method['judging_method_id']); ?>">
                                        <?php echo htmlspecialchars($method['method_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="button button-primary">Add Competition</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="existing-competitions-section">
                <h2>Existing Competitions</h2>
                <div class="competitions-list">
                    <?php if (empty($competitions)): ?>
                        <p>No competitions added for this event yet.</p>
                    <?php else: ?>
                        <?php foreach ($competitions as $competition): ?>
                            <div class="competition-card">
                                <div class="competition-details">
                                    <h3 class="competition-name"><?php echo htmlspecialchars($competition['competition_name']); ?></h3>
                                    <p class="competition-method">Method: <?php echo htmlspecialchars($competition['method_name']); ?></p>
                                </div>
                                <div class="card-buttons">
                                    <a href="manage_competition.php?competition_id=<?php echo $competition['competition_id']; ?>" class="button button-secondary">Manage</a>
                                    <!-- Placeholder buttons for edit/delete competition -->
                                    <a href="edit_competition.php?competition_id=<?php echo $competition['competition_id']; ?>" class="button button-edit">Edit</a>
                                    <a href="delete_competition.php?competition_id=<?php echo $competition['competition_id']; ?>" class="button button-delete">Delete</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <div class="form-footer-links">
                <p><a href="dashboard.php?tab=events">Back to Events Dashboard</a></p>
            </div>
        </main>

        <footer class="main-footer">
            <p class="footer-text">&copy; 2025 Digital Judging System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
