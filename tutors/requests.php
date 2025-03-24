<!-- =========================== REQUESTS TAB =========================== -->
<div class="tab-pane fade" id="requests" role="tabpanel" aria-labelledby="requests-tab">
      
      
      
      <?php
        // Display messages from form processing
        $success_message = get_transient('tutor_request_message');
        $error_message = get_transient('tutor_request_error');

        if ($success_message) {
            echo '<div class="alert alert-success">' . esc_html($success_message) . '</div>';
            delete_transient('tutor_request_message');
        }

        if ($error_message) {
            echo '<div class="alert alert-danger">' . esc_html($error_message) . '</div>';
            delete_transient('tutor_request_error');
        }

        // Get current user's display name
        $current_user = wp_get_current_user();
        $tutor_display_name = $current_user->display_name;
    $tutor_username = $current_user->user_login;
    ?>

    <div class="container-fluid">
        <h3>Request Management</h3>
        
        <!-- Minimalist Tab Navigation -->
        <ul class="nav nav-pills mb-4" id="requestTabs" role="tablist" style="padding-bottom: 10px; padding-left: 0px !important;">
            <li class="nav-item"><a class="nav-link active" id="outgoing-tab" data-bs-toggle="tab" href="#outgoing" role="tab" style="border-radius: 4px; margin-right: 10px;">Outgoing Requests</a></li>
            <li class="nav-item"><a class="nav-link" id="incoming-tab" data-bs-toggle="tab" href="#incoming" role="tab" style="border-radius: 4px;">Incoming Requests</a></li>
        </ul>
        
        <!-- Tab Content -->
        <div class="tab-content" id="requestTabContent">
            
            <!-- Outgoing Requests Tab -->
            <div class="tab-pane fade show active" id="outgoing" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6>Requests You've Sent</h6>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newRescheduleModal">Create New Request</button>
                </div>
                
                <!-- Outgoing Requests Table -->
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Student</th><th>Original Time</th><th>Proposed Time</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php
        // Query for outgoing reschedule requests
        $args = array(
            'post_type'      => 'progress_report',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => 'tutor_name',
                    'value'   => $tutor_display_name, // Use display name here
                    'compare' => '=',
                ),
                array(
                    'key'     => 'request_type',
                    'value'   => 'reschedule',
                    'compare' => '=',
                )
            ),
            'order'          => 'DESC',
            'orderby'        => 'date'
        );
        
        $outgoing_requests = get_posts($args);
        
        if (!empty($outgoing_requests)) {
            foreach ($outgoing_requests as $request) {
                $request_id = $request->ID;
                $student_id = get_post_meta($request_id, 'student_id', true);
                $student = get_userdata($student_id);
                
                // Get the student's first and last name instead of username
                $student_name = '';
                if ($student) {
                    // Try to get first and last name
                    if (!empty($student->first_name) && !empty($student->last_name)) {
                        $student_name = $student->first_name . ' ' . $student->last_name;
                    } else {
                        // Fall back to display name
                        $student_name = $student->display_name;
                    }
                } else {
                    $student_name = 'Unknown Student';
                }
                
                $original_date = get_post_meta($request_id, 'original_date', true);
                $original_time = get_post_meta($request_id, 'original_time', true);
                $status = get_post_meta($request_id, 'status', true);
                
                // Format dates for display
                $original_datetime = date('M j, Y', strtotime($original_date)) . ' at ' . date('g:i A', strtotime($original_time));
                
                // Status badge
                $status_class = 'warning';
                $status_text = 'Pending';
                
                if ($status === 'confirmed') {
                    $status_class = 'success';
                    $status_text = 'Confirmed';
                } elseif ($status === 'unavailable') {
                    $status_class = 'danger';
                    $status_text = 'Unavailable';
                }
                
                echo '<tr>';
                echo '<td>' . esc_html($student_name) . '</td>';
                echo '<td>' . esc_html($original_datetime) . '</td>';
                echo '<td>';
                // Display preferred times if they exist
                $preferred_times = get_post_meta($request_id, 'preferred_times', true);
                if (!empty($preferred_times)) {
                    foreach ($preferred_times as $index => $time) {
                        if (!empty($time['date']) && !empty($time['time'])) {
                            $formatted_time = date('M j, Y', strtotime($time['date'])) . ' at ' . 
                                            date('g:i A', strtotime($time['time']));
                            echo 'Option ' . ($index + 1) . ': ' . esc_html($formatted_time) . '<br>';
                        }
                    }
                } else {
                    echo 'No preferred times specified';
                }
                echo '</td>';
                echo '<td><span class="badge bg-' . $status_class . '">' . $status_text . '</span></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="4" class="text-center text-muted">No outgoing requests found.</td></tr>';
        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Incoming Requests Tab -->
            <div class="tab-pane fade" id="incoming" role="tabpanel">
                <h6 class="mb-3">Requests From Students</h6>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Student</th><th>Original Time</th><th>Requested Time</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php
                        // Query for incoming student-initiated reschedule requests
        $incoming_requests = get_posts(array(
            'post_type'      => 'progress_report',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => 'request_type',
                    'value'   => array('reschedule', 'student_reschedule'), // Include both types
                    'compare' => 'IN',
                ),
                array(
                    'key'     => 'tutor_name',
                                    'value'   => $tutor_username, // Match current tutor's username
                    'compare' => '=',
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key'     => 'status',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key'     => 'status',
                        'value'   => 'pending',
                        'compare' => '=',
                    )
                )
            ),
            'order'          => 'DESC',
            'orderby'        => 'date'
        ));
        
        if (!empty($incoming_requests)) {
            foreach ($incoming_requests as $request) {
                $request_id = $request->ID;
                $student_id = get_post_meta($request_id, 'student_id', true);
                $student = get_userdata($student_id);
                $student_name = $student ? $student->display_name : 'Unknown Student';
                
                $original_date = get_post_meta($request_id, 'original_date', true);
                $original_time = get_post_meta($request_id, 'original_time', true);
                $status = get_post_meta($request_id, 'status', true);
                $reason = get_post_meta($request_id, 'reason', true);
                
                // Get preferred times array
                $preferred_times = get_post_meta($request_id, 'preferred_times', true);
                
                // Format original datetime
                $original_datetime = date('M j, Y \a\t g:i A', strtotime("$original_date $original_time"));
                
                // Status badge
                $status_class = 'warning';
                $status_text = 'Pending';
                if ($status === 'confirmed') {
                    $status_class = 'success';
                    $status_text = 'Confirmed';
                } elseif ($status === 'declined') {
                    $status_class = 'danger';
                    $status_text = 'Declined';
                }
                
                echo '<tr data-request-id="' . $request_id . '">';
                echo '<td>' . esc_html($student_name) . '</td>';
                echo '<td>' . esc_html($original_datetime) . '</td>';
                
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
                
                echo '<td><span class="badge bg-' . $status_class . '">' . $status_text . '</span></td>';
                echo '<td>';
                
                                // Direct action buttons (no JavaScript needed)
                                if ($status === 'pending') {
                                    // Use direct forms similar to student implementation
                                    echo '<div class="d-flex align-items-center action-btns">';
                                    
                                    // Confirm form
                                    echo '<form method="post" class="d-inline me-2">';
                                    echo '<input type="hidden" name="confirm_tutor_request" value="1">';
                                    echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                                    echo '<input type="hidden" name="active_tab" value="requests">';
                                    echo '<button type="submit" class="btn btn-sm btn-success">Confirm</button>';
                                    echo '</form>';
                                    
                                    // Decline form
                                    echo '<form method="post" class="d-inline">';
                                    echo '<div class="input-group input-group-sm">';
                                    echo '<input type="text" class="form-control form-control-sm" name="reason" placeholder="Reason" required>';
                                    echo '<input type="hidden" name="decline_tutor_request" value="1">';
                                    echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                                    echo '<input type="hidden" name="active_tab" value="requests">';
                                    echo '<button type="submit" class="btn btn-sm btn-danger">Decline</button>';
                                    echo '</div>';
                                    echo '</form>';
                                    
                                    echo '</div>';
                                } elseif ($status === 'declined') {
                                    echo 'Reason: ' . esc_html($reason);
                                } else {
                                    echo '<span class="text-muted">No actions available</span>';
                                }
                
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="5" class="text-center">No incoming requests found.</td></tr>';
        }
                        ?>
                        </tbody>
                    </table>
                </div>
                
                <?php
                // Display unavailable requests section
        $unavailable_all_args = array(
            'post_type'      => 'progress_report',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => 'tutor_name',
                            'value'   => $tutor_display_name,
                    'compare' => '=',
                ),
                array(
                    'key'     => 'request_type',
                    'value'   => 'reschedule_unavailable_all',
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

        $unavailable_all_requests = get_posts($unavailable_all_args);

        if (!empty($unavailable_all_requests)) {
            echo '<div class="alert alert-warning mt-3">';
            echo '<h6><i class="fas fa-exclamation-triangle me-2"></i>Students Unavailable for All Alternatives</h6>';
            echo '<p>The following students are unavailable for all alternative times you provided:</p>';
            echo '<ul class="list-group">';
            
            foreach ($unavailable_all_requests as $request) {
                $request_id = $request->ID;
                $student_id = get_post_meta($request_id, 'student_id', true);
                $student = get_userdata($student_id);
                $student_name = $student ? $student->display_name : 'Unknown Student';
                $alternatives_request_id = get_post_meta($request_id, 'alternatives_request_id', true);
                $request_date = get_the_date('F j, Y', $request_id);
                
                echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                echo '<div>';
                echo '<strong>' . esc_html($student_name) . '</strong> - ' . $request_date;
                echo '<br><small class="text-muted">Student is unavailable for all alternative times provided</small>';
                echo '</div>';
                echo '<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newAlternativesModal' . $request_id . '">Provide New Alternatives</button>';
                echo '</li>';
                
                // Add modal for providing new alternatives
                echo '<div class="modal fade" id="newAlternativesModal' . $request_id . '" tabindex="-1" aria-labelledby="newAlternativesModalLabel' . $request_id . '" aria-hidden="true">';
                echo '<div class="modal-dialog">';
                echo '<div class="modal-content">';
                echo '<div class="modal-header">';
                echo '<h5 class="modal-title" id="newAlternativesModalLabel' . $request_id . '">Provide New Alternatives for ' . esc_html($student_name) . '</h5>';
                echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                echo '</div>';
                echo '<div class="modal-body">';
                echo '<form method="post" id="newAlternativesForm' . $request_id . '">';
                echo '<input type="hidden" name="provide_new_alternatives" value="1">';
                echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                echo '<input type="hidden" name="student_id" value="' . $student_id . '">';
                        echo '<input type="hidden" name="active_tab" value="requests">';
                
                echo '<p>Please provide new alternative times for this student:</p>';
                
                echo '<div class="mb-3">';
                echo '<label class="form-label">Alternative 1</label>';
                echo '<div class="row">';
                echo '<div class="col-md-6">';
                echo '<input type="date" class="form-control" name="alt1_date" required>';
                echo '</div>';
                echo '<div class="col-md-6">';
                echo '<input type="time" class="form-control" name="alt1_time" required>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="mb-3">';
                echo '<label class="form-label">Alternative 2</label>';
                echo '<div class="row">';
                echo '<div class="col-md-6">';
                echo '<input type="date" class="form-control" name="alt2_date">';
                echo '</div>';
                echo '<div class="col-md-6">';
                echo '<input type="time" class="form-control" name="alt2_time">';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="mb-3">';
                echo '<label class="form-label">Alternative 3</label>';
                echo '<div class="row">';
                echo '<div class="col-md-6">';
                echo '<input type="date" class="form-control" name="alt3_date">';
                echo '</div>';
                echo '<div class="col-md-6">';
                echo '<input type="time" class="form-control" name="alt3_time">';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="mb-3">';
                echo '<label class="form-label">Message to Student</label>';
                echo '<textarea class="form-control" name="message" rows="3" placeholder="Optional message to the student"></textarea>';
                echo '</div>';
                
                echo '<div class="modal-footer">';
                echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                echo '<button type="submit" class="btn btn-primary">Submit New Alternatives</button>';
                echo '</div>';
                echo '</form>';
                echo '</div>'; // End modal-body
                echo '</div>'; // End modal-content
                echo '</div>'; // End modal-dialog
                echo '</div>'; // End modal
            }
            
            echo '</ul>';
            echo '</div>';
        }
                ?>
            </div>
        </div>
    </div>
</div>

<?php
// We need to include these PHP functions at the bottom
// These handle the form submissions

// Tab preservation script
add_action('wp_footer', function() {
    ?>
    <script type="text/javascript">
    (function($) {
        $(function() {
            // Check for active tab in URL
            var urlParams = new URLSearchParams(window.location.search);
            var activeTab = urlParams.get('active_tab');
            
            if (activeTab === 'requests') {
                // Activate the requests tab
                $('#requests-tab').tab('show');
                
                // Get request tab param (if any)
                var requestTab = urlParams.get('request_tab');
                if (requestTab === 'incoming') {
                    // Activate the incoming tab inside requests
                    $('#incoming-tab').tab('show');
                }
            }
        });
    })(jQuery);
    </script>
    <?php
}, 10000);

// Handle form submissions for tutor request actions
if (!function_exists('handle_tutor_request_form_actions')) {
    function handle_tutor_request_form_actions() {
        // Check if we need to set the active tab
        $active_tab = isset($_POST['active_tab']) ? sanitize_text_field($_POST['active_tab']) : '';
        
        // Handle confirmation action
        if (isset($_POST['confirm_tutor_request']) && isset($_POST['request_id'])) {
            $request_id = intval($_POST['request_id']);
            
            if ($request_id > 0) {
                // Set the request status to confirmed
                $result = update_post_meta($request_id, 'status', 'confirmed');
                
                // Debug info
                error_log('Tutor confirm request: ID=' . $request_id . ', Result=' . ($result ? 'success' : 'failed'));
                
                if ($result !== false) {
                    // Set a transient message to display after redirect
                    set_transient('tutor_request_message', 'Request confirmed successfully.', 60);
                } else {
                    // If update_post_meta returned false, check if the meta already existed with the same value
                    $existing_status = get_post_meta($request_id, 'status', true);
                    if ($existing_status === 'confirmed') {
                        set_transient('tutor_request_message', 'Request was already confirmed.', 60);
                    } else {
                        set_transient('tutor_request_error', 'Failed to update the request status.', 60);
                    }
                }
            }
            
            // Redirect to the same page with the active tab parameter
            $redirect_url = add_query_arg('active_tab', $active_tab, remove_query_arg('_wpnonce', $_SERVER['REQUEST_URI']));
            wp_redirect($redirect_url);
            exit;
        }
        
        // Handle decline action
        if (isset($_POST['decline_tutor_request']) && isset($_POST['request_id'])) {
            $request_id = intval($_POST['request_id']);
            $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
            
            if (empty($reason)) {
                set_transient('tutor_request_error', 'Please provide a reason for declining.', 60);
                $redirect_url = add_query_arg('active_tab', $active_tab, remove_query_arg('_wpnonce', $_SERVER['REQUEST_URI']));
                wp_redirect($redirect_url);
                exit;
            }
            
            if ($request_id > 0) {
                // Set the request status to declined
                $status_result = update_post_meta($request_id, 'status', 'declined');
                $reason_result = update_post_meta($request_id, 'reason', $reason);
                
                // Debug info
                error_log('Tutor decline request: ID=' . $request_id . ', Status=' . ($status_result ? 'success' : 'failed') . 
                          ', Reason=' . ($reason_result ? 'success' : 'failed'));
                
                if ($status_result && $reason_result) {
                    set_transient('tutor_request_message', 'Request declined successfully.', 60);
                } else {
                    set_transient('tutor_request_error', 'Failed to update the request status.', 60);
                }
            }
            
            // Redirect to the same page with the active tab parameter
            $redirect_url = add_query_arg('active_tab', $active_tab, remove_query_arg('_wpnonce', $_SERVER['REQUEST_URI']));
            wp_redirect($redirect_url);
            exit;
        }
        
        // Process provide new alternatives submission
        if (isset($_POST['provide_new_alternatives']) && isset($_POST['request_id'])) {
            $request_id = intval($_POST['request_id']);
            $student_id = intval($_POST['student_id']);
            
            // Get alternative times
            $alternatives = array();
            
            if (!empty($_POST['alt1_date']) && !empty($_POST['alt1_time'])) {
                $alternatives[] = array(
                    'date' => sanitize_text_field($_POST['alt1_date']),
                    'time' => sanitize_text_field($_POST['alt1_time'])
                );
            }
            
            if (!empty($_POST['alt2_date']) && !empty($_POST['alt2_time'])) {
                $alternatives[] = array(
                    'date' => sanitize_text_field($_POST['alt2_date']),
                    'time' => sanitize_text_field($_POST['alt2_time'])
                );
            }
            
            if (!empty($_POST['alt3_date']) && !empty($_POST['alt3_time'])) {
                $alternatives[] = array(
                    'date' => sanitize_text_field($_POST['alt3_date']),
                    'time' => sanitize_text_field($_POST['alt3_time'])
                );
            }
            
            $message = !empty($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
            
            // Create a new reschedule request with alternatives
            $new_request = array(
                'post_title'   => 'New Alternative Reschedule Request - ' . wp_get_current_user()->display_name,
                'post_content' => '',
                'post_status'  => 'publish',
                'post_type'    => 'progress_report',
            );
            
            $new_request_id = wp_insert_post($new_request);
            
            if (!is_wp_error($new_request_id)) {
                // Get the original request ID
                $original_request_id = get_post_meta($request_id, 'original_request_id', true);
                
                // Save the request details
                update_post_meta($new_request_id, 'tutor_name', wp_get_current_user()->display_name);
                update_post_meta($new_request_id, 'student_id', $student_id);
                update_post_meta($new_request_id, 'request_type', 'reschedule_alternatives');
                update_post_meta($new_request_id, 'original_request_id', $original_request_id);
                update_post_meta($new_request_id, 'alternatives', $alternatives);
                update_post_meta($new_request_id, 'message', $message);
                update_post_meta($new_request_id, 'status', 'pending');
                
                // Mark the original unavailable_all request as handled
                update_post_meta($request_id, 'status', 'handled');
                
                set_transient('tutor_request_message', 'New alternative times have been successfully submitted.', 60);
            } else {
                set_transient('tutor_request_error', 'Error: ' . $new_request_id->get_error_message(), 60);
            }
            
            // Redirect back with active tab
            $redirect_url = add_query_arg('active_tab', $active_tab, remove_query_arg('_wpnonce', $_SERVER['REQUEST_URI']));
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    // Add action to handle form submissions
    add_action('init', 'handle_tutor_request_form_actions');
}
?>