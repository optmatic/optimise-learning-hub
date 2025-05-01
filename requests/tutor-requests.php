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
    <div class="initiate-request-section mb-4 text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tutorRescheduleModal">
            <i class="fa-regular fa-calendar-plus me-2"></i>Initiate Lesson Reschedule Request
        </button>
    </div>

    <!-- Outgoing Reschedule Requests Card -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <div class="d-flex justify-content-between align-items-center">
                 <span><i class="fas fa-arrow-left me-2"></i> My Outgoing Reschedule Requests</span>
                 <!-- Optional: Add a refresh button -->
                 <!-- <button class="btn btn-sm btn-light refresh-outgoing-tutor-requests"><i class="fas fa-sync-alt"></i></button> -->
            </div>
        </div>
        <div class="card-body" id="tutor-outgoing-requests-container">
             <!-- Outgoing requests will be loaded here via AJAX -->
             <div class="text-center"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading outgoing requests...</div>
        </div>
    </div>

    <!-- Incoming Requests Card -->
    <div class="card mb-4">
         <div class="card-header bg-warning text-dark">
            <div class="d-flex justify-content-between align-items-center">
                 <span><i class="fas fa-arrow-right me-2"></i> Incoming Requests from Students</span>
                 <span class="badge bg-danger incoming-student-request-count" style="display: none;"></span> <!-- Badge updated by AJAX -->
            </div>
        </div>
        <div class="card-body" id="tutor-incoming-requests-container">
            <!-- Incoming requests will be loaded here via AJAX -->
            <div class="text-center"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading incoming requests...</div>
        </div>
    </div>

    <!-- Student Alternative Time Suggestions Card -->
    <div class="card mb-4" id="student-alternatives-card-wrapper"> <!-- Added wrapper for potential AJAX replacement -->
        <div class="card-header bg-secondary text-white">
             <div class="d-flex justify-content-between align-items-center">
                 <span><i class="fas fa-calendar-alt me-2"></i> Student Alternative Time Suggestions</span>
                  <span class="badge bg-danger student-alternatives-count" style="display: none;"></span> <!-- Badge updated by AJAX -->
             </div>
        </div>
        <div class="card-body" id="tutor-student-alternatives-container">
             <!-- Alternatives will be loaded here via AJAX -->
             <div class="text-center"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading student alternatives...</div>
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
            $(containerId).html('<div class="text-center"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...</div>'); // Show loading spinner
            $.ajax({
                url: ajaxurl, // WordPress AJAX URL
                type: 'POST',
                data: {
                    action: action, // e.g., 'load_tutor_outgoing_requests'
                    nonce: nonce,
                    // Add other parameters if needed, like tutor_id
                     tutor_id: <?php echo json_encode($tutor_id); ?>
                },
                success: function(response) {
                    if(response.success) {
                        $(containerId).html(response.data.html);
                        // Reinitialize tooltips if needed
                        if (typeof bootstrap !== 'undefined') {
                             var tooltipTriggerList = [].slice.call(document.querySelectorAll(containerId + ' [data-bs-toggle="tooltip"]'));
                             var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                                 return new bootstrap.Tooltip(tooltipTriggerEl);
                             });
                        }
                    } else {
                        $(containerId).html('<div class="alert alert-danger">Error loading requests: ' + (response.data.message || 'Unknown error') + '</div>');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error:", textStatus, errorThrown);
                    $(containerId).html('<div class="alert alert-danger">Failed to load requests. Please try again later.</div>');
                }
            });
        }

         // --- Initial Loads ---
         var loadNonce = $('#tutor_load_requests_nonce_field').val();
         var notificationNonce = $('#check_tutor_notifications_nonce_field').val();

         // Load Notifications
         if (notificationNonce) {
             loadTutorRequestSection('load_tutor_notifications', notificationNonce, '#tutor-notifications-container');
         } else {
              console.error("Tutor notification nonce not found.");
             $('#tutor-notifications-container').html('<div class="alert alert-warning">Could not load notifications (nonce missing).</div>');
         }

         // Load Outgoing Requests
         if (loadNonce) {
             loadTutorRequestSection('load_tutor_outgoing_requests', loadNonce, '#tutor-outgoing-requests-container');
         } else {
             console.error("Tutor load requests nonce not found.");
             $('#tutor-outgoing-requests-container').html('<div class="alert alert-warning">Could not load outgoing requests (nonce missing).</div>');
         }

         // Load Incoming Requests
         if (loadNonce) {
             loadTutorRequestSection('load_tutor_incoming_requests', loadNonce, '#tutor-incoming-requests-container');
         } else {
             // Nonce error already logged
             $('#tutor-incoming-requests-container').html('<div class="alert alert-warning">Could not load incoming requests (nonce missing).</div>');
         }

         // Load Student Alternatives
         if (loadNonce) {
              loadTutorRequestSection('load_tutor_student_alternatives', loadNonce, '#tutor-student-alternatives-container');
         } else {
             // Nonce error already logged
              $('#tutor-student-alternatives-container').html('<div class="alert alert-warning">Could not load student alternatives (nonce missing).</div>');
         }

        // --- Event Handlers (Delegated for AJAX-loaded content) ---

        // Handle Tutor deleting their own outgoing request
        $('#tutor-outgoing-requests-container').on('click', '.delete-tutor-request-btn', function() {
            if (!confirm('Are you sure you want to cancel this reschedule request?')) {
                return;
            }
            var button = $(this);
            var requestId = button.data('request-id');
            var nonce = $('#tutor_delete_request_nonce_field').val(); // Use the generic delete nonce

            if (!requestId || !nonce) {
                alert('Error: Could not identify request or security token.');
                return;
            }

            button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tutor_delete_outgoing_request', // Specific AJAX action
                    request_id: requestId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Request cancelled successfully.');
                        // Reload the outgoing requests section
                        loadTutorRequestSection('load_tutor_outgoing_requests', loadNonce, '#tutor-outgoing-requests-container');
                    } else {
                        alert('Error cancelling request: ' + (response.data.message || 'Please try again.'));
                        button.prop('disabled', false).html('<i class="fa-solid fa-trash-can"></i>'); // Restore button
                    }
                },
                error: function() {
                    alert('An error occurred while cancelling the request. Please try again.');
                    button.prop('disabled', false).html('<i class="fa-solid fa-trash-can"></i>'); // Restore button
                }
            });
        });

         // Handle Tutor clicking Accept/Decline/Unavailable on an INCOMING student request
         $('#tutor-incoming-requests-container').on('click', '.handle-student-request-btn', function() {
             var requestId = $(this).data('request-id');
             var actionType = $(this).data('action'); // 'accept', 'decline', 'unavailable'
             var handleNonce = $('#tutor_handle_student_request_nonce_field').val();
             var modal = $('#tutorHandleStudentRequestModal');
             var modalBody = $('#tutorHandleStudentRequestModalBody');
             var modalTitle = $('#tutorHandleStudentRequestModalLabel');

             if (!requestId || !actionType || !handleNonce) {
                 alert('Error: Missing data to handle request.');
                 return;
             }

             modalTitle.text('Respond to Request: ' + actionType.charAt(0).toUpperCase() + actionType.slice(1));
             modalBody.html('<div class="text-center p-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading details...</div>');
             modal.modal('show');

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
                     if (response.success) {
                         modalBody.html(response.data.html);
                         // Re-initialize any JS needed inside the modal (like form validation, date pickers)
                     } else {
                         modalBody.html('<div class="alert alert-danger">' + (response.data.message || 'Error loading details.') + '</div>');
                     }
                 },
                 error: function() {
                      modalBody.html('<div class="alert alert-danger">Failed to load request details. Please close and try again.</div>');
                 }
             });
         });

         // Handle Tutor clicking a button related to STUDENT ALTERNATIVES
         $('#tutor-student-alternatives-container').on('click', '.respond-to-student-alternative-btn', function() {
             var altRequestId = $(this).data('alt-request-id'); // The ID of the 'tutor_unavailable' post
             var actionType = $(this).data('action'); // e.g., 'accept', 'decline_all'
             var respondNonce = $('#tutor_respond_alternatives_nonce_field').val();
             var modal = $('#tutorRespondToAlternativesModal');
             var modalBody = $('#tutorRespondToAlternativesModalBody');
             var modalTitle = $('#tutorRespondToAlternativesModalLabel');

             if (!altRequestId || !actionType || !respondNonce) {
                  alert('Error: Missing data to handle alternatives.');
                  return;
             }

             modalTitle.text('Respond to Alternatives: ' + actionType.charAt(0).toUpperCase() + actionType.slice(1));
              modalBody.html('<div class="text-center p-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading details...</div>');
             modal.modal('show');

              // AJAX call to get the modal content / form
             $.ajax({
                 url: ajaxurl,
                 type: 'POST',
                 data: {
                     action: 'get_tutor_respond_alternatives_modal_content',
                     alt_request_id: altRequestId,
                     action_type: actionType,
                     nonce: respondNonce
                 },
                 success: function(response) {
                     if (response.success) {
                         modalBody.html(response.data.html);
                     } else {
                          modalBody.html('<div class="alert alert-danger">' + (response.data.message || 'Error loading details.') + '</div>');
                     }
                 },
                 error: function() {
                      modalBody.html('<div class="alert alert-danger">Failed to load alternative details. Please close and try again.</div>');
                 }
             });
         });


         // Add logic for tutor reschedule form lesson selection if needed
         $('#tutor_reschedule_lesson_select').on('change', function() {
             var selectedOption = $(this).find('option:selected');
             var valueParts = selectedOption.val().split('|'); // date|time|student_id
             if (valueParts.length === 3) {
                 $('#tutor_reschedule_original_date').val(valueParts[0]);
                 $('#tutor_reschedule_original_time').val(valueParts[1]);
                 // Update the hidden student ID field based on the selected lesson
                 $('#tutor_reschedule_selected_student_id').val(valueParts[2]);
                 // Also update the main student select dropdown if desired, though hidden field is safer for submission
                 // $('#tutor_reschedule_student_select').val(valueParts[2]);
             } else {
                 $('#tutor_reschedule_original_date').val('');
                 $('#tutor_reschedule_original_time').val('');
                 $('#tutor_reschedule_selected_student_id').val('');
             }
         });

         // Handle Tutor Initiate Reschedule Form Submission (using AJAX is better than POST for modals)
         $('#tutorRescheduleForm').on('submit', function(e) {
             e.preventDefault(); // Prevent default form submission

             var form = $(this);
             var submitButton = $('#submitTutorReschedule');
             var successMsg = $('#tutorRescheduleSuccessMessage');
             var errorMsg = $('#tutorRescheduleErrorMessage');

             submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...');
             successMsg.hide();
             errorMsg.hide();

             // Ensure the correct student ID is included (from the hidden field updated by lesson selection)
             var formData = form.serializeArray();
             var studentIdFromLesson = $('#tutor_reschedule_selected_student_id').val();
             if(studentIdFromLesson) {
                 // Remove the possibly incorrect student_id from the select dropdown if it exists
                 formData = formData.filter(item => item.name !== 'student_id');
                 // Add the correct one from the hidden field
                 formData.push({ name: 'student_id', value: studentIdFromLesson });
             } else {
                 // If no lesson selected, rely on the main student dropdown (though lesson should be required)
                 console.warn("Submitting tutor reschedule without a specific lesson selected.");
             }


             $.ajax({
                 url: ajaxurl, // Use admin-ajax.php
                 type: 'POST',
                 data: $.param(formData) + '&action=tutor_initiate_reschedule_ajax', // Add AJAX action
                 success: function(response) {
                     if (response.success) {
                         successMsg.show();
                         form[0].reset(); // Clear the form
                         $('#tutor_reschedule_original_date').val(''); // Clear hidden fields too
                         $('#tutor_reschedule_original_time').val('');
                         $('#tutor_reschedule_selected_student_id').val('');
                         // Optionally close modal after delay
                         setTimeout(function() {
                             $('#tutorRescheduleModal').modal('hide');
                             successMsg.hide(); // Hide message on close
                         }, 2500);
                         // Reload outgoing requests list
                         loadTutorRequestSection('load_tutor_outgoing_requests', loadNonce, '#tutor-outgoing-requests-container');
                     } else {
                         errorMsg.html('<p><i class="fas fa-exclamation-triangle"></i> ' + (response.data.message || 'Error submitting request.') + '</p>').show();
                     }
                 },
                 error: function() {
                      errorMsg.html('<p><i class="fas fa-exclamation-triangle"></i> An unexpected error occurred. Please try again.</p>').show();
                 },
                 complete: function() {
                      submitButton.prop('disabled', false).text('Submit Request');
                 }
             });
         });


         // Add more handlers for other forms/buttons inside modals if they also need AJAX submission

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
    /* Style for notification badge */
    .notification-badge {
        font-size: 0.75rem;
        vertical-align: super;
    }
</style>