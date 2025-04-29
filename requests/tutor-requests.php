<?php
// Ensure this file is loaded within WordPress context
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Ensure helper functions are available
// require_once dirname(__DIR__) . '/requests/request-functions.php'; 

// Get current tutor data
$current_tutor_id = get_current_user_id();
$current_tutor = wp_get_current_user();
?>
    <h4>Reschedule Requests</h4>

    <!-- Notifications Section - populated by AJAX (check_tutor_incoming_requests_ajax) -->
    <div class="mb-4" id="tutorRequestNotifications">
         <!-- Placeholder or loading indicator -->
         <div class="alert alert-light">Loading notifications...</div>
         <?php
         /* 
         // PHP fallback (optional)
         $pending_student_request_count = get_pending_request_count($current_tutor_id, 'tutor', 'student_reschedule');
         $pending_alternatives_count = get_pending_alternatives_count($current_tutor_id, 'tutor');

         if ($pending_student_request_count > 0 || $pending_alternatives_count > 0): ?>
             <div class="alert alert-info">
                 <h5><i class="fas fa-bell me-2"></i>Notifications</h5>
                 <ul class="mb-0" style="list-style: none; padding-left: 0;">
                     <?php if ($pending_student_request_count > 0): ?>
                         <li>
                             <i class="fas fa-arrow-right me-1 text-warning"></i> You have <strong><?php echo $pending_student_request_count; ?></strong> pending reschedule request<?php echo ($pending_student_request_count > 1 ? 's' : ''); ?> from students.
                             <a href="#incomingRequestsSection" class="btn btn-sm btn-primary ms-2">View</a>
                         </li>
                     <?php endif; ?>
                     <?php if ($pending_alternatives_count > 0): ?>
                         <li class="mt-2"> 
                             <i class="fas fa-exchange-alt me-1 text-primary"></i> You have <strong><?php echo $pending_alternatives_count; ?></strong> alternative time suggestion<?php echo ($pending_alternatives_count > 1 ? 's' : ''); ?> from students.
                             <a href="#alternativeAccordion" class="btn btn-sm btn-primary ms-2">View</a>
                         </li>
                     <?php endif; ?>
                 </ul>
             </div>
         <?php endif;
         */
         ?>
    </div>

    <!-- Add Tutor Reschedule Request -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
             <i class="fas fa-plus-circle me-2"></i> Initiate Lesson Reschedule
        </div>
        <div class="card-body">
            <p>Initiate a reschedule request for an upcoming lesson with one of your students.</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTutorRescheduleRequestModal">
                Initiate Lesson Reschedule
            </button>
        </div>
    </div>

    <!-- Outgoing Reschedule Requests (Tutor-initiated) -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-arrow-left me-2"></i> Your Outgoing Reschedule Requests
        </div>
        <div class="card-body tutor-outgoing-requests-section">
            <?php
            $tutor_requests = get_reschedule_requests('tutor_reschedule', $current_tutor_id, 'tutor');
            
            if (!empty($tutor_requests)) {
                echo '<div class="table-responsive" style="max-height: 350px; overflow-y: auto;">';
                echo '<table class="table table-striped table-hover">';
                echo '<thead class="table-light"><tr>
                    <th>Date Requested</th>
                    <th>Lesson</th>
                    <th>Proposed Times</th>
                    <th>Student</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr></thead>';
                echo '<tbody class="tutor-requests-table-body">';
                
                foreach ($tutor_requests as $request) {
                    $request_id = $request->ID;
                    $student_id = get_post_meta($request_id, 'student_id', true);
                    $student_name = get_post_meta($request_id, 'student_name', true); 
                    $original_date = get_post_meta($request_id, 'original_date', true);
                    $original_time = get_post_meta($request_id, 'original_time', true);
                    $status = get_post_meta($request_id, 'status', true);
                    $request_date = get_the_date('M j, Y', $request_id);
                    $reason = get_post_meta($request_id, 'reason', true);
                    $preferred_times = get_post_meta($request_id, 'preferred_times', true); // Tutor's proposed times
                    $student_response = get_post_meta($request_id, 'student_response', true); // For declined reasons

                    $student_display_name = 'N/A';
                    if($student_id) {
                        $student_user = get_user_by('id', $student_id);
                        if ($student_user) {
                             $student_display_name = get_student_display_name($student_user->user_login);
                        }
                    } elseif ($student_name) {
                        $student_display_name = get_student_display_name($student_name);
                    }

                    $formatted_original = format_datetime($original_date, $original_time);
                    $status_badge = get_status_badge($status);
                    $notification = '';

                    // --- Status Specific Logic ---
                     if ($status === 'declined' || $status === 'student_declined') {
                         if (!empty($student_response)) {
                             $notification = '<div class="mt-1"><small class="text-danger"><i class="fas fa-info-circle"></i> Student response: ' . esc_html($student_response) . '</small></div>';
                         }
                     } elseif ($status === 'student_unavailable') { // Student marked unavailable, check for alternatives from them
                          $alternative_requests = get_posts([
                              'post_type' => 'progress_report',
                              'posts_per_page' => 1,
                              'meta_query' => [
                                  'relation' => 'AND',
                                  ['key' => 'original_request_id', 'value' => $request_id, 'compare' => '='],
                                  ['key' => 'request_type', 'value' => 'reschedule_alternatives', 'compare' => '='], // Student provides alternatives
                                  ['key' => 'status', 'value' => 'pending', 'compare' => '='] 
                              ],
                              'fields' => 'ids'
                          ]);
                          if (!empty($alternative_requests)) {
                             $status_badge = get_status_badge('student_unavailable');
                              $notification = '<div class="mt-1"><small class="text-info"><i class="fas fa-info-circle"></i> Student proposed alternative times.</small></div>';
                              $notification .= '<a href="#alternativeAccordion" class="btn btn-sm btn-outline-primary mt-1">View Alternatives</a>';
                          } else {
                              $notification = '<div class="mt-1"><small class="text-warning"><i class="fas fa-info-circle"></i> Student was unavailable for the proposed time.</small></div>';
                          }
                      } 

                    echo '<tr data-request-id="' . $request_id . '">';
                    echo '<td>' . esc_html($request_date) . '</td>';
                    echo '<td>' . esc_html($formatted_original) . '</td>';
                    echo '<td>';
                    if (!empty($preferred_times) && is_array($preferred_times)) {
                        foreach ($preferred_times as $index => $time) {
                            if (!empty($time['date']) && !empty($time['time'])) {
                                echo '<div><small>Option ' . ($index + 1) . ': ' . esc_html(format_datetime($time['date'], $time['time'])) . '</small></div>';
                            }
                        }
                    } else {
                        echo '-';
                    }
                    echo '</td>';
                    echo '<td>' . esc_html($student_display_name) . '</td>';
                    echo '<td>';
                    if (!empty($reason)) {
                        $truncated_reason = strlen($reason) > 30 ? substr($reason, 0, 30) . '...' : $reason;
                        echo '<span class="reason-text" style="cursor: pointer; color: #0d6efd;" 
                               data-bs-toggle="modal" data-bs-target="#reasonModal" 
                               data-reason="' . esc_attr($reason) . '" 
                               data-tooltip="tooltip" title="Click to view full reason">' . esc_html($truncated_reason) . '</span>';
                    } else {
                        echo '<em>-</em>';
                    }
                    echo '</td>';
                    echo '<td>' . $status_badge . $notification . '</td>';
                    echo '<td class="request-actions">';
                    
                    // Show edit/delete only for pending requests
                    if ($status === 'pending') {
                         echo '<button type="button" class="btn btn-sm btn-primary me-1 edit-tutor-request-btn" 
                             data-bs-toggle="modal" 
                             data-bs-target="#editTutorRescheduleRequestModal" 
                             data-request-id="' . $request_id . '"
                             data-student-id="' . esc_attr($student_id) . '" 
                             data-student-name="' . esc_attr($student_display_name) . '" 
                             data-original-date="' . esc_attr($original_date) . '"
                             data-original-time="' . esc_attr($original_time) . '"
                             data-reason="' . esc_attr($reason) . '"
                             data-preferred-times=\''. esc_attr(json_encode($preferred_times)) .'\'
                             aria-label="Edit Request">
                             <i class="fas fa-edit"></i> <span class="d-none d-md-inline">Edit</span>
                         </button>';
                         
                         echo '<button type="button" class="btn btn-sm btn-danger delete-tutor-request-btn" 
                                 data-request-id="' . $request_id . '" 
                                 data-nonce="' . wp_create_nonce('delete_tutor_request_' . $request_id) . '" 
                                 aria-label="Delete Request">
                                 <i class="fas fa-trash"></i> <span class="d-none d-md-inline">Delete</span>
                         </button>';
                    } else {
                        // Archive logic (similar to student view)
                        echo '-';
                    }
                    echo '</td></tr>';
                }
                echo '</tbody></table>';
                echo '</div>';
            } else {
                echo '<p>You have not initiated any reschedule requests yet.</p>';
            }
            ?>
        </div>
    </div>
    
    <!-- Incoming Reschedule Requests (Student-initiated) -->
    <div class="card mb-4" id="incomingRequestsSection">
        <div class="card-header bg-warning text-dark">
             <div class="d-flex justify-content-between align-items-center">
                 <span><i class="fas fa-arrow-right me-2"></i> Incoming Reschedule Requests from Students</span>
                 <span class="badge bg-danger incoming-student-request-count" style="display: none;"></span> <!-- Updated by AJAX -->
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                <table class="table table-striped table-hover">
                    <thead class="table-light"><tr><th>Date Requested</th><th>Original Lesson</th><th>Preferred Times</th><th>Student</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody class="incoming-requests-table-body">
                         <!-- Content loaded via AJAX (check_tutor_incoming_requests_ajax) -->
                         <tr><td colspan="7"><p>Loading incoming requests...</p></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Student Alternative Times Section -->
     <div id="studentAlternativeTimesSectionWrapper">
        <?php
        // Get pending alternative time suggestions from students (request_type = reschedule_alternatives)
        // Only get PENDING ones for the accordion action items
        $alternative_requests = get_reschedule_requests('reschedule_alternatives', $current_tutor_id, 'tutor', 'pending');
        
        if (!empty($alternative_requests)) {
            $pending_alternatives = count($alternative_requests);
        ?>
            <div class="card mb-4" id="alternativeAccordion">
                <div class="card-header bg-secondary text-white">
                     <div class="d-flex justify-content-between align-items-center">
                         <span><i class="fas fa-exchange-alt me-2"></i> Student Alternative Time Suggestions</span>
                          <?php if ($pending_alternatives > 0): ?>
                             <span class="badge bg-danger student-alternatives-count"><?php echo $pending_alternatives; ?></span>
                         <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                     <?php if ($pending_alternatives > 0) : ?>
                         <div class="alert alert-info mb-3">
                              <i class="fas fa-bell me-2"></i> <strong>Action Required:</strong> A student couldn't make your proposed time and suggested alternatives below. Please review and respond.
                         </div>
                     <?php endif; ?>
                    
                    <div class="accordion" id="studentAlternativeAccordion">
                        <?php 
                        $counter = 1;
                        foreach ($alternative_requests as $request) {
                            $request_id = $request->ID;
                            $original_tutor_request_id = get_post_meta($request_id, 'original_request_id', true);
                            $student_id = get_post_meta($request_id, 'student_id', true);
                            $student_name = get_post_meta($request_id, 'student_name', true); // Login name
                            $alternatives = get_post_meta($request_id, 'alternatives', true); // Student's alternatives
                            $status = get_post_meta($request_id, 'status', true);
                            $request_date = get_the_date('F j, Y', $request_id);
                            
                             $student_display_name = get_student_display_name($student_name ?: get_user_by('id', $student_id)->user_login);
                            
                            // Get original lesson details from the TUTOR's initial request
                            $original_date = get_post_meta($original_tutor_request_id, 'original_date', true);
                            $original_time = get_post_meta($original_tutor_request_id, 'original_time', true);
                            $formatted_original = format_datetime($original_date, $original_time, 'l, jS F Y \\a\\t g:i A');
                            
                            $is_pending = ($status === 'pending');
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="studentAltHeading<?php echo $counter; ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#studentAltCollapse<?php echo $counter; ?>" aria-expanded="false" 
                                        aria-controls="studentAltCollapse<?php echo $counter; ?>">
                                     Alternatives from <?php echo esc_html($student_display_name); ?> (Requested: <?php echo esc_html($request_date); ?>)
                                    <?php if ($is_pending) echo '<span class="badge bg-warning ms-2">Action Required</span>'; ?>
                                </button>
                            </h2>
                            <div id="studentAltCollapse<?php echo $counter; ?>" class="accordion-collapse collapse" 
                                    aria-labelledby="studentAltHeading<?php echo $counter; ?>" data-bs-parent="#studentAlternativeAccordion">
                                <div class="accordion-body">
                                    <div class="card mb-3 bg-light">
                                         <div class="card-body">
                                             <p class="mb-1"><small>Regarding your request to reschedule:</small></p>
                                             <p><strong><?php echo esc_html($formatted_original); ?></strong></p>
                                         </div>
                                    </div>
                                    
                                     <?php if ($is_pending && !empty($alternatives) && is_array($alternatives)) : ?>
                                         <p>Please select one of the student's suggested alternative times:</p>
                                         <form method="post">
                                              <?php wp_nonce_field('select_alternative_' . $request_id); ?>
                                              <input type="hidden" name="select_alternative" value="1">
                                              <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                                              
                                              <div class="list-group mb-3">
                                                  <?php foreach ($alternatives as $index => $alternative) : 
                                                      if (empty($alternative['date']) || empty($alternative['time'])) continue;
                                                      $formatted_alt = format_datetime($alternative['date'], $alternative['time'], 'l, jS F Y \\a\\t g:i A');
                                                  ?>
                                                      <label class="list-group-item list-group-item-action" for="student_alt_<?php echo $request_id; ?>_<?php echo $index; ?>">
                                                          <input class="form-check-input me-1" type="radio" name="selected_alternative" 
                                                                 value="<?php echo $index; ?>" id="student_alt_<?php echo $request_id; ?>_<?php echo $index; ?>" <?php checked($index, 0); ?>>
                                                          Option <?php echo ($index + 1); ?>: <?php echo esc_html($formatted_alt); ?>
                                                      </label>
                                                  <?php endforeach; ?>
                                              </div>
                                              
                                              <button type="submit" class="btn btn-success">Confirm Selected Time</button>
                                              <!-- Option to say none work? Requires another flow -->
                                              <!-- <button type="button" class="btn btn-outline-danger ms-2 none-work-btn">None of these work</button> -->
                                          </form>
                                     <?php elseif (!$is_pending): ?>
                                         <?php 
                                         // Show confirmed status
                                         $final_status = get_post_meta($request_id, 'status', true);
                                         if ($final_status === 'confirmed') {
                                             $selected_index = get_post_meta($request_id, 'selected_alternative', true);
                                             if (isset($alternatives[$selected_index])) {
                                                 $selected_alt = $alternatives[$selected_index];
                                                 $formatted_selected = format_datetime($selected_alt['date'], $selected_alt['time'], 'l, jS F Y \\a\\t g:i A');
                                                 echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><strong>Confirmed Time:</strong> ' . esc_html($formatted_selected) . '</div>';
                                             }
                                         } 
                                         // Add other handled statuses if needed
                                         ?>
                                     <?php elseif (empty($alternatives)): ?>
                                          <p class="text-muted">No alternative times were provided by the student.</p>
                                     <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php $counter++; } ?>
                    </div> <!-- End accordion -->
                </div> <!-- End card-body -->
            </div> <!-- End card -->
        <?php } // End if !empty($alternative_requests) ?>
    </div> <!-- End #studentAlternativeTimesSectionWrapper -->

