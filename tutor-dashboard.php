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

<?php
// Get the current user to use as the tutor
$current_user = wp_get_current_user();
$tutor = $current_user;
$tutor_id = get_current_user_id();

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

// Add this function to test if the AJAX endpoint is working
function test_reschedule_requests() {
    // Create a test reschedule request
    $student_id = get_current_user_id();
    $student_name = 'Test Student';
    $original_date = date('Y-m-d');
    $original_time = '14:00:00';
    $new_date = date('Y-m-d', strtotime('+1 day'));
    $new_time = '15:00:00';
    
    // Get the current user as tutor
    $tutor_id = get_current_user_id();
     
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
        // Count unread requests - keeping this section for the data, but removing the badge display
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
        // Removing this badge display
        // if ($unread_count > 0) {
        //     echo '<span class="badge rounded-pill bg-danger">' . $unread_count . '</span>';
        // }
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
                        <?php
                        // Count pending student reschedule requests
                        $student_requests_args = array(
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
                                    'value'   => array('student_reschedule', 'reschedule_unavailable_all'),
                                    'compare' => 'IN',
                                ),
                                array(
                                    'key'     => 'status',
                                    'value'   => 'pending',
                                    'compare' => '=',
                                )
                            ),
                            'fields'         => 'ids'
                        );
                        $requests_notification_count = count(get_posts($student_requests_args));
                        
                        if ($requests_notification_count > 0): 
                        ?>
                            <span class="badge rounded-pill bg-danger notification-badge"><?php echo $requests_notification_count; ?></span>
                        <?php endif; ?>
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
            include 'tutors/requests/index.php';
?>

            </div>
        <div>

<?php
} else {
    echo "Access denied. You don't have permission to view this page.";
}
?>

<!-- Modals from incoming-requests.php -->
<!-- Modal for Providing Alternative Times -->
<div class="modal fade" id="unavailableModal" tabindex="-1" aria-labelledby="unavailableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg"> <!-- Consider larger modal for better layout -->
        <div class="modal-content">
            <form id="unavailableForm" method="post">
                <?php wp_nonce_field('decline_reschedule_action', 'decline_reschedule_nonce'); ?>
                <input type="hidden" name="action" value="decline_reschedule">
                <input type="hidden" name="request_id" id="unavailable_request_id">
                <input type="hidden" name="student_id" id="unavailable_student_id">
                <input type="hidden" name="active_tab" value="requests"> <!-- Consider if still needed -->

                <div class="modal-header">
                    <h5 class="modal-title" id="unavailableModalLabel">Suggest Alternative Times</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="unavailableErrorMessage" class="alert alert-danger" style="display: none;">
                        Please provide at least one valid alternative date and time.
                    </div>

                    <p class="lead mb-3">You've indicated you're unavailable for the student's preferred times. Please suggest your own alternatives.</p>

                    <!-- Student Request Details -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-light">
                            <i class="fas fa-user-clock me-2"></i> <strong>Student's Request</strong>
                        </div>
                        <div class="card-body">
                            <p><strong>Student:</strong> <span id="unavailable_student_name" class="text-primary fw-bold"></span></p>
                            <p><strong>Original Lesson:</strong> <span id="unavailable_original_time" class="text-secondary"></span></p>

                            <div id="student_preferred_times_container" class="mb-2">
                                <p class="mb-1"><strong>Student's Preferred Alternatives:</strong></p>
                                <ul id="preferred_times_list" class="list-unstyled ps-3"></ul>
                            </div>

                            <div id="student_reason_container">
                                <p class="mb-1"><strong>Reason:</strong></p>
                                <blockquote class="blockquote blockquote-sm mb-0 border-start border-3 ps-3">
                                    <p id="unavailable_reason" class="mb-0"></p>
                                </blockquote>
                            </div>
                        </div>
                    </div>

                    <!-- Tutor Alternative Times -->
                    <h5 class="mt-4"><i class="fas fa-calendar-alt me-2"></i> Your Alternative Times</h5>
                    <p class="text-muted small mb-3">Provide up to 3 alternative times that work for you. The first option is required.</p>

                    <div id="alternative-times-container">
                        <?php
                        // Use the helper function from functions.php
                        // Assumes render_preferred_time_inputs generates appropriate HTML structure
                        // including labels, inputs (date & time), required attributes, and classes.
                        if (function_exists('render_preferred_time_inputs')) {
                            render_preferred_time_inputs('alt_', 3, true); // prefix, count, first required
                        } else {
                            // Fallback or error message if function doesn't exist
                            echo '<p class="text-danger">Error: Could not render time input fields.</p>';
                            // Basic fallback (less ideal)
                            for ($i = 1; $i <= 3; $i++) {
                                echo '<div class="mb-2 row">';
                                echo '<div class="col-md-6"><label class="form-label small">Alternative Date ' . $i . ':</label><input type="date" class="form-control alt-date" name="alt_date_' . $i . '" id="alt_date_' . $i . '" ' . ($i == 1 ? 'required' : '') . '></div>';
                                echo '<div class="col-md-6"><label class="form-label small">Alternative Time ' . $i . ':</label><input type="time" class="form-control alt-time" name="alt_time_' . $i . '" id="alt_time_' . $i . '" ' . ($i == 1 ? 'required' : '') . '></div>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitUnavailable"><i class="fas fa-paper-plane me-2"></i> Submit Alternatives</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal for Displaying Full Reason Text -->
<div class="modal fade" id="reasonModal" tabindex="-1" aria-labelledby="reasonModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reasonModalLabel"><i class="fas fa-info-circle me-2"></i> Full Reschedule Reason</h5>
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
<!-- End Modals -->

<?php get_footer(); ?>