<?php
/*
Template Name: Student Dashboard
*/
?>

<?php get_header(); ?>

<?php
if (current_user_can('student')) {
?>

<?php
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
        echo '<div class="alert alert-success">Your selection has been confirmed.</div>';
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
        echo '<div class="alert alert-info">You have marked yourself as unavailable for all alternative times. Your tutor will be notified.</div>';
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
    }
}

// Process confirmation of tutor-initiated reschedule
if (isset($_POST['confirm_reschedule']) && $_POST['confirm_reschedule'] === '1') {
    $request_id = intval($_POST['request_id']);
    
    // Update the request status
    update_post_meta($request_id, 'status', 'confirmed');
    
    // Show confirmation message
    echo '<div class="alert alert-success">You have accepted the reschedule request.</div>';
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
        
        // Show confirmation message
        echo '<div class="alert alert-info">You have declined the reschedule request. Your tutor will be notified.</div>';
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
        
        // Show confirmation message
        echo '<div class="alert alert-success">Your reschedule request has been deleted.</div>';
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
        
        // Show confirmation message
        echo '<div class="alert alert-success">Your reschedule request has been updated.</div>';
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
            array('key' => 'request_type', 'value' => 'reschedule', 'compare' => '='),
            array('key' => 'status', 'value' => 'pending', 'compare' => '=')
        ),
        'order'          => 'DESC',
        'orderby'        => 'date'
    );
    
    $tutor_requests = get_posts($tutor_requests_args);
    $count = count($tutor_requests);
    
    // Generate HTML for the requests section
    $html = '';
    if ($count > 0) {
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-striped">';
        $html .= '<thead><tr><th>Date Requested</th><th>Original Lesson</th><th>Proposed New Time</th><th>Tutor</th><th>Action</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($tutor_requests as $request) {
            $request_id = $request->ID;
            $tutor_name = esc_html(get_post_meta($request_id, 'tutor_name', true));
            $original_date = esc_html(get_post_meta($request_id, 'original_date', true));
            $original_time = esc_html(get_post_meta($request_id, 'original_time', true));
            $new_date = esc_html(get_post_meta($request_id, 'new_date', true));
            $new_time = esc_html(get_post_meta($request_id, 'new_time', true));
            $request_date = esc_html(get_the_date('M j, Y', $request_id));
            
            $html .= '<tr>';
            $html .= '<td>' . $request_date . '</td>';
            $html .= '<td>' . $original_date . ' at ' . $original_time . '</td>';
            $html .= '<td>' . $new_date . ' at ' . $new_time . '</td>';
            $html .= '<td>' . $tutor_name . '</td>';
            $html .= '<td>';
            $html .= '<form method="post" class="d-inline">';
            $html .= '<input type="hidden" name="confirm_reschedule" value="1">';
            $html .= '<input type="hidden" name="request_id" value="' . $request_id . '">';
            $html .= '<button type="submit" class="btn btn-sm btn-success me-1">Accept</button>';
            $html .= '</form>';
            $html .= '<form method="post" class="d-inline">';
            $html .= '<input type="hidden" name="decline_reschedule" value="1">';
            $html .= '<input type="hidden" name="request_id" value="' . $request_id . '">';
            $html .= '<button type="submit" class="btn btn-sm btn-danger">Decline</button>';
            $html .= '</form>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        $html .= '</div>';
    } else {
        $html = '<p>No incoming reschedule requests from tutors at this time.</p>';
    }
    
    wp_send_json_success(['count' => $count, 'html' => $html]);
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

// Uncomment this line to run the test (then comment it out again after testing)
// echo test_reschedule_requests();
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
            
            // Count pending reschedule requests
            $pending_args = array(
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
                        'relation' => 'OR',
                        array(
                            'key'     => 'status',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key'     => 'status',
                            'value'   => 'pending',
                            'compare' => '=',
                        ),
                        array(
                            'key'     => 'status',
                            'value'   => '',
                            'compare' => '=',
                        )
                    )
                ),
                'fields'         => 'ids'
            );
            $pending_count = count(get_posts($pending_args));
            
            // Count pending alternative reschedule requests
            $alternative_args = array(
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
                        'value'   => 'reschedule_alternatives',
                        'compare' => '=',
                    ),
                    array(
                        'key'     => 'status',
                        'value'   => 'pending',
                        'compare' => '=',
                    ),
                    array(
                        'key'     => 'viewed_by_student',
                        'compare' => 'NOT EXISTS',
                    )
                )
            );

            $alternative_count = count(get_posts($alternative_args));
            
            // Create notification badges
            $schedule_notification = $confirmed_count > 0 ? '<span class="badge rounded-pill bg-danger">' . $confirmed_count . '</span>' : '';
            $comms_notification = $pending_count > 0 ? '<span class="badge rounded-pill bg-danger">' . $pending_count . '</span>' : '';
            ?>
            <ul class="nav nav-tabs" id="myTab" role="tablist" style="padding-left: 0px !important;">
                <li class="nav-item">
                    <a class="nav-link active" id="home" data-bs-toggle="tab" href="#home-tab">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="learning-goals-tab" data-bs-toggle="tab" href="#learning-goals">Your Learning Plan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="schedule-tab" data-bs-toggle="tab" href="#schedule">Your Lesson Schedule <?php echo $schedule_notification; ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="classroom-tab" data-bs-toggle="tab" href="#classroom">Your Classrooms</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="my-progress-tab" data-bs-toggle="tab" href="#my-progress">Your Learning Overviews</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" id="tutor-comms-tab" data-bs-toggle="tab" href="#tutor-comms">
                        Requests                        <?php if ($alternative_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $alternative_count; ?>
                            <span class="visually-hidden">alternative times</span>
                        </span>
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
                include 'students/requests.php';
                ?>
    

                
<?php
} else {
    echo "Access denied. You don't have permission to view this page.";
}
?>

<?php get_footer(); ?>
