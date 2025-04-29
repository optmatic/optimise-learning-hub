<?php
// Ensure this file is loaded within WordPress context
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Ensure helper functions are available (might be redundant if already included, but safe)
// require_once dirname(__DIR__) . '/requests/request-functions.php'; 

// Get current student data
$current_student_id = get_current_user_id();
$current_student = wp_get_current_user();
?>
<div class="student-requests-content">
    <h4>Reschedule Requests</h4>
    
    <!-- Notifications Section - populated by AJAX (check_student_incoming_requests_ajax) -->
    <div class="mb-4" id="studentRequestNotifications">
        <!-- Placeholder or loading indicator -->
        <div class="alert alert-light">Loading notifications...</div>
        <?php
        /* 
        // PHP fallback (optional, AJAX is preferred)
        $pending_tutor_request_count = get_pending_request_count($current_student_id, 'student', 'tutor_reschedule');
        $pending_alternatives_count = get_pending_alternatives_count($current_student_id, 'student');
        $unread_confirmed_count = get_unread_confirmed_count($current_student_id, 'student');
        
        if ($pending_tutor_request_count > 0 || $pending_alternatives_count > 0 || $unread_confirmed_count > 0): ?>
             <div class="alert alert-info">
                 <h5><i class="fas fa-bell me-2"></i>Notifications</h5>
                 <ul class="mb-0" style="list-style: none; padding-left: 0;">
                    <?php if ($unread_confirmed_count > 0): ?>
                        <li>
                             <i class="fas fa-check-circle me-1 text-success"></i> You have <strong><?php echo $unread_confirmed_count; ?></strong> accepted reschedule request<?php echo ($unread_confirmed_count > 1 ? 's' : ''); ?>.
                             <a href="#schedule" class="btn btn-sm btn-primary ms-2">View Schedule</a>
                         </li>
                    <?php endif; ?>
                    <?php if ($pending_tutor_request_count > 0): ?>
                        <li class="mt-2">
                             <i class="fas fa-arrow-right me-1 text-warning"></i> You have <strong><?php echo $pending_tutor_request_count; ?></strong> pending reschedule request<?php echo ($pending_tutor_request_count > 1 ? 's' : ''); ?> from your tutor.
                             <a href="#incomingRescheduleSection" class="btn btn-sm btn-primary ms-2">View Requests</a>
                         </li>
                     <?php endif; ?>
                     <?php if ($pending_alternatives_count > 0): ?>
                         <li class="mt-2">
                             <i class="fas fa-exchange-alt me-1 text-primary"></i> Your tutor proposed <strong><?php echo $pending_alternatives_count; ?></strong> alternative time<?php echo ($pending_alternatives_count > 1 ? 's' : ''); ?>.
                             <a href="#alternativeTimesSection" class="btn btn-sm btn-primary ms-2">View Alternatives</a>
                         </li>
                     <?php endif; ?>
                 </ul>
             </div>
        <?php endif; 
        */
        ?>
    </div>
    
    <!-- Add Reschedule Request -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-plus-circle me-2"></i> Request Lesson Reschedule
        </div>
        <div class="card-body">
            <p>Need to reschedule an upcoming lesson? Use the button below.</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newStudentRescheduleRequestModal">
                Request Lesson Reschedule
            </button>
        </div>
    </div>
    
    <!-- Outgoing Reschedule Requests (Student-initiated) -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-arrow-left me-2"></i> Your Outgoing Reschedule Requests
        </div>
        <div class="card-body student-outgoing-requests-section">
            <?php
            $student_requests = get_reschedule_requests('student_reschedule', $current_student_id, 'student');
            
            if (!empty($student_requests)) {
                echo '<div class="table-responsive" style="max-height: 350px; overflow-y: auto;">'; // Added scroll
                echo '<table class="table table-striped table-hover">'; // Added hover
                echo '<thead class="table-light"><tr><th>Date Requested</th><th>Lesson</th><th>Preferred Times</th><th>Tutor</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>';
                echo '<tbody class="student-requests-table-body">';
                
                foreach ($student_requests as $request) {
                    $request_id = $request->ID;
                    $tutor_name = get_post_meta($request_id, 'tutor_name', true);
                    $original_date = get_post_meta($request_id, 'original_date', true);
                    $original_time = get_post_meta($request_id, 'original_time', true);
                    $status = get_post_meta($request_id, 'status', true);
                    $request_date = get_the_date('M j, Y', $request_id);
                    $reason = get_post_meta($request_id, 'reason', true);
                    $preferred_times = get_post_meta($request_id, 'preferred_times', true);
                    $tutor_response = get_post_meta($request_id, 'tutor_response', true); // For declined reasons
                    $tutor_display_name = get_tutor_display_name($tutor_name);

                    $formatted_original = format_datetime($original_date, $original_time);
                    $status_badge = get_status_badge($status);
                    $notification = '';

                    // --- Status Specific Logic ---
                    if ($status === 'declined' || $status === 'denied') {
                        if (!empty($tutor_response)) {
                            $notification = '<div class="mt-1"><small class="text-danger"><i class="fas fa-info-circle"></i> Tutor response: ' . esc_html($tutor_response) . '</small></div>';
                        }
                    } elseif ($status === 'unavailable') { // Tutor marked unavailable, check for alternatives
                         $alternative_requests = get_posts([
                             'post_type' => 'progress_report',
                             'posts_per_page' => 1,
                             'meta_query' => [
                                 'relation' => 'AND',
                                 ['key' => 'original_request_id', 'value' => $request_id, 'compare' => '='],
                                 ['key' => 'request_type', 'value' => 'tutor_unavailable', 'compare' => '='],
                                 ['key' => 'status', 'value' => 'pending', 'compare' => '='] // Only show link if pending
                             ],
                             'fields' => 'ids'
                         ]);
                         if (!empty($alternative_requests)) {
                            $status_badge = get_status_badge('tutor_unavailable'); // More specific badge
                             $notification = '<div class="mt-1"><small class="text-info"><i class="fas fa-info-circle"></i> Tutor proposed alternative times.</small></div>';
                             $notification .= '<a href="#alternativeTimesSection" class="btn btn-sm btn-outline-primary mt-1">View Alternatives</a>';
                         } else {
                             // Maybe the alternative request was already handled (confirmed/unavailable)
                             $notification = '<div class="mt-1"><small class="text-warning"><i class="fas fa-info-circle"></i> Tutor was unavailable for these times.</small></div>';
                         }
                     } 

                    echo '<tr data-request-id="' . $request_id . '">'; // Add ID to row
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
                    echo '<td>' . esc_html($tutor_display_name) . '</td>';
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
                         echo '<button type="button" class="btn btn-sm btn-primary me-1 edit-student-request-btn" 
                             data-bs-toggle="modal" 
                             data-bs-target="#editStudentRescheduleRequestModal" 
                             data-request-id="' . $request_id . '"
                             data-tutor-name="' . esc_attr($tutor_name) . '" 
                             data-original-date="' . esc_attr($original_date) . '"
                             data-original-time="' . esc_attr($original_time) . '"
                             data-reason="' . esc_attr($reason) . '"
                             data-preferred-times=\''. esc_attr(json_encode($preferred_times)) .'\'
                             aria-label="Edit Request">
                             <i class="fas fa-edit"></i> <span class="d-none d-md-inline">Edit</span>
                         </button>';
                         
                         echo '<button type="button" class="btn btn-sm btn-danger delete-student-request-btn" 
                                 data-request-id="' . $request_id . '" 
                                 data-nonce="' . wp_create_nonce('delete_student_request_' . $request_id) . '" 
                                 aria-label="Delete Request">
                                 <i class="fas fa-trash"></i> <span class="d-none d-md-inline">Delete</span>
                         </button>';
                    } else {
                        // Allow archiving completed/declined requests?
                        // Maybe only show archive if the date has passed?
                         echo '-'; // Or an archive button
                         /*
                         $can_archive = false;
                         if ($status === 'confirmed' || $status === 'accepted') {
                             $lesson_dt = format_datetime($original_date, $original_time, 'Y-m-d H:i:s');
                             if ($lesson_dt !== 'N/A' && new DateTime($lesson_dt) < new DateTime()) $can_archive = true;
                         } elseif ($status === 'declined' || $status === 'denied') {
                             $can_archive = true;
                         }
                         if ($can_archive) {
                             echo '<button type="button" class="btn btn-sm btn-secondary archive-student-request-btn" 
                                     data-request-id="' . $request_id . '" 
                                     data-nonce="' . wp_create_nonce('archive_student_request_' . $request_id) . '" 
                                     aria-label="Archive Request">
                                     <i class="fas fa-archive"></i> <span class="d-none d-md-inline">Archive</span>
                             </button>';
                         } else {
                             echo '-';
                         }
                         */
                    }
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                echo '</div>';
            } else {
                echo '<p>You have not submitted any reschedule requests yet.</p>';
            }
            ?>
        </div>
    </div>
    
    <!-- Incoming Reschedule Requests (Tutor-initiated) -->
    <div class="card mb-4" id="incomingRescheduleSection">
        <div class="card-header bg-warning text-dark">
            <div class="d-flex justify-content-between align-items-center">
                <span><i class="fas fa-arrow-right me-2"></i> Incoming Reschedule Requests</span>
                <span class="badge bg-danger incoming-tutor-request-count" style="display: none;"></span> <!-- Badge updated by AJAX -->
            </div>
        </div>
        <div class="card-body">
             <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                 <table class="table table-striped table-hover">
                     <thead class="table-light"><tr><th>Date Requested</th><th>Original Lesson</th><th>Proposed New Time</th><th>Tutor</th><th>Status</th><th>Action</th></tr></thead>
                     <tbody class="incoming-tutor-requests-table-body">
                         <!-- Content loaded via AJAX (check_student_incoming_requests_ajax) -->
                         <tr><td colspan="6"><p>Loading incoming requests...</p></td></tr>
                     </tbody>
                 </table>
            </div>
        </div>
    </div>
    
    <!-- Tutor Alternative Times -->
    <div id="alternativeTimesSectionWrapper">
        <?php
        // Get pending alternative time suggestions from tutors (request_type = tutor_unavailable)
        $alternative_requests = get_reschedule_requests('tutor_unavailable', $current_student_id, 'student', 'pending');
        
        if (!empty($alternative_requests)) {
            $pending_alternatives = count($alternative_requests); // We already queried pending ones
        ?>
            <div class="card mb-4" id="alternativeTimesSection">
                <div class="card-header bg-secondary text-white"> <!-- Changed color -->
                    <div class="d-flex justify-content-between align-items-center">
                         <span><i class="fas fa-calendar-alt me-2"></i> Tutor Alternative Time Suggestions</span>
                         <?php if ($pending_alternatives > 0): ?>
                            <span class="badge bg-danger tutor-alternatives-count"><?php echo $pending_alternatives; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($pending_alternatives > 0) : ?>
                        <div class="alert alert-warning mb-3">
                             <i class="fas fa-exclamation-circle me-2"></i> Your tutor was unavailable for a requested time but proposed alternatives below. Please review and respond.
                        </div>
                    <?php endif; ?>
                    
                    <div class="accordion" id="tutorUnavailableAccordion">
                        <?php 
                        $counter = 1;
                        foreach ($alternative_requests as $request) {
                            $request_id = $request->ID;
                            $original_request_id = get_post_meta($request_id, 'original_request_id', true);
                            $tutor_name = get_post_meta($request_id, 'tutor_name', true);
                            $alternatives = get_post_meta($request_id, 'alternatives', true);
                            $status = get_post_meta($request_id, 'status', true);
                            $request_date = get_the_date('F j, Y', $request_id);
                            $tutor_display_name = get_tutor_display_name($tutor_name);
                            
                            // Get original student request details
                            $original_student_date = get_post_meta($original_request_id, 'original_date', true);
                            $original_student_time = get_post_meta($original_request_id, 'original_time', true);
                            $formatted_original = format_datetime($original_student_date, $original_student_time, 'l, jS F Y \\a\\t g:i A');
                            
                            $is_pending = ($status === 'pending');
                        ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="tutorUnavailHeading<?php echo $counter; ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#tutorUnavailCollapse<?php echo $counter; ?>" aria-expanded="false" 
                                            aria-controls="tutorUnavailCollapse<?php echo $counter; ?>">
                                         Alternatives from <?php echo esc_html($tutor_display_name); ?> (Requested: <?php echo esc_html($request_date); ?>)
                                         <?php if ($is_pending) echo '<span class="badge bg-warning ms-2">Action Required</span>'; ?>
                                    </button>
                                </h2>
                                <div id="tutorUnavailCollapse<?php echo $counter; ?>" class="accordion-collapse collapse" 
                                        aria-labelledby="tutorUnavailHeading<?php echo $counter; ?>" data-bs-parent="#tutorUnavailableAccordion">
                                    <div class="accordion-body">
                                        <div class="card mb-3 bg-light">
                                             <div class="card-body">
                                                 <p class="mb-1"><small>Regarding your request for:</small></p>
                                                 <p><strong><?php echo esc_html($formatted_original); ?></strong></p>
                                             </div>
                                        </div>
                                        
                                        <?php if ($is_pending && !empty($alternatives) && is_array($alternatives)) : ?>
                                            <p>Please select an alternative time that works for you:</p>
                                            <form method="post">
                                                 <?php wp_nonce_field('accept_tutor_alternative_' . $request_id); ?>
                                                 <input type="hidden" name="accept_tutor_alternative" value="1">
                                                 <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                                                 
                                                 <div class="list-group mb-3">
                                                     <?php foreach ($alternatives as $index => $alternative) : 
                                                         if (empty($alternative['date']) || empty($alternative['time'])) continue;
                                                         $formatted_alt = format_datetime($alternative['date'], $alternative['time'], 'l, jS F Y \\a\\t g:i A');
                                                     ?>
                                                         <label class="list-group-item list-group-item-action" for="tutor_alt_<?php echo $request_id; ?>_<?php echo $index; ?>">
                                                             <input class="form-check-input me-1" type="radio" name="selected_alternative" 
                                                                    value="<?php echo $index; ?>" id="tutor_alt_<?php echo $request_id; ?>_<?php echo $index; ?>" <?php checked($index, 0); ?>>
                                                             Option <?php echo ($index + 1); ?>: <?php echo esc_html($formatted_alt); ?>
                                                         </label>
                                                     <?php endforeach; ?>
                                                 </div>
                                                 
                                                 <button type="submit" class="btn btn-success me-2">Accept Selected Time</button>
                                                 
                                                 <button type="button" class="btn btn-outline-danger unavailable-all-btn" 
                                                         data-request-id="<?php echo $request_id; ?>" 
                                                         data-nonce="<?php echo wp_create_nonce('unavailable_all_' . $request_id); ?>">
                                                         Unavailable for All Options
                                                 </button>
                                             </form>
                                        <?php elseif (!$is_pending): // Show confirmed or unavailable status ?>
                                             <?php 
                                             $final_status = get_post_meta($request_id, 'status', true);
                                             if ($final_status === 'confirmed') {
                                                 $selected_index = get_post_meta($request_id, 'selected_alternative', true);
                                                 if (isset($alternatives[$selected_index])) {
                                                     $selected_alt = $alternatives[$selected_index];
                                                     $formatted_selected = format_datetime($selected_alt['date'], $selected_alt['time'], 'l, jS F Y \\a\\t g:i A');
                                                     echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><strong>Confirmed Time:</strong> ' . esc_html($formatted_selected) . '</div>';
                                                 }
                                             } elseif ($final_status === 'unavailable') {
                                                  echo '<div class="alert alert-warning"><i class="fas fa-times-circle me-2"></i>You marked yourself as unavailable for all these options.</div>';
                                             }
                                             ?>
                                        <?php elseif (empty($alternatives)): ?>
                                             <p class="text-muted">No alternative times were provided for this request.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php $counter++; } ?>
                    </div> <!-- End accordion -->
                </div> <!-- End card-body -->
            </div> <!-- End card -->
        <?php } // End if !empty($alternative_requests) ?>
    </div> <!-- End #alternativeTimesSectionWrapper -->

