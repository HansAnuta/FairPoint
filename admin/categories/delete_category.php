<?php
/**
 * Delete Category Script
 * Handles the deletion of a category.
 */
session_start();

require_once '../../includes/db_connection.php'; // Adjust path

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$category_id = $_GET['category_id'] ?? null;
$competition_id = $_GET['competition_id'] ?? null; // To redirect back

$message = '';

if ($category_id === null) {
    $message = "No category specified for deletion.";
    if ($competition_id) {
        header("Location: ../manage_competition.php?competition_id=" . htmlspecialchars($competition_id) . "&subtab=categories&message=" . urlencode($message));
    } else {
        header("Location: ../dashboard.php?tab=events&message=" . urlencode($message));
    }
    exit();
}

$conn->begin_transaction();

try {
    // Check for associated participants
    $stmt_check_participants = $conn->prepare("SELECT COUNT(*) FROM participants WHERE category_id = ?");
    if (!$stmt_check_participants) {
        throw new Exception("Prepare statement failed for checking participants: " . $conn->error);
    }
    $stmt_check_participants->bind_param("i", $category_id);
    $stmt_check_participants->execute();
    $stmt_check_participants->bind_result($part_count);
    $stmt_check_participants->fetch();
    $stmt_check_participants->close();

    if ($part_count > 0) {
        throw new Exception("Cannot delete category: It has " . $part_count . " associated participant(s).");
    }

    // Check for associated criteria
    $stmt_check_criteria = $conn->prepare("SELECT COUNT(*) FROM criteria WHERE category_id = ?");
    if (!$stmt_check_criteria) {
        throw new Exception("Prepare statement failed for checking criteria: " . $conn->error);
    }
    $stmt_check_criteria->bind_param("i", $category_id);
    $stmt_check_criteria->execute();
    $stmt_check_criteria->bind_result($crit_count);
    $stmt_check_criteria->fetch();
    $stmt_check_criteria->close();

    if ($crit_count > 0) {
        throw new Exception("Cannot delete category: It has " . $crit_count . " associated criteria.");
    }

    // Proceed with deleting the category
    $stmt_delete = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
    if (!$stmt_delete) {
        throw new Exception("Prepare statement failed for deleting category: " . $conn->error);
    }
    $stmt_delete->bind_param("i", $category_id);
    if ($stmt_delete->execute()) {
        $conn->commit();
        $message = "Category deleted successfully!";
    } else {
        throw new Exception("Error deleting category: " . $stmt_delete->error);
    }
} catch (Exception $e) {
    $conn->rollback();
    $message = "Deletion failed: " . $e->getMessage();
} finally {
    $conn->close();
}

// Redirect back to competition management, categories tab
if ($competition_id) {
    header("Location: ../manage_competition.php?competition_id=" . htmlspecialchars($competition_id) . "&subtab=categories&message=" . urlencode($message));
} else {
    header("Location: ../dashboard.php?tab=events&message=" . urlencode($message));
}
exit();
?>
