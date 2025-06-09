<?php
// Digital_Judging_System/delete_event.php

require_once 'db_connect.php';

$event_id = $_GET['event_id'] ?? null;
$message = '';
$message_type = '';

if (!$event_id) {
    header('Location: /Digital_Judging_System/admin_events.php?status=error&message=' . urlencode('No event ID provided for deletion.'));
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Delete records from 'Score' (if it exists and links to Participant)
    //    Assumes Score table links to Participant, Category, or Competition.
    //    This step might vary based on your actual Score table schema.
    //    If you have a Score table that references Participant_ID, you might need to delete scores first.
    //    For now, we'll assume Assignment and Participant deletions handle necessary cascade or are sufficient.
    //    If you have a 'Score' table, ensure it's handled here or has CASCADE DELETE.

    // 2. Delete from 'Assignment' for this event
    $stmt = $pdo->prepare("DELETE FROM Assignment WHERE event_id = ?");
    $stmt->execute([$event_id]);

    // 3. Delete from 'Participant' for competitions within this event
    //    This assumes Participant can be directly linked to Competition.
    //    If Participant also links to Category, the Category deletion might handle it.
    //    To be safe, we'll explicitly delete participants linked to competitions of this event.
    $stmt = $pdo->prepare("DELETE P FROM Participant P JOIN Competition C ON P.competition_id = C.competition_id WHERE C.event_id = ?");
    $stmt->execute([$event_id]);

    // 4. Delete from 'Category' for competitions within this event
    $stmt = $pdo->prepare("DELETE Cat FROM Category Cat JOIN Competition Comp ON Cat.competition_id = Comp.competition_id WHERE Comp.event_id = ?");
    $stmt->execute([$event_id]);

    // 5. Delete from 'Competition' for this event
    $stmt = $pdo->prepare("DELETE FROM Competition WHERE event_id = ?");
    $stmt->execute([$event_id]);

    // 6. Finally, delete the 'Event' itself
    $stmt = $pdo->prepare("DELETE FROM Event WHERE event_id = ?");
    $stmt->execute([$event_id]);

    $pdo->commit();
    $message = "Event and all associated data deleted successfully!";
    $message_type = 'success';

} catch (\PDOException $e) {
    $pdo->rollBack();
    $message = "Error deleting event: " . $e->getMessage();
    $message_type = 'error';
    error_log("Error in delete_event.php: " . $e->getMessage());
}

header('Location: /Digital_Judging_System/admin_events.php?status=' . $message_type . '&message=' . urlencode($message));
exit();
?>