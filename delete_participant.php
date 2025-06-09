<?php
// Digital_Judging_System/delete_participant.php

require_once 'db_connect.php';

$participant_id = $_GET['participant_id'] ?? null;
// Need competition_id to redirect back to the correct competition details page
$competition_id = $_GET['competition_id'] ?? null;

$message = '';
$message_type = '';

if (!$participant_id) {
    header('Location: /Digital_Judging_System/admin_events.php?status=error&message=' . urlencode('No participant ID provided for deletion.'));
    exit();
}

try {
    $pdo->beginTransaction();

    // Fetch competition_id if not provided, needed for redirection
    if (!$competition_id) {
        $stmt = $pdo->prepare("SELECT competition_id FROM Participant WHERE participant_id = ?");
        $stmt->execute([$participant_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $competition_id = $result['competition_id'];
        } else {
            header('Location: /Digital_Judging_System/admin_events.php?status=error&message=' . urlencode('Participant not found.'));
            exit();
        }
    }

    // 1. Delete from 'Score' (if it exists and links to Participant_ID)
    //    Ensure your Score table has ON DELETE CASCADE on participant_id
    //    or explicitly delete scores here:
    //    $stmt = $pdo->prepare("DELETE FROM Score WHERE participant_id = ?");
    //    $stmt->execute([$participant_id]);

    // 2. Delete the 'Participant' itself
    $stmt = $pdo->prepare("DELETE FROM Participant WHERE participant_id = ?");
    $stmt->execute([$participant_id]);

    $pdo->commit();
    $message = "Participant deleted successfully!";
    $message_type = 'success';

} catch (\PDOException $e) {
    $pdo->rollBack();
    $message = "Error deleting participant: " . $e->getMessage();
    $message_type = 'error';
    error_log("Error in delete_participant.php: " . $e->getMessage());
}

header('Location: /Digital_Judging_System/competition_details.php?competition_id=' . htmlspecialchars($competition_id) . '&status=' . $message_type . '&message=' . urlencode($message));
exit();
?>