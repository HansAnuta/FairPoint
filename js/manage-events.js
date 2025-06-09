// Placeholder JavaScript functions for interactivity
        function handleLogout() {
            alert('Logging out...');
            // In a real application, you would redirect to a logout script:
            // window.location.href = 'logout.php';
        }

        function viewEventDetails(eventId) {
            alert('Viewing details for event: ' + eventId);
            // In a real application, this would redirect to an event details page:
            // window.location.href = 'event_details.php?id=' + eventId;
        }

        function editEvent(eventId) {
            alert('Editing event: ' + eventId);
            // In a real application, this would redirect to an edit event form:
            // window.location.href = 'edit_event.php?id=' + eventId;
        }

        function deleteEvent(eventId) {
            if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
                alert('Deleting event: ' + eventId);
                // In a real application, this would send an AJAX request to a delete script:
                // fetch('delete_event.php?id=' + eventId, { method: 'DELETE' })
                //     .then(response => response.json())
                //     .then(data => {
                //         if (data.success) {
                //             alert('Event deleted successfully!');
                //             // Remove the event item from the list
                //             document.getElementById('eventList').removeChild(document.querySelector(`[onclick*="${eventId}"]`).closest('.event-item'));
                //         } else {
                //             alert('Error deleting event: ' + data.message);
                //         }
                //     })
                //     .catch(error => console.error('Error:', error));
            }
        }

        // Example of how to dynamically load events using PHP
        // This is a conceptual example and would require server-side PHP to function
        function loadEvents() {
            // This PHP block would be embedded in your actual .php file
            /*
            <?php
            // Assuming you have a database connection and session management setup
            // Include your database connection file
            // require_once 'db_connect.php';

            // Start session to get admin_id
            // session_start();
            // if (!isset($_SESSION['admin_id'])) {
            //     header('Location: login.php'); // Redirect to login if not authenticated
            //     exit();
            // }

            // $admin_id = $_SESSION['admin_id'];

            // Fetch events managed by this admin
            // $stmt = $pdo->prepare("SELECT event_id, event_name, created_at, judging_method.method_name
            //                      FROM Event
            //                      JOIN JudgingMethod ON Event.judging_method_id = JudgingMethod.judging_method_id
            //                      WHERE admin_id = ? ORDER BY created_at DESC");
            // $stmt->execute([$admin_id]);
            // $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // if (count($events) > 0) {
            //     foreach ($events as $event) {
            //         echo '<li class="event-item">';
            //         echo '<div class="event-details">';
            //         echo '<h3>' . htmlspecialchars($event['event_name']) . '</h3>';
            //         echo '<p>Judging Method: ' . htmlspecialchars($event['method_name']) . '</p>';
            //         echo '<p>Created: ' . date('Y-m-d', strtotime($event['created_at'])) . '</p>';
            //         echo '</div>';
            //         echo '<div class="event-actions">';
            //         echo '<button class="btn view-btn" onclick="viewEventDetails(\'' . $event['event_id'] . '\')">View</button>';
            //         echo '<button class="btn edit-btn" onclick="editEvent(\'' . $event['event_id'] . '\')">Edit</button>';
            //         echo '<button class="btn delete-btn" onclick="deleteEvent(\'' . $event['event_id'] . '\')">Delete</button>';
            //         echo '</div>';
            //         echo '</li>';
            //     }
            // } else {
            //     echo '<div class="empty-state"><p>No events found. Click "Create New Event" to get started!</p></div>';
            //     echo '<script>document.getElementById("eventList").style.display = "none"; document.getElementById("emptyState").style.display = "block";</script>';
            // }
            ?>
            */
        }

        // Call loadEvents on page load (if using the PHP example)
        // window.onload = loadEvents;