    <!-- Outgoing Reschedule Requests (Student-initiated) -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-arrow-left me-2"></i> Your Outgoing Reschedule Requests
        </div>
        <div class="card-body">
            <div class="table-container" style="max-height: 400px; overflow-y: auto;">
                <?php
                // Get student's reschedule requests
                $student_requests_args = array(
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
                        'value'   => 'student_reschedule',
                        'compare' => '=',
                        )
                    ),
                    'order'          => 'DESC',
                    'orderby'        => 'date'
                );
                
                $student_requests = get_posts($student_requests_args);
                
                if (!empty($student_requests)) {
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-striped">';
                    echo '<thead><tr><th>Date Requested</th><th>Lesson Date</th><th>Preferred Times</th><th>Tutor</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>';
                    echo '<tbody>';
                    
                    foreach ($student_requests as $request) {
                        $request_id = $request->ID;
                        $tutor_name = get_post_meta($request_id, 'tutor_name', true);
                        $original_date = get_post_meta($request_id, 'original_date', true);
                        $original_time = get_post_meta($request_id, 'original_time', true);
                        $status = get_post_meta($request_id, 'status', true);
                        $request_date = get_the_date('M j, Y', $request_id);
                        $reason = get_post_meta($request_id, 'reason', true);
                        $preferred_times = get_post_meta($request_id, 'preferred_times', true);
                        $tutor_response = get_post_meta($request_id, 'tutor_response', true);
                        
                        // Format the original date for display
                        $formatted_original = !empty($original_date) ? date('M j, Y', strtotime($original_date)) . ' at ' . date('g:i A', strtotime($original_time)) : 'N/A';
                        
                        // Get tutor's full name
                        $tutor_full_name = $tutor_name;
                        $tutor_user = get_user_by('login', $tutor_name);
                        if ($tutor_user) {
                            $first_name = get_user_meta($tutor_user->ID, 'first_name', true);
                            $last_name = get_user_meta($tutor_user->ID, 'last_name', true);
                            
                            if (!empty($first_name) && !empty($last_name)) {
                                $tutor_full_name = $first_name . ' ' . $last_name;
                            } else {
                                $tutor_full_name = $tutor_user->display_name;
                            }
                        }
                        
                        // Set status badge
                        $status_badge = '';
                        $notification = ''; // Initialize notification variable to prevent undefined variable error
                        
                        if ($status === 'confirmed' || $status === 'accepted') {
                            $status_badge = '<span class="badge bg-success">Accepted</span>';
                        } elseif ($status === 'denied' || $status === 'declined') {
                            $status_badge = '<span class="badge bg-danger">Declined</span>';
                            
                            // Add notification for declined requests
                            if (!empty($tutor_response)) {
                                $notification = '<div class="mt-1"><small class="text-danger"><i class="fas fa-info-circle"></i> ' . esc_html($tutor_response) . '</small></div>';
                            }
                        } elseif ($status === 'unavailable') {
                            $status_badge = '<span class="badge bg-warning">Tutor Unavailable</span>';
                            
                            // Check if there are alternative times provided by the tutor
                            $alternative_requests_args = array(
                                'post_type'      => 'progress_report',
                                'posts_per_page' => 1,
                                'meta_query'     => array(
                                    'relation' => 'AND',
                                    array(
                                        'key'     => 'original_request_id',
                                        'value'   => $request_id,
                                        'compare' => '=',
                                    ),
                                    array(
                                        'key'     => 'request_type',
                                        'value'   => 'tutor_unavailable',
                                        'compare' => '=',
                                    )
                                )
                            );
                            
                            $alternative_requests = get_posts($alternative_requests_args);
                            
                            if (!empty($alternative_requests)) {
                                $notification = '<div class="mt-1"><small class="text-info"><i class="fas fa-info-circle"></i> Tutor has provided alternative times</small></div>';
                                $notification .= '<a href="#alternativeTimesSection" class="btn btn-sm btn-outline-primary mt-1">View Alternative Times</a>';
                            } else {
                                $notification = '<div class="mt-1"><small class="text-warning"><i class="fas fa-info-circle"></i> Tutor is unavailable for this time</small></div>';
                            }
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
                        
                        echo '<td>' . esc_html($tutor_full_name) . '</td>';

                        // Modify reason column to use modal
                        echo '<td>';
                        if (!empty($reason)) {
                            $truncated_reason = strlen($reason) > 30 ? substr($reason, 0, 30) . '...' : $reason;
                            echo '<span class="reason-text" style="cursor: pointer; color: #0056b3;" 
                                   data-bs-toggle="modal" 
                                   data-bs-target="#reasonModal" 
                                   data-reason="' . esc_attr($reason) . '">' 
                                   . esc_html($truncated_reason) . '</span>';
                        } else {
                            echo '<em>No reason provided</em>';
                        }
                        echo '</td>';
                        
                        echo '<td>' . $status_badge . $notification . '</td>';
                        echo '<td>';
                        
                        // Only show edit/delete buttons for pending requests
                        if ($status !== 'confirmed' && $status !== 'denied' && $status !== 'accepted' && $status !== 'declined') {
                            // Encode preferred times as JSON for the data attribute
                            $preferred_times_json = !empty($preferred_times) ? esc_attr(json_encode($preferred_times)) : '';
                            
                            echo '<button type="button" class="btn btn-sm btn-primary me-1 edit-request-btn" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editRescheduleRequestModal" 
                                data-request-id="' . $request_id . '"
                                data-tutor-name="' . esc_attr($tutor_name) . '"
                                data-original-date="' . esc_attr($original_date) . '"
                                data-original-time="' . esc_attr($original_time) . '"
                                data-reason="' . esc_attr($reason) . '"
                                data-preferred-times="' . $preferred_times_json . '">
                                <i class="fas fa-edit"></i> Edit
                            </button>';
                            
                            echo '<form method="post" class="d-inline delete-request-form">';
                            echo '<input type="hidden" name="delete_student_request" value="1">';
                            echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                            echo '<input type="hidden" name="active_tab" value="requests">';
                            echo '<button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>';
                            echo '</form>';
                        } else {
                            // Allow archiving for declined/denied requests regardless of date
                            if ($status === 'denied' || $status === 'declined') {
                                echo '<form method="post" class="d-inline delete-request-form">';
                                echo '<input type="hidden" name="delete_student_request" value="1">';
                                echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                                echo '<input type="hidden" name="active_tab" value="requests">';
                                echo '<button type="submit" class="btn btn-sm btn-secondary"><i class="fas fa-archive"></i> Archive</button>';
                                echo '</form>';
                            } else {
                                // For accepted/confirmed requests, check if they're expired
                                $now = new DateTime('now', new DateTimeZone('Australia/Brisbane'));
                                $lesson_date = new DateTime($original_date . ' ' . $original_time, new DateTimeZone('Australia/Brisbane'));
                                $is_expired = $lesson_date < $now;
                                
                                // Check if all preferred times have passed too
                                $all_times_passed = true;
                                if (!empty($preferred_times) && is_array($preferred_times)) {
                                    foreach ($preferred_times as $time) {
                                        if (!empty($time['date']) && !empty($time['time'])) {
                                            $preferred_date = new DateTime($time['date'] . ' ' . $time['time'], new DateTimeZone('Australia/Brisbane'));
                                            if ($preferred_date > $now) {
                                                $all_times_passed = false;
                                                break;
                                            }
                                        }
                                    }
                                }
                                
                                // Show delete button if the request is completed and expired
                                if ($is_expired && $all_times_passed) {
                                    echo '<form method="post" class="d-inline delete-request-form">';
                                    echo '<input type="hidden" name="delete_student_request" value="1">';
                                    echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                                    echo '<input type="hidden" name="active_tab" value="requests">';
                                    echo '<button type="submit" class="btn btn-sm btn-secondary"><i class="fas fa-archive"></i> Archive</button>';
                                    echo '</form>';
                                } else {
                                    echo '<span class="text-muted">No actions available</span>';
                                }
                            }
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
    </div>
    

    <!-- Add Modal for displaying full reason text -->
    <div class="modal fade" id="reasonModal" tabindex="-1" aria-labelledby="reasonModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reasonModalLabel">Reschedule Reason</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="fullReasonText" class="p-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