</div> <!-- End .student-requests-content -->

<!-- === Modals === -->

<!-- Modal for creating a new student reschedule request -->
<div class="modal fade" id="newStudentRescheduleRequestModal" tabindex="-1" aria-labelledby="newStudentRescheduleRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg"> <!-- Increased size -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newStudentRescheduleRequestModalLabel">Request Lesson Reschedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                 <div id="newRescheduleRequestFormContainer">
                     <div id="newRescheduleRequestSuccessMessage" class="alert alert-success" style="display: none;">
                         <p><i class="fas fa-check-circle"></i> Your reschedule request has been successfully submitted. Your tutor will be notified.</p>
                     </div>
                     <div id="newRescheduleRequestErrorMessage" class="alert alert-danger" style="display: none;">
                         <p><i class="fas fa-exclamation-triangle"></i> Please fill in all required fields.</p>
                     </div>
                    <form id="newStudentRescheduleRequestForm" method="post"> 
                         <?php wp_nonce_field('submit_student_reschedule_request_nonce', 'submit_student_reschedule_request_nonce'); ?>
                         <input type="hidden" name="submit_student_reschedule_request" value="1">
                         <input type="hidden" name="student_id" value="<?php echo $current_student_id; ?>">
                         
                         <div class="mb-3">
                             <label for="new_tutor_select" class="form-label">Select Tutor <span class="text-danger">*</span></label>
                             <?php
                             // Get assigned tutors for the current student
                             $assigned_tutors_ids = [];
                             $assigned_tutors_meta = get_user_meta($current_student_id, 'assigned_tutors', true);
                             if (!empty($assigned_tutors_meta)) {
                                 $assigned_tutors_ids = is_array($assigned_tutors_meta) ? $assigned_tutors_meta : array_map('trim', explode(',', $assigned_tutors_meta));
                             }
                             
                             $tutors_available = [];
                             if (!empty($assigned_tutors_ids)) {
                                 $tutor_query = new WP_User_Query([
                                     'include' => $assigned_tutors_ids,
                                     'role' => 'tutor', 
                                     'fields' => ['ID', 'user_login', 'display_name']
                                 ]);
                                 $tutors_available = $tutor_query->get_results();
                             }
                             
                             if (!empty($tutors_available)) {
                                 echo '<select name="tutor_name" id="new_tutor_select" class="form-select" required>';
                                 echo '<option value="">-- Select Tutor --</option>';
                                 foreach ($tutors_available as $tutor) {
                                     $first_name = get_user_meta($tutor->ID, 'first_name', true);
                                     $last_name = get_user_meta($tutor->ID, 'last_name', true);
                                     $display_name = (!empty($first_name) && !empty($last_name)) ? esc_html($first_name . ' ' . $last_name) : esc_html($tutor->display_name);
                                     // Value should be the tutor's username (user_login) as expected by the handler
                                     echo '<option value="' . esc_attr($tutor->user_login) . '" data-tutor-id="' . esc_attr($tutor->ID) . '">' . $display_name . '</option>';
                                 }
                                 echo '</select>';
                             } else {
                                 echo '<div class="alert alert-warning">No tutors assigned. Please contact support.</div>';
                             }
                             ?>
                         </div>
                         
                         <div class="mb-3">
                             <label for="new_lesson_select" class="form-label">Lesson to Reschedule <span class="text-danger">*</span></label>
                             <select class="form-select" id="new_lesson_select" name="lesson_select" required>
                                 <option value="">-- Select a Scheduled Lesson --</option>
                                 <?php
                                 $upcoming_lessons = get_upcoming_lessons_for_user($current_student_id);
                                 if (!empty($upcoming_lessons)) {
                                     foreach ($upcoming_lessons as $lesson) {
                                         echo '<option value="' . esc_attr($lesson['date_value']) . '|' . esc_attr($lesson['time_value']) . '">' 
                                              . esc_html($lesson['subject']) . ' - ' . esc_html($lesson['formatted']) . '</option>';
                                     }
                                 } else {
                                     echo '<option value="" disabled>No upcoming lessons found in your schedule.</option>';
                                 }
                                 ?>
                             </select>
                             <input type="hidden" id="new_original_date" name="original_date">
                             <input type="hidden" id="new_original_time" name="original_time">
                         </div>
                         
                         <div class="mb-3">
                             <label for="new_reason" class="form-label">Reason for Reschedule <span class="text-danger">*</span></label>
                             <textarea class="form-control" id="new_reason" name="reason" rows="3" required></textarea>
                         </div>
                         
                         <div class="mb-3">
                            <label class="form-label">Preferred Alternative Times <span class="text-danger">*</span></label>
                            <p class="text-muted small mb-1">Please provide at least one preferred alternative date and time.</p>
                            <?php render_preferred_time_inputs('new_', true); // Render the inputs ?>
                         </div>
                        
                         <div class="modal-footer">
                             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                             <button type="submit" class="btn btn-primary" id="submitNewStudentReschedule">Submit Request</button>
                         </div>
                     </form>
                 </div> <!-- /#newRescheduleRequestFormContainer -->
            </div>
        </div>
    </div>
