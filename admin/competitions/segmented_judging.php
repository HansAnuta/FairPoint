<?php
/**
 * Partial Content for Segmented Judging Method
 * This file is included by admin/manage_competition.php
 * Displays Participants and Categories tabs for segmented judging.
 */

if (!isset($competition_id)) {
    echo '<p class="error-message">Error: Competition ID not provided for Segmented Judging management.</p>';
    return;
}

global $conn;

// Fetch participants for this competition (may or may not be tied to specific categories at this level)
$participants = [];
$stmt_part = $conn->prepare("SELECT participant_id, participant_name, category_id FROM participants WHERE competition_id = ? ORDER BY participant_name ASC");
if ($stmt_part) {
    $stmt_part->bind_param("i", $competition_id);
    $stmt_part->execute();
    $result_part = $stmt_part->get_result();
    while ($row = $result_part->fetch_assoc()) {
        $participants[] = $row;
    }
    $stmt_part->close();
} else {
    error_log("Failed to prepare statement for fetching participants (Segmented Judging): " . $conn->error);
}


// Fetch categories for this competition
$categories = [];
$stmt_cat = $conn->prepare("SELECT category_id, category_name FROM categories WHERE competition_id = ? ORDER BY created_at ASC");
if ($stmt_cat) {
    $stmt_cat->bind_param("i", $competition_id);
    $stmt_cat->execute();
    $result_cat = $stmt_cat->get_result();
    while ($row = $result_cat->fetch_assoc()) {
        $categories[] = $row;
    }
    $stmt_cat->close();
} else {
    error_log("Failed to prepare statement for fetching categories (Segmented Judging): " . $conn->error);
}

// Determine active sub-tab
$active_subtab = $_GET['subtab'] ?? 'participants';
?>

<div class="competition-tabs">
    <div class="tabs">
        <button class="tab-button <?php echo ($active_subtab === 'participants') ? 'active' : ''; ?>" data-tab="participants">Participants</button>
        <button class="tab-button <?php echo ($active_subtab === 'categories') ? 'active' : ''; ?>" data-tab="categories">Categories</button>
    </div>

    <div id="participants-tab" class="tab-content <?php echo ($active_subtab === 'participants') ? 'active' : ''; ?>">
        <div class="section-header">
            <h2 class="tab-heading">Participants for Segmented Judging</h2>
            <!-- Link to create participant, passing competition_id but not category_id initially -->
            <a href="../participants/create_participant.php?competition_id=<?php echo htmlspecialchars($competition_id); ?>" class="button button-primary">Add New Participant</a>
        </div>
        <div class="list-container">
            <?php if (empty($participants)): ?>
                <p>No participants found for this competition. Participants can be added directly here, or within specific categories.</p>
            <?php else: ?>
                <?php foreach ($participants as $participant): ?>
                    <div class="item-card">
                        <div class="item-details">
                            <h3 class="item-name"><?php echo htmlspecialchars($participant['participant_name']); ?></h3>
                            <?php if ($participant['category_id']): ?>
                                <p class="item-category">Category ID: <?php echo htmlspecialchars($participant['category_id']); ?></p>
                            <?php else: ?>
                                <p class="item-category">Not assigned to a specific category yet.</p>
                            <?php endif; ?>
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

    <div id="categories-tab" class="tab-content <?php echo ($active_subtab === 'categories') ? 'active' : ''; ?>">
        <div class="section-header">
            <h2 class="tab-heading">Categories for Segmented Judging</h2>
            <a href="../categories/create_category.php?competition_id=<?php echo htmlspecialchars($competition_id); ?>" class="button button-primary">Add New Category</a>
        </div>
        <div class="list-container">
            <?php if (empty($categories)): ?>
                <p>No categories found for this segmented competition.</p>
            <?php else: ?>
                <?php foreach ($categories as $category): ?>
                    <div class="item-card">
                        <div class="item-details">
                            <h3 class="item-name"><?php echo htmlspecialchars($category['category_name']); ?></h3>
                        </div>
                        <div class="card-buttons">
                            <a href="../categories/manage_category.php?category_id=<?php echo htmlspecialchars($category['category_id']); ?>" class="button button-secondary">Manage Category</a>
                            <a href="../categories/edit_category.php?category_id=<?php echo htmlspecialchars($category['category_id']); ?>&competition_id=<?php echo htmlspecialchars($competition_id); ?>" class="button button-edit">Edit</a>
                            <button type="button" class="button button-delete open-comp-delete-modal"
                                    data-id="<?php echo htmlspecialchars($category['category_id']); ?>"
                                    data-type="category"
                                    data-name="<?php echo htmlspecialchars($category['category_name']); ?>"
                                    data-url="../categories/delete_category.php?category_id="
                                    data-extra-params="&competition_id=<?php echo htmlspecialchars($competition_id); ?>"
                                    data-confirm-message="Are you sure you want to delete the category '<?php echo htmlspecialchars($category['category_name']); ?>'? This will also delete its associated participants and criteria.">
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