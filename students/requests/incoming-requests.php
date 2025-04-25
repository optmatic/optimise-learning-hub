<?php
// Helper function to format reschedule request data
if (!function_exists('format_reschedule_request_data')) {
    function format_reschedule_request_data($request_id) {
        $data = array(
            'tutor_name'         => get_post_meta($request_id, 'tutor_name', true),
            'original_date'      => get_post_meta($request_id, 'original_date', true),
            'original_time'      => get_post_meta($request_id, 'original_time', true),
            'new_date'           => '',
            'new_time'           => '',
            'formatted_original' => 'N/A',
            'formatted_new'      => 'N/A',
        );

        // Format Original Time
        if (!empty($data['original_date']) && !empty($data['original_time'])) {
            $data['formatted_original'] = date('M j, Y', strtotime($data['original_date'])) . ' at ' . date('g:i A', strtotime($data['original_time']));
        }

        // --- Find Proposed New Date/Time ---
        // Priority 1: Direct meta fields
        $data['new_date'] = get_post_meta($request_id, 'new_date', true);
        if (empty($data['new_date'])) {
            $data['new_date'] = get_post_meta($request_id, 'proposed_date', true);
        }
        $data['new_time'] = get_post_meta($request_id, 'new_time', true);
        if (empty($data['new_time'])) {
            $data['new_time'] = get_post_meta($request_id, 'proposed_time', true);
        }

        // Priority 2: 'proposed_time_slot' array
        if (empty($data['new_date']) || empty($data['new_time'])) {
            $proposed_slot = get_post_meta($request_id, 'proposed_time_slot', true);
            if (!empty($proposed_slot) && is_array($proposed_slot)) {
                $data['new_date'] = $proposed_slot['date'] ?? $data['new_date'];
                $data['new_time'] = $proposed_slot['time'] ?? $data['new_time'];
            }
        }

        // Priority 3: 'preferred_times' array (use the first one)
        if (empty($data['new_date']) || empty($data['new_time'])) {
            $preferred_times = get_post_meta($request_id, 'preferred_times', true);
            if (!empty($preferred_times) && is_array($preferred_times) && isset($preferred_times[0])) {
                $data['new_date'] = $preferred_times[0]['date'] ?? $data['new_date'];
                $data['new_time'] = $preferred_times[0]['time'] ?? $data['new_time'];
            }
        }

        // Priority 4: 'alternatives' array (use the first one)
        if (empty($data['new_date']) || empty($data['new_time'])) {
            $alternatives = get_post_meta($request_id, 'alternatives', true);
            if (!empty($alternatives) && is_array($alternatives) && isset($alternatives[0])) {
                $data['new_date'] = $alternatives[0]['date'] ?? $data['new_date'];
                $data['new_time'] = $alternatives[0]['time'] ?? $data['new_time'];
            }
        }
        
        // Format New Time if found
        if (!empty($data['new_date']) && !empty($data['new_time'])) {
             $data['formatted_new'] = date('M j, Y', strtotime($data['new_date'])) . ' at ' . date('g:i A', strtotime($data['new_time']));
        }

        return $data;
    }
}
?>
    <!-- Incoming Reschedule Requests (Tutor-initiated) -->
    <div class="card mb-4" id="incomingRescheduleSection">
        <div class="card-header bg-warning text-dark">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-arrow-right me-2"></i> Incoming Reschedule Requests
                </div>
                <?php 
                // Fetch pending reschedule requests once
                $current_user_id = get_current_user_id();
                $tutor_requests_args = array(
                    'post_type'      => 'progress_report',
                    'posts_per_page' => -1,
                    'meta_query'     => array(
                        'relation' => 'AND',
                        array('key' => 'student_id', 'value' => $current_user_id, 'compare' => '='),
                        array('key' => 'request_type', 'value' => 'tutor_reschedule', 'compare' => '='),
                        array('key' => 'status', 'value' => 'pending', 'compare' => '=')
                    ),
                    'order'          => 'DESC',
                    'orderby'        => 'date'
                    // No 'fields' => 'ids' needed anymore, we need the full post objects
                );
                $tutor_requests = get_posts($tutor_requests_args);
                $pending_reschedule_count = count($tutor_requests); // Count the fetched posts

                if ($pending_reschedule_count > 0): 
                ?>
                <span class="badge bg-danger"><?php echo $pending_reschedule_count; ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php
            if (!empty($tutor_requests)) {
                echo '<div class="table-responsive">';
                echo '<table class="table table-striped">';
                echo '<thead><tr><th>Date Requested</th><th>Original Lesson</th><th>Proposed New Time</th><th>Tutor</th><th>Action</th></tr></thead>';
                echo '<tbody>';
                
                foreach ($tutor_requests as $request) {
                    $request_id = $request->ID;
                    $request_date = get_the_date('M j, Y', $request_id);
                    
                    // Use the helper function to get formatted data
                    $request_data = format_reschedule_request_data($request_id);
                    
                    echo '<tr>';
                    echo '<td>' . esc_html($request_date) . '</td>';
                    echo '<td>' . esc_html($request_data['formatted_original']) . '</td>';
                    echo '<td>' . esc_html($request_data['formatted_new']) . '</td>';
                    echo '<td>' . esc_html($request_data['tutor_name']) . '</td>';
                    echo '<td>';
                    // Accept Form
                    echo '<form method="post" class="d-inline">';
                    echo '<input type="hidden" name="confirm_reschedule" value="1">';
                    echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                    echo '<input type="hidden" name="active_tab" value="requests-tab">'; // Ensure this is the correct tab identifier if needed elsewhere
                    echo '<button type="submit" class="btn btn-sm btn-success me-1">Accept</button>';
                    echo '</form>';
                    
                    // Unavailable Button (triggers modal)
                    // Note: No form needed here, the button just opens the modal. The modal has its own form.
                    echo '<button type="button" class="btn btn-sm btn-warning" 
                            data-bs-toggle="modal" 
                            data-bs-target="#unavailableModal" 
                            data-request-id="' . $request_id . '"
                            data-tutor-name="' . esc_attr($request_data['tutor_name']) . '"
                            data-original-datetime="' . esc_attr($request_data['formatted_original']) . '">
                        Unavailable
                    </button>';
                    echo '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
                echo '</div>';
            } else {
                echo '<p>No incoming reschedule requests from tutors at this time.</p>';
            }
            ?>
        </div>
    </div>
    

    <!-- Add the Unavailable Modal -->
<div class="modal fade" id="unavailableModal" tabindex="-1" aria-labelledby="unavailableModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="unavailableModalLabel">Provide Alternative Times</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="unavailableErrorMessage" class="alert alert-danger" style="display: none;">
                    <p>Please provide at least one alternative time.</p>
                </div>
                <p>You've indicated you're unavailable for the proposed time. Please provide alternative times that would work for you.</p>
                <p><strong>Tutor:</strong> <span id="modal_tutor_name"></span></p> <!-- Changed ID -->
                <p><strong>Original Time:</strong> <span id="modal_original_datetime"></span></p> <!-- Changed ID -->
                
                <form id="unavailableForm" method="post">
                    <input type="hidden" name="mark_unavailable" value="1">
                    <input type="hidden" name="request_id" id="unavailable_request_id" value="">
                    <input type="hidden" name="active_tab" value="requests-tab"> <!-- Add active tab here too -->
                    
                    <div class="mb-3">
                        <label class="form-label">Alternative Times <span class="text-danger">*</span></label>
                        <p class="text-muted small">Please provide at least one alternative date and time.</p>
                        
                        <div id="alternative-times-container">
                            <?php for ($i = 1; $i <= 3; $i++) : ?>
                                <div class="mb-2 alternative-time-group"> <!-- Added class -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label small" for="alt_date_<?php echo $i; ?>">Alternative Date <?php echo $i; ?>:</label>
                                            <input type="date" class="form-control alt-date" 
                                                   name="alternatives[<?php echo $i-1; ?>][date]" id="alt_date_<?php echo $i; ?>" 
                                                   <?php echo ($i == 1) ? 'required' : ''; ?>>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small" for="alt_time_<?php echo $i; ?>">Alternative Time <?php echo $i; ?>:</label>
                                            <input type="time" class="form-control alt-time" 
                                                   name="alternatives[<?php echo $i-1; ?>][time]" id="alt_time_<?php echo $i; ?>" 
                                                   <?php echo ($i == 1) ? 'required' : ''; ?>>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitUnavailable">Submit Alternative Times</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript to populate the modal fields when it's shown
document.addEventListener('DOMContentLoaded', function () {
    var unavailableModal = document.getElementById('unavailableModal');
    if (unavailableModal) {
        unavailableModal.addEventListener('show.bs.modal', function (event) {
            // Button that triggered the modal
            var button = event.relatedTarget;
            // Extract info from data-* attributes
            var requestId = button.getAttribute('data-request-id');
            var tutorName = button.getAttribute('data-tutor-name');
            var originalDateTime = button.getAttribute('data-original-datetime'); // Use combined datetime

            // Update the modal's content.
            var modalTutorName = unavailableModal.querySelector('#modal_tutor_name');
            var modalOriginalTime = unavailableModal.querySelector('#modal_original_datetime');
            var modalRequestIdInput = unavailableModal.querySelector('#unavailable_request_id');
            var errorMessage = unavailableModal.querySelector('#unavailableErrorMessage');
            var form = unavailableModal.querySelector('#unavailableForm');
            var altDateInputs = unavailableModal.querySelectorAll('.alt-date');
            var altTimeInputs = unavailableModal.querySelectorAll('.alt-time');

            if (modalTutorName) modalTutorName.textContent = tutorName;
            if (modalOriginalTime) modalOriginalTime.textContent = originalDateTime;
            if (modalRequestIdInput) modalRequestIdInput.value = requestId;

            // Reset form state
            errorMessage.style.display = 'none';
            if (form) form.reset(); 
            // Ensure the first alternative is marked required again after reset
            if (altDateInputs[0]) altDateInputs[0].required = true;
            if (altTimeInputs[0]) altTimeInputs[0].required = true;
        });

        // Add validation for the alternative times form within the modal
        var unavailableForm = document.getElementById('unavailableForm');
        if (unavailableForm) {
            unavailableForm.addEventListener('submit', function(event) {
                var firstAltDate = document.getElementById('alt_date_1');
                var firstAltTime = document.getElementById('alt_time_1');
                var errorMessage = document.getElementById('unavailableErrorMessage');

                if (!firstAltDate.value || !firstAltTime.value) {
                    event.preventDefault(); // Stop form submission
                    errorMessage.style.display = 'block'; // Show error message
                } else {
                    errorMessage.style.display = 'none'; // Hide error message
                }
            });
        }
    }
});
</script>
