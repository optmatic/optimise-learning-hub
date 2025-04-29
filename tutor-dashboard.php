<?php
/*
Template Name: Tutor Dashboard
*/
?>
<?php
get_header(); ?>

<?php
if (current_user_can('tutor')) {
    // PHP logic previously here (lines 15-70, 150-195 approx) has been moved to requests/post-handlers.php and requests/ajax-handlers.php
    // The handlers are now hooked to 'init' and 'wp_ajax_' respectively in those files.
    $tutor_id = get_current_user_id(); // Keep needed variables for the template
    $tutor = wp_get_current_user();
?>

<div class="container mt-4">
    <div class="row">
<!-- ===========================
     NAVIGATION TABS
     =========================== -->
        <div class="col-12">
            <?php
             // Calculate notification counts using centralized functions
             $pending_student_request_count = get_pending_request_count($tutor_id, 'tutor', 'student_reschedule');
             $pending_alternatives_count = get_pending_alternatives_count($tutor_id, 'tutor'); // Student alternative suggestions
             // $unread_confirmed_count = get_unread_confirmed_count($tutor_id, 'tutor'); // Count confirmed requests tutor hasn't seen? Maybe needed later.

             // Requests tab notification
             $requests_notification_count = $pending_student_request_count + $pending_alternatives_count;
             $requests_notification = $requests_notification_count > 0 ? 
                 '<span class="badge rounded-pill bg-danger notification-badge">' . $requests_notification_count . '</span>' : '';
             ?>
            <ul class="nav nav-tabs" id="myTab" role="tablist" style="padding-left: 0px !important;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="progress-report-tab-button" data-bs-toggle="tab" data-bs-target="#progress-report-tab-pane" type="button" role="tab" aria-controls="progress-report-tab-pane" aria-selected="true">Home</button>
                </li>
                 <li class="nav-item" role="presentation">
                     <button class="nav-link" id="your-students-tab-button" data-bs-toggle="tab" data-bs-target="#your-students-tab-pane" type="button" role="tab" aria-controls="your-students-tab-pane" aria-selected="false">Your Students</button>
                </li>
                <li class="nav-item" role="presentation">
                     <button class="nav-link" id="classroom-url-tab-button" data-bs-toggle="tab" data-bs-target="#classroom-url-tab-pane" type="button" role="tab" aria-controls="classroom-url-tab-pane" aria-selected="false">Your Lessons</button>
                </li>
                <li class="nav-item" role="presentation">
                     <button class="nav-link" id="curriculum-links-tab-button" data-bs-toggle="tab" data-bs-target="#curriculum-links-tab-pane" type="button" role="tab" aria-controls="curriculum-links-tab-pane" aria-selected="false">Curriculum Links</button>
                </li>
                <li class="nav-item" role="presentation">
                     <button class="nav-link" id="submit-progress-report-tab-button" data-bs-toggle="tab" data-bs-target="#submit-progress-report-tab-pane" type="button" role="tab" aria-controls="submit-progress-report-tab-pane" aria-selected="false">Submit Lesson Overview</button>
                </li>
                <li class="nav-item" role="presentation">
                     <button class="nav-link" id="sample-overviews-tab-button" data-bs-toggle="tab" data-bs-target="#sample-overviews-tab-pane" type="button" role="tab" aria-controls="sample-overviews-tab-pane" aria-selected="false">Sample Lesson Overviews</button>
                </li>
                <li class="nav-item" role="presentation">
                     <button class="nav-link" id="sample-reports-tab-button" data-bs-toggle="tab" data-bs-target="#sample-reports-tab-pane" type="button" role="tab" aria-controls="sample-reports-tab-pane" aria-selected="false">Sample Progress Comments</button>
                </li>
                <li class="nav-item" role="presentation">
                     <button class="nav-link position-relative" id="requests-tab-button" data-bs-toggle="tab" data-bs-target="#requests-tab-pane" type="button" role="tab" aria-controls="requests-tab-pane" aria-selected="false">
                        Requests <?php echo $requests_notification; ?>
                    </button>
                </li>
            </ul>
        </div>
        <!-- ===========================
             MAIN CONTENT
             =========================== -->
         <div class="tab-content" id="myTabContent" style="padding-top: 20px;">
             <?php 
             // Define paths to includes
             $tutor_base_path = get_stylesheet_directory() . '/tutors/';
             $requests_include_path = get_stylesheet_directory() . '/requests/tutor-requests.php';

             // Include tutor-specific tabs - EACH wrapped in its own tab-pane div
             
             // Home Tab
             echo '<div class="tab-pane fade show active" id="progress-report-tab-pane" role="tabpanel" aria-labelledby="progress-report-tab-button" tabindex="0">';
             include_once $tutor_base_path . 'home-tab.php';
             echo '</div>';
             
             // Your Students Tab
             echo '<div class="tab-pane fade" id="your-students-tab-pane" role="tabpanel" aria-labelledby="your-students-tab-button" tabindex="0">';
             include_once $tutor_base_path . 'your-students.php';
             echo '</div>';
             
             // Your Lessons Tab
             echo '<div class="tab-pane fade" id="classroom-url-tab-pane" role="tabpanel" aria-labelledby="classroom-url-tab-button" tabindex="0">';
             include_once $tutor_base_path . 'your-lessons.php';
             echo '</div>';
             
             // Curriculum Links Tab
             echo '<div class="tab-pane fade" id="curriculum-links-tab-pane" role="tabpanel" aria-labelledby="curriculum-links-tab-button" tabindex="0">';
             include_once $tutor_base_path . 'curriculum-links.php';
             echo '</div>';
             
             // Submit Lesson Overview Tab
             echo '<div class="tab-pane fade" id="submit-progress-report-tab-pane" role="tabpanel" aria-labelledby="submit-progress-report-tab-button" tabindex="0">';
             include_once $tutor_base_path . 'submit-lesson-overview.php';
             echo '</div>';

             // Sample Lesson Overviews Tab
             echo '<div class="tab-pane fade" id="sample-overviews-tab-pane" role="tabpanel" aria-labelledby="sample-overviews-tab-button" tabindex="0">';
             include_once $tutor_base_path . 'sample-lesson-overview.php';
             echo '</div>';
             
             // Sample Progress Comments Tab
             echo '<div class="tab-pane fade" id="sample-reports-tab-pane" role="tabpanel" aria-labelledby="sample-reports-tab-button" tabindex="0">';
             include_once $tutor_base_path . 'sample-progress-comments.php';
             echo '</div>';
             
             // Include the requests tab content
             if (file_exists($requests_include_path)) {
                  // Wrap in a tab pane div matching the structure
                  echo '<div class="tab-pane fade" id="requests-tab-pane" role="tabpanel" aria-labelledby="requests-tab-button" tabindex="0">';
                  include_once $requests_include_path;
                  echo '</div>';
             } else {
                  echo '<div class="tab-pane fade" id="requests-tab-pane" role="tabpanel" aria-labelledby="requests-tab-button" tabindex="0"><p>Error loading requests content.</p></div>';
             }
             ?>
        </div> <!-- #myTabContent -->
    </div> <!-- .row -->
</div> <!-- .container -->

<?php
} else {
     // Standard access denied message
     echo '<div class="container mt-4"><div class="alert alert-danger">Access denied. You do not have permission to view this page.</div></div>';
}
?>

<?php get_footer(); ?>

<?php // JavaScript remains the same ?>