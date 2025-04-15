<?php
/*
Template Name: Student Dashboard
*/

// Start session once at the very beginning of the file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Process form submissions before any output is generated
if (isset($_POST['confirm_reschedule']) || isset($_POST['decline_reschedule']) || 
    isset($_POST['delete_student_request']) || isset($_POST['update_student_reschedule_request']) ||
    isset($_POST['select_alternative']) || isset($_POST['unavailable_all']) ||
    isset($_POST['submit_student_reschedule_request'])) {
    
    // Store current tab in session instead of using redirect
    $_SESSION['active_tab'] = 'requests';
    
    // Process confirmation of tutor-initiated reschedule
    if (isset($_POST['confirm_reschedule']) && $_POST['confirm_reschedule'] === '1') {
        $request_id = intval($_POST['request_id']);
        
        // Update the request status
        update_post_meta($request_id, 'status', 'confirmed');
        
        // Set a transient message instead of echoing directly
        set_transient('student_dashboard_message', [
            'type' => 'success',
            'text' => 'You have accepted the reschedule request.'
        ], 60);
    }
    
    // Process declining of tutor-initiated reschedule
    if (isset($_POST['decline_reschedule']) && $_POST['decline_reschedule'] === '1') {
        $request_id = intval($_POST['request_id']);
        
        // Update the request status
        update_post_meta($request_id, 'status', 'declined');
        
        // Create a notification for the tutor
        $new_request = array(
            'post_title'   => 'Student Declined Reschedule Request',
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'progress_report',
        );
        
        $new_request_id = wp_insert_post($new_request);
        
        if (!is_wp_error($new_request_id)) {
            // Copy over the original details
            $student_id = get_post_meta($request_id, 'student_id', true);
            $tutor_name = get_post_meta($request_id, 'tutor_name', true);
            $original_date = get_post_meta($request_id, 'original_date', true);
            $original_time = get_post_meta($request_id, 'original_time', true);
            
            // Save the request details
            update_post_meta($new_request_id, 'tutor_name', $tutor_name);
            update_post_meta($new_request_id, 'student_id', $student_id);
            update_post_meta($new_request_id, 'request_type', 'reschedule_declined');
            update_post_meta($new_request_id, 'original_request_id', $request_id);
            update_post_meta($new_request_id, 'original_date', $original_date);
            update_post_meta($new_request_id, 'original_time', $original_time);
            update_post_meta($new_request_id, 'status', 'pending');
            
            // Set a transient message
            set_transient('student_dashboard_message', [
                'type' => 'info',
                'text' => 'You have declined the reschedule request. Your tutor will be notified.'
            ], 60);
        }
    }
    
    // Process deletion of student-initiated reschedule request
    if (isset($_POST['delete_student_request']) && isset($_POST['request_id'])) {
        $request_id = intval($_POST['request_id']);
        
        // Verify this request belongs to the current student
        $student_id = get_post_meta($request_id, 'student_id', true);
        if ($student_id == get_current_user_id()) {
            // Delete the request
            wp_delete_post($request_id, true);
            
            // Set a transient message
            set_transient('student_dashboard_message', [
                'type' => 'success',
                'text' => 'Your reschedule request has been deleted.'
            ], 60);
        }
    }
    
    // Process update of student-initiated reschedule request
    if (isset($_POST['update_student_reschedule_request']) && $_POST['update_student_reschedule_request'] === '1') {
        $request_id = intval($_POST['request_id']);
        $reason = sanitize_textarea_field($_POST['reason']);
        
        // Process preferred times
        $preferred_times = array();
        for ($i = 1; $i <= 3; $i++) {
            if (!empty($_POST['preferred_date_' . $i]) && !empty($_POST['preferred_time_' . $i])) {
                $preferred_times[] = array(
                    'date' => sanitize_text_field($_POST['preferred_date_' . $i]),
                    'time' => sanitize_text_field($_POST['preferred_time_' . $i])
                );
            }
        }
        
        // Verify this request belongs to the current student
        $student_id = get_post_meta($request_id, 'student_id', true);
        if ($student_id == get_current_user_id()) {
            // Update the request details
            update_post_meta($request_id, 'reason', $reason);
            update_post_meta($request_id, 'preferred_times', $preferred_times);
            
            // Set a transient message
            set_transient('student_dashboard_message', [
                'type' => 'success',
                'text' => 'Your reschedule request has been updated.'
            ], 60);
        }
    }

    // Process alternative time selection
    if (isset($_POST['select_alternative']) && isset($_POST['request_id'])) {
        $request_id = intval($_POST['request_id']);
        $selected_alternative = intval($_POST['selected_alternative']);
        
        // Update the request status
        update_post_meta($request_id, 'status', 'confirmed');
        update_post_meta($request_id, 'selected_alternative', $selected_alternative);
        
        // Get the alternatives
        $alternatives = get_post_meta($request_id, 'alternatives', true);
        $selected = $alternatives[$selected_alternative];
        
        // Get the original request ID
        $original_request_id = get_post_meta($request_id, 'original_request_id', true);
        
        // Create a new confirmed reschedule request
        $new_request = array(
            'post_title'   => 'Confirmed Reschedule Request',
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'progress_report',
        );
        
        $new_request_id = wp_insert_post($new_request);
        
        if (!is_wp_error($new_request_id)) {
            // Copy over the original details
            $student_id = get_post_meta($request_id, 'student_id', true);
            $tutor_name = get_post_meta($request_id, 'tutor_name', true);
            $original_date = get_post_meta($original_request_id, 'original_date', true);
            $original_time = get_post_meta($original_request_id, 'original_time', true);
            
            // Save the request details
            update_post_meta($new_request_id, 'tutor_name', $tutor_name);
            update_post_meta($new_request_id, 'student_id', $student_id);
            update_post_meta($new_request_id, 'request_type', 'reschedule');
            update_post_meta($new_request_id, 'original_date', $original_date);
            update_post_meta($new_request_id, 'original_time', $original_time);
            update_post_meta($new_request_id, 'new_date', $selected['date']);
            update_post_meta($new_request_id, 'new_time', $selected['time']);
            update_post_meta($new_request_id, 'status', 'confirmed');
            
            // Show confirmation message
            set_transient('student_dashboard_message', [
                'type' => 'success',
                'text' => 'Your selection has been confirmed.'
            ], 60);
        }
    }

    // Process "Unavailable for All" selection
    if (isset($_POST['unavailable_all']) && isset($_POST['request_id'])) {
        $request_id = intval($_POST['request_id']);
        
        // Update the request status
        update_post_meta($request_id, 'status', 'unavailable');
        
        // Get the original request ID
        $original_request_id = get_post_meta($request_id, 'original_request_id', true);
        
        // Create a notification for the tutor
        $new_request = array(
            'post_title'   => 'Student Unavailable for All Alternatives',
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'progress_report',
        );
        
        $new_request_id = wp_insert_post($new_request);
        
        if (!is_wp_error($new_request_id)) {
            // Copy over the original details
            $student_id = get_post_meta($request_id, 'student_id', true);
            $tutor_name = get_post_meta($request_id, 'tutor_name', true);
            
            // Save the request details
            update_post_meta($new_request_id, 'tutor_name', $tutor_name);
            update_post_meta($new_request_id, 'student_id', $student_id);
            update_post_meta($new_request_id, 'request_type', 'reschedule_unavailable_all');
            update_post_meta($new_request_id, 'original_request_id', $original_request_id);
            update_post_meta($new_request_id, 'alternatives_request_id', $request_id);
            update_post_meta($new_request_id, 'status', 'pending');
            
            // Show confirmation message
            set_transient('student_dashboard_message', [
                'type' => 'info',
                'text' => 'You have marked yourself as unavailable for all alternative times. Your tutor will be notified.'
            ], 60);
        }
    }

    // Process student reschedule request
    if (isset($_POST['submit_student_reschedule_request']) && $_POST['submit_student_reschedule_request'] === '1') {
        $student_id = intval($_POST['student_id']);
        $tutor_name = sanitize_text_field($_POST['tutor_name']);
        $original_date = sanitize_text_field($_POST['original_date']);
        $original_time = sanitize_text_field($_POST['original_time']);
        $reason = sanitize_textarea_field($_POST['reason']);
        
        // Process preferred times - make sure we're getting the correct format
        $preferred_times = array();
        for ($i = 1; $i <= 3; $i++) {
            $date_key = 'preferred_date_' . $i;
            $time_key = 'preferred_time_' . $i;
            
            if (!empty($_POST[$date_key]) && !empty($_POST[$time_key])) {
                // Ensure proper date/time format
                $date = sanitize_text_field($_POST[$date_key]);
                $time = sanitize_text_field($_POST[$time_key]);
                
                // Validate date and time format
                if (strtotime($date) && strtotime($time)) {
                    $preferred_times[] = array(
                        'date' => $date,
                        'time' => $time
                    );
                }
            }
        }

        $new_request = array(
            'post_title'   => 'Student Reschedule Request',
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'progress_report',
        );
        
        $new_request_id = wp_insert_post($new_request);
        
        if (!is_wp_error($new_request_id)) {
            // Save the request details
            update_post_meta($new_request_id, 'student_id', $student_id);
            update_post_meta($new_request_id, 'tutor_name', $tutor_name);
            update_post_meta($new_request_id, 'request_type', 'student_reschedule');
            update_post_meta($new_request_id, 'original_date', $original_date);
            update_post_meta($new_request_id, 'original_time', $original_time);
            update_post_meta($new_request_id, 'reason', $reason);
            
            // Only save preferred times if we have valid ones
            if (!empty($preferred_times)) {
                update_post_meta($new_request_id, 'preferred_times', $preferred_times);
            }
            
            update_post_meta($new_request_id, 'status', 'pending');
            
            // Debug log to check what's being saved
            error_log('Preferred times saved: ' . print_r($preferred_times, true));
            
            // Set a transient message
            set_transient('student_dashboard_message', [
                'type' => 'success',
                'text' => 'Your reschedule request has been submitted.'
            ], 60);
        }
    }
}

