
    <!-- Incoming Reschedule Requests (Tutor-initiated) -->
    <div class="card mb-4" id="incomingRescheduleSection">
        <div class="card-header bg-warning text-dark">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-arrow-right me-2"></i> Incoming Reschedule Requests
                </div>
                <?php 
                // Count pending reschedule requests
                $pending_reschedule_count = count(get_posts(array(
                    'post_type'      => 'progress_report',
                    'posts_per_page' => -1,
                    'meta_query'     => array(
                        'relation' => 'AND',
                        array('key' => 'student_id', 'value' => get_current_user_id(), 'compare' => '='),
                        array('key' => 'request_type', 'value' => 'tutor_reschedule', 'compare' => '='),
                        array('key' => 'status', 'value' => 'pending', 'compare' => '=')
                    ),
                    'fields'         => 'ids'
                )));
                
                if ($pending_reschedule_count > 0): 
                ?>
                <span class="badge bg-danger"><?php echo $pending_reschedule_count; ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php
            // Get tutor-initiated reschedule requests
            $tutor_requests_args = array(
                'post_type'      => 'progress_report',
                'posts_per_page' => -1,
                'meta_query'     => array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'student_id',
                        'value'   => get_current_user_id(),
                        'compare' => '=',
                    ),
                    array(
                        'key'     => 'request_type',
                        'value'   => 'tutor_reschedule',
                        'compare' => '=',
                    ),
                    array(
                        'key'     => 'status',
                        'value'   => 'pending',
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
                echo '<thead><tr><th>Date Requested</th><th>Original Lesson</th><th>Proposed New Time</th><th>Tutor</th><th>Action</th></tr></thead>';
                echo '<tbody>';
                
                foreach ($tutor_requests as $request) {
                    $request_id = $request->ID;
                    $tutor_name = get_post_meta($request_id, 'tutor_name', true);
                    $original_date = get_post_meta($request_id, 'original_date', true);
                    $original_time = get_post_meta($request_id, 'original_time', true);
                    
                    // Check all possible field names for new date/time
                    $new_date = get_post_meta($request_id, 'new_date', true);
                    if (empty($new_date)) {
                        $new_date = get_post_meta($request_id, 'proposed_date', true);
                    }
                    
                    $new_time = get_post_meta($request_id, 'new_time', true);
                    if (empty($new_time)) {
                        $new_time = get_post_meta($request_id, 'proposed_time', true);
                    }
                    
                    // Try to get proposed time from a different structure
                    if (empty($new_date) || empty($new_time)) {
                        $proposed_time = get_post_meta($request_id, 'proposed_time_slot', true);
                        if (!empty($proposed_time) && is_array($proposed_time)) {
                            if (isset($proposed_time['date'])) {
                                $new_date = $proposed_time['date'];
                            }
                            if (isset($proposed_time['time'])) {
                                $new_time = $proposed_time['time'];
                            }
                        }
                    }
                    
                    // Check for alternative times array
                    if (empty($new_date) || empty($new_time)) {
                        $alternatives = get_post_meta($request_id, 'alternatives', true);
                        if (!empty($alternatives) && is_array($alternatives) && isset($alternatives[0])) {
                            if (isset($alternatives[0]['date'])) {
                                $new_date = $alternatives[0]['date'];
                            }
                            if (isset($alternatives[0]['time'])) {
                                $new_time = $alternatives[0]['time'];
                            }
                        }
                    }
                    
                    $request_date = get_the_date('M j, Y', $request_id);
                    
                    // Format dates for display - add debugging info if both are empty
                    $formatted_original = !empty($original_date) ? date('M j, Y', strtotime($original_date)) . ' at ' . date('g:i A', strtotime($original_time)) : 'N/A';
                    
                    // Format new date and add debug info if needed
                    if (!empty($new_date) && !empty($new_time)) {
                        $formatted_new = date('M j, Y', strtotime($new_date)) . ' at ' . date('g:i A', strtotime($new_time));
                    } else {
                        // For debugging - dump the post meta to help identify where the data might be stored
                        $all_meta = get_post_meta($request_id);
                        $formatted_new = 'N/A';
                        
                        // Try to find the proposed time in any field
                        foreach ($all_meta as $meta_key => $meta_value) {
                            // Look for keys that might contain date/time information
                            if (strpos($meta_key, 'date') !== false || 
                                strpos($meta_key, 'time') !== false || 
                                strpos($meta_key, 'proposed') !== false || 
                                strpos($meta_key, 'new') !== false) {
                                
                                $value = maybe_unserialize($meta_value[0]);
                                
                                // If we found a serialized array with date/time
                                if (is_array($value) && 
                                    (isset($value['date']) || isset($value['time']) || 
                                     isset($value[0]['date']) || isset($value[0]['time']))) {
                                    
                                    if (isset($value['date']) && isset($value['time'])) {
                                        $new_date = $value['date'];
                                        $new_time = $value['time'];
                                        $formatted_new = date('M j, Y', strtotime($new_date)) . ' at ' . date('g:i A', strtotime($new_time));
                                        break;
                                    } else if (isset($value[0]['date']) && isset($value[0]['time'])) {
                                        $new_date = $value[0]['date'];
                                        $new_time = $value[0]['time'];
                                        $formatted_new = date('M j, Y', strtotime($new_date)) . ' at ' . date('g:i A', strtotime($new_time));
                                        break;
                                    }
                                }
                                // Check for simple date/time strings
                                else if (is_string($value) && strtotime($value) !== false) {
                                    // If it's a date field
                                    if (strpos($meta_key, 'date') !== false && empty($new_date)) {
                                        $new_date = $value;
                                    }
                                    // If it's a time field
                                    else if (strpos($meta_key, 'time') !== false && empty($new_time)) {
                                        $new_time = $value;
                                    }
                                    
                                    // If we have both date and time now, format them
                                    if (!empty($new_date) && !empty($new_time)) {
                                        $formatted_new = date('M j, Y', strtotime($new_date)) . ' at ' . date('g:i A', strtotime($new_time));
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    
                    echo '<tr>';
                    echo '<td>' . esc_html($request_date) . '</td>';
                    echo '<td>' . esc_html($formatted_original) . '</td>';
                    echo '<td>' . esc_html($formatted_new) . '</td>';
                    echo '<td>' . esc_html($tutor_name) . '</td>';
                    echo '<td>';
                    echo '<form method="post" class="d-inline">';
                    echo '<input type="hidden" name="confirm_reschedule" value="1">';
                    echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                    echo '<input type="hidden" name="active_tab" value="requests-tab">';
                    echo '<button type="submit" class="btn btn-sm btn-success me-1">Accept</button>';
                    echo '</form>';
                    
                    echo '<form method="post" class="d-inline">
                        <input type="hidden" name="mark_unavailable" value="1">
                        <input type="hidden" name="request_id" value="' . $request_id . '">
                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                data-bs-target="#unavailableModal" 
                                data-request-id="' . $request_id . '"
                                data-tutor-name="' . esc_attr($tutor_name) . '"
                                data-original-date="' . esc_attr($original_date) . '"
                                data-original-time="' . esc_attr($original_time) . '">
                            Unavailable
                        </button>
                    </form>';
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
                <p><strong>Tutor:</strong> <span id="unavailable_tutor_name"></span></p>
                <p><strong>Original Time:</strong> <span id="unavailable_original_time"></span></p>
                
                <form id="unavailableForm" method="post">
                    <input type="hidden" name="mark_unavailable" value="1">
                    <input type="hidden" name="request_id" id="unavailable_request_id" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">Alternative Times <span class="text-danger">*</span></label>
                        <p class="text-muted small">Please provide at least one alternative date and time.</p>
                        
                        <div id="alternative-times-container">
                            <?php for ($i = 1; $i <= 3; $i++) { ?>
                                <div class="mb-2">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label small">Alternative Date <?php echo $i; ?>:</label>
                                            <input type="date" class="form-control alt-date" 
                                                   name="alt_date_<?php echo $i; ?>" id="alt_date_<?php echo $i; ?>" 
                                                   <?php echo ($i == 1) ? 'required' : ''; ?>>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Alternative Time <?php echo $i; ?>:</label>
                                            <input type="time" class="form-control alt-time" 
                                                   name="alt_time_<?php echo $i; ?>" id="alt_time_<?php echo $i; ?>" 
                                                   <?php echo ($i == 1) ? 'required' : ''; ?>>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
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
