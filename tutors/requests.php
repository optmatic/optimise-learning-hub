<?php
/**
 * Request management functionality for the tutor dashboard
 */

// Function to add the requests tab to the navigation
if (!function_exists('add_requests_tab_to_navigation')) {
    function add_requests_tab_to_navigation() {
        // For testing - force a badge to appear
        return '<a class="nav-link position-relative" id="requests-tab" data-bs-toggle="tab" href="#requests">
            Requests <span class="badge rounded-pill bg-danger">1</span>
        </a>';
    }
}

/**
 * Displays the Requests tab content for tutors
 */
if (!function_exists('display_tutor_requests_content')) {
    function display_tutor_requests_content() {
        // Get current user's display name
        $current_user = wp_get_current_user();
        $tutor_display_name = $current_user->display_name;
        
        echo '<div class="container-fluid">';
        echo '<h3>Request Management</h3>';
        
        // Minimalist Tab Navigation
        echo '<ul class="nav nav-pills mb-4" id="requestTabs" role="tablist" style="padding-bottom: 10px; padding-left: 0px !important;">';
        echo '<li class="nav-item"><a class="nav-link active" id="outgoing-tab" data-bs-toggle="tab" href="#outgoing" role="tab" style="border-radius: 4px; margin-right: 10px;">Outgoing Requests</a></li>';
        echo '<li class="nav-item"><a class="nav-link" id="incoming-tab" data-bs-toggle="tab" href="#incoming" role="tab" style="border-radius: 4px;">Incoming Requests</a></li>';
        echo '</ul>';
        
        // Tab Content
        echo '<div class="tab-content" id="requestTabContent">';
        
        // Outgoing Requests Tab
        echo '<div class="tab-pane fade show active" id="outgoing" role="tabpanel">';
        echo '<div class="d-flex justify-content-between align-items-center mb-3">';
        echo '<h6>Requests You\'ve Sent</h6>';
        echo '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newRescheduleModal">Create New Request</button>';
        echo '</div>';
        
        // Outgoing Requests Table
        echo '<div class="table-responsive">';
        echo '<table class="table">';
        echo '<thead><tr><th>Student</th><th>Original Time</th><th>Proposed Time</th><th>Status</th></tr></thead>';
        echo '<tbody>';
        
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
                $new_date = get_post_meta($request_id, 'new_date', true);
                $new_time = get_post_meta($request_id, 'new_time', true);
                $status = get_post_meta($request_id, 'status', true);
                
                // Format dates for display
                $original_datetime = date('M j, Y', strtotime($original_date)) . ' at ' . date('g:i A', strtotime($original_time));
                $new_datetime = date('M j, Y', strtotime($new_date)) . ' at ' . date('g:i A', strtotime($new_time));
                
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
        
        echo '</tbody></table>';
        echo '</div>'; // End table-responsive
        echo '</div>'; // End outgoing tab
        
        // Incoming Requests Tab
        echo '<div class="tab-pane fade" id="incoming" role="tabpanel">';
        echo '<h6 class="mb-3">Requests From Students</h6>';
        
        echo '<div class="table-responsive">';
        echo '<table class="table">';
        echo '<thead><tr><th>Student</th><th>Original Time</th><th>Requested Time</th><th>Status</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        
        // Replace with this updated query to include student-initiated reschedule requests
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
                    'value'   => wp_get_current_user()->user_login, // Match current tutor's username
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
                // Get preferred times array
                $preferred_times = get_post_meta($request_id, 'preferred_times', true);

                // Display preferred times
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
                
                // Render action buttons
                echo render_action_buttons($request);
                
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="5" class="text-center">No incoming requests found.</td></tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
        
        // Process form submissions
        handle_request_form_submissions();
        
        // Display unavailable requests section
        display_unavailable_requests_section();
        
        echo '</div>'; // End tab-content
        echo '</div>'; // End container-fluid
    }
}

