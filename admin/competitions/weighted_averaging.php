<?php
/**
 * Partial Content for Weighted Averaging Judging Method
 * This file is included by admin/manage_competition.php
 * Displays Participants and Criteria tabs for weighted averaging.
 */

if (!isset($competition_id)) {
    echo '<p class="error-message">Error: Competition ID not provided for Weighted Averaging management.</p>';
    return;
}

global $conn;

// Fetch participants for this competition
$participants = [];
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
    error_log("Failed to prepare statement for fetching participants (Weighted Averaging): " . $conn->error);
}

// Fetch criteria for this competition (assuming a default category)
$criteria = [];
$default_category_id = null;
$stmt_default_cat = $conn->prepare("SELECT category_id FROM categories WHERE competition_id = ? LIMIT 1");
if ($stmt_default_cat) {
    $stmt_default_cat->bind_param("i", $competition_id);
    $stmt_default_cat->execute();
    $stmt_default_cat->bind_result($default_category_id);
    $stmt_default_cat->fetch();
    $stmt_default_cat->close();
}

if ($default_category_id) {
    $stmt_crit = $conn->prepare("SELECT criteria_id, criteria_name, weight FROM criteria WHERE category_id = ? ORDER BY criteria_name ASC");
    if ($stmt_crit) {
        $stmt_crit->bind_param("i", $default_category_id);
        $stmt_crit->execute();
        $result_crit = $stmt_crit->get_result();
        while ($row = $result_crit->fetch_assoc()) {
            $criteria[] = $row;
        }
        $stmt_crit->close();
    } else {
        error_log("Failed to prepare statement for fetching criteria (Weighted Averaging): " . $conn->error);
    }
} else {
    error_log("No default category found for competition ID: " . $competition_id . ". Criteria will not be displayed. Consider creating a default category for this competition type.");
}

// Determine active sub-tab
$active_subtab = $_GET['subtab'] ?? 'participants';
?>

<div class="competition-tabs">
    <div class="tabs">
        <button class="tab-button <?php echo ($active_subtab === 'participants') ? 'active' : ''; ?>" data-tab="participants">Participants</button>
        <button class="tab-button <?php echo ($active_subtab === 'criteria') ? 'active' : ''; ?>" data-tab="criteria">Criteria</button>
    </div>

    <div id="participants-tab" class="tab-content <?php echo ($active_subtab === 'participants') ? 'active' : ''; ?>">
        <div class="section-header">
            <h2 class="tab-heading">Participants for Weighted Averaging</h2>
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
                            <a href="../participants/edit_participant.php?participant_id=<?php echo htmlspecialchars($participant['participant_id']); ?>&competition_id=<?php echo htmlspecialchars($competition_id); ?>" class="button button-edit">Edit</a>
                            <button type="button" class="button button-delete open-comp-delete-modal"
                                    data-id="<?php echo htmlspecialchars($participant['participant_id']); ?>"
                                    data-type="participant"
                                    data-name="<?php echo htmlspecialchars($participant['participant_name']); ?>"
                                    data-url="../participants/delete_participant.php?participant_id="
                                    data-confirm-message="Are you sure you want to delete the participant '<?php echo htmlspecialchars($participant['participant_name']); ?>'?">
                                Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="criteria-tab" class="tab-content <?php echo ($active_subtab === 'criteria') ? 'active' : ''; ?>">
        <div class="section-header">
            <h2 class="tab-heading">Criteria for Weighted Averaging</h2>
            <?php if ($default_category_id): ?>
                <a href="../criteria/create_criteria.php?competition_id=<?php echo htmlspecialchars($competition_id); ?>&category_id=<?php echo htmlspecialchars($default_category_id); ?>" class="button button-primary">Add New Criteria</a>
            <?php else: ?>
                <p>No default category found. Cannot add criteria without a category. Please create a default category for this competition.</p>
            <?php endif; ?>
        </div>
        <div class="list-container">
            <?php if (empty($criteria)): ?>
                <p>No criteria found for this competition. For Weighted Averaging, ensure criteria have appropriate weights assigned.</p>
            <?php else: ?>
                <?php foreach ($criteria as $criterion): ?>
                    <div class="item-card">
                        <div class="item-details">
                            <h3 class="item-name"><?php echo htmlspecialchars($criterion['criteria_name']); ?></h3>
                            <p class="item-weight">Weight: <strong><?php echo htmlspecialchars($criterion['weight']); ?></strong></p>
                        </div>
                        <div class="card-buttons">
                            <a href="../criteria/edit_criteria.php?criteria_id=<?php echo htmlspecialchars($criterion['criteria_id']); ?>&competition_id=<?php echo htmlspecialchars($competition_id); ?>&category_id=<?php echo htmlspecialchars($default_category_id); ?>" class="button button-edit">Edit</a>
                            <button type="button" class="button button-delete open-comp-delete-modal"
                                    data-id="<?php echo htmlspecialchars($criterion['criteria_id']); ?>"
                                    data-type="criteria"
                                    data-name="<?php echo htmlspecialchars($criterion['criteria_name']); ?>"
                                    data-url="../criteria/delete_criteria.php?criteria_id="
                                    data-confirm-message="Are you sure you want to delete the criteria '<?php echo htmlspecialchars($criterion['criteria_name']); ?>'?">
                                Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Sub-tab switching for this partial
    document.addEventListener('DOMContentLoaded', function() {
        const subTabButtons = document.querySelectorAll('.competition-tabs .tab-button');
        const subTabContents = document.querySelectorAll('.competition-tabs .tab-content');

        subTabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetSubTabId = this.dataset.tab + '-tab';

                subTabButtons.forEach(btn => btn.classList.remove('active'));
                subTabContents.forEach(content => content.classList.remove('active'));

                this.classList.add('active');
                document.getElementById(targetSubTabId).classList.add('active');

                const url = new URL(window.location.href);
                url.searchParams.set('subtab', this.dataset.tab);
                history.pushState(null, '', url.toString());
            });
        });

        const urlParams = new URLSearchParams(window.location.search);
        const initialSubTab = urlParams.get('subtab');
        if (initialSubTab) {
            const initialButton = document.querySelector(`.competition-tabs .tab-button[data-tab="${initialSubTab}"]`);
            if (initialButton) {
                initialButton.click();
            }
        } else {
            document.querySelector('.competition-tabs .tab-button[data-tab="participants"]').click();
        }
    });
</script>