<?php
// Digital_Judging_System/delete_competition.php

require_once 'db_connect.php';

$competition_id = $_GET['competition_id'] ?? null;
$event_id = $_GET['event_id'] ?? null; // To redirect back to the event

$message = '';
$message_type = '';

if (!$competition_id) {
    header('Location: /Digital_Judging_System/event_details.php?event_id=' . ($event_id ?? '') . '&status=error&message=' . urlencode('No competition ID provided for deletion.'));
    exit();
}

try {
    $pdo->beginTransaction();

    // Fetch event_id if not provided, needed for redirection
    if (!$event_id) {
        $stmt = $pdo->prepare("SELECT event_id FROM Competition WHERE competition_id = ?");
        $stmt->execute([$competition_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $event_id = $result['event_id'];
        } else {
            // Competition not found, cannot determine event_id for redirection
            header('Location: /Digital_Judging_System/admin_events.php?status=error&message=' . urlencode('Competition not found.'));
            exit();
        }
    }

    // 1. Delete from 'Assignment' for this competition (and its categories)
    $stmt = $pdo->prepare("DELETE FROM Assignment WHERE competition_id = ?");
    $stmt->execute([$competition_id]);

    // 2. Delete from 'Participant' for this competition (including those in categories)
    $stmt = $pdo->prepare("DELETE FROM Participant WHERE competition_id = ?");
    $stmt->execute([$competition_id]);

    // 3. Delete from 'Category' for this competition
    $stmt = $pdo->prepare("DELETE FROM Category WHERE competition_id = ?");
    $stmt->execute([$competition_id]);

    // 4. Finally, delete the 'Competition' itself
    $stmt = $pdo->prepare("DELETE FROM Competition WHERE competition_id = ?");
    $stmt->execute([$competition_id]);

    $pdo->commit();
    $message = "Competition and all associated data deleted successfully!";
    $message_type = 'success';

} catch (\PDOException $e) {
    $pdo->rollBack();
    $message = "Error deleting competition: " . $e->getMessage();
    $message_type = 'error';
    error_log("Error in delete_competition.php: " . $e->getMessage());
}

header('Location: /Digital_Judging_System/event_details.php?event_id=' . htmlspecialchars($event_id) . '&status=' . $message_type . '&message=' . urlencode($message));
exit();
?>