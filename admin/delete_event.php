<?php
/**
 * Delete Event Script
 * Handles the deletion of an event and validates if it can be deleted.
 */
session_start();

require_once '../includes/db_connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$event_id = $_GET['event_id'] ?? null;
$message = '';

if ($event_id === null) {
    header("Location: dashboard.php?tab=events&message=" . urlencode("No event specified for deletion."));
    exit();
}

// Start a transaction for atomicity
$conn->begin_transaction();

try {
    // Check for associated competitions first
    $stmt_check_comp = $conn->prepare("SELECT COUNT(*) FROM competitions WHERE event_id = ?");
    if (!$stmt_check_comp) {
        throw new Exception("Prepare statement failed for checking competitions: " . $conn->error);
    }
    $stmt_check_comp->bind_param("i", $event_id);
    $stmt_check_comp->execute();
    $stmt_check_comp->bind_result($comp_count);
    $stmt_check_comp->fetch();
    $stmt_check_comp->close();

    if ($comp_count > 0) {
        throw new Exception("Cannot delete event: It has " . $comp_count . " associated competition(s). Please delete all competitions within this event first.");
    }

    // You would add similar checks for other dependent tables like 'assignments', 'judge_links'
    // if you don't have CASCADE DELETE set up on your foreign keys.
    // For simplicity, for now, if competitions are gone, we assume other dependencies are minor
    // or are also cascade-deleted if your DB schema supports it.
    // However, it's generally best practice to handle all foreign key dependencies explicitly.

    // If no competitions, proceed with deleting the event
    $stmt_delete = $conn->prepare("DELETE FROM events WHERE event_id = ?");
    if (!$stmt_delete) {
        throw new Exception("Prepare statement failed for deleting event: " . $conn->error);
    }
    $stmt_delete->bind_param("i", $event_id);
    if ($stmt_delete->execute()) {
        $conn->commit();
        $message = "Event deleted successfully!";
        header("Location: dashboard.php?tab=events&message=" . urlencode($message));
        exit();
    } else {
        throw new Exception("Error deleting event: " . $stmt_delete->error);
    }
} catch (Exception $e) {
    $conn->rollback();
    $message = "Deletion failed: " . $e->getMessage();
    header("Location: dashboard.php?tab=events&message=" . urlencode($message));
    exit();
} finally {
    $conn->close();
}
?>