<!-- === Modals === -->

<!-- Modal for Tutor initiating a reschedule request -->
<div class="modal fade" id="newTutorRescheduleRequestModal" tabindex="-1" aria-labelledby="newTutorRescheduleRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newTutorRescheduleRequestModalLabel">Initiate Lesson Reschedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                 <div id="newTutorRequestFormContainer">
                     <div id="newTutorRequestSuccessMessage" class="alert alert-success" style="display: none;">
                         <p><i class="fas fa-check-circle"></i> Your reschedule request has been successfully submitted. The student will be notified.</p>
                     </div>
                     <div id="newTutorRequestErrorMessage" class="alert alert-danger" style="display: none;">
                         <p><i class="fas fa-exclamation-triangle"></i> Please fill in all required fields.</p>
                     </div>
                     <form id="newTutorRescheduleRequestForm" method="post">
                         <?php wp_nonce_field('submit_tutor_reschedule_request_nonce', 'submit_tutor_reschedule_request_nonce'); ?>
                         <input type="hidden" name="submit_tutor_reschedule_request" value="1">
                         <input type="hidden" name="tutor_id" value="<?php echo $current_tutor_id; ?>">
                         
                         <div class="mb-3">
                             <label for="new_tutor_student_select" class="form-label">Select Student <span class="text-danger">*</span></label>
                             <?php
                             $students = get_tutor_students(); // Use the helper function
                             if (!empty($students)) {
                                 echo '<select name="student_id" id="new_tutor_student_select" class="form-select" required>';
                                 echo '<option value="">-- Select Student --</option>';
                                 foreach ($students as $student) {
                                     echo '<option value="' . esc_attr($student['id']) . '">' . esc_html($student['display_name']) . '</option>';
                                 }
                                 echo '</select>';
                             } else {
                                 echo '<div class="alert alert-warning">No students assigned to you.</div>';
                             }
                             ?>
                         </div>
                         
                         <div class="mb-3">
                             <label for="new_tutor_lesson_date" class="form-label">Original Lesson Date <span class="text-danger">*</span></label>
                             <input type="date" class="form-control" id="new_tutor_lesson_date" name="original_date" required>
                         </div>
                         
                         <div class="mb-3">
                             <label for="new_tutor_lesson_time" class="form-label">Original Lesson Time <span class="text-danger">*</span></label>
                             <input type="time" class="form-control" id="new_tutor_lesson_time" name="original_time" required>
                             <small class="text-muted">Select the original time of the lesson you need to reschedule.</small>
                         </div>
                         
                         <div class="mb-3">
                             <label for="new_tutor_reason" class="form-label">Reason for Reschedule <span class="text-danger">*</span></label>
                             <textarea class="form-control" id="new_tutor_reason" name="reason" rows="3" required></textarea>
                         </div>
                         
                         <div class="mb-3">
                             <label class="form-label">Proposed Alternative Times <span class="text-danger">*</span></label>
                             <p class="text-muted small mb-1">Provide at least one alternative date and time for the student to consider.</p>
                             <?php render_preferred_time_inputs('new_tutor_', true); ?>
                         </div>
                         
                         <div class="modal-footer">
                             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                             <button type="submit" class="btn btn-primary" id="submitNewTutorReschedule">Submit Request</button>
                         </div>
                     </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Tutor editing their reschedule request -->
