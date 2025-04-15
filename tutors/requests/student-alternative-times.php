    

    
    
    <!-- Alternative Times Section -->
    <?php
    // Debugging: Log current user ID and other details
    $current_user_id = get_current_user_id();
    error_log("Current Tutor User ID: " . $current_user_id);
    
    $alternative_requests_args = array(
        'post_type'      => 'progress_report',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'tutor_id',
                'value'   => $current_user_id,
                'compare' => '='
            ),
            array(
                'key'     => 'request_type',
                'value'   => 'student_unavailable',
                'compare' => '='
            )
        ),
        'order'          => 'DESC',
        'orderby'        => 'date'
    );
    
    $alternative_requests = get_posts($alternative_requests_args);
    ?>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div><i class="fas fa-exchange-alt me-2"></i> Alternative Lesson Times</div>
                <?php 
                // Count pending alternatives
                $pending_alternatives = count(get_posts(array(
                    'post_type'      => 'progress_report',
                    'posts_per_page' => -1,
                    'meta_query'     => array(
                        'relation' => 'AND',
                        array(
                            'key'     => 'tutor_id',
                            'value'   => $current_user_id,
                            'compare' => '=',
                        ),
                        array(
                            'key'     => 'request_type',
                            'value'   => 'student_unavailable',
                            'compare' => '=',
                        ),
                        array(
                            'key'     => 'status',
                            'value'   => 'pending',
                            'compare' => '=',
                        )
                    ),
                    'fields'         => 'ids'
                )));
                
                if ($pending_alternatives > 0) {
                    echo '<span class="badge bg-danger">' . $pending_alternatives . '</span>';
                }
                ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($alternative_requests)) : 
                // Check for new (unviewed) alternatives
                $has_new_alternatives = false;
                foreach ($alternative_requests as $request) {
                    $viewed = get_post_meta($request->ID, 'viewed_by_tutor', true);
                    $status = get_post_meta($request->ID, 'status', true);
                    if (empty($viewed) && $status === 'pending') {
                        $has_new_alternatives = true;
                        break;
                    }
                }
                
                if ($has_new_alternatives) : ?>
                    <div class="alert alert-info">
                        <i class="fas fa-bell me-2"></i> <strong>New!</strong> Your student has provided alternative lesson times for you to review.
                    </div>
                <?php endif; ?>
                
                <p>Your student is unavailable for the originally requested time and has provided alternative times. Please review and select a time that works for you:</p>
                
                <div class="accordion" id="alternativeAccordion">
                    <?php 
                    $counter = 1;
                    foreach ($alternative_requests as $request) {
                        $request_id = $request->ID;
                        $original_request_id = get_post_meta($request_id, 'original_request_id', true);
                        $student_name = get_post_meta($request_id, 'student_name', true);
                        $alternatives = get_post_meta($request_id, 'alternatives', true);
                        $message = get_post_meta($request_id, 'message', true);
                        $status = get_post_meta($request_id, 'status', true);
                        $request_date = get_the_date('F j, Y', $request_id);
                        $viewed = get_post_meta($request_id, 'viewed_by_tutor', true);
                        
                        // Debugging: Log request details
                        error_log("Alternative Request ID: " . $request_id);
                        error_log("Original Request ID: " . $original_request_id);
                        error_log("Student Name: " . $student_name);
                        error_log("Alternatives: " . print_r($alternatives, true));
                        error_log("Status: " . $status);
                        
                        // Get original request details
                        $original_date = get_post_meta($original_request_id, 'original_date', true);
                        $original_time = get_post_meta($original_request_id, 'original_time', true);
                        
                        $is_new = empty($viewed) && $status === 'pending';
                        $student_display_name = get_student_display_name($student_name);
                        
                        // Format dates
                        $formatted_original_date = !empty($original_date) ? date('l, jS \of F, Y', strtotime($original_date)) : 'N/A';
                        $formatted_original_time = !empty($original_time) ? date('g:i A', strtotime($original_time)) : '';
                        
                        // Add "New" badge for unviewed alternatives
                        $new_badge = $is_new ? '<span class="badge bg-danger ms-2">New</span>' : '';
                        ?>
                        
                        <div class="accordion-item<?php echo $is_new ? ' border-danger' : ''; ?>">
                            <h2 class="accordion-header" id="alternativeHeading<?php echo $counter; ?>">
                                <button class="accordion-button<?php echo $is_new ? '' : ' collapsed'; ?>" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#alternativeCollapse<?php echo $counter; ?>" 
                                        aria-expanded="<?php echo $is_new ? 'true' : 'false'; ?>" 
                                        aria-controls="alternativeCollapse<?php echo $counter; ?>">
                                    Alternative Times - <?php echo $request_date; ?> from <?php echo $student_display_name; ?> 
                                    <?php echo get_status_badge($status) . $new_badge; ?>
                                </button>
                            </h2>
                            
                            <div id="alternativeCollapse<?php echo $counter; ?>" 
                                 class="accordion-collapse collapse<?php echo $is_new ? ' show' : ''; ?>" 
                                 aria-labelledby="alternativeHeading<?php echo $counter; ?>" 
                                 data-bs-parent="#alternativeAccordion">
                                <div class="accordion-body">
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">Original Lesson</div>
                                        <div class="card-body">
                                            <p><strong>Date:</strong> <?php echo $formatted_original_date; ?></p>
                                            <?php if (!empty($formatted_original_time)) : ?>
                                                <p><strong>Time:</strong> <?php echo $formatted_original_time; ?></p>
                                            <?php endif; ?>
                                            <p><strong>Student:</strong> <?php echo esc_html($student_display_name); ?></p>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($message)) : ?>
                                        <div class="alert alert-info">
                                            <p><strong>Message from student:</strong> <?php echo esc_html($message); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($status !== 'confirmed') : ?>
                                        <form method="post" class="mt-3">
                                            <input type="hidden" name="select_alternative" value="1">
                                            <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                                            <input type="hidden" name="active_tab" value="requests">
                                            
                                            <div class="list-group mb-3">
                                                <?php foreach ($alternatives as $index => $alternative) : 
                                                    $alt_date = $alternative['date'];
                                                    $alt_time = $alternative['time'];
                                                    
                                                    $formatted_alt_date = date('l, jS \of F, Y', strtotime($alt_date));
                                                    $formatted_alt_time = date('g:i A', strtotime($alt_time));
                                                ?>
                                                    <div class="list-group-item">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="selected_alternative" 
                                                                value="<?php echo $index; ?>" id="alt<?php echo $request_id; ?>_<?php echo $index; ?>" 
                                                                <?php echo ($index === 0) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="alt<?php echo $request_id; ?>_<?php echo $index; ?>">
                                                                Option <?php echo ($index + 1); ?>: <?php echo $formatted_alt_date; ?> at <?php echo $formatted_alt_time; ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-success">Confirm Selected Time</button>
                                        </form>
                                    <?php else : 
                                        // Show the confirmed alternative
                                        $selected_index = get_post_meta($request_id, 'selected_alternative', true);
                                        $selected_alternative = $alternatives[$selected_index];
                                        
                                        $formatted_selected_date = date('l, jS \of F, Y', strtotime($selected_alternative['date']));
                                        $formatted_selected_time = date('g:i A', strtotime($selected_alternative['time']));
                                    ?>
                                        <div class="alert alert-success">
                                            <p><strong>Confirmed Time:</strong> <?php echo $formatted_selected_date; ?> at <?php echo $formatted_selected_time; ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php $counter++; ?>
                    <?php } ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No alternative times have been provided yet. When your students provide alternative times for a lesson, they will appear here.
                </div>
            <?php endif; ?>
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
</div>