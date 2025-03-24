<?php
/*
Template Name: Tutor Dashboard
*/
?>
<?php
get_header(); ?>

<?php
if (current_user_can('tutor')) {
?>

<?php get_header(); ?>

<?php 
if (current_user_can('tutor')) {
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
                'key'     => 'tutor_id',
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
            update_post_meta($reschedule_id, 'viewed_by_tutor', '1');
        }
        
        // If this is an AJAX request, return success
        if (wp_doing_ajax()) {
            wp_send_json_success();
            exit;
        }
    }

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
        $new_date = get_post_meta($new_request_id, 'new_date', true);
        $new_time = get_post_meta($new_request_id, 'new_time', true);

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
    update_post_meta($request_id, 'status', 'unavailable_all');

    // Get the original request ID
    $original_request_id = get_post_meta($request_id, 'original_request_id', true);
    
    // Create a notification for the student
    $new_request = array(
        'post_title'   => 'Tutor Unavailable for All Alternatives',
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'progress_report',
    );

    $new_request_id = wp_insert_post($new_request);

    if (!is_wp_error($new_request_id)) {
        // Copy over the original details
        $tutor_id = get_post_meta($request_id, 'tutor_id', true);
        $student_name = get_post_meta($request_id, 'student_name', true);
        
        // Save the request details
        update_post_meta($new_request_id, 'student_name', $student_name);
        update_post_meta($new_request_id, 'tutor_id', $tutor_id);
        update_post_meta($new_request_id, 'request_type', 'reschedule_unavailable_all');
        update_post_meta($new_request_id, 'original_request_id', $original_request_id);
        update_post_meta($new_request_id, 'alternatives_request_id', $request_id);
        update_post_meta($new_request_id, 'status', 'pending');
        
        // Show confirmation message
        echo '<div class="alert alert-info">You have marked yourself as unavailable for all alternative times. Your student will be notified.</div>';
    }
}