get_header();
?>

<?php
if (current_user_can('student')) {
    // Display any messages saved in transients at the top of the page
    $message = get_transient('student_dashboard_message');
    if ($message) {
        echo '<div class="alert alert-' . $message['type'] . '">' . $message['text'] . '</div>';
        delete_transient('student_dashboard_message');
    }
    
    // Handle AJAX request to mark reschedules as viewed
    if (isset($_POST['mark_viewed']) && $_POST['mark_viewed'] === '1') {
        $confirmed_reschedules = get_posts(array(
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
                    'value'   => 'reschedule',
                    'compare' => '=',
                ),
                array(
                    'key'     => 'status',
                    'value'   => 'confirmed',
                    'compare' => '=',
                )
            ),
            'fields'         => 'ids'
        ));
        
        foreach ($confirmed_reschedules as $reschedule_id) {
            update_post_meta($reschedule_id, 'viewed_by_student', '1');
        }
        
        // If this is an AJAX request, return success
        if (wp_doing_ajax()) {
            wp_send_json_success();
            exit;
        }
    }

    // Add this near the top of the file, after the get_header() call
    add_action('wp_ajax_get_preferred_times', 'get_preferred_times_ajax');
    function get_preferred_times_ajax() {
        if (!isset($_POST['request_id'])) {
            wp_send_json_error();
        }
        
        $request_id = intval($_POST['request_id']);
        $student_id = get_post_meta($request_id, 'student_id', true);
        
        // Verify this request belongs to the current user
        if ($student_id != get_current_user_id()) {
            wp_send_json_error();
        }
        
        $preferred_times = get_post_meta($request_id, 'preferred_times', true);
        wp_send_json_success(array('preferred_times' => $preferred_times));
    }

    /**
     * AJAX handler to check for incoming reschedule requests
     */
    function check_incoming_reschedule_requests_ajax() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'check_incoming_reschedule_requests_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get student ID
        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
        if (!$student_id) {
            wp_send_json_error('Invalid student ID');
            return;
        }
        
        // Query for incoming reschedule requests
        $tutor_requests_args = array(
            'post_type'      => 'progress_report',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array('key' => 'student_id', 'value' => $student_id, 'compare' => '='),
                array('key' => 'request_type', 'value' => 'tutor_reschedule', 'compare' => '='),
                array('key' => 'status', 'value' => 'pending', 'compare' => '=')
            ),
            'order'          => 'DESC',
            'orderby'        => 'date'
        );
        
        $tutor_requests = get_posts($tutor_requests_args);
        $pending_reschedule_count = count($tutor_requests);
        
        // Count pending alternative times
        $pending_alternatives_count = count(get_posts(array(
            'post_type'      => 'progress_report',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array('key' => 'student_id', 'value' => $student_id, 'compare' => '='),
                array('key' => 'request_type', 'value' => 'tutor_unavailable', 'compare' => '='),
                array('key' => 'status', 'value' => 'pending', 'compare' => '=')
            ),
            'fields'         => 'ids'
        )));
        
        // Calculate total unread count
        $count = $pending_reschedule_count + $pending_alternatives_count;
        
        // Generate HTML for the notifications section
        $notifications_html = '';
        if ($pending_reschedule_count > 0 || $pending_alternatives_count > 0) {
            $notifications_html .= '<div class="alert alert-info">';
            $notifications_html .= '<h5><i class="fas fa-bell me-2"></i>Notifications</h5>';
            $notifications_html .= '<ul class="mb-0">';
            
            if ($pending_reschedule_count > 0) {
                $notifications_html .= '<li>You have <strong>' . $pending_reschedule_count . '</strong> pending reschedule ';
                $notifications_html .= 'request' . ($pending_reschedule_count > 1 ? 's' : '') . ' from your tutor. ';
                $notifications_html .= '<a href="#incomingRescheduleSection" class="btn btn-sm btn-primary ms-2">View</a></li>';
            }
            
            if ($pending_alternatives_count > 0) {
                $notifications_html .= '<li>You have <strong>' . $pending_alternatives_count . '</strong> alternative time ';
                $notifications_html .= 'suggestion' . ($pending_alternatives_count > 1 ? 's' : '') . ' from your tutor. ';
                $notifications_html .= '<a href="#alternativeTimesSection" class="btn btn-sm btn-primary ms-2">View</a></li>';
            }
            
            $notifications_html .= '</ul></div>';
        }
        
        // Generate HTML for the requests section
        $incoming_html = '';
        if ($pending_reschedule_count > 0) {
            $incoming_html .= '<div class="table-responsive">';
            $incoming_html .= '<table class="table table-striped">';
            $incoming_html .= '<thead><tr><th>Date Requested</th><th>Original Lesson</th><th>Proposed New Time</th><th>Tutor</th><th>Action</th></tr></thead>';
            $incoming_html .= '<tbody>';
            
            foreach ($tutor_requests as $request) {
                $request_id = $request->ID;
                $tutor_name = esc_html(get_post_meta($request_id, 'tutor_name', true));
                $original_date = esc_html(get_post_meta($request_id, 'original_date', true));
                $original_time = esc_html(get_post_meta($request_id, 'original_time', true));
                $new_date = esc_html(get_post_meta($request_id, 'new_date', true));
                $new_time = esc_html(get_post_meta($request_id, 'new_time', true));
                $request_date = esc_html(get_the_date('M j, Y', $request_id));
                
                // Format dates for display
                $formatted_original = !empty($original_date) ? date('M j, Y', strtotime($original_date)) . ' at ' . date('g:i A', strtotime($original_time)) : 'N/A';
                $formatted_new = !empty($new_date) ? date('M j, Y', strtotime($new_date)) . ' at ' . date('g:i A', strtotime($new_time)) : 'N/A';
                
                $incoming_html .= '<tr>';
                $incoming_html .= '<td>' . $request_date . '</td>';
                $incoming_html .= '<td>' . $formatted_original . '</td>';
                $incoming_html .= '<td>' . $formatted_new . '</td>';
                $incoming_html .= '<td>' . $tutor_name . '</td>';
                $incoming_html .= '<td>';
                $incoming_html .= '<form method="post" class="d-inline">';
                $incoming_html .= '<input type="hidden" name="confirm_reschedule" value="1">';
                $incoming_html .= '<input type="hidden" name="request_id" value="' . $request_id . '">';
                $incoming_html .= '<button type="submit" class="btn btn-sm btn-success me-1">Accept</button>';
                $incoming_html .= '</form>';
                $incoming_html .= '<form method="post" class="d-inline">';
                $incoming_html .= '<input type="hidden" name="decline_reschedule" value="1">';
                $incoming_html .= '<input type="hidden" name="request_id" value="' . $request_id . '">';
                $incoming_html .= '<button type="submit" class="btn btn-sm btn-danger">Decline</button>';
                $incoming_html .= '</form>';
                $incoming_html .= '</td>';
                $incoming_html .= '</tr>';
            }
            
            $incoming_html .= '</tbody></table>';
            $incoming_html .= '</div>';
        } else {
            $incoming_html = '<p>No incoming reschedule requests from tutors at this time.</p>';
        }
        
        wp_send_json_success([
            'count' => $count, 
            'pendingRescheduleCount' => $pending_reschedule_count,
            'pendingAlternativesCount' => $pending_alternatives_count,
            'notificationsHtml' => $notifications_html,
            'incomingHtml' => $incoming_html
        ]);
        exit;
    }
    add_action('wp_ajax_check_incoming_reschedule_requests', 'check_incoming_reschedule_requests_ajax');

    // Add this function to test if the AJAX endpoint is working
    function test_reschedule_requests() {
        // Create a test reschedule request
        $student_id = get_current_user_id();
        $tutor_name = 'Test Tutor';
        $original_date = date('Y-m-d');
        $original_time = '14:00:00';
        $new_date = date('Y-m-d', strtotime('+1 day'));
        $new_time = '15:00:00';
        
        // Create a new reschedule request post
        $new_request = array(
            'post_title'   => 'Test Reschedule Request',
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'progress_report',
        );
        
        $new_request_id = wp_insert_post($new_request);
        
        if (!is_wp_error($new_request_id)) {
            // Save the request details
            update_post_meta($new_request_id, 'student_id', $student_id);
            update_post_meta($new_request_id, 'tutor_name', $tutor_name);
            update_post_meta($new_request_id, 'request_type', 'reschedule');
            update_post_meta($new_request_id, 'original_date', $original_date);
            update_post_meta($new_request_id, 'original_time', $original_time);
            update_post_meta($new_request_id, 'new_date', $new_date);
            update_post_meta($new_request_id, 'new_time', $new_time);
            update_post_meta($new_request_id, 'status', 'pending');
            
            return "Test reschedule request created with ID: $new_request_id";
        } else {
            return "Error creating test reschedule request: " . $new_request_id->get_error_message();
        }
    }