// Helper function to render action buttons
if (!function_exists('render_action_buttons')) {
    function render_action_buttons($request) {
        $request_id = $request->ID;
        $status = get_post_meta($request_id, 'status', true);
        $reason = get_post_meta($request_id, 'reason', true);
        
        $output = '';
        
        if ($status === 'pending') {
            $output .= '<button type="button" class="btn btn-sm btn-success confirm-action" data-request-id="' . $request_id . '">Confirm</button> ';
            $output .= '<div class="d-inline-block">';
            $output .= '<input type="text" class="form-control form-control-sm d-inline-block decline-reason" style="width: 150px;" placeholder="Reason">';
            $output .= '<button type="button" class="btn btn-sm btn-danger decline-action" data-request-id="' . $request_id . '">Decline</button>';
            $output .= '</div>';
        } elseif ($status === 'declined') {
            $output .= 'Reason: ' . esc_html($reason);
        } else {
            $output .= '<span class="text-muted">No actions available</span>';
        }
        
        return $output;
    }
}

// Handle processing of form submissions
if (!function_exists('handle_request_form_submissions')) {
    function handle_request_form_submissions() {
        // Process alternative times submission
        if (isset($_POST['provide_alternatives']) && isset($_POST['request_id'])) {
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
                'post_title'   => 'Alternative Reschedule Request - ' . wp_get_current_user()->display_name,
                'post_content' => '',
                'post_status'  => 'publish',
                'post_type'    => 'progress_report',
            );
            
            $new_request_id = wp_insert_post($new_request);
            
            if (!is_wp_error($new_request_id)) {
                // Save the request details
                update_post_meta($new_request_id, 'tutor_name', wp_get_current_user()->display_name);
                update_post_meta($new_request_id, 'student_id', $student_id);
                update_post_meta($new_request_id, 'request_type', 'reschedule_alternatives');
                update_post_meta($new_request_id, 'original_request_id', $request_id);
                update_post_meta($new_request_id, 'alternatives', $alternatives);
                update_post_meta($new_request_id, 'message', $message);
                update_post_meta($new_request_id, 'status', 'pending');
                
                // Mark the original request as having alternatives provided
                update_post_meta($request_id, 'alternatives_provided', '1');
                
                // Set a global message to display to the user
                global $submission_message;
                $submission_message = 'Alternative times have been successfully submitted.';
            } else {
                global $submission_message;
                $submission_message = 'Error: ' . $new_request_id->get_error_message();
            }
        }
        
        // Process new alternatives submission
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
                
                // Set a global message to display to the user
                global $submission_message;
                $submission_message = 'New alternative times have been successfully submitted.';
            } else {
                global $submission_message;
                $submission_message = 'Error: ' . $new_request_id->get_error_message();
            }
        }
    }
}

// Display section for unavailable requests
if (!function_exists('display_unavailable_requests_section')) {
    function display_unavailable_requests_section() {
        // Get requests where student is unavailable for all alternatives
        $unavailable_all_args = array(
            'post_type'      => 'progress_report',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => 'tutor_name',
                    'value'   => wp_get_current_user()->display_name,
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
    }
}

// Add AJAX handler for tutor request actions
add_action('wp_ajax_handle_tutor_request_ajax', 'handle_tutor_request_ajax');
if (!function_exists('handle_tutor_request_ajax')) {
    function handle_tutor_request_ajax() {
        // Check nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'tutor_request_action')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $request_action = isset($_POST['request_action']) ? sanitize_text_field($_POST['request_action']) : '';
        $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
        
        if (!$request_id) {
            wp_send_json_error(['message' => 'Invalid request ID']);
            return;
        }
        
        // Handle confirm action
        if ($request_action === 'confirm') {
            update_post_meta($request_id, 'status', 'confirmed');
            wp_send_json_success(['message' => 'Request confirmed successfully']);
            return;
        }
        
        // Handle decline action
        if ($request_action === 'decline') {
            $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
            
            if (empty($reason)) {
                wp_send_json_error(['message' => 'Please provide a reason for declining']);
                return;
            }
            
            update_post_meta($request_id, 'status', 'declined');
            update_post_meta($request_id, 'reason', $reason);
            
            wp_send_json_success(['message' => 'Request declined successfully']);
            return;
        }
        
        wp_send_json_error(['message' => 'Invalid action']);
    }
}

// The HTML tab content
?>
                <!-- ===========================
                     REQUESTS TAB
                     =========================== -->
                     <div class="tab-pane fade" id="requests" role="tabpanel" aria-labelledby="requests-tab">
                    <?php display_tutor_requests_content(); ?>
                </div>