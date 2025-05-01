<?php
/**
 * Template part for displaying tutor reschedule request interface.
 * Refactored to rely on AJAX for loading request data.
 *
 * @package Understrap
 */

// Prevent direct file access
defined( 'ABSPATH' ) || exit;

// Ensure we're on the tutor dashboard page and the user is a tutor
// Note: This check might be better placed in the parent template (tutor-dashboard.php)
if ( ! is_page_template( 'tutor-dashboard.php' ) || ! current_user_can( 'tutor' ) ) {
    return; // Exit if accessed directly or by wrong user role
}

// Fetch current tutor data - needed for AJAX parameters potentially
$current_user = wp_get_current_user();
$tutor_id     = $current_user->ID;

?>

<div class="tutor-requests-section">

    <!-- Notifications Placeholder -->
    <div id="tutor-notifications-container" class="mb-4">
        <!-- Notifications will be loaded here via AJAX -->
        <div class="alert alert-light text-center"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading notifications...</div>
    </div>
    <!-- <hr/> Removed redundant hr -->

    <!-- Initiate Lesson Reschedule Request -->
    <div class="initiate-request-section mb-4 text-start">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tutorRescheduleModal">
            <i class="fa-regular fa-calendar-plus me-2"></i>Initiate Lesson Reschedule Request
        </button>
    </div>

    <!-- Outgoing Reschedule Requests Card -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <div class="d-flex justify-content-between align-items-center">
                 <span><i class="fas fa-arrow-left me-2"></i> Your Outgoing Reschedule Requests</span>
                 <!-- Optional: Add a refresh button -->
                 <!-- <button class="btn btn-sm btn-light refresh-outgoing-tutor-requests"><i class="fas fa-sync-alt"></i></button> -->
            </div>
        </div>
        <div class="card-body" id="tutor-outgoing-requests-container">
             <!-- Outgoing requests will be loaded here via AJAX -->
             <div class="text-center p-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading outgoing requests...</div>
        </div>
    </div>

    <!-- Incoming Requests Card -->
    <div class="card mb-4">
         <div class="card-header bg-warning text-dark">
            <div class="d-flex justify-content-between align-items-center">
                 <span><i class="fas fa-arrow-right me-2"></i> Incoming Requests from Students</span>
                 <span class="badge bg-danger incoming-student-request-count notification-badge" style="display: none;"></span> <!-- Added notification-badge class -->
            </div>
        </div>
        <div class="card-body" id="tutor-incoming-requests-container">
            <!-- Incoming requests will be loaded here via AJAX -->
            <div class="text-center p-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading incoming requests...</div>
        </div>
    </div>

    <!-- Student Alternative Time Suggestions Card -->
    <div class="card mb-4" id="student-alternatives-card-wrapper"> <!-- Added wrapper for potential AJAX replacement -->
        <div class="card-header bg-secondary text-white">
             <div class="d-flex justify-content-between align-items-center">
                 <span><i class="fas fa-calendar-alt me-2"></i> Student Alternative Time Suggestions</span>
                  <span class="badge bg-danger student-alternatives-count notification-badge" style="display: none;"></span> <!-- Added notification-badge class -->
             </div>
        </div>
        <div class="card-body" id="tutor-student-alternatives-container">
             <!-- Alternatives will be loaded here via AJAX -->
             <div class="text-center p-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading student alternatives...</div>
        </div>
    </div>

</div> <!-- /.tutor-requests-section -->


<!-- Modals -->