<div class="modal fade" id="editTutorRescheduleRequestModal" tabindex="-1" aria-labelledby="editTutorRescheduleRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTutorRescheduleRequestModalLabel">Edit Reschedule Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                 <div id="editTutorRequestFormContainer">
                     <div id="editTutorRequestSuccessMessage" class="alert alert-success" style="display: none;">
                         <p><i class="fas fa-check-circle"></i> Your reschedule request has been successfully updated.</p>
                     </div>
                     <div id="editTutorRequestErrorMessage" class="alert alert-danger" style="display: none;">
                         <p><i class="fas fa-exclamation-triangle"></i> Please fill in all required fields.</p>
                     </div>
                     <form id="editTutorRescheduleRequestForm" method="post">
                         <?php wp_nonce_field('update_tutor_reschedule_request_nonce', 'update_tutor_reschedule_request_nonce'); ?>
                         <input type="hidden" name="update_tutor_reschedule_request" value="1">
                         <input type="hidden" name="request_id" id="edit_tutor_request_id" value="">
                         
                         <div class="mb-3">
                             <label class="form-label">Student</label>
                             <input type="text" class="form-control" id="edit_tutor_student_name_display" disabled>
                         </div>
                         
                         <div class="mb-3">
                             <label class="form-label">Original Lesson Date/Time</label>
                             <input type="text" class="form-control" id="edit_tutor_original_datetime_display" disabled>
                         </div>
                         
                         <div class="mb-3">
                             <label for="edit_tutor_reason" class="form-label">Reason for Reschedule <span class="text-danger">*</span></label>
                             <textarea class="form-control" id="edit_tutor_reason" name="reason" rows="3" required></textarea>
                         </div>
                         
                         <div class="mb-3">
                             <label class="form-label">Proposed Alternative Times <span class="text-danger">*</span></label>
                             <p class="text-muted small mb-1">Update the alternative times offered to the student.</p>
                              <?php render_preferred_time_inputs('edit_tutor_', true); // Names like id="edit_tutor_preferred_date_1" ?>
                         </div>
                         
                         <div class="modal-footer">
                             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                             <button type="submit" class="btn btn-primary" id="updateTutorReschedule">Update Request</button>
                         </div>
                     </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Tutor providing alternative times when declining student request -->