?>

<div class="container mt-4">
    <div class="row">
        <!-- (Navigation) -->
        <div class="col-12">
            <?php
            // Count confirmed reschedule requests that haven't been viewed
            $confirmed_args = array(
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
                        'value'   => 'reschedule',
                        'compare' => '=',
                    ),
                    array(
                        'key'     => 'status',
                        'value'   => 'confirmed',
                        'compare' => '=',
                    ),
                    array(
                        'relation' => 'OR',
                        array(
                            'key'     => 'viewed_by_student',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key'     => 'viewed_by_student',
                            'value'   => '1',
                            'compare' => '!=',
                        )
                    )
                ),
                'fields'         => 'ids'
            );
            $confirmed_count = count(get_posts($confirmed_args));
            
            // Count pending reschedule requests from tutors
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
                'fields'         => 'ids'
            );
            $tutor_requests_count = count(get_posts($tutor_requests_args));
            
            // Count pending alternative times
            $alternatives_args = array(
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
                        'value'   => 'tutor_unavailable',
                        'compare' => '=',
                    ),
                    array(
                        'key'     => 'status',
                        'value'   => 'pending',
                        'compare' => '=',
                    )
                ),
                'fields'         => 'ids'
            );
            $alternatives_count = count(get_posts($alternatives_args));
            
            // Calculate total pending items for the Requests tab
            $requests_notification_count = $tutor_requests_count + $alternatives_count;
            $requests_notification = $requests_notification_count > 0 ? 
                '<span class="badge rounded-pill bg-danger">' . $requests_notification_count . '</span>' : '';
            
            // Create notification badges
            $schedule_notification = $confirmed_count > 0 ? 
                '<span class="badge rounded-pill bg-danger">' . $confirmed_count . '</span>' : '';
            ?>
            <ul class="nav nav-tabs" id="myTab" role="tablist" style="padding-left: 0px !important;">
                <li class="nav-item">
                    <a class="nav-link <?php echo (!isset($_GET['active_tab']) || $_GET['active_tab'] == 'home-tab') ? 'active' : ''; ?>" 
                       id="home" data-bs-toggle="tab" href="#home-tab">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (isset($_GET['active_tab']) && $_GET['active_tab'] == 'learning-goals') ? 'active' : ''; ?>" 
                       id="learning-goals-tab" data-bs-toggle="tab" href="#learning-goals">Your Learning Plan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (isset($_GET['active_tab']) && $_GET['active_tab'] == 'schedule') ? 'active' : ''; ?>" 
                       id="schedule-tab" data-bs-toggle="tab" href="#schedule">Your Lesson Schedule <?php echo $schedule_notification; ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (isset($_GET['active_tab']) && $_GET['active_tab'] == 'classroom') ? 'active' : ''; ?>" 
                       id="classroom-tab" data-bs-toggle="tab" href="#classroom">Your Classrooms</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (isset($_GET['active_tab']) && $_GET['active_tab'] == 'my-progress') ? 'active' : ''; ?>" 
                       id="my-progress-tab" data-bs-toggle="tab" href="#my-progress">Your Learning Overviews</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative <?php echo (isset($_GET['active_tab']) && $_GET['active_tab'] == 'requests') ? 'active' : ''; ?>" 
                       id="requests-tab" data-bs-toggle="tab" href="#requests" role="tab">
                        Requests
                        <?php if ($requests_notification_count > 0): ?>
                            <span class="badge rounded-pill bg-danger notification-badge"><?php echo $requests_notification_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-12">
            <div class="tab-content" id="myTabContent" style="padding-top: 20px;">
                <?php 
                include 'students/home-tab.php';
                include 'students/learning-plan.php';
                include 'students/lesson-schedule.php';
                include 'students/classrooms.php';
                include 'students/learning-overviews.php';
                include 'students/requests/index.php';
                ?>
            </div>
        </div>
    </div>