<!-- Tutor Initiate Reschedule Modal -->
<div class="modal fade" id="tutorRescheduleModal" tabindex="-1" aria-labelledby="tutorRescheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tutorRescheduleModalLabel">Initiate Lesson Reschedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Form for tutor to initiate reschedule -->
                <div id="tutorRescheduleFormContainer">
                     <div id="tutorRescheduleSuccessMessage" class="alert alert-success" style="display: none;">
                         <p><i class="fas fa-check-circle"></i> Reschedule request sent successfully.</p>
                     </div>
                     <div id="tutorRescheduleErrorMessage" class="alert alert-danger" style="display: none;">
                         <p><i class="fas fa-exclamation-triangle"></i> Please fill in all required fields.</p>
                     </div>
                     <form id="tutorRescheduleForm" method="post">
                         <?php wp_nonce_field('submit_tutor_reschedule_request_nonce', 'submit_tutor_reschedule_request_nonce'); ?>
                         <input type="hidden" name="submit_tutor_reschedule_request" value="1">
                         <input type="hidden" name="tutor_id" value="<?php echo esc_attr($tutor_id); ?>">

                         <div class="mb-3">
                             <label for="tutor_reschedule_student_select" class="form-label">Select Student <span class="text-danger">*</span></label>
                             <select name="student_id" id="tutor_reschedule_student_select" class="form-select" required>
                                 <option value="">-- Select Student --</option>
                                 <?php
                                 $assigned_students = get_tutor_students($tutor_id); // Use helper function
                                 if (!empty($assigned_students)) {
                                     foreach ($assigned_students as $student) {
                                         echo '<option value="' . esc_attr($student->ID) . '">' . esc_html($student->display_name) . '</option>';
                                     }
                                 }
                                 ?>
                             </select>
                         </div>

                         <div class="mb-3">
                            <label for="tutor_reschedule_lesson_select" class="form-label">Lesson to Reschedule <span class="text-danger">*</span></label>
                             <select class="form-select" id="tutor_reschedule_lesson_select" name="lesson_select" required>
                                 <option value="">-- Select a Scheduled Lesson --</option>
                                 <?php
                                 // Note: Lessons could be dynamically loaded based on selected student via JS/AJAX
                                 // Or list all tutor's upcoming lessons initially
                                 $upcoming_lessons = get_upcoming_lessons_for_user($tutor_id, 'tutor'); // Fetch tutor's lessons
                                 if (!empty($upcoming_lessons)) {
                                     foreach ($upcoming_lessons as $lesson) {
                                          // Assume lesson contains student info if fetched this way, adjust if needed
                                         $student_name_lesson = isset($lesson['student_name']) ? ' (' . esc_html($lesson['student_name']) . ')' : '';
                                         echo '<option value="' . esc_attr($lesson['date_value']) . '|' . esc_attr($lesson['time_value']) . '|' . esc_attr($lesson['student_id']) . '">' // Include student ID
                                              . esc_html($lesson['subject']) . ' - ' . esc_html($lesson['formatted']) . $student_name_lesson . '</option>';
                                     }
                                 } else {
                                     echo '<option value="" disabled>No upcoming lessons found in your schedule.</option>';
                                 }
                                 ?>
                             </select>
                             <input type="hidden" id="tutor_reschedule_original_date" name="original_date">
                             <input type="hidden" id="tutor_reschedule_original_time" name="original_time">
                             <!-- Hidden field to store the correct student_id when a lesson is selected -->
                             <input type="hidden" id="tutor_reschedule_selected_student_id" name="student_id_from_lesson">
                         </div>

                         <div class="mb-3">
                             <label for="tutor_reschedule_reason" class="form-label">Reason for Reschedule <span class="text-danger">*</span></label>
                             <textarea class="form-control" id="tutor_reschedule_reason" name="reason" rows="3" required></textarea>
                         </div>

                         <div class="mb-3">
                             <label for="tutor_reschedule_proposed_date" class="form-label">Proposed New Date <span class="text-danger">*</span></label>
                             <input type="date" class="form-control" id="tutor_reschedule_proposed_date" name="proposed_date" required>
                         </div>

                         <div class="mb-3">
                             <label for="tutor_reschedule_proposed_time" class="form-label">Proposed New Time <span class="text-danger">*</span></label>
                             <input type="time" class="form-control" id="tutor_reschedule_proposed_time" name="proposed_time" required>
                         </div>

                         <div class="modal-footer">
                             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                             <button type="submit" class="btn btn-primary" id="submitTutorReschedule">Submit Request</button>
                         </div>
                    </form>
                </div> <!-- /#tutorRescheduleFormContainer -->
            </div>
        </div>
    </div>
</div>


