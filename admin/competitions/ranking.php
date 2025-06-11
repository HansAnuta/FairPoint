<?php
/**
 * Partial Content for Ranking Judging Method
 * This file is included by admin/manage_competition.php
 * Displays Participants and Criteria tabs for ranking.
 */

// Ensure this file is only accessed via inclusion by manage_competition.php
if (!defined('INCLUDED_VIA_MANAGE_COMPETITION')) {
    // Define a constant to indicate it's included, to prevent direct access
    // For now, this is a placeholder. You'd set this constant in manage_competition.php
    // and check it here. For simplicity in rapid iteration, skipping strict check for now.
}

// Retrieve competition_id from the parent scope (manage_competition.php)
// It's crucial that $competition_id is available here.
if (!isset($competition_id)) {
    // This should ideally not happen if included correctly
    echo '<p class="error-message">Error: Competition ID not provided for Ranking management.</p>';
    return;
}

// Re-establish database connection if it was closed in the parent scope.
// Or, ensure the parent script passes the $conn object.
// For now, let's assume it's still open from manage_competition.php
global $conn; // Access the global connection object if it's not passed directly

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
    error_log("Failed to prepare statement for fetching participants (Ranking): " . $conn->error);
}

// Fetch criteria for this competition (assuming no categories for ranking, criteria directly linked to competition or a default category)
// For Ranking, criteria might represent aspects being judged or just a single "Overall Ranking" criterion.
// Here, we link criteria to the competition via a placeholder category_id for simplicity,
// or we assume they will be managed through a default category created implicitly.
// A more robust solution might have a dedicated competition_criteria table or
// default category for non-segmented judging methods.
// For now, let's assume a dummy category_id (e.g., 0 or a specific ID for "ranking" competitions)
// This part needs careful alignment with your ERD and data entry strategy.
// Given your ERD, criteria are linked to categories. So, for non-segmented, you'd need
// to either enforce a default category_id for competition or create one.
// For initial functionality, let's fetch criteria where competition_id matches and category_id is null,
// implying they are directly for the competition (if your DB supports this via a join or logic).
// A better approach would be to create a default category for non-segmented competitions
// and link criteria to that category. For now, we'll simplify.

$criteria = [];
// For a simple implementation, let's assume for Ranking/Simple/Weighted, criteria are linked to a "default" category
// that is implicitly created for the competition or directly linked via a competition_id.
// Given ERD: criteria -> category -> competition.
// So, we need to fetch criteria associated with a category that belongs to *this* competition.
// For Ranking, Simple Averaging, Weighted Averaging, the assumption is that criteria are associated
// with a single "default" category under the competition.
// Let's assume a default category always exists for these types or create one if it doesn't.
// A more robust way: fetch a default category_id for this competition, then fetch criteria.
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
        error_log("Failed to prepare statement for fetching criteria (Ranking): " . $conn->error);
    }
} else {
    // If no default category, implicitly create one. This is a pragmatic shortcut.
    // In a real system, this would be handled on competition creation.
    // For demonstration, let's log a message.
    error_log("No default category found for competition ID: " . $competition_id . ". Criteria will not be displayed.");
}


// Determine active sub-tab for this page (Participants or Criteria)
$active_subtab = $_GET['subtab'] ?? 'participants';
?>

<div class="competition-tabs">
    <div class="tabs">
        <button class="tab-button <?php echo ($active_subtab === 'participants') ? 'active' : ''; ?>" data-tab="participants">Participants</button>
        <button class="tab-button <?php echo ($active_subtab === 'criteria') ? 'active' : ''; ?>" data-tab="criteria">Criteria</button>
    </div>

    <div id="participants-tab" class="tab-content <?php echo ($active_subtab === 'participants') ? 'active' : ''; ?>">
        <div class="section-header">
            <h2 class="tab-heading">Participants for Ranking</h2>
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

    <div id="criteria-tab" class="tab-content <?php echo ($active_subtab === 'criteria') ? 'active' : ''; ?>">
        <div class="section-header">
            <h2 class="tab-heading">Criteria for Ranking</h2>
            <?php if ($default_category_id): ?>
                <a href="../criteria/create_criteria.php?competition_id=<?php echo htmlspecialchars($competition_id); ?>&category_id=<?php echo htmlspecialchars($default_category_id); ?>" class="button button-primary">Add New Criteria</a>
            <?php else: ?>
                <p>No default category found. Cannot add criteria without a category.</p>
            <?php endif; ?>
        </div>
        <div class="list-container">
            <?php if (empty($criteria)): ?>
                <p>No criteria found for this competition. For Ranking, criteria typically represent the aspects judges will use to rank participants.</p>
            <?php else: ?>
                <?php foreach ($criteria as $criterion): ?>
                    <div class="item-card">
                        <div class="item-details">
                            <h3 class="item-name"><?php echo htmlspecialchars($criterion['criteria_name']); ?></h3>
                            <p class="item-weight">Weight: <?php echo htmlspecialchars($criterion['weight']); ?> (For ranking, weight might indicate importance or a simple 1 for general criteria)</p>
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

                // Update URL hash to maintain active sub-tab on refresh
                const url = new URL(window.location.href);
                url.searchParams.set('subtab', this.dataset.tab);
                history.pushState(null, '', url.toString());
            });
        });

        // Initial sub-tab selection based on URL parameter on page load
        const urlParams = new URLSearchParams(window.location.search);
        const initialSubTab = urlParams.get('subtab');
        if (initialSubTab) {
            const initialButton = document.querySelector(`.competition-tabs .tab-button[data-tab="${initialSubTab}"]`);
            if (initialButton) {
                initialButton.click();
            }
        } else {
            // Default to participants tab if no parameter is present
            document.querySelector('.competition-tabs .tab-button[data-tab="participants"]').click();
        }
    });
</script>