</div>

<?php
} else {
    echo "Access denied. You don't have permission to view this page.";
}
?>

<?php
// Get active tab from session if available
$active_tab = isset($_SESSION['active_tab']) ? $_SESSION['active_tab'] : '';

// Clear the session variable after reading it
if (!empty($active_tab)) {
    unset($_SESSION['active_tab']);
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check for active tab from PHP session
    const activeTabFromSession = '<?php echo $active_tab; ?>';
    
    if (activeTabFromSession) {
        // Activate the tab from session
        const tabElement = document.querySelector(`a[href="#${activeTabFromSession}"]`);
        if (tabElement) {
            const tab = new bootstrap.Tab(tabElement);
            tab.show();
        }
    } else {
        // Use localStorage as fallback
        const storedTab = localStorage.getItem('activeStudentTab');
        if (storedTab) {
            const tabToSelect = document.querySelector(`a[href="${storedTab}"]`);
            if (tabToSelect) {
                // Trigger a click on the stored tab
                tabToSelect.click();
            }
        }
    }
    
    // Handle tab switching
    const tabLinks = document.querySelectorAll('a[data-bs-toggle="tab"]');
    tabLinks.forEach(function(tabLink) {
        tabLink.addEventListener('click', function(event) {
            // Store which tab was clicked
            localStorage.setItem('activeStudentTab', this.getAttribute('href'));
        });
    });
    
    // Handle form submissions to preserve active tab
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            // Add a hidden field with the current active tab
            const activeTab = document.querySelector('.nav-link.active');
            if (activeTab) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'active_tab';
                hiddenInput.value = activeTab.getAttribute('href').substring(1); // Remove the # from the href
                this.appendChild(hiddenInput);
            }
        });
    });
});
</script>

<?php get_footer(); ?>