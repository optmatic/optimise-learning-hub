<?php
/*
Template Name: Student Dashboard
*/
?>

<?php get_header(); ?>

<?php
if (current_user_can('student')) {
    // PHP logic previously here (lines 11-300 approx) has been moved to requests/post-handlers.php and requests/ajax-handlers.php
    // The handlers are now hooked to 'init' and 'wp_ajax_' respectively in those files.
    $student_id = get_current_user_id(); // Keep needed variables for the template
?>
<div class="container mt-4">
    <div class="row">
        <!-- (Navigation) -->
        <div class="col-12">
            <?php
            // Calculate notification counts using centralized functions
            $pending_tutor_request_count = get_pending_request_count($student_id, 'student', 'tutor_reschedule');
            $pending_alternatives_count = get_pending_alternatives_count($student_id, 'student');
            $unread_confirmed_count = get_unread_confirmed_count($student_id, 'student');

            // Schedule tab notification (unread confirmed requests)
            $schedule_notification = $unread_confirmed_count > 0 ? 
                '<span class="badge rounded-pill bg-danger notification-badge">' . $unread_confirmed_count . '</span>' : '';

            // Requests tab notification (pending tutor requests + pending alternatives)
            $requests_notification_count = $pending_tutor_request_count + $pending_alternatives_count;
            $requests_notification = $requests_notification_count > 0 ? 
                '<span class="badge rounded-pill bg-danger notification-badge">' . $requests_notification_count . '</span>' : '';
            ?>
            <ul class="nav nav-tabs" id="myTab" role="tablist" style="padding-left: 0px !important;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="home-tab-button" data-bs-toggle="tab" data-bs-target="#home-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">Home</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="learning-goals-tab-button" data-bs-toggle="tab" data-bs-target="#learning-goals-tab-pane" type="button" role="tab" aria-controls="learning-goals-tab-pane" aria-selected="false">Your Learning Plan</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative" id="schedule-tab-button" data-bs-toggle="tab" data-bs-target="#schedule-tab-pane" type="button" role="tab" aria-controls="schedule-tab-pane" aria-selected="false">
                        Your Lesson Schedule <?php echo $schedule_notification; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="classroom-tab-button" data-bs-toggle="tab" data-bs-target="#classroom-tab-pane" type="button" role="tab" aria-controls="classroom-tab-pane" aria-selected="false">Your Classrooms</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="my-progress-tab-button" data-bs-toggle="tab" data-bs-target="#my-progress-tab-pane" type="button" role="tab" aria-controls="my-progress-tab-pane" aria-selected="false">Your Learning Overviews</button>
                </li>
                 <li class="nav-item" role="presentation">
                     <button class="nav-link position-relative" id="requests-tab-button" data-bs-toggle="tab" data-bs-target="#requests-tab-pane" type="button" role="tab" aria-controls="requests-tab-pane" aria-selected="false">
                        Requests <?php echo $requests_notification; ?>
                    </button>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
         <div class="tab-content" id="myTabContent" style="padding-top: 20px;">
             <?php 
             // Define paths to includes
             $base_path = get_stylesheet_directory() . '/students/';
             $requests_include_path = get_stylesheet_directory() . '/requests/student-requests.php';

             // Include tab content
             // Using include_once to avoid potential issues if files are included elsewhere
             include_once $base_path . 'home-tab.php';
             include_once $base_path . 'learning-plan.php';
             include_once $base_path . 'lesson-schedule.php';
             include_once $base_path . 'classrooms.php';
             include_once $base_path . 'learning-overviews.php';

             // Include the requests tab content
             if (file_exists($requests_include_path)) {
                 // Wrap in a tab pane div matching the structure of other includes
                 echo '<div class="tab-pane fade" id="requests-tab-pane" role="tabpanel" aria-labelledby="requests-tab-button" tabindex="0">';
                 include_once $requests_include_path;
                 echo '</div>';
             } else {
                 // Fallback or error message if the include file doesn't exist
                 echo '<div class="tab-pane fade" id="requests-tab-pane" role="tabpanel" aria-labelledby="requests-tab-button" tabindex="0"><p>Error loading requests content.</p></div>';
             }
             ?>
        </div>
    </div> <!-- .row -->
</div> <!-- .container -->
<?php
} else {
    // Standard access denied message
     echo '<div class="container mt-4"><div class="alert alert-danger">Access denied. You do not have permission to view this page.</div></div>';
    // Optionally redirect to login or home page
    // wp_redirect(wp_login_url(get_permalink()));
    // exit;
}
?>

<?php get_footer(); ?>

<?php // JavaScript remains the same as it interacts with the DOM and AJAX handlers, which are now globally available. ?>