<!-- Modal for Tutor to Accept/Decline/Propose Alternatives for Student Request -->
<!-- This modal might be dynamically populated by JS based on the clicked button -->
<div class="modal fade" id="tutorHandleStudentRequestModal" tabindex="-1" aria-labelledby="tutorHandleStudentRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tutorHandleStudentRequestModalLabel">Respond to Student Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="tutorHandleStudentRequestModalBody">
                <!-- Content loaded dynamically -->
                Loading request details...
            </div>
             <!-- Footer might also be dynamic, or contain generic close button -->
            <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                 <!-- Action buttons (Accept, Decline, Propose Alt) will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>


<!-- Modal for Tutor to Respond to Student Alternative Time Suggestions -->
<div class="modal fade" id="tutorRespondToAlternativesModal" tabindex="-1" aria-labelledby="tutorRespondToAlternativesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tutorRespondToAlternativesModalLabel">Respond to Student Alternatives</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <div class="modal-body" id="tutorRespondToAlternativesModalBody">
                 <!-- Content loaded dynamically based on which alternative suggestion was clicked -->
                Loading alternative suggestions...
             </div>
             <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                 <!-- Action buttons will be loaded dynamically -->
             </div>
        </div>
    </div>
</div>

<!-- Add Nonces for AJAX Actions -->
<?php wp_nonce_field('tutor_load_requests_nonce', 'tutor_load_requests_nonce_field'); ?>
<?php wp_nonce_field('tutor_delete_request_nonce', 'tutor_delete_request_nonce_field'); // Generic nonce for deleting outgoing ?>
<?php wp_nonce_field('tutor_handle_student_request_nonce', 'tutor_handle_student_request_nonce_field'); // For accept/decline/unavailable actions ?>
<?php wp_nonce_field('tutor_respond_alternatives_nonce', 'tutor_respond_alternatives_nonce_field'); // For handling student-proposed alternatives ?>
<?php wp_nonce_field('check_tutor_notifications_nonce', 'check_tutor_notifications_nonce_field'); // For notifications ?>