</div>

<!-- Modal for editing a student reschedule request -->
<div class="modal fade" id="editStudentRescheduleRequestModal" tabindex="-1" aria-labelledby="editStudentRescheduleRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStudentRescheduleRequestModalLabel">Edit Reschedule Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="editStudentRescheduleFormContainer">
                    <div id="editRescheduleSuccessMessage" class="alert alert-success" style="display: none;">
                         <p><i class="fas fa-check-circle"></i> Your reschedule request has been successfully updated.</p>
                    </div>
                     <div id="editRescheduleErrorMessage" class="alert alert-danger" style="display: none;">
                         <p><i class="fas fa-exclamation-triangle"></i> Please fill in all required fields.</p>
                     </div>
                    <form id="editStudentRescheduleRequestForm" method="post">
                         <?php wp_nonce_field('update_student_reschedule_request_nonce', 'update_student_reschedule_request_nonce'); ?>
                        <input type="hidden" name="update_student_reschedule_request" value="1">
                        <input type="hidden" name="request_id" id="edit_request_id" value="">
                        
                        <div class="mb-3">
                            <label class="form-label">Tutor</label>
                            <input type="text" class="form-control" id="edit_tutor_name_display" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Original Lesson Date/Time</label>
                            <input type="text" class="form-control" id="edit_original_datetime_display" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_reason" class="form-label">Reason for Reschedule <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit_reason" name="reason" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Preferred Alternative Times <span class="text-danger">*</span></label>
                            <p class="text-muted small mb-1">Please provide at least one preferred alternative date and time.</p>
                            <?php render_preferred_time_inputs('edit_', true); // Render the inputs, using 'edit_' prefix ?>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="updateStudentReschedule">Update Request</button>
                        </div>
                    </form>
                </div>
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
<?php wp_nonce_field('check_student_requests_nonce', 'check_student_requests_nonce_field'); ?>
<?php wp_nonce_field('mark_tutor_alternatives_viewed_nonce', 'mark_tutor_alternatives_viewed_nonce_field'); ?>
<?php wp_nonce_field('reschedule_request_nonce', 'reschedule_request_nonce_field'); // Nonce for get_preferred_times ?>

