<?php
/**
 * Delete Criteria Script
 * Handles the deletion of a criteria.
 */
session_start();

require_once '../../includes/db_connection.php'; // Adjust path

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$criteria_id = $_GET['criteria_id'] ?? null;
$competition_id = $_GET['competition_id'] ?? null; // To redirect back
$category_id = $_GET['category_id'] ?? null; // To redirect back

$message = '';

if ($criteria_id === null) {
    $message = "No criteria specified for deletion.";
    // Redirect back to correct management page
    if ($category_id) {
        header("Location: ../categories/manage_category.php?category_id=" . htmlspecialchars($category_id) . "&subtab=criteria&message=" . urlencode($message));
    } elseif ($competition_id) {
        header("Location: ../manage_competition.php?competition_id=" . htmlspecialchars($competition_id) . "&subtab=criteria&message=" . urlencode($message));
    } else {
        header("Location: ../dashboard.php?tab=events&message=" . urlencode($message));
    }
    exit();
}

$conn->begin_transaction();

try {
    // Check for associated scores
    $stmt_check_scores = $conn->prepare("SELECT COUNT(*) FROM scores WHERE criteria_id = ?");
    if (!$stmt_check_scores) {
        throw new Exception("Prepare statement failed for checking scores: " . $conn->error);
    }
    $stmt_check_scores->bind_param("i", $criteria_id);
    $stmt_check_scores->execute();
    $stmt_check_scores->bind_result($score_count);
    $stmt_check_scores->fetch();
    $stmt_check_scores->close();

    if ($score_count > 0) {
        throw new Exception("Cannot delete criteria: This criteria has " . $score_count . " associated score(s).");
    }

    // Proceed with deleting the criteria
    $stmt_delete = $conn->prepare("DELETE FROM criteria WHERE criteria_id = ?");
    if (!$stmt_delete) {
        throw new Exception("Prepare statement failed for deleting criteria: " . $conn->error);
    }
    $stmt_delete->bind_param("i", $criteria_id);
    if ($stmt_delete->execute()) {
        $conn->commit();
        $message = "Criteria deleted successfully!";
    } else {
        throw new Exception("Error deleting criteria: " . $stmt_delete->error);
    }
} catch (Exception $e) {
    $conn->rollback();
    $message = "Deletion failed: " . $e->getMessage();
} finally {
    $conn->close();
}

// Redirect back to correct management page
if ($category_id) {
    header("Location: ../categories/manage_category.php?category_id=" . htmlspecialchars($category_id) . "&subtab=criteria&message=" . urlencode($message));
} elseif ($competition_id) {
    header("Location: ../manage_competition.php?competition_id=" . htmlspecialchars($competition_id) . "&subtab=criteria&message=" . urlencode($message));
} else {
    header("Location: ../dashboard.php?tab=events&message=" . urlencode($message));
}
exit();
?>
