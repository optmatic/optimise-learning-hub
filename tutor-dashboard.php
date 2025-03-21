<?php
/*
Template Name: Tutor Dashboard
*/
?>
<?php
get_header();
?>

<?php
if (current_user_can('tutor')) {
?>

<div class="container mt-4">
    <div class="row">
        <!-- ===========================
             NAVIGATION TABS
             =========================== -->
        <div class="col-12">
        <ul class="nav nav-tabs" id="myTab" role="tablist" style="padding-left: 0px !important;">
                <li class="nav-item">
                    <a class="nav-link active" id="progress-report-tab" data-bs-toggle="tab" href="#progress-report">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="your-students-tab" data-bs-toggle="tab" href="#your-students">Your Students</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" id="classroom-url-tab" data-bs-toggle="tab" href="#classroom-url">Your Lessons
                    <?php
                    // Count unavailable reschedule requests that need alternatives
                    $unavailable_args = array(
                        'post_type'      => 'progress_report',
                        'posts_per_page' => -1,
                        'meta_query'     => array(
                            'relation' => 'OR',
                            array(
                                'relation' => 'AND',
                                array(
                                    'key'     => 'tutor_name',
                                    'value'   => wp_get_current_user()->display_name,
                                    'compare' => '=',
                                ),
                                array(
                                    'key'     => 'request_type',
                                    'value'   => 'reschedule',
                                    'compare' => '=',
                                ),
                                array(
                                    'key'     => 'status',
                                    'value'   => 'unavailable',
                                    'compare' => '=',
                                ),
                                array(
                                    'key'     => 'alternatives_provided',
                                    'compare' => 'NOT EXISTS',
                                )
                            ),
                            array(
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
                            )
                        )
                    );
                    
                    $unavailable_requests = get_posts($unavailable_args);
                    $unavailable_count = count($unavailable_requests);
                    ?>
                    <?php if ($unavailable_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?php echo $unavailable_count; ?>
                        <span class="visually-hidden">unconfirmed requests</span>
                    </span>
                    <?php endif; ?>
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
                <?php echo add_requests_tab_to_navigation(); ?>
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

            </div> <!-- This div closes the "tab-content" div -->
        <div>

<?php
} else {
    echo "Access denied. You don't have permission to view this page.";
}
?>

<?php get_footer(); ?>


<?php
// Add this function definition before line 106 where it's being called
function add_requests_tab_to_navigation() {
    $current_user = wp_get_current_user();
    $tutor_name = $current_user->display_name;
    
    // Count unread requests
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
    $notification_badge = $unread_count > 0 ? '<span class="badge rounded-pill bg-danger">' . $unread_count . '</span>' : '';
    
    // Return the tab HTML
    return '<li class="nav-item">
        <a class="nav-link position-relative" id="requests-tab" data-bs-toggle="tab" href="#requests">
            Requests ' . $notification_badge . '
        </a>
    </li>';
}
?>

<?php get_footer(); ?>
