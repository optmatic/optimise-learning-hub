<!-- Outgoing Reschedule Requests (Tutor-initiated) -->
<div class="card mb-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-arrow-left me-2"></i> Your Outgoing Reschedule Requests
        </div>
        <div class="card-body">
            <?php
            // Get tutor's reschedule requests
            $tutor_requests_args = array(
                'post_type'      => 'progress_report',
                'posts_per_page' => -1,
                'meta_query'     => array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'tutor_id',
                        'value'   => get_current_user_id(),
                        'compare' => '=',
                    ),
                    array(
                        'key'     => 'request_type',
                        'value'   => 'tutor_reschedule',
                        'compare' => '=',
                    )
                ),
                'order'          => 'DESC',
                'orderby'        => 'date'
            );
            
            $tutor_requests = get_posts($tutor_requests_args);
            
            if (!empty($tutor_requests)) {
                echo '<div class="table-responsive">';
                echo '<table class="table table-striped">';
                echo '<thead><tr>
                    <th>Date Requested</th>
                    <th>Lesson Date</th>
                    <th>Preferred Times</th>
                    <th>Student</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr></thead>';
                echo '<tbody>';
                
                foreach ($tutor_requests as $request) {
                    $request_id = $request->ID;
                    $student_name = get_post_meta($request_id, 'student_name', true);
                    $original_date = get_post_meta($request_id, 'original_date', true);
                    $original_time = get_post_meta($request_id, 'original_time', true);
                    $status = get_post_meta($request_id, 'status', true);
                    $request_date = get_the_date('M j, Y', $request_id);
                    $reason = get_post_meta($request_id, 'reason', true);
                    $preferred_times = get_post_meta($request_id, 'preferred_times', true);
                    
                    // Format the original date for display
                    $formatted_original = !empty($original_date) ? date('M j, Y', strtotime($original_date)) . ' at ' . date('g:i A', strtotime($original_time)) : 'N/A';
                    
                    // Set status badge
                    if ($status === 'confirmed' || $status === 'accepted') {
                        $status_badge = '<span class="badge bg-success">Accepted</span>';
                    } elseif ($status === 'denied' || $status === 'declined') {
                        $status_badge = '<span class="badge bg-danger">Declined</span>';
                    } elseif ($status === 'unavailable') {
                        $status_badge = '<span class="badge bg-warning">Student Unavailable</span>';
                    } else {
                        $status_badge = '<span class="badge bg-warning">Pending</span>';
                    }
                    
                    echo '<tr>';
                    echo '<td>' . esc_html($request_date) . '</td>';
                    echo '<td>' . esc_html($formatted_original) . '</td>';
                    
                    // Display preferred times
                    echo '<td>';
                    if (!empty($preferred_times) && is_array($preferred_times)) {
                        foreach ($preferred_times as $index => $time) {
                            if (!empty($time['date']) && !empty($time['time'])) {
                                $formatted_time = date('M j, Y \a\t g:i A', 
                                    strtotime($time['date'] . ' ' . $time['time']));
                                echo 'Option ' . ($index + 1) . ': ' . esc_html($formatted_time) . '<br>';
                            }
                        }
                    } else {
                        echo 'No preferred times specified';
                    }
                    echo '</td>';
                    
                    echo '<td>' . esc_html(get_student_display_name($student_name)) . '</td>';
                    
                    // Add reason column with truncation and modal functionality
                    echo '<td>';
                    if (!empty($reason)) {
                        $truncated_reason = strlen($reason) > 30 ? substr($reason, 0, 30) . '...' : $reason;
                        echo '<span class="reason-text" style="cursor: pointer; color: #fcb31e;" 
                               data-bs-toggle="modal" data-bs-target="#reasonModal" 
                               data-reason="' . esc_attr($reason) . '"
                               data-bs-toggle="tooltip" title="Click to expand">' . esc_html($truncated_reason) . '</span>';
                    } else {
                        echo '<em>No reason provided</em>';
                    }
                    echo '</td>';
                    
                    echo '<td>' . $status_badge . '</td>';
                    echo '<td>';
                    
                    // Only show edit/delete buttons for pending requests
                    if ($status !== 'confirmed' && $status !== 'denied' && $status !== 'accepted' && $status !== 'declined') {
                        echo '<button type="button" class="btn btn-sm btn-primary me-1 edit-request-btn" 
                            data-bs-toggle="modal" data-bs-target="#editRescheduleRequestModal" 
                            data-request-id="' . $request_id . '"
                            data-student-name="' . esc_attr($student_name) . '"
                            data-original-date="' . esc_attr($original_date) . '"
                            data-original-time="' . esc_attr($original_time) . '"
                            data-reason="' . esc_attr($reason) . '">
                            <i class="fas fa-edit"></i> Edit
                        </button>';
                        
                        echo '<button type="button" class="btn btn-sm btn-danger delete-request-btn" 
                            data-request-id="' . $request_id . '">
                            <i class="fas fa-trash"></i> Delete
                        </button>';
                    } else {
                        echo '<span class="text-muted">No actions available</span>';
                    }
                    
                    echo '</td></tr>';
                }
                
                echo '</tbody></table>';
                echo '</div>';
            } else {
                echo '<p>You have not submitted any reschedule requests yet.</p>';
            }
            ?>
        </div>
    </div> 
    
    <!-- Modal for editing a reschedule request -->
    <div class="modal fade" id="editRescheduleRequestModal" tabindex="-1" aria-labelledby="editRescheduleRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRescheduleRequestModalLabel">Edit Reschedule Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="editRescheduleSuccessMessage" class="alert alert-success" style="display: none;">
                        <p>Your reschedule request has been successfully updated.</p>
                    </div>
                    <form id="editRescheduleRequestForm" method="post">
                        <input type="hidden" name="update_tutor_reschedule_request" value="1">
                        <input type="hidden" name="request_id" id="edit_request_id" value="">
                        <input type="hidden" name="active_tab" value="requests">
                        
                        <div class="mb-3">
                            <label class="form-label">Student</label>
                            <input type="text" class="form-control" id="edit_student_name" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Original Lesson Date/Time</label>
                            <input type="text" class="form-control" id="edit_original_datetime" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_reason" class="form-label">Reason for Reschedule <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit_reason" name="reason" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Preferred Alternative Times</label>
                            <p class="text-muted small">Please select up to 3 preferred alternative dates and times.</p>
                            
                            <div id="edit-preferred-times-container">
                                <?php render_preferred_time_inputs('edit_'); ?>
                            </div>
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