<?php
// Digital_Judging_System/delete_category.php

require_once 'db_connect.php';

$category_id = $_GET['category_id'] ?? null;
$competition_id = $_GET['competition_id'] ?? null; // To redirect back

$message = '';
$message_type = '';

if (!$category_id) {
    header('Location: /Digital_Judging_System/admin_events.php?status=error&message=' . urlencode('No category ID provided for deletion.'));
    exit();
}

try {
    $pdo->beginTransaction();

    // Fetch competition_id if not provided, needed for redirection
    if (!$competition_id) {
        $stmt = $pdo->prepare("SELECT competition_id FROM Category WHERE category_id = ?");
        $stmt->execute([$category_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $competition_id = $result['competition_id'];
        } else {
            header('Location: /Digital_Judging_System/admin_events.php?status=error&message=' . urlencode('Category not found.'));
            exit();
        }
    }

    // 1. Delete from 'Assignment' for this category
    $stmt = $pdo->prepare("DELETE FROM Assignment WHERE category_id = ?");
    $stmt->execute([$category_id]);

    // 2. Delete from 'Participant' for this category
    $stmt = $pdo->prepare("DELETE FROM Participant WHERE category_id = ?");
    $stmt->execute([$category_id]);

    // 3. Finally, delete the 'Category' itself
    $stmt = $pdo->prepare("DELETE FROM Category WHERE category_id = ?");
    $stmt->execute([$category_id]);

    $pdo->commit();
    $message = "Category and all associated data deleted successfully!";
    $message_type = 'success';

} catch (\PDOException $e) {
    $pdo->rollBack();
    $message = "Error deleting category: " . $e->getMessage();
    $message_type = 'error';
    error_log("Error in delete_category.php: " . $e->getMessage());
}

header('Location: /Digital_Judging_System/competition_details.php?competition_id=' . htmlspecialchars($competition_id) . '&status=' . $message_type . '&message=' . urlencode($message));
exit();
?>