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
                                         // Use array access based on debug log warning - corrected key
                                         echo '<option value="' . esc_attr($student['id']) . '">' . esc_html($student['display_name']) . '</option>';
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