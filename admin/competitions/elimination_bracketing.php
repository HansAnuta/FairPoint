<?php
/**
 * Partial Content for Elimination and Bracketing Judging Method
 * This file is included by admin/manage_competition.php
 * Displays Participants tab for elimination/bracketing.
 */

if (!isset($competition_id)) {
    echo '<p class="error-message">Error: Competition ID not provided for Elimination and Bracketing management.</p>';
    return;
}

global $conn;

// Fetch participants for this competition
$participants = [];
// Assuming for elimination, participants are directly linked to competition, not categories initially.
$stmt_part = $conn->prepare("SELECT participant_id, participant_name FROM participants WHERE competition_id = ? AND category_id IS NULL ORDER BY participant_name ASC");
if ($stmt_part) {
    $stmt_part->bind_param("i", $competition_id);
    $stmt_part->execute();
    $result_part = $stmt_part->get_result();
    while ($row = $result_part->fetch_assoc()) {
        $participants[] = $row;
    }
    $stmt_part->close();
} else {
    error_log("Failed to prepare statement for fetching participants (Elimination): " . $conn->error);
}

// No tabs for criteria or categories in this initial version, just participants.
?>

<div class="competition-section">
    <div class="section-header">
        <h2 class="tab-heading">Participants for Elimination and Bracketing</h2>
        <a href="../participants/create_participant.php?competition_id=<?php echo htmlspecialchars($competition_id); ?>" class="button button-primary">Add New Participant</a>
    </div>
    <div class="list-container">
        <?php if (empty($participants)): ?>
            <p>No participants found for this competition.</p>
        <?php else: ?>
            <?php foreach ($participants as $participant): ?>
                <div class="item-card">
                    <div class="item-details">
                        <h3 class="item-name"><?php echo htmlspecialchars($participant['participant_name']); ?></h3>
                    </div>
                    <div class="card-buttons">
                        <a href="#" class="button button-edit">Edit</a>
                        <a href="#" class="button button-delete">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