<div class="modal fade" id="unavailableModal" tabindex="-1" aria-labelledby="unavailableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="unavailableModalLabel">Provide Alternative Times</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="unavailableTutorErrorMessage" class="alert alert-danger" style="display: none;">
                    <p>Please provide at least one alternative time.</p>
                </div>
                <p>You've indicated you're unavailable for the student's requested time/preferred options. Please provide alternative times that would work for you.</p>
                <p><strong>Student:</strong> <span id="unavailable_student_name_display"></span></p>
                <p><strong>Original Lesson:</strong> <span id="unavailable_original_lesson_display"></span></p>
                
                <form id="unavailableForm" method="post"> 
                    <?php // Nonce will be added dynamically if needed, or use a single nonce for the form ?>
                    <input type="hidden" name="decline_reschedule" value="1"> 
                    <input type="hidden" name="request_id" id="unavailable_request_id" value="">
                    <input type="hidden" name="tutor_id" value="<?php echo $current_tutor_id; ?>"> 
                    <input type="hidden" name="student_id" id="unavailable_student_id" value=""> 
                    <?php wp_nonce_field('decline_reschedule_nonce_placeholder', 'decline_reschedule_nonce'); // Placeholder, replace in JS if needed per request ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Alternative Times <span class="text-danger">*</span></label>
                        <p class="text-muted small mb-1">Provide at least one alternative date and time for the student.</p>
                        <?php render_alternative_time_inputs('tutor_alt_', true); // Names like id="tutor_alt_date_1" ?>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitTutorUnavailable">Submit Alternative Times</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal for displaying full reason text -->