<script>
// Keep the JavaScript for handling modals, AJAX calls (now using centralized handlers),
// table updates, delete confirmations, etc.
// Ensure AJAX calls use the correct nonces and action names defined in ajax-handlers.php
document.addEventListener('DOMContentLoaded', function() {

    // --- Modal & Form Handling --- 

    // Populate Edit Modal (Student Request)
    const editStudentModal = document.getElementById('editStudentRescheduleRequestModal');
    if (editStudentModal) {
        editStudentModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const requestId = button.dataset.requestId;
            const tutorName = button.dataset.tutorName;
            const originalDate = button.dataset.originalDate;
            const originalTime = button.dataset.originalTime;
            const reason = button.dataset.reason;
            let preferredTimes = [];
            try {
                 preferredTimes = JSON.parse(button.dataset.preferredTimes || '[]');
            } catch(e) {
                console.error("Error parsing preferred times JSON: ", e);
            }

            const modal = this;
            modal.querySelector('#edit_request_id').value = requestId;
            modal.querySelector('#edit_tutor_name_display').value = get_tutor_display_name(tutorName); // Use helper if needed, or pass display name
            modal.querySelector('#edit_original_datetime_display').value = format_datetime(originalDate, originalTime);
            modal.querySelector('#edit_reason').value = reason;
            
            // Clear previous preferred times
            for (let i = 1; i <= 3; i++) {
                 modal.querySelector(`#edit_preferred_date_${i}`).value = '';
                 modal.querySelector(`#edit_preferred_time_${i}`).value = '';
            }
            // Populate preferred times
            if (Array.isArray(preferredTimes)) {
                preferredTimes.forEach((time, index) => {
                    if (index < 3) {
                         modal.querySelector(`#edit_preferred_date_${index + 1}`).value = time.date || '';
                         modal.querySelector(`#edit_preferred_time_${index + 1}`).value = time.time || '';
                    }
                });
            }
             // Hide success/error messages
             modal.querySelector('#editRescheduleSuccessMessage').style.display = 'none';
             modal.querySelector('#editRescheduleErrorMessage').style.display = 'none';
        });
    }
    
    // Handle Lesson Select Change in New Request Modal
    const newLessonSelect = document.getElementById('new_lesson_select');
    if (newLessonSelect) {
        newLessonSelect.addEventListener('change', function() {
            const selectedValue = this.value;
            if (selectedValue) {
                const [date, time] = selectedValue.split('|');
                document.getElementById('new_original_date').value = date;
                document.getElementById('new_original_time').value = time;
            } else {
                document.getElementById('new_original_date').value = '';
                document.getElementById('new_original_time').value = '';
            }
        });
    }

    // Reset New Request Modal on close
    const newStudentModal = document.getElementById('newStudentRescheduleRequestModal');
    if (newStudentModal) {
        newStudentModal.addEventListener('hidden.bs.modal', function () {
             const form = document.getElementById('newStudentRescheduleRequestForm');
             if (form) form.reset();
             document.getElementById('new_original_date').value = ''; // Clear hidden fields too
             document.getElementById('new_original_time').value = '';
             document.getElementById('newRescheduleRequestSuccessMessage').style.display = 'none';
             document.getElementById('newRescheduleRequestErrorMessage').style.display = 'none';
             document.getElementById('new_preferred-times-error').style.display = 'none';
             const submitBtn = document.getElementById('submitNewStudentReschedule');
             if (submitBtn) {
                 submitBtn.disabled = false;
                 submitBtn.textContent = 'Submit Request';
             }
         });
     }
     
     // Populate Reason Modal
     const reasonModal = document.getElementById('reasonModal');
     if (reasonModal) {
         reasonModal.addEventListener('show.bs.modal', function(event) {
             const button = event.relatedTarget; // Span that triggered the modal
             const reason = button.dataset.reason || 'No reason provided.';
             const modalBodyP = reasonModal.querySelector('#fullReasonText');
             modalBodyP.textContent = reason;
         });
     }
     
    // --- Form Submission (using standard POST, handled by post-handlers.php) ---
    // Add validation before submitting the forms
    
    // New Student Request Form Validation
    const newStudentForm = document.getElementById('newStudentRescheduleRequestForm');
    if (newStudentForm) {
        const submitNewBtn = document.getElementById('submitNewStudentReschedule');
         submitNewBtn?.addEventListener('click', function(e) {
             // Basic check for required fields
             let isValid = true;
             const requiredFields = newStudentForm.querySelectorAll('[required]');
             requiredFields.forEach(field => {
                 if (!field.value) isValid = false;
             });

             // Check at least one preferred time
            let hasPreferred = false;
            for(let i=1; i<=3; i++) {
                if (newStudentForm.querySelector(`#new_preferred_date_${i}`).value && newStudentForm.querySelector(`#new_preferred_time_${i}`).value) {
                    hasPreferred = true;
                    break;
                }
            }
            if (!hasPreferred) isValid = false;
             
             if (!isValid) {
                e.preventDefault(); // Stop submission
                document.getElementById('newRescheduleRequestErrorMessage').style.display = 'block';
                document.getElementById('new_preferred-times-error').style.display = !hasPreferred ? 'block' : 'none';
             } else {
                document.getElementById('newRescheduleRequestErrorMessage').style.display = 'none';
                document.getElementById('new_preferred-times-error').style.display = 'none';
                 submitNewBtn.disabled = true; // Prevent double submit
                 submitNewBtn.textContent = 'Submitting...';
                 newStudentForm.submit(); // Allow submission
             }
         });
     }

    // Edit Student Request Form Validation
    const editStudentForm = document.getElementById('editStudentRescheduleRequestForm');
    if (editStudentForm) {
        const submitEditBtn = document.getElementById('updateStudentReschedule');
        submitEditBtn?.addEventListener('click', function(e) {
             let isValid = true;
             if (!editStudentForm.querySelector('#edit_reason').value) isValid = false;

             let hasPreferred = false;
             for(let i=1; i<=3; i++) {
                 if (editStudentForm.querySelector(`#edit_preferred_date_${i}`).value && editStudentForm.querySelector(`#edit_preferred_time_${i}`).value) {
                     hasPreferred = true;
                     break;
                 }
             }
            if (!hasPreferred) isValid = false;

             if (!isValid) {
                e.preventDefault();
                document.getElementById('editRescheduleErrorMessage').style.display = 'block';
                document.getElementById('edit_preferred-times-error').style.display = !hasPreferred ? 'block' : 'none';
            } else {
                document.getElementById('editRescheduleErrorMessage').style.display = 'none';
                document.getElementById('edit_preferred-times-error').style.display = 'none';
                submitEditBtn.disabled = true;
                submitEditBtn.textContent = 'Updating...';
                editStudentForm.submit();
             }
         });
    }

    // --- AJAX Actions (Delete, Mark Unavailable, etc.) ---

    // AJAX Delete Student Request
    document.body.addEventListener('click', function(event) {
        if (event.target.matches('.delete-student-request-btn')) {
            event.preventDefault();
            const button = event.target;
            const requestId = button.dataset.requestId;
            const nonce = button.dataset.nonce; // Get nonce from button data
            const row = button.closest('tr');

            if (confirm('Are you sure you want to delete this request?')) {
                 button.disabled = true;
                 button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                const formData = new FormData();
                formData.append('action', 'delete_student_request');
                formData.append('request_id', requestId);
                 formData.append('_ajax_nonce', nonce); // Use the correct nonce key

                 fetch(ajaxurl, { method: 'POST', body: formData })
                     .then(response => response.json())
                     .then(data => {
                         if (data.success) {
                             row.style.opacity = '0';
                             setTimeout(() => row.remove(), 300); 
                             showGlobalAlert('success', data.message || 'Request deleted.');
                         } else {
                             showGlobalAlert('danger', data.data?.message || 'Failed to delete request.');
                             button.disabled = false;
                             button.innerHTML = '<i class="fas fa-trash"></i> <span class="d-none d-md-inline">Delete</span>';
                         }
                     })
                     .catch(error => {
                         showGlobalAlert('danger', 'An error occurred while deleting the request.');
                         console.error('Error deleting request:', error);
                         button.disabled = false;
                          button.innerHTML = '<i class="fas fa-trash"></i> <span class="d-none d-md-inline">Delete</span>';
                     });
            }
        }
        
        // Handle "Unavailable for All Options" button click
        if (event.target.matches('.unavailable-all-btn')) {
            event.preventDefault();
            const button = event.target;
            const requestId = button.dataset.requestId;
            const nonce = button.dataset.nonce;

            if (confirm('Are you sure you are unavailable for all the proposed alternative times?')) {
                 // This action is handled by a standard POST request via post-handlers.php
                 // Create a temporary form and submit it
                 const tempForm = document.createElement('form');
                 tempForm.method = 'post';
                 tempForm.style.display = 'none';

                 const nonceInput = document.createElement('input');
                 nonceInput.type = 'hidden';
                 nonceInput.name = '_wpnonce'; // Use correct nonce name for check_admin_referer
                 nonceInput.value = nonce;
                 tempForm.appendChild(nonceInput);

                 const actionInput = document.createElement('input');
                 actionInput.type = 'hidden';
                 actionInput.name = 'unavailable_all';
                 actionInput.value = '1';
                 tempForm.appendChild(actionInput);

                 const requestIdInput = document.createElement('input');
                 requestIdInput.type = 'hidden';
                 requestIdInput.name = 'request_id';
                 requestIdInput.value = requestId;
                 tempForm.appendChild(requestIdInput);

                 document.body.appendChild(tempForm);
                 tempForm.submit();
             }
         }
         
         // Add similar handlers for other AJAX actions if needed (e.g., archive)
         // Ensure they use the correct action names and nonces defined in ajax-handlers.php
         
    }); // End body event listener

    // --- AJAX Polling/Updating --- 
    let initialLoadComplete = false;
    function checkStudentIncomingRequests() {
        const nonce = document.getElementById('check_student_requests_nonce_field')?.value;
        if (!nonce) return; // Don't run if nonce field isn't present
        
        const formData = new FormData();
        formData.append('action', 'check_student_incoming_requests');
        formData.append('nonce', nonce);

        fetch(ajaxurl, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                     // Update Notifications Box
                     const notificationsDiv = document.getElementById('studentRequestNotifications');
                     if (notificationsDiv) {
                         notificationsDiv.innerHTML = data.data.notificationsHtml || '<div class="alert alert-secondary">No current notifications.</div>';
                     }

                     // Update Incoming Tutor Requests Table
                     const incomingTutorTableBody = document.querySelector('.incoming-tutor-requests-table-body');
                     if (incomingTutorTableBody) {
                         incomingTutorTableBody.innerHTML = data.data.incomingTutorRequestsHtml || '<tr><td colspan="6">No incoming requests found.</td></tr>';
                     }
                     
                     // Update Incoming Tutor Request Count Badge
                     const incomingTutorBadge = document.querySelector('.incoming-tutor-request-count');
                     if (incomingTutorBadge) {
                         const count = data.data.pendingTutorRequestCount || 0;
                         incomingTutorBadge.textContent = count;
                         incomingTutorBadge.style.display = count > 0 ? 'inline-block' : 'none';
                     }
                     
                     // Update Tutor Alternatives Count Badge
                     const alternativesBadge = document.querySelector('.tutor-alternatives-count');
                     if (alternativesBadge) {
                         const count = data.data.pendingAlternativesCount || 0;
                          alternativesBadge.textContent = count;
                          alternativesBadge.style.display = count > 0 ? 'inline-block' : 'none';
                      }
                      
                     // Update main Requests Tab Badge
                     updateMainRequestsTabBadge(data.data.count || 0);

                     // Refresh tooltips if new content was added
                     initializeTooltips(); 
                     
                     initialLoadComplete = true;

                } else {
                    console.error('Error checking student requests:', data.data?.message);
                    if (!initialLoadComplete) {
                        // Show error on initial load failure
                         const notificationsDiv = document.getElementById('studentRequestNotifications');
                         if (notificationsDiv) notificationsDiv.innerHTML = '<div class="alert alert-danger">Error loading notifications.</div>';
                         const incomingTutorTableBody = document.querySelector('.incoming-tutor-requests-table-body');
                         if (incomingTutorTableBody) incomingTutorTableBody.innerHTML = '<tr><td colspan="6">Error loading requests.</td></tr>';
                         initialLoadComplete = true; // Prevent repeated errors shown
                    }
                }
            })
            .catch(error => {
                console.error('Fetch error checking student requests:', error);
                 if (!initialLoadComplete) {
                     const notificationsDiv = document.getElementById('studentRequestNotifications');
                     if (notificationsDiv) notificationsDiv.innerHTML = '<div class="alert alert-danger">Network error loading notifications.</div>';
                     const incomingTutorTableBody = document.querySelector('.incoming-tutor-requests-table-body');
                     if (incomingTutorTableBody) incomingTutorTableBody.innerHTML = '<tr><td colspan="6">Network error loading requests.</td></tr>';
                      initialLoadComplete = true; 
                 }
            });
    }

    function updateMainRequestsTabBadge(count) {
        const requestsTabButton = document.getElementById('requests-tab-button');
        if (!requestsTabButton) return;
        let badge = requestsTabButton.querySelector('.notification-badge');
        
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'badge rounded-pill bg-danger notification-badge ms-1'; // Added ms-1
                requestsTabButton.appendChild(badge);
            }
            badge.textContent = count;
            badge.style.display = 'inline-block';
        } else if (badge) {
            badge.style.display = 'none';
        }
    }
    
     // Function to show dismissible alerts at the top of the content area
     function showGlobalAlert(type, message) {
         const container = document.querySelector('.student-requests-content'); // Target the main content div
         if (!container) return;

         const alertDiv = document.createElement('div');
         alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
         alertDiv.role = 'alert';
         alertDiv.innerHTML = `
             ${message}
             <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         `;
         
         // Prepend the alert to the container
         container.insertBefore(alertDiv, container.firstChild);

         // Optional: Auto-dismiss after some time
         // setTimeout(() => {
         //     const alertInstance = bootstrap.Alert.getOrCreateInstance(alertDiv);
         //     if (alertInstance) alertInstance.close();
         // }, 5000); 
     }
     
     // Initialize Bootstrap Tooltips
     function initializeTooltips() {
         const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-tooltip="tooltip"]'));
         tooltipTriggerList.map(function (tooltipTriggerEl) {
             // Ensure tooltip isn't already initialized
             if (!bootstrap.Tooltip.getInstance(tooltipTriggerEl)) {
                  return new bootstrap.Tooltip(tooltipTriggerEl);
             }
             return null;
         });
     }

    // Initial check when DOM is ready
    checkStudentIncomingRequests();
    initializeTooltips();

    // Periodic check (e.g., every 30 seconds)
     setInterval(checkStudentIncomingRequests, 30000); 

     // Re-check when the requests tab becomes active (if using Bootstrap tabs)
     const requestsTabButton = document.getElementById('requests-tab-button');
     if (requestsTabButton) {
         requestsTabButton.addEventListener('shown.bs.tab', function (event) {
             checkStudentIncomingRequests();
             // Mark alternatives as viewed? Depends on UX preference
             // markTutorAlternativesAsViewed(); 
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
             // Add other formats if needed
             return dt.toISOString(); // Fallback
         } catch (e) {
             return 'Invalid Date';
         }
     }
     
     function get_tutor_display_name(login) {
         // In JS, we don't have direct access to WP user meta.
         // Pass the display name via data attributes instead, or make another AJAX call if absolutely necessary.
         return login; // Simple fallback
     }

});
</script>

<style>
    /* Style for notification badge */
    .notification-badge {
        font-size: 0.75rem;
        vertical-align: super;
        /* padding: 0.2em 0.4em; */ /* Adjust padding if needed */
    }
</style>

