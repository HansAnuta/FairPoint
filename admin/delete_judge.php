<?php
/**
 * Delete Judge Script
 * Handles the deletion of a judge and their associated user account.
 * Validates if the judge can be deleted based on associated data.
 */
session_start();

require_once '../includes/db_connection.php'; // Adjust path

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$judge_id = $_GET['judge_id'] ?? null;
$message = '';

if ($judge_id === null) {
    header("Location: dashboard.php?tab=judges&message=" . urlencode("No judge specified for deletion."));
    exit();
}

// Start a transaction for atomicity
$conn->begin_transaction();

try {
    // Get the user_id associated with this judge_id
    $user_id_to_delete = null;
    $stmt_get_user_id = $conn->prepare("SELECT user_id FROM judges WHERE judge_id = ?");
    if (!$stmt_get_user_id) {
        throw new Exception("Prepare statement failed for getting user_id: " . $conn->error);
    }
    $stmt_get_user_id->bind_param("i", $judge_id);
    $stmt_get_user_id->execute();
    $stmt_get_user_id->bind_result($user_id_to_delete);
    $stmt_get_user_id->fetch();
    $stmt_get_user_id->close();

    if ($user_id_to_delete === null) {
        throw new Exception("Associated user account not found for this judge.");
    }

    // Check for associated scores
    $stmt_check_scores = $conn->prepare("SELECT COUNT(*) FROM scores WHERE judge_id = ?");
    if (!$stmt_check_scores) {
        throw new Exception("Prepare statement failed for checking scores: " . $conn->error);
    }
    $stmt_check_scores->bind_param("i", $judge_id);
    $stmt_check_scores->execute();
    $stmt_check_scores->bind_result($score_count);
    $stmt_check_scores->fetch();
    $stmt_check_scores->close();

    if ($score_count > 0) {
        throw new Exception("Cannot delete judge: This judge has " . $score_count . " associated score(s).");
    }

    // Check for associated assignments
    $stmt_check_assignments = $conn->prepare("SELECT COUNT(*) FROM assignments WHERE judge_id = ?");
    if (!$stmt_check_assignments) {
        throw new Exception("Prepare statement failed for checking assignments: " . $conn->error);
    }
    $stmt_check_assignments->bind_param("i", $judge_id);
    $stmt_check_assignments->execute();
    $stmt_check_assignments->bind_result($assignment_count);
    $stmt_check_assignments->fetch();
    $stmt_check_assignments->close();

    if ($assignment_count > 0) {
        throw new Exception("Cannot delete judge: This judge has " . $assignment_count . " associated assignment(s).");
    }

    // Check for associated judge_links
    $stmt_check_links = $conn->prepare("SELECT COUNT(*) FROM judge_links WHERE judge_id = ?");
    if (!$stmt_check_links) {
        throw new Exception("Prepare statement failed for checking judge_links: " . $conn->error);
    }
    $stmt_check_links->bind_param("i", $judge_id);
    $stmt_check_links->execute();
    $stmt_check_links->bind_result($link_count);
    $stmt_check_links->fetch();
    $stmt_check_links->close();

    if ($link_count > 0) {
        throw new Exception("Cannot delete judge: This judge has " . $link_count . " associated judge link(s).");
    }


    // If no associated records, proceed with deletion
    // Delete from 'judges' table first (child table)
    $stmt_delete_judge = $conn->prepare("DELETE FROM judges WHERE judge_id = ?");
    if (!$stmt_delete_judge) {
        throw new Exception("Prepare statement failed for deleting judge: " . $conn->error);
    }
    $stmt_delete_judge->bind_param("i", $judge_id);
    if (!$stmt_delete_judge->execute()) {
        throw new Exception("Error deleting judge entry: " . $stmt_delete_judge->error);
    }

    // Then delete from 'users' table (parent table)
    $stmt_delete_user = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    if (!$stmt_delete_user) {
        throw new Exception("Prepare statement failed for deleting user: " . $conn->error);
    }
    $stmt_delete_user->bind_param("i", $user_id_to_delete);
    if (!$stmt_delete_user->execute()) {
        throw new Exception("Error deleting associated user entry: " . $stmt_delete_user->error);
    }

    $conn->commit();
    $message = "Judge deleted successfully!";
    header("Location: dashboard.php?tab=judges&message=" . urlencode($message));
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $message = "Deletion failed: " . $e->getMessage();
    header("Location: dashboard.php?tab=judges&message=" . urlencode($message));
    exit();
} finally {
    $conn->close();
}
?>