<div class="modal fade" id="reasonModal" tabindex="-1" aria-labelledby="reasonModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reasonModalLabel">Full Reason</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="fullReasonText"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Nonces for AJAX actions -->
<?php wp_nonce_field('check_tutor_requests_nonce', 'check_tutor_requests_nonce_field'); ?>
<?php wp_nonce_field('mark_alternatives_viewed_nonce', 'mark_alternatives_viewed_nonce_field'); ?>
<?php wp_nonce_field('reschedule_request_nonce', 'reschedule_request_nonce_field'); // For get_preferred_times ?>

<script>
// JavaScript for Tutor Requests Tab
document.addEventListener('DOMContentLoaded', function() {

    // --- Modal & Form Handling ---

    // Populate Edit Modal (Tutor Request)
    const editTutorModal = document.getElementById('editTutorRescheduleRequestModal');
    if (editTutorModal) {
        editTutorModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const requestId = button.dataset.requestId;
            const studentName = button.dataset.studentName;
            const originalDate = button.dataset.originalDate;
            const originalTime = button.dataset.originalTime;
            const reason = button.dataset.reason;
            let preferredTimes = [];
            try {
                 preferredTimes = JSON.parse(button.dataset.preferredTimes || '[]');
            } catch(e) { console.error("Error parsing preferred times: ", e); }

            const modal = this;
            modal.querySelector('#edit_tutor_request_id').value = requestId;
            modal.querySelector('#edit_tutor_student_name_display').value = studentName; 
            modal.querySelector('#edit_tutor_original_datetime_display').value = format_datetime(originalDate, originalTime);
            modal.querySelector('#edit_tutor_reason').value = reason;
            
            // Clear & Populate preferred times (using 'edit_tutor_' prefix)
            for (let i = 1; i <= 3; i++) {
                 modal.querySelector(`#edit_tutor_preferred_date_${i}`).value = '';
                 modal.querySelector(`#edit_tutor_preferred_time_${i}`).value = '';
            }
            if (Array.isArray(preferredTimes)) {
                preferredTimes.forEach((time, index) => {
                    if (index < 3) {
                         modal.querySelector(`#edit_tutor_preferred_date_${index + 1}`).value = time.date || '';
                         modal.querySelector(`#edit_tutor_preferred_time_${index + 1}`).value = time.time || '';
                    }
                });
            }
             modal.querySelector('#editTutorRequestSuccessMessage').style.display = 'none';
             modal.querySelector('#editTutorRequestErrorMessage').style.display = 'none';
             modal.querySelector('#edit_tutor_preferred-times-error').style.display = 'none'; // Ensure error msg is hidden
        });
    }

    // Populate Unavailable Modal (when Tutor declines Student Request)
     const unavailableModal = document.getElementById('unavailableModal');
     if (unavailableModal) {
         unavailableModal.addEventListener('show.bs.modal', function(event) {
             const button = event.relatedTarget;
             const requestId = button.dataset.requestId;
             const studentId = button.dataset.studentId;
             const studentName = button.dataset.studentName;
             const originalDate = button.dataset.originalDate;
             const originalTime = button.dataset.originalTime;

             const modal = this;
             modal.querySelector('#unavailable_request_id').value = requestId;
             modal.querySelector('#unavailable_student_id').value = studentId;
             modal.querySelector('#unavailable_student_name_display').textContent = studentName;
             modal.querySelector('#unavailable_original_lesson_display').textContent = format_datetime(originalDate, originalTime);
             
              // Update nonce for the specific request being declined
             const nonceField = modal.querySelector('#decline_reschedule_nonce');
             if(nonceField) {
                 // Ideally, generate the nonce in PHP when rendering the button and pass it via data attribute
                 // For now, we might have to assume a generic nonce or fetch it if critical
                 // nonceField.value = button.dataset.nonce; // If nonce passed via button
             }
             
             // Clear alternatives and error
             for (let i = 1; i <= 3; i++) {
                 modal.querySelector(`#tutor_alt_date_${i}`).value = '';
                 modal.querySelector(`#tutor_alt_time_${i}`).value = '';
             }
             modal.querySelector('#unavailableTutorErrorMessage').style.display = 'none';
             modal.querySelector('#tutor_alt_times-error').style.display = 'none';
         });
     }
     
     // Populate Reason Modal
     const reasonModal = document.getElementById('reasonModal');
     if (reasonModal) {
         reasonModal.addEventListener('show.bs.modal', function(event) {
             const button = event.relatedTarget;
             const reason = button.dataset.reason || 'No reason provided.';
             const modalBodyP = reasonModal.querySelector('#fullReasonText');
             modalBodyP.textContent = reason;
         });
     }
     
     // Reset Modals on close
     ['newTutorRescheduleRequestModal', 'editTutorRescheduleRequestModal', 'unavailableModal'].forEach(modalId => {
         const modalElement = document.getElementById(modalId);
         if (modalElement) {
             modalElement.addEventListener('hidden.bs.modal', function() {
                  const form = this.querySelector('form');
                  if (form) form.reset();
                  // Hide all alert messages within the modal
                  this.querySelectorAll('.alert').forEach(alert => alert.style.display = 'none');
                   this.querySelectorAll('.text-danger[id*="-error"]').forEach(err => err.style.display = 'none');
                  // Re-enable submit button if it exists
                  const submitBtn = this.querySelector('button[type="submit"]');
                  if (submitBtn) {
                      submitBtn.disabled = false;
                      // Reset text (might need specific IDs)
                      if(submitBtn.id === 'submitNewTutorReschedule') submitBtn.textContent = 'Submit Request';
                      if(submitBtn.id === 'updateTutorReschedule') submitBtn.textContent = 'Update Request';
                      if(submitBtn.id === 'submitTutorUnavailable') submitBtn.textContent = 'Submit Alternative Times';
                  }
              });
         }
     });

    // --- Form Submission (Standard POST) Validation ---

    // New Tutor Request Form Validation
    const newTutorForm = document.getElementById('newTutorRescheduleRequestForm');
    if (newTutorForm) {
        const submitBtn = document.getElementById('submitNewTutorReschedule');
        submitBtn?.addEventListener('click', function(e) {
            let isValid = true;
            newTutorForm.querySelectorAll('[required]').forEach(f => { if (!f.value) isValid = false; });

            let hasPreferred = false;
            for(let i=1; i<=3; i++) {
                if (newTutorForm.querySelector(`#new_tutor_preferred_date_${i}`).value && newTutorForm.querySelector(`#new_tutor_preferred_time_${i}`).value) {
                    hasPreferred = true; break;
                }
            }
            if (!hasPreferred) isValid = false;
             
             if (!isValid) {
                e.preventDefault();
                document.getElementById('newTutorRequestErrorMessage').style.display = 'block';
                 document.getElementById('new_tutor_preferred-times-error').style.display = !hasPreferred ? 'block' : 'none';
            } else {
                 document.getElementById('newTutorRequestErrorMessage').style.display = 'none';
                 document.getElementById('new_tutor_preferred-times-error').style.display = 'none';
                 submitBtn.disabled = true;
                 submitBtn.textContent = 'Submitting...';
                 newTutorForm.submit();
             }
        });
    }

    // Edit Tutor Request Form Validation
     const editTutorForm = document.getElementById('editTutorRescheduleRequestForm');
     if (editTutorForm) {
         const submitBtn = document.getElementById('updateTutorReschedule');
         submitBtn?.addEventListener('click', function(e) {
             let isValid = true;
             editTutorForm.querySelectorAll('[required]').forEach(f => { if (!f.value) isValid = false; });

             let hasPreferred = false;
             for(let i=1; i<=3; i++) {
                 if (editTutorForm.querySelector(`#edit_tutor_preferred_date_${i}`).value && editTutorForm.querySelector(`#edit_tutor_preferred_time_${i}`).value) {
                     hasPreferred = true; break;
                 }
             }
             if (!hasPreferred) isValid = false;

             if (!isValid) {
                 e.preventDefault();
                 document.getElementById('editTutorRequestErrorMessage').style.display = 'block';
                 document.getElementById('edit_tutor_preferred-times-error').style.display = !hasPreferred ? 'block' : 'none';
             } else {
                 document.getElementById('editTutorRequestErrorMessage').style.display = 'none';
                  document.getElementById('edit_tutor_preferred-times-error').style.display = 'none';
                 submitBtn.disabled = true;
                 submitBtn.textContent = 'Updating...';
                 editTutorForm.submit();
             }
         });
     }

    // Tutor Unavailable Form Validation (Alternatives)
     const unavailableForm = document.getElementById('unavailableForm');
     if (unavailableForm) {
         const submitBtn = document.getElementById('submitTutorUnavailable');
         submitBtn?.addEventListener('click', function(e) {
             let hasAlternative = false;
             for(let i=1; i<=3; i++) {
                 if (unavailableForm.querySelector(`#tutor_alt_date_${i}`).value && unavailableForm.querySelector(`#tutor_alt_time_${i}`).value) {
                     hasAlternative = true; break;
                 }
             }

             if (!hasAlternative) {
                 e.preventDefault();
                 document.getElementById('unavailableTutorErrorMessage').style.display = 'block';
                 document.getElementById('tutor_alt_times-error').style.display = 'block';
             } else {
                 document.getElementById('unavailableTutorErrorMessage').style.display = 'none';
                 document.getElementById('tutor_alt_times-error').style.display = 'none';
                 submitBtn.disabled = true;
                 submitBtn.textContent = 'Submitting...';
                 unavailableForm.submit();
             }
         });
     }

    // --- AJAX Actions ---

    // AJAX Delete Tutor Request
    document.body.addEventListener('click', function(event) {
        if (event.target.matches('.delete-tutor-request-btn')) {
            event.preventDefault();
            const button = event.target;
            const requestId = button.dataset.requestId;
            const nonce = button.dataset.nonce; 
            const row = button.closest('tr');

            if (confirm('Are you sure you want to delete this request?')) {
                 button.disabled = true;
                 button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                const formData = new FormData();
                formData.append('action', 'delete_tutor_request');
                formData.append('request_id', requestId);
                 formData.append('_ajax_nonce', nonce); 

                 fetch(ajaxurl, { method: 'POST', body: formData })
                     .then(response => response.json())
                     .then(data => {
                         if (data.success) {
                             row.style.opacity = '0';
                             setTimeout(() => row.remove(), 300);
                             showGlobalAlert('success', data.message || 'Request deleted.', '.tutor-requests-content');
                         } else {
                             showGlobalAlert('danger', data.data?.message || 'Failed to delete request.', '.tutor-requests-content');
                             button.disabled = false;
                             button.innerHTML = '<i class="fas fa-trash"></i> <span class="d-none d-md-inline">Delete</span>';
                         }
                     })
                     .catch(error => {
                         showGlobalAlert('danger', 'An error occurred.', '.tutor-requests-content');
                         console.error('Error deleting request:', error);
                         button.disabled = false;
                         button.innerHTML = '<i class="fas fa-trash"></i> <span class="d-none d-md-inline">Delete</span>';
                     });
            }
        }
        
         // Tutor clicks 'Accept' on an incoming student request (handle via AJAX? or POST?)
         // If AJAX:
         if (event.target.closest('.ajax-confirm-form')) { 
             event.preventDefault();
             const form = event.target.closest('form');
             const button = form.querySelector('button[type="submit"]');
             const requestId = form.dataset.requestId; // Assuming request ID is on form data
             const nonce = form.querySelector('input[name="_wpnonce"]')?.value;

             if (!nonce || !requestId) return;

             button.disabled = true;
             button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

             const formData = new FormData(form);
             formData.append('action', 'confirm_student_request'); // Define this AJAX action
             formData.append('_ajax_nonce', nonce);

             fetch(ajaxurl, { method: 'POST', body: formData })
                 .then(response => response.json())
                 .then(data => {
                     if (data.success) {
                         showGlobalAlert('success', data.message || 'Request accepted!', '.tutor-requests-content');
                         // Refresh the list or update the row status
                         checkTutorIncomingRequests(); // Refresh lists via AJAX
                     } else {
                         showGlobalAlert('danger', data.data?.message || 'Failed to accept request.', '.tutor-requests-content');
                         button.disabled = false;
                         button.textContent = 'Accept';
                     }
                 })
                 .catch(error => {
                     showGlobalAlert('danger', 'An error occurred.', '.tutor-requests-content');
                     console.error('Error accepting request:', error);
                      button.disabled = false;
                      button.textContent = 'Accept';
                 });
         }
         
    }); // End body event listener

    // --- AJAX Polling/Updating --- 
    let tutorInitialLoadComplete = false;
    function checkTutorIncomingRequests() {
        const nonce = document.getElementById('check_tutor_requests_nonce_field')?.value;
        if (!nonce) return; 
        
        const formData = new FormData();
        formData.append('action', 'check_tutor_incoming_requests');
        formData.append('nonce', nonce);

        fetch(ajaxurl, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notificationsDiv = document.getElementById('tutorRequestNotifications');
                    if (notificationsDiv) {
                        notificationsDiv.innerHTML = data.data.notificationsHtml || '<div class="alert alert-secondary">No current notifications.</div>';
                    }

                    const incomingTableBody = document.querySelector('.incoming-requests-table-body');
                    if (incomingTableBody) {
                        incomingTableBody.innerHTML = data.data.incomingRequestsHtml || '<tr><td colspan="7">No incoming requests found.</td></tr>';
                    }
                    
                    const incomingBadge = document.querySelector('.incoming-student-request-count');
                     if (incomingBadge) {
                         const count = data.data.pendingStudentRequestCount || 0;
                         incomingBadge.textContent = count;
                         incomingBadge.style.display = count > 0 ? 'inline-block' : 'none';
                     }
                     
                    const alternativesBadge = document.querySelector('.student-alternatives-count');
                    if (alternativesBadge) {
                        const count = data.data.pendingAlternativesCount || 0;
                         alternativesBadge.textContent = count;
                         alternativesBadge.style.display = count > 0 ? 'inline-block' : 'none';
                     }

                    updateMainTutorRequestsTabBadge(data.data.count || 0);
                    initializeTooltips(); 
                    tutorInitialLoadComplete = true;

                } else {
                    console.error('Error checking tutor requests:', data.data?.message);
                    if (!tutorInitialLoadComplete) {
                         const notificationsDiv = document.getElementById('tutorRequestNotifications');
                         if (notificationsDiv) notificationsDiv.innerHTML = '<div class="alert alert-danger">Error loading notifications.</div>';
                         const incomingTableBody = document.querySelector('.incoming-requests-table-body');
                         if (incomingTableBody) incomingTableBody.innerHTML = '<tr><td colspan="7">Error loading requests.</td></tr>';
                         tutorInitialLoadComplete = true;
                    }
                }
            })
            .catch(error => {
                 console.error('Fetch error checking tutor requests:', error);
                  if (!tutorInitialLoadComplete) {
                      const notificationsDiv = document.getElementById('tutorRequestNotifications');
                      if (notificationsDiv) notificationsDiv.innerHTML = '<div class="alert alert-danger">Network error loading notifications.</div>';
                      const incomingTableBody = document.querySelector('.incoming-requests-table-body');
                      if (incomingTableBody) incomingTableBody.innerHTML = '<tr><td colspan="7">Network error loading requests.</td></tr>';
                       tutorInitialLoadComplete = true;
                  }
            });
    }

    function updateMainTutorRequestsTabBadge(count) {
        const requestsTabButton = document.getElementById('requests-tab-button'); // ID from tutor-dashboard.php
        if (!requestsTabButton) return;
        let badge = requestsTabButton.querySelector('.notification-badge');
        
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'badge rounded-pill bg-danger notification-badge ms-1';
                requestsTabButton.appendChild(badge);
            }
            badge.textContent = count;
            badge.style.display = 'inline-block';
        } else if (badge) {
            badge.style.display = 'none';
        }
    }
    
    // Global Alert Function (scoped to tutor content)
     function showGlobalAlert(type, message, containerSelector = '.tutor-requests-content') {
         const container = document.querySelector(containerSelector);
         if (!container) return;

         const alertDiv = document.createElement('div');
         alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`; // Added mt-3
         alertDiv.role = 'alert';
         alertDiv.innerHTML = `
             ${message}
             <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         `;
         container.insertBefore(alertDiv, container.firstChild);
     }

    // Initialize Tooltips
    function initializeTooltips() {
         const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-tooltip="tooltip"]'));
         tooltipTriggerList.map(function (tooltipTriggerEl) {
             if (!bootstrap.Tooltip.getInstance(tooltipTriggerEl)) {
                  return new bootstrap.Tooltip(tooltipTriggerEl);
             }
              return null;
         });
     }

    // Initial Load & Interval
    checkTutorIncomingRequests();
    initializeTooltips();
    setInterval(checkTutorIncomingRequests, 30000);

    // Refresh on Tab Show
    const requestsTabButton = document.getElementById('requests-tab-button');
    if (requestsTabButton) {
         requestsTabButton.addEventListener('shown.bs.tab', function (event) {
             checkTutorIncomingRequests();
             // Mark student alternatives as viewed when opening accordion?
             // markStudentAlternativesAsViewed(); 
         });
     }
     
      // Helper function (example - implement based on your needs)
     function format_datetime(dateStr, timeStr, format = 'default') {
         if (!dateStr || !timeStr) return 'N/A';
         try {
             const dt = new Date(`${dateStr} ${timeStr}`);
             if (isNaN(dt)) return 'Invalid Date';
             if (format === 'default') {
                 return dt.toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' }); 
             }
             return dt.toISOString();
         } catch (e) {
             return 'Invalid Date';
         }
     }

});
</script>

<style>
    /* Add any specific styles for the tutor requests tab here */
    .table-responsive {
        scrollbar-width: thin; /* For Firefox */
        scrollbar-color: #adb5bd #f8f9fa; /* For Firefox */
    }
    .table-responsive::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    .table-responsive::-webkit-scrollbar-track {
        background: #f8f9fa;
        border-radius: 4px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background-color: #adb5bd;
        border-radius: 4px;
        border: 2px solid #f8f9fa;
    }
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background-color: #6c757d;
    }
    .request-actions .btn {
        margin-bottom: 0.25rem; /* Add space between buttons on small screens */
    }
</style>