// Process student reschedule request
if (isset($_POST['submit_tutor_reschedule_request']) && $_POST['submit_tutor_reschedule_request'] === '1') {
    $tutor_id = intval($_POST['tutor_id']);
    $student_name = sanitize_text_field($_POST['student_name']);
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
        'post_title'   => 'Tutor Reschedule Request',
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'progress_report',
    );

    $new_request_id = wp_insert_post($new_request);

    if (!is_wp_error($new_request_id)) {
        // Save the request details
        update_post_meta($new_request_id, 'tutor_id', $tutor_id);
        update_post_meta($new_request_id, 'student_name', $student_name);
        update_post_meta($new_request_id, 'request_type', 'tutor_reschedule');
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

        // Show confirmation message
        echo '<div class="alert alert-success">Your reschedule request has been submitted.</div>';
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

// Process declining of student-initiated reschedule
if (isset($_POST['decline_reschedule']) && $_POST['decline_reschedule'] === '1') {
    $request_id = intval($_POST['request_id']);
    
    // Update the request status
    update_post_meta($request_id, 'status', 'declined');

// Create a notification for the student
$new_request = array(
    'post_title'   => 'Tutor Declined Reschedule Request',
    'post_content' => '',
    'post_status'  => 'publish',
    'post_type'    => 'progress_report',
);

$new_request_id = wp_insert_post($new_request);


if (!is_wp_error($new_request_id)) {
    // Copy over the original details
    $tutor_id = get_post_meta($request_id, 'tutor_id', true);
    $student_name = get_post_meta($request_id, 'student_name', true);
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


    // Process deletion of tutor-initiated reschedule request
    if (isset($_POST['delete_tutor_request']) && isset($_POST['request_id'])) {
        $request_id = intval($_POST['request_id']);
        
        // Verify this request belongs to the current tutor
        $tutor_id = get_post_meta($request_id, 'tutor_id', true);
        if ($tutor_id == get_current_user_id()) {
            // Delete the request
            wp_delete_post($request_id, true);
            
            // Show confirmation message
            echo '<div class="alert alert-success">Your reschedule request has been deleted.</div>';
        }
    }

    // Process update of tutor-initiated reschedule request
    if (isset($_POST['update_tutor_reschedule_request']) && $_POST['update_tutor_reschedule_request'] === '1') {
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


    // Verify this request belongs to the current tutor
    $tutor_id = get_post_meta($request_id, 'tutor_id', true);
    if ($tutor_id == get_current_user_id()) {
        // Update the request details
        update_post_meta($request_id, 'reason', $reason);
        update_post_meta($request_id, 'preferred_times', $preferred_times);
        
        // Show confirmation message
        echo '<div class="alert alert-success">Your reschedule request has been updated.</div>';
    }
}

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
    
    // Get tutor ID
    $tutor_id = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : 0;
    if (!$tutor_id) {
        wp_send_json_error('Invalid tutor ID');
        return;
    }

       // Query for incoming reschedule requests
       $student_requests_args = array(
        'post_type'      => 'progress_report',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array('key' => 'tutor_id', 'value' => $tutor_id, 'compare' => '='),
            array('key' => 'request_type', 'value' => 'reschedule', 'compare' => '='),
            array('key' => 'status', 'value' => 'pending', 'compare' => '=')
        ),
        'order'          => 'DESC',
        'orderby'        => 'date'
    );
    
    $student_requests = get_posts($student_requests_args);
    $count = count($student_requests);

    // Generate HTML for the requests section
    $html = '';
    if ($count > 0) {
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-striped">';
        $html .= '<thead><tr><th>Date Requested</th><th>Original Lesson</th><th>Proposed New Time</th><th>Student</th><th>Action</th></tr></thead>';
        $html .= '<tbody>';


        foreach ($student_requests as $request) {
            $request_id = $request->ID;
            $student_name = esc_html(get_post_meta($request_id, 'student_name', true));
            $original_date = esc_html(get_post_meta($request_id, 'original_date', true));
            $original_time = esc_html(get_post_meta($request_id, 'original_time', true));
            $new_date = esc_html(get_post_meta($request_id, 'new_date', true));
            $new_time = esc_html(get_post_meta($request_id, 'new_time', true));
            $request_date = esc_html(get_the_date('M j, Y', $request_id));
            
            $html .= '<tr>';
            $html .= '<td>' . $request_date . '</td>';
            $html .= '<td>' . $original_date . ' at ' . $original_time . '</td>';
            $html .= '<td>' . $new_date . ' at ' . $new_time . '</td>';
            $html .= '<td>' . $student_name . '</td>';
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
        $html = '<p>No incoming reschedule requests from students at this time.</p>';
    }
    
    wp_send_json_success(['count' => $count, 'html' => $html]);
    exit;
}
add_action('wp_ajax_check_incoming_reschedule_requests', 'check_incoming_reschedule_requests_ajax');

// Add this function to test if the AJAX endpoint is working
function test_reschedule_requests() {
    // Create a test reschedule request
    $student_id = get_current_user_id();
    $student_name = 'Test Student';
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
        update_post_meta($new_request_id, 'tutor_id', $tutor_id);
        update_post_meta($new_request_id, 'student_name', $student_name);
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
<!-- ===========================
     NAVIGATION TABS
     =========================== -->
        <div class="col-12">
        <?php
                        // Count unread requests
                        $current_user = wp_get_current_user();
                        $tutor_name = $current_user->display_name;
                        
                        $unread_requests = get_posts(array(
                            'post_type'      => 'progress_report',
                            'posts_per_page' => -1,
                            'meta_query'     => array(
                                'relation' => 'AND',
                                array(
                                    'key'     => 'tutor_name',
                                    'value'   => $tutor_name,
                                    'compare' => '=',
                                ),
                                array(
                                    'key'     => 'request_type',
                                    'value'   => array('reschedule_unavailable_all', 'student_reschedule'),
                                    'compare' => 'IN',
                                ),
                                array(
                                    'key'     => 'status',
                                    'value'   => 'pending',
                                    'compare' => '=',
                                ),
                                array(
                                    'key'     => 'viewed_by_tutor',
                                    'compare' => 'NOT EXISTS',
                                )
                            ),
                            'fields'         => 'ids'
                        ));
                        
                        $unread_count = count($unread_requests);
                        if ($unread_count > 0) {
                            echo '<span class="badge rounded-pill bg-danger">' . $unread_count . '</span>';
                        }
                        ?>
            
        <ul class="nav nav-tabs" id="myTab" role="tablist" style="padding-left: 0px !important;">
                <li class="nav-item">
                    <a class="nav-link active" id="progress-report-tab" data-bs-toggle="tab" href="#progress-report">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="your-students-tab" data-bs-toggle="tab" href="#your-students">Your Students</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" id="classroom-url-tab" data-bs-toggle="tab" href="#classroom-url">Your Lessons
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="curriculum-links-tab" data-bs-toggle="tab" href="#curriculum-links">Curriculum Links</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="submit-progress-report-tab" data-bs-toggle="tab" href="#submit-progress-report">Submit Lesson Overview</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="sample-overviews-tab" data-bs-toggle="tab" href="#sample-overviews">Sample Lesson Overviews</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="sample-reports-tab" data-bs-toggle="tab" href="#sample-reports">Sample Progress Comments</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" id="requests-tab" data-bs-toggle="tab" href="#requests">
                        Requests 
                    </a>
                </li>
            </ul>
        </div>
        <!-- ===========================
             MAIN CONTENT
             =========================== -->
        <div class="tab-content" id="myTabContent" style="padding-top: 20px;">

        <?php 
            include 'tutors/home-tab.php';
            include 'tutors/your-students.php';
            include 'tutors/your-lessons.php';
            include 'tutors/curriculum-links.php';
            include 'tutors/submit-lesson-overview.php';
            include 'tutors/sample-lesson-overview.php';
            include 'tutors/sample-progress-comments.php';
            include 'tutors/requests.php';
?>

            </div>
        <div>

<?php
} else {
    echo "Access denied. You don't have permission to view this page.";
}
?>

<?php get_footer(); ?>