<script type="text/javascript">
    // Add JS to trigger AJAX calls on document ready or via buttons
    jQuery(document).ready(function($) {
        // Function to load content into a container
        function loadTutorRequestSection(action, nonce, containerId) {
            var container = $(containerId);
            if (!container.length) {
                 console.error("Container not found:", containerId);
                 return; // Exit if container doesn't exist
            }
            container.html('<div class="text-center p-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...</div>'); // Show loading spinner
            
            $.ajax({
                url: ajaxurl, // WordPress AJAX URL
                type: 'POST',
                data: {
                    action: action, // e.g., 'load_tutor_outgoing_requests'
                    nonce: nonce,
                    tutor_id: <?php echo json_encode($tutor_id); ?>
                },
                success: function(response) {
                    if(response.success && response.data && typeof response.data.html !== 'undefined') {
                        container.html(response.data.html);
                        // Safely reinitialize tooltips if Bootstrap and Tooltip are available
                        if (typeof bootstrap !== 'undefined' && typeof bootstrap.Tooltip !== 'undefined') {
                             try {
                                 var tooltipTriggerList = [].slice.call(container[0].querySelectorAll('[data-bs-toggle="tooltip"]'));
                                 var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                                     // Get existing instance or create new one
                                     return bootstrap.Tooltip.getOrCreateInstance(tooltipTriggerEl); 
                                 });
                             } catch (e) {
                                 console.warn("Error initializing tooltips:", e);
                             }
                        } else {
                            console.warn("Bootstrap Tooltip component not available when trying to initialize after loading", containerId);
                        }
                        // Trigger a custom event indicating content loaded for this section
                        $(document).trigger('tutorRequestSectionLoaded', { containerId: containerId, response: response.data });
                    } else {
                        console.error("AJAX Error for", action, ":", response.data?.message || 'Unknown error or missing HTML');
                        container.html('<div class="alert alert-danger">Error loading content: ' + (response.data?.message || 'Unknown error') + '</div>');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Network/Server Error for", action, ":", textStatus, errorThrown);
                    var errorMsg = 'Failed to load content.';
                    if (jqXHR.status === 403) {
                        errorMsg = 'Permission denied. Please ensure you are logged in.';
                    } else if (jqXHR.responseText) {
                         // Try to display server error if available (for debugging)
                         // errorMsg += "<br><small>Server Response: " + jqXHR.responseText.substring(0, 300) + "</small>"; 
                         console.error("Server Response: ", jqXHR.responseText);
                    }
                     container.html('<div class="alert alert-danger">' + errorMsg + '</div>');
                }
            });
        }

         // --- Initial Loads (with slight delay) ---
         // Use setTimeout to potentially avoid race conditions with other scripts/Bootstrap initialization
         setTimeout(function() {
             var loadNonce = $('#tutor_load_requests_nonce_field').val();
             var notificationNonce = $('#check_tutor_notifications_nonce_field').val();

             // Load Notifications
             if (notificationNonce) {
                 loadTutorRequestSection('load_tutor_notifications', notificationNonce, '#tutor-notifications-container');
             } else {
                 console.error("Tutor notification nonce not found.");
                 $('#tutor-notifications-container').html('<div class="alert alert-warning">Could not load notifications (security token missing).</div>');
             }

             // Load Outgoing Requests
             if (loadNonce) {
                 loadTutorRequestSection('load_tutor_outgoing_requests', loadNonce, '#tutor-outgoing-requests-container');
             } else {
                 console.error("Tutor load requests nonce not found.");
                 $('#tutor-outgoing-requests-container').html('<div class="alert alert-warning">Could not load outgoing requests (security token missing).</div>');
             }

             // Load Incoming Requests
             if (loadNonce) {
                 loadTutorRequestSection('load_tutor_incoming_requests', loadNonce, '#tutor-incoming-requests-container');
             } else {
                 $('#tutor-incoming-requests-container').html('<div class="alert alert-warning">Could not load incoming requests (security token missing).</div>');
             }

             // Load Student Alternatives
             if (loadNonce) {
                 loadTutorRequestSection('load_tutor_student_alternatives', loadNonce, '#tutor-student-alternatives-container');
             } else {
                 $('#tutor-student-alternatives-container').html('<div class="alert alert-warning">Could not load student alternatives (security token missing).</div>');
             }
         }, 100); // 100ms delay - adjust if needed


        // --- Event Handlers (Delegated for AJAX-loaded content) ---

        // Handle Tutor deleting their own outgoing request
        $(document).on('click', '#tutor-outgoing-requests-container .delete-tutor-request-btn', function() {
            var button = $(this);
            var requestId = button.data('request-id');
            // *** Get specific nonce from the button itself if available (more secure) ***
            var nonce = button.data('nonce') || $('#tutor_delete_request_nonce_field').val(); 

            if (!requestId || !nonce) {
                alert('Error: Could not identify request or security token.');
                console.error("Delete error: Missing request ID or nonce.", {requestId, nonce});
                return;
            }
            
             if (!confirm('Are you sure you want to cancel this reschedule request?')) {
                return;
            }

            button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tutor_delete_outgoing_request', // Specific AJAX action
                    request_id: requestId,
                    nonce: nonce // Send the specific or generic nonce
                },
                success: function(response) {
                    if (response.success) {
                        // alert('Request cancelled successfully.');
                        // Visually remove the row
                        button.closest('tr').fadeOut(300, function() { $(this).remove(); });
                        // Optionally show a success message somewhere else
                        // Refresh counts/notifications if necessary
                        var notificationNonce = $('#check_tutor_notifications_nonce_field').val();
                        if(notificationNonce) loadTutorRequestSection('load_tutor_notifications', notificationNonce, '#tutor-notifications-container');

                    } else {
                        alert('Error cancelling request: ' + (response.data?.message || 'Please try again.'));
                        button.prop('disabled', false).html('<i class="fa-solid fa-trash-can"></i> <span class="d-none d-md-inline">Cancel</span>'); // Restore button icon/text
                    }
                },
                error: function() {
                    alert('An error occurred while cancelling the request. Please try again.');
                    button.prop('disabled', false).html('<i class="fa-solid fa-trash-can"></i> <span class="d-none d-md-inline">Cancel</span>'); // Restore button icon/text
                }
            });
        });

         // Handle Tutor clicking Accept/Decline/Unavailable on an INCOMING student request
         $(document).on('click', '#tutor-incoming-requests-container .handle-student-request-btn', function() {
             var button = $(this);
             var requestId = button.data('request-id');
             var actionType = button.data('action'); // 'accept', 'decline', 'unavailable'
             var handleNonce = $('#tutor_handle_student_request_nonce_field').val();
             var modal = $('#tutorHandleStudentRequestModal');
             var modalBody = $('#tutorHandleStudentRequestModalBody');
             var modalTitle = $('#tutorHandleStudentRequestModalLabel');

             if (!requestId || !actionType || !handleNonce || !modal.length || !modalBody.length || !modalTitle.length) {
                 alert('Error: Cannot process request (missing data or modal elements).');
                 console.error("Handle student request error: Missing elements or data", {requestId, actionType, handleNonce, modalExists: modal.length > 0});
                 return;
             }

             var actionText = actionType.charAt(0).toUpperCase() + actionType.slice(1);
             if(actionType === 'unavailable') actionText = 'Propose Alternatives';
             modalTitle.text('Respond to Request: ' + actionText);
             modalBody.html('<div class="text-center p-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading details...</div>');
             
             // Show modal using Bootstrap 5 method
             var modalInstance = bootstrap.Modal.getOrCreateInstance(modal[0]);
             modalInstance.show();

             // AJAX call to get the modal content / form
             $.ajax({
                 url: ajaxurl,
                 type: 'POST',
                 data: {
                     action: 'get_tutor_handle_request_modal_content',
                     request_id: requestId,
                     action_type: actionType,
                     nonce: handleNonce
                 },
                 success: function(response) {
                     if (response.success && response.data && response.data.html) {
                         modalBody.html(response.data.html);
                         // Initialize components within the modal if necessary (e.g., datepickers)
                         // Safely initialize tooltips within the modal body
                         if (typeof bootstrap !== 'undefined' && typeof bootstrap.Tooltip !== 'undefined') {
                             var tooltipTriggerListModal = [].slice.call(modalBody[0].querySelectorAll('[data-bs-toggle="tooltip"]'));
                             tooltipTriggerListModal.map(function (tooltipTriggerEl) {
                                 return bootstrap.Tooltip.getOrCreateInstance(tooltipTriggerEl);
                             });
                         }
                     } else {
                         modalBody.html('<div class="alert alert-danger">' + (response.data?.message || 'Error loading details.') + '</div>');
                     }
                 },
                 error: function() {
                      modalBody.html('<div class="alert alert-danger">Failed to load request details. Please close and try again.</div>');
                 }
             });
         });

         // Handle form submissions INSIDE the 'Handle Student Request' modal
         $(document).on('submit', '#tutorHandleStudentRequestModal form.ajax-modal-form', function(e) {
             e.preventDefault();
             var form = $(this);
             var submitButton = form.find('button[type="submit"]');
             var responseDiv = form.find('.ajax-modal-response');
             var modal = form.closest('.modal');
             var ajaxAction = form.data('action'); // e.g., 'tutor_accept_student_request'

             if (!ajaxAction) {
                 console.error("Modal form submit error: Missing data-action attribute on form.");
                 responseDiv.html('<div class="alert alert-danger">Configuration error.</div>').show();
                 return;
             }

             // Store original button text/html
             var originalButtonContent = submitButton.html();
             submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
             responseDiv.hide().html('');

             var formData = form.serialize() + '&action=' + ajaxAction; // Add the WP AJAX action

             $.ajax({
                 url: ajaxurl,
                 type: 'POST',
                 data: formData,
                 success: function(response) {
                     if (response.success) {
                         responseDiv.html('<div class="alert alert-success">' + (response.data?.message || 'Action successful!') + '</div>').show();
                         // Refresh the main lists
                         var loadNonce = $('#tutor_load_requests_nonce_field').val();
                         if (loadNonce) {
                              loadTutorRequestSection('load_tutor_incoming_requests', loadNonce, '#tutor-incoming-requests-container');
                              loadTutorRequestSection('load_tutor_outgoing_requests', loadNonce, '#tutor-outgoing-requests-container'); // Refresh outgoing if action affects it
                              loadTutorRequestSection('load_tutor_student_alternatives', loadNonce, '#tutor-student-alternatives-container');
                         }
                          var notificationNonce = $('#check_tutor_notifications_nonce_field').val();
                          if(notificationNonce) loadTutorRequestSection('load_tutor_notifications', notificationNonce, '#tutor-notifications-container');
                          
                         // Close modal after delay
                         setTimeout(function() {
                             var modalInstance = bootstrap.Modal.getInstance(modal[0]);
                             if(modalInstance) modalInstance.hide();
                         }, 2000);
                     } else {
                         responseDiv.html('<div class="alert alert-danger">Error: ' + (response.data?.message || 'Could not complete action.') + '</div>').show();
                         submitButton.prop('disabled', false).html(originalButtonContent); // Restore button
                     }
                 },
                 error: function() {
                     responseDiv.html('<div class="alert alert-danger">AJAX error processing action.</div>').show();
                     submitButton.prop('disabled', false).html(originalButtonContent); // Restore button
                 }
             });
         });


         // Handle Tutor clicking a button related to STUDENT ALTERNATIVES (within accordion)
         // These buttons might now trigger AJAX directly OR open the modal
         // Based on the load_tutor_student_alternatives_ajax, buttons are inside an accordion.
         // Let's make them trigger AJAX actions directly for accept/decline_all/cancel_original

         $(document).on('click', '#tutor-student-alternatives-container .respond-to-student-alternative-btn', function() {
             var button = $(this);
             var altRequestId = button.data('alt-request-id');
             var actionType = button.data('action'); // 'accept', 'decline_all', 'cancel_original'
             var form = button.closest('form'); // Find the wrapping form
             var nonce = form.find('input[name="tutor_respond_alt_nonce"]').val(); // Get nonce from hidden field in the form
             var responseDiv = form.find('.ajax-modal-response'); // Find response div within the same form
             var selectedIndex = null;
             var ajaxAction = '';
             var confirmMsg = '';

             if (!altRequestId || !actionType || !nonce) {
                 alert('Error: Missing data for handling alternatives.');
                 console.error("Respond alternative error: Missing data", {altRequestId, actionType, nonce});
                 return;
             }

             if (actionType === 'accept') {
                 var selectedRadio = form.find('input.tutor-accept-alternative-radio:checked');
                 if (!selectedRadio.length) {
                     alert('Please select an alternative time to accept.');
                     return;
                 }
                 selectedIndex = selectedRadio.data('selected-index');
                 ajaxAction = 'tutor_accept_student_alternative';
                 confirmMsg = 'Are you sure you want to accept this alternative time?';
             } else if (actionType === 'decline_all') {
                 ajaxAction = 'tutor_decline_student_alternatives';
                 confirmMsg = 'Are you sure you want to decline all alternatives and cancel the original request?';
             } else if (actionType === 'cancel_original') {
                  ajaxAction = 'tutor_cancel_original_from_unavailable';
                  confirmMsg = 'Are you sure you want to acknowledge this and cancel the original request?';
             } else {
                 console.error("Unknown alternative action type:", actionType);
                 return;
             }

             if (!confirm(confirmMsg)) {
                 return;
             }
             
             // Store original button text/html
             var originalButtonContent = button.html();
             button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
             responseDiv.hide().html('');

             var data = {
                 action: ajaxAction,
                 alt_request_id: altRequestId,
                 nonce: nonce,
                 action_type: actionType // May not be strictly needed by handler, but good for context
             };
             if (selectedIndex !== null) {
                 data.selected_index = selectedIndex;
             }
             // TODO: Add reason field for decline_all if needed

             $.ajax({
                 url: ajaxurl,
                 type: 'POST',
                 data: data,
                 success: function(response) {
                      if (response.success) {
                         responseDiv.html('<div class="alert alert-success">' + (response.data?.message || 'Action successful!') + '</div>').show();
                         // Refresh relevant sections
                         var loadNonce = $('#tutor_load_requests_nonce_field').val();
                         if (loadNonce) {
                             // Delay slightly to allow changes to propagate before reload
                              setTimeout(function() {
                                 loadTutorRequestSection('load_tutor_outgoing_requests', loadNonce, '#tutor-outgoing-requests-container');
                                 loadTutorRequestSection('load_tutor_student_alternatives', loadNonce, '#tutor-student-alternatives-container');
                                  var notificationNonce = $('#check_tutor_notifications_nonce_field').val();
                                  if(notificationNonce) loadTutorRequestSection('load_tutor_notifications', notificationNonce, '#tutor-notifications-container');
                             }, 500); 
                         }
                     } else {
                         responseDiv.html('<div class="alert alert-danger">Error: ' + (response.data?.message || 'Could not complete action.') + '</div>').show();
                         button.prop('disabled', false).html(originalButtonContent); // Restore button
                     }
                 },
                 error: function() {
                     responseDiv.html('<div class="alert alert-danger">AJAX error processing action.</div>').show();
                     button.prop('disabled', false).html(originalButtonContent); // Restore button
                 }
                 // No 'complete' needed as success handles refresh
             });
         });

         // --- Tutor Initiate Reschedule Modal Form --- 
         // Add logic for tutor reschedule form lesson selection 
         $('#tutor_reschedule_lesson_select').on('change', function() {
             var selectedOption = $(this).find('option:selected');
             var value = selectedOption.val();
             if (value) {
                 var valueParts = value.split('|'); // date|time|student_id
                 if (valueParts.length === 3) {
                     $('#tutor_reschedule_original_date').val(valueParts[0]);
                     $('#tutor_reschedule_original_time').val(valueParts[1]);
                     // Update the hidden student ID field *AND* the visible select (for clarity, though hidden is used for submit)
                     var studentIdFromLesson = valueParts[2];
                     $('#tutor_reschedule_selected_student_id').val(studentIdFromLesson);
                     $('#tutor_reschedule_student_select').val(studentIdFromLesson); 
                 } else {
                     console.warn("Lesson option value format unexpected:", value);
                     $('#tutor_reschedule_original_date').val('');
                     $('#tutor_reschedule_original_time').val('');
                     $('#tutor_reschedule_selected_student_id').val('');
                 }
            } else {
                 $('#tutor_reschedule_original_date').val('');
                 $('#tutor_reschedule_original_time').val('');
                 $('#tutor_reschedule_selected_student_id').val('');
            }
         });
          // Ensure student select also updates hidden field if changed manually (edge case)
          $('#tutor_reschedule_student_select').on('change', function() {
               var selectedStudentId = $(this).val();
               // Only update the hidden field if the lesson select is NOT set (otherwise lesson dictates)
               if (!$('#tutor_reschedule_lesson_select').val()) {
                    $('#tutor_reschedule_selected_student_id').val(selectedStudentId);
               }
               // TODO: Optionally filter the lesson dropdown based on the selected student here?
          });

         // Handle Tutor Initiate Reschedule Form Submission via AJAX
         $('#tutorRescheduleForm').on('submit', function(e) {
             e.preventDefault(); 

             var form = $(this);
             var submitButton = $('#submitTutorReschedule');
             var successMsg = $('#tutorRescheduleSuccessMessage');
             var errorMsg = $('#tutorRescheduleErrorMessage');

             // --- Basic Frontend Validation --- 
             let isValid = true;
             const requiredFields = form.find('[required]');
             requiredFields.each(function() {
                 if (!$(this).val()) {
                      isValid = false;
                      $(this).addClass('is-invalid'); // Highlight invalid fields
                 } else {
                      $(this).removeClass('is-invalid');
                 }
             });
             // Ensure hidden student ID is set (should be by lesson select)
             var studentIdSelected = $('#tutor_reschedule_selected_student_id').val();
             if (!studentIdSelected) {
                  isValid = false;
                  $('#tutor_reschedule_student_select').addClass('is-invalid'); // Highlight student select
                  console.warn("Tutor Reschedule: No student ID selected/derived from lesson.")
             } else {
                  $('#tutor_reschedule_student_select').removeClass('is-invalid');
             }

             if (!isValid) {
                  errorMsg.html('<p><i class="fas fa-exclamation-triangle"></i> Please fill in all required fields (marked with *).</p>').show();
                  successMsg.hide();
                  return; // Stop submission
             }
             // --- End Validation --- 

             var originalButtonContent = submitButton.html();
             submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...');
             successMsg.hide();
             errorMsg.hide();
             form.find('.is-invalid').removeClass('is-invalid'); // Clear validation classes

             // Prepare form data, ensuring the correct student ID is used
             var formData = form.serializeArray();
              // Remove student_id from the select if it exists, use the hidden one
             formData = formData.filter(item => item.name !== 'student_id'); 
             formData.push({ name: 'student_id', value: studentIdSelected }); 
             // Add the AJAX action
             formData.push({ name: 'action', value: 'tutor_initiate_reschedule_ajax' });

             $.ajax({
                 url: ajaxurl, 
                 type: 'POST',
                 data: $.param(formData), // Serialize the array correctly for POST
                 success: function(response) {
                     if (response.success) {
                         successMsg.html('<p><i class="fas fa-check-circle"></i> ' + (response.data?.message || 'Request submitted successfully.') + '</p>').show();
                         form[0].reset(); 
                         $('#tutor_reschedule_original_date').val(''); 
                         $('#tutor_reschedule_original_time').val('');
                         $('#tutor_reschedule_selected_student_id').val('');
                         
                         setTimeout(function() {
                             var modalInstance = bootstrap.Modal.getInstance($('#tutorRescheduleModal')[0]);
                             if (modalInstance) modalInstance.hide();
                             successMsg.hide(); // Hide message on close
                         }, 2500);
                         // Refresh relevant lists
                         var loadNonce = $('#tutor_load_requests_nonce_field').val();
                         if (loadNonce) loadTutorRequestSection('load_tutor_outgoing_requests', loadNonce, '#tutor-outgoing-requests-container');
                         var notificationNonce = $('#check_tutor_notifications_nonce_field').val();
                         if(notificationNonce) loadTutorRequestSection('load_tutor_notifications', notificationNonce, '#tutor-notifications-container');

                     } else {
                         errorMsg.html('<p><i class="fas fa-exclamation-triangle"></i> ' + (response.data?.message || 'Error submitting request.') + '</p>').show();
                     }
                 },
                 error: function() {
                      errorMsg.html('<p><i class="fas fa-exclamation-triangle"></i> An unexpected network or server error occurred. Please try again.</p>').show();
                 },
                 complete: function() {
                      // Only restore button if error occurred
                      if (errorMsg.is(':visible')) {
                          submitButton.prop('disabled', false).html(originalButtonContent);
                      } else {
                           // Keep it disabled on success until modal closes
                           submitButton.html(originalButtonContent); // Restore content but keep disabled
                      }
                 }
             });
         });


         // Add smooth scroll for notification links
         $(document).on('click', 'a.scroll-to', function(e) {
             e.preventDefault();
             var targetId = $(this).attr('href'); // Get the target ID like #some-element
             var targetElement = $(targetId);
             if (targetElement.length) {
                 $('html, body').animate({
                     scrollTop: targetElement.offset().top - 100 // Adjust offset as needed (e.g., for fixed headers)
                 }, 500);
             }
         });

    }); // end document ready
</script>


<style>
    /* Optional: Styles specific to tutor requests */
    .request-table th, .request-table td {
        vertical-align: middle;
    }
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
        border-width: .2em;
    }
    /* Style for notification badge (match student) */
    .notification-badge {
        font-size: 0.75rem;
        vertical-align: super; /* or adjust as needed */
        /* padding: 0.2em 0.4em; */
    }
    .request-actions .btn-group-sm .btn {
        /* Ensure buttons in groups don't wrap unnecessarily on small screens */
        white-space: nowrap;
    }
     .accordion-button:not(.collapsed) {
        /* Match student style? */
        /* color: #0c63e4; */
        /* background-color: #e7f1ff; */
    }
</style>