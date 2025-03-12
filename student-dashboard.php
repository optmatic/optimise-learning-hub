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
    
    // Create a new reschedule request
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
        update_post_meta($new_request_id, 'preferred_times', $preferred_times);
        update_post_meta($new_request_id, 'status', 'pending');
        
        // If this is an AJAX request, return success
        if (wp_doing_ajax()) {
            wp_send_json_success();
            exit;
        }
    } else {
        // If this is an AJAX request, return error
        if (wp_doing_ajax()) {
            wp_send_json_error();
            exit;
        }
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

                <!-- Home Tab -->
                <div class="tab-pane fade show active" id="home-tab" role="tabpanel" aria-labelledby="home">
                    <h3>Welcome to the Optimise Learning Hub!</h3>
                    <p>Welcome to the <strong>Optimise Learning Hub</strong>, an innovative and interactive platform designed to keep you actively informed in regard to your child's tutoring and their academic progress.</p>
                    <p>Inside the <strong>Optimise Hub</strong>, you have secure access to your child's personalised Learning Plan and their specific learning goals. You can also read weekly comments written by your child's tutor in regard to their focus, the topics that were covered during each lesson and the progress they are making towards their academic learning goals.</p>
                    <p>Additionally, your child can log in to their online tutoring classroom directly from their dashboard on the <strong>Optimise Hub</strong>, ensuring secure and uninterrupted access to their lessons. You also have the convenience of viewing their tutoring schedule, helping you stay organised and prepared.</p>
                    <p>At Optimise Learning, we understand that collaboration between parents and tutors has a positive impact on student learning. The <strong>Optimise Hub</strong> keeps you involved and informed of your child's academic progress. Together, we can help your child to excel and achieve their best.
</p>
                </div>

                <!-- Learning Goals Tab -->
                <div class="tab-pane fade" id="learning-goals" role="tabpanel" aria-labelledby="learning-goals-tab">
                  <!--  <h3>Your Learning Plan</h3> -->
<p>Please see your child's <strong>Individual Learning Plan</strong> below, which provides an overview of the curriculum content your child will be studying during their tutoring lessons, and their specific learning goals. This plan is based upon the goals you have for your child's learning and the academic objectives we have developed based on our initial observations and assessments.</p>
<p>Your child's personalised <strong>Learning Plan</strong> is fully aligned with the Australian National Curriculum and will be regularly reviewed and updated to ensure it supports your child's ongoing academic progress and development.</p>
       <blockquote>
    <div style="background-color: rgba(42, 98, 143, 0.07); padding: 1.5rem 1.5rem .5rem 1.5rem;">
        <?php
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if (in_array('student', (array)$user->roles)) {
                // User is a student, load the plan
                echo '<p><strong>STUDENT NAME:</strong> ' . $current_user->display_name . '</p>';
                echo '<p><strong>YEAR LEVEL:</strong> ' . get_field('year', 'user_' . $user->ID) . '</p>';
                // echo '<p><strong>LESSON SCHEDULE:</strong> ' . get_field('lesson_schedule', 'user_' . $user->ID) . '</p>';
                echo '<p><strong>CURRICULUM OVERVIEW:</strong><br> ' . get_field('overarching_learning_goals', 'user_' . $user->ID) . '</p>';
                echo '<p><strong>SPECIFIC LEARNING GOALS:</strong><br> ' . get_field('specific_learning_goals', 'user_' . $user->ID) . '</p>';  
            }
        }
        ?>
    </div>
 	</blockquote>

					
					<p>
						If you have any questions or require further clarification, please do not hesitate to <a href="mailto:info@optimiselearning.com">contact us</a>.


					</p>
                      <!--  <hr>
                <h5 style="margin-top: 15px;">Australian Curriculum Links</h5> -->
                   <?php
                    // if (is_user_logged_in()) {
                      //  $user = wp_get_current_user();
                        // if (in_array('student', (array)$user->roles)) {
                        //    echo get_field('curriculum_links', 'user_' . $user->ID);
                      //  }
                   // }
                    ?> 
                </div>
    
               <!--Your Schedule Tab -->
<div class="tab-pane fade" id="schedule" role="tabpanel" aria-labelledby="your-schedule-tab">
    <?php
    // Mark confirmed reschedule requests as viewed when this tab is opened
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
            ),
            array(
                'key'     => 'viewed_by_student',
                'value'   => '1',
                'compare' => '!=',
            )
        ),
        'fields'         => 'ids'
    ));
    
    foreach ($confirmed_reschedules as $reschedule_id) {
        update_post_meta($reschedule_id, 'viewed_by_student', '1');
    }
    ?>
    <div style="background-color: rgba(42, 98, 143, 0.07); padding: 1.5rem 1.5rem 1.5rem 1.5rem;">
        <div style="margin-bottom: 30px;"> 
            <h4>Your Upcoming Lesson Details</h4> 
            <p style="font-size: 14px; font-style: italic;">Please note that the times displayed below are in <strong>AEST</strong> (Australian Eastern Standard Time)</strong>.</p>
        </div>
        
        <?php
        // Get current date for comparison
        $current_date = new DateTime('now', new DateTimeZone('Australia/Brisbane'));
        $current_date_str = $current_date->format('Y-m-d');
        
        // Display rescheduled lessons
        $args = array(
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
            'order'          => 'ASC',
            'orderby'        => 'meta_value',
            'meta_key'       => 'new_date'
        );
        
        $rescheduled_lessons = get_posts($args);
        $has_future_lessons = false;
        
        if (!empty($rescheduled_lessons)) {
            echo '<div class="mb-4">';
            echo '<h5>Rescheduled Lessons</h5>';
            echo '<div class="table-responsive">';
            echo '<table class="table table-striped">';
            echo '<thead><tr><th>Subject</th><th>Original Date/Time</th><th>Rescheduled To</th><th>Tutor</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($rescheduled_lessons as $lesson) {
                $lesson_id = $lesson->ID;
                $tutor_username = get_post_meta($lesson_id, 'tutor_name', true);
                $original_date = get_post_meta($lesson_id, 'original_date', true);
                $original_time = get_post_meta($lesson_id, 'original_time', true);
                $new_date = get_post_meta($lesson_id, 'new_date', true);
                $new_time = get_post_meta($lesson_id, 'new_time', true);
                
                // Create DateTime objects for proper comparison
                $new_lesson_datetime = new DateTime($new_date . ' ' . $new_time, new DateTimeZone('Australia/Brisbane'));
                
                // Skip if the new date and time has passed
                if ($new_lesson_datetime < $current_date) {
                    continue;
                }
                
                $has_future_lessons = true;
                
                // Try to determine subject from the original lesson schedule
                $subject = 'Lesson';
                $lesson_schedule = get_user_meta(get_current_user_id(), 'lesson_schedule_list', true);
                if (!empty($lesson_schedule)) {
                    $lessons = explode("\n", $lesson_schedule);
                    foreach ($lessons as $scheduled_lesson) {
                        if (strpos($scheduled_lesson, date('d F Y', strtotime($original_date))) !== false) {
                            // Extract subject from the lesson schedule
                            if (stripos($scheduled_lesson, 'mathematics') !== false) {
                                $subject = 'Mathematics';
                            } elseif (stripos($scheduled_lesson, 'english') !== false) {
                                $subject = 'English';
                            } elseif (stripos($scheduled_lesson, 'chemistry') !== false) {
                                $subject = 'Chemistry';
                            } elseif (stripos($scheduled_lesson, 'physics') !== false) {
                                $subject = 'Physics';
                            }
                            break;
                        }
                    }
                }
                
                // Get the tutor's full name
                $tutor_full_name = $tutor_username;
                
                // Try to find the tutor user by their stored username
                $tutor_user = get_user_by('login', $tutor_username);
                if ($tutor_user) {
                    // Get first and last name
                    $first_name = get_user_meta($tutor_user->ID, 'first_name', true);
                    $last_name = get_user_meta($tutor_user->ID, 'last_name', true);
                    
                    // If both first and last name exist, use them
                    if (!empty($first_name) && !empty($last_name)) {
                        $tutor_full_name = $first_name . ' ' . $last_name;
                    } else {
                        // Otherwise use display name
                        $tutor_full_name = $tutor_user->display_name;
                    }
                }
                
                // Format dates for display
                $formatted_original = date('l, jS \of F Y', strtotime($original_date)) . ' at ' . date('g:i A', strtotime($original_time));
                $formatted_new = date('l, jS \of F Y', strtotime($new_date)) . ' at ' . date('g:i A', strtotime($new_time));
                
                echo '<tr>';
                echo '<td>' . esc_html($subject) . '</td>';
                echo '<td>' . esc_html($formatted_original) . '</td>';
                echo '<td>' . esc_html($formatted_new) . '</td>';
                echo '<td>' . esc_html($tutor_full_name) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            echo '</div>'; // End table-responsive
            
            if (!$has_future_lessons) {
                echo '<p>No upcoming rescheduled lessons.</p>';
            }
            
            echo '</div>'; // End margin-bottom div
        }
        
        // Original lesson schedule code
        $lesson_schedule = get_user_meta(get_current_user_id(), 'lesson_schedule_list', true);
        $now = new DateTime('now', new DateTimeZone('Australia/Brisbane'));

        if (!empty($lesson_schedule)) {
            $lessons = explode("\n", $lesson_schedule);
            $mathematics_lessons = [];
            $english_lessons = [];
            $chemistry_lessons = [];
            $physics_lessons = [];

            // Sort lessons into separate arrays
            foreach ($lessons as $lesson) {
                if (!empty(trim($lesson))) {
                    if (stripos($lesson, 'mathematics') !== false) {
                        if (preg_match('/on ([A-Za-z]+) (\d+) ([A-Za-z]+) (\d{4}) at (\d{2}:\d{2})/', $lesson, $matches)) {
                            $date_string = $matches[1] . ' ' . $matches[2] . ' ' . $matches[3] . ' ' . $matches[4] . ' ' . $matches[5];
                            $lesson_date = DateTime::createFromFormat('l d F Y H:i', $date_string, new DateTimeZone('Australia/Brisbane'));
                            if ($lesson_date > $now) {
                                $mathematics_lessons[] = [
                                    'date' => $lesson_date,
                                    'formatted' => $lesson_date->format('l, jS \of F Y \a\t g:i A')
                                ];
                            }
                        }
                    } elseif (stripos($lesson, 'english') !== false) {
                        if (preg_match('/on ([A-Za-z]+) (\d+) ([A-Za-z]+) (\d{4}) at (\d{2}:\d{2})/', $lesson, $matches)) {
                            $date_string = $matches[1] . ' ' . $matches[2] . ' ' . $matches[3] . ' ' . $matches[4] . ' ' . $matches[5];
                            $lesson_date = DateTime::createFromFormat('l d F Y H:i', $date_string, new DateTimeZone('Australia/Brisbane'));
                            if ($lesson_date > $now) {
                                $english_lessons[] = [
                                    'date' => $lesson_date,
                                    'formatted' => $lesson_date->format('l, jS \of F Y \a\t g:i A')
                                ];
                            }
                        }
                    } elseif (stripos($lesson, 'chemistry') !== false) {
                        if (preg_match('/on ([A-Za-z]+) (\d+) ([A-Za-z]+) (\d{4}) at (\d{2}:\d{2})/', $lesson, $matches)) {
                            $date_string = $matches[1] . ' ' . $matches[2] . ' ' . $matches[3] . ' ' . $matches[4] . ' ' . $matches[5];
                            $lesson_date = DateTime::createFromFormat('l d F Y H:i', $date_string, new DateTimeZone('Australia/Brisbane'));
                            if ($lesson_date > $now) {
                                $chemistry_lessons[] = [
                                    'date' => $lesson_date,
                                    'formatted' => $lesson_date->format('l, jS \of F Y \a\t g:i A')
                                ];
                            }
                        }
                    } elseif (stripos($lesson, 'physics') !== false) {
                        if (preg_match('/on ([A-Za-z]+) (\d+) ([A-Za-z]+) (\d{4}) at (\d{2}:\d{2})/', $lesson, $matches)) {
                            $date_string = $matches[1] . ' ' . $matches[2] . ' ' . $matches[3] . ' ' . $matches[4] . ' ' . $matches[5];
                            $lesson_date = DateTime::createFromFormat('l d F Y H:i', $date_string, new DateTimeZone('Australia/Brisbane'));
                            if ($lesson_date > $now) {
                                $physics_lessons[] = [
                                    'date' => $lesson_date,
                                    'formatted' => $lesson_date->format('l, jS \of F Y \a\t g:i A')
                                ];
                            }
                        }
                    }
                }
            }

            // Sort all lesson arrays
            foreach ([$mathematics_lessons, $english_lessons, $chemistry_lessons, $physics_lessons] as &$lesson_array) {
                usort($lesson_array, function($a, $b) {
                    return $a['date']->getTimestamp() - $b['date']->getTimestamp();
                });
            }

            // Display lessons for each subject
            $subjects = [
                'Mathematics' => $mathematics_lessons,
                'English' => $english_lessons,
                'Chemistry' => $chemistry_lessons,
                'Physics' => $physics_lessons
            ];

            foreach ($subjects as $subject => $lessons) {
                if (!empty($lessons)) {
                    echo '<h5 style="margin-top: 20px;">' . $subject . '</h5>';
                    echo '<div class="lesson-list">';
                    foreach ($lessons as $lesson) {
                        echo '<div class="lesson-item">' . $lesson['formatted'] . '</div>';
                    }
                    echo '</div>';
                }
            }
        } else {
            echo '<p>No lessons scheduled at this time.</p>';
        }
        ?>
    </div>
</div>
                <!-- Classroom Tab -->
                <div class="tab-pane fade" id="classroom" role="tabpanel" aria-labelledby="classroom-tab">
                <h3>Please note</h3>
                    <ul>
                        <li>Remember to log in to your classroom a few minutes before your lesson is due to commence.</li>
                        <li>If after entering your name, you see a pop-up stating there is no presenter, please log out and log in a few minutes later.</li>
                        <li>Our online classrooms function well if <a href="https://www.apple.com/au/safari/" target="_blank" rel="nofollow">Safari</a>, <a href="https://www.google.com.au/intl/en_au/chrome/" target="_blank" rel="nofollow">Google Chrome</a>, or <a href="https://www.mozilla.org/en-US/firefox/new/" target="_blank" rel="nofollow">Firefox</a> is used as the browser. They are not able to function effectively if Internet Explorer is used as the browser.</li>
                    </ul>
                    <div style="background-color: rgba(42, 98, 143, 0.07); padding:25px;">
                    <h3>Access your classrooms here</h3>
                    <?php
                    if (is_user_logged_in()) {
                        $user = wp_get_current_user();
                        if (in_array('student', (array)$user->roles)) {
                            // User is a student, load the embed
                            $english_classroom = get_field('english_classroom', 'user_' . $user->ID);
                            $mathematics_classroom = get_field('mathematics_classroom', 'user_' . $user->ID);
                            $custom_classroom_name = get_field('custom_classroom_name', 'user_' . $user->ID);
                            $custom_classroom_url = get_field('custom_classroom_url', 'user_' . $user->ID);

                            if ($mathematics_classroom) {
                                echo '<h5 style="margin-top: 25px;">Mathematics Classroom</h5>';
                                echo '<a href="' . esc_url($mathematics_classroom) . '" target="_blank">' . esc_url($mathematics_classroom) . '</a>';
                            }
                            
                            if ($english_classroom) {
                                echo '<h5 style="margin-top: 25px;">English Classroom</h5>';
                                echo '<a href="' . esc_url($english_classroom) . '" target="_blank">' . esc_url($english_classroom) . '</a>';
                            }
                            
                            if ($custom_classroom_name && $custom_classroom_url) {
                                echo '<h5 style="margin-top: 25px;">' . esc_html($custom_classroom_name) . ' Classroom</h5>';
                                echo '<a href="' . esc_url($custom_classroom_url) . '" target="_blank">' . esc_url($custom_classroom_url) . '</a>';
                            }
                        }
                    }
                    ?>
                    </div>
                </div>

                <!-- My Progress Tab -->
<div class="tab-pane fade" id="my-progress" role="tabpanel" aria-labelledby="my-progress-tab">
    <?php
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;

    $progress_reports = get_student_progress_reports($user_id);

    if (!empty($progress_reports)) {
        echo '<div class="accordion" id="progressAccordion">';
        $counter = 1;
        foreach ($progress_reports as $report) {
            $report_id = $report->ID;
            $tutor_name = get_post_meta($report_id, 'tutor_name', true);
            $lesson_date = get_post_meta($report_id, 'lesson_date', true);
            $datetime = DateTime::createFromFormat('Y-m-d', $lesson_date);
            $formatted_date = $datetime->format('jS \of F, Y');
            $lesson_focus = get_post_meta($report_id, 'lesson_focus', true);
            $content_covered = get_post_meta($report_id, 'content_covered', true);
            $student_progress = get_post_meta($report_id, 'student_progress', true);
            $next_focus = get_post_meta($report_id, 'next_focus', true);
            $resources = get_post_meta($report_id, 'lesson_resources', true);

            echo '<div class="accordion-item">';
            echo '<h2 class="accordion-header" id="heading' . $counter . '">';
            echo '<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $counter . '" aria-expanded="true" aria-controls="collapse' . $counter . '">';
            echo 'Learning Overview - ' . $formatted_date;
            echo '</button>';
            echo '</h2>';
            echo '<div id="collapse' . $counter . '" class="accordion-collapse collapse" aria-labelledby="heading' . $counter . '" data-bs-parent="#progressAccordion">';
            echo '<div class="accordion-body">';
            echo '<p><strong>Tutor Name:</strong> ' . $tutor_name . '</p>';
            echo '<p><strong>Lesson Date:</strong> ' . $formatted_date . '</p>';
            echo '<p><strong>Lesson Focus:</strong> ' . $lesson_focus . '</p>';
            echo '<p><strong>Content Covered During the Lesson:</strong> ' . $content_covered . '</p>';
            echo '<p><strong>Student Progress:</strong> ' . $student_progress . '</p>';
            echo '<p><strong>Focus for Next Lesson:</strong> ' . $next_focus . '</p>';
            
            // Display multiple resources if they exist
            if (!empty($resources)) {
                echo '<p><strong>Resources:</strong></p>';
                echo '<ul class="list-unstyled" style="padding:0 !important;">';
                foreach ((array)$resources as $resource_url) {
                    $filename = basename($resource_url);
                    echo '<li><i class="fas fa-file"></i> <a href="' . esc_url($resource_url) . '" target="_blank">' . esc_html($filename) . '</a></li>';
                }
                echo '</ul>';
            }
            
            echo '</div>';
            echo '</div>';
            echo '</div>';

            $counter++;
        }
        echo '</div>';
    } else {
        echo '<p>No lesson overviews found.</p>';
    }
    ?>
</div>

                <!-- Tutor Comms Tab -->
                <div class="tab-pane fade" id="tutor-comms" role="tabpanel" aria-labelledby="tutor-comms-tab">
                    <h4>Reschedule Requests</h4>
                    
                    <!-- Add Reschedule Request Form -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            Request Lesson Reschedule
                        </div>
                        <div class="card-body">
                            <p>Use this form to request a reschedule for an upcoming lesson.</p>
                            
                            <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#newRescheduleRequestModal">
                                Request Lesson Reschedule
                            </button>
                            
                            <!-- Modal for creating a new reschedule request -->
                            <div class="modal fade" id="newRescheduleRequestModal" tabindex="-1" aria-labelledby="newRescheduleRequestModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="newRescheduleRequestModalLabel">Request Lesson Reschedule</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div id="rescheduleRequestSuccessMessage" class="alert alert-success" style="display: none;">
                                                <p>Your reschedule request has been successfully submitted. Your tutor will be notified.</p>
                                            </div>
                                            <div id="rescheduleRequestErrorMessage" class="alert alert-danger" style="display: none;">
                                                <p>Please fill in all required fields (tutor, date, and time).</p>
                                            </div>
                                            <form id="rescheduleRequestForm" method="post">
                                                <input type="hidden" name="submit_student_reschedule_request" value="1">
                                                <input type="hidden" name="student_id" value="<?php echo get_current_user_id(); ?>">
                                                
                                                <div class="mb-3">
                                                    <label for="tutor_select" class="form-label">Select Tutor <span class="text-danger">*</span></label>
                    <?php
                                                    // Get the student's assigned tutors
                                                    $current_user_id = get_current_user_id();
                                                    $tutors = array();
                                                    
                                                    // Query for users with the tutor role
                                                    $tutor_query = new WP_User_Query(array(
                                                        'role' => 'tutor',
                                                        'fields' => array('ID', 'display_name')
                                                    ));
                                                    
                                                    // Get all tutors
                                                    $all_tutors = $tutor_query->get_results();
                                                    
                                                    // Check each tutor to see if the current student is assigned to them
                                                    foreach ($all_tutors as $tutor) {
                                                        $assigned_students = get_user_meta($tutor->ID, 'assigned_students', true);
                                                        if (!empty($assigned_students)) {
                                                            $student_ids = explode(',', $assigned_students);
                                                            if (in_array($current_user_id, $student_ids)) {
                                                                $tutors[] = array(
                                                                    'id' => $tutor->ID,
                                                                    'name' => $tutor->display_name
                                                                );
                                                            }
                                                        }
                                                    }
                                                    
                                                    if (!empty($tutors)) {
                                                        echo '<select name="tutor_name" id="tutor_select" class="form-select" required>';
                                                        echo '<option value="">--Select tutor--</option>';
                                                        foreach ($tutors as $tutor) {
                                                            echo '<option value="' . esc_attr($tutor['name']) . '">' . esc_html($tutor['name']) . '</option>';
                                                        }
                                                        echo '</select>';
                                                    } else {
                                                        echo '<div class="alert alert-warning">No tutors assigned to you. Please contact support.</div>';
                                                    }
                                                    ?>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="lesson_date" class="form-label">Lesson Date to Reschedule <span class="text-danger">*</span></label>
                                                    <input type="date" class="form-control" id="lesson_date" name="original_date" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="lesson_time" class="form-label">Lesson Time <span class="text-danger">*</span></label>
                                                    <input type="time" class="form-control" id="lesson_time" name="original_time" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="reason" class="form-label">Reason for Reschedule <span class="text-danger">*</span></label>
                                                    <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Preferred Alternative Times (Optional)</label>
                                                    <p class="text-muted small">Please select up to 3 preferred alternative dates and times.</p>
                                                    
                                                    <div id="preferred-times-container">
                                                        <!-- Preferred Time 1 -->
                                                        <div class="preferred-time-row mb-2">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <label class="form-label small">Preferred Date 1:</label>
                                                                    <input type="date" class="form-control" name="preferred_date_1">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label small">Preferred Time 1:</label>
                                                                    <input type="time" class="form-control" name="preferred_time_1">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Preferred Time 2 -->
                                                        <div class="preferred-time-row mb-2">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <label class="form-label small">Preferred Date 2:</label>
                                                                    <input type="date" class="form-control" name="preferred_date_2">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label small">Preferred Time 2:</label>
                                                                    <input type="time" class="form-control" name="preferred_time_2">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Preferred Time 3 -->
                                                        <div class="preferred-time-row mb-2">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <label class="form-label small">Preferred Date 3:</label>
                                                                    <input type="date" class="form-control" name="preferred_date_3">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label small">Preferred Time 3:</label>
                                                                    <input type="time" class="form-control" name="preferred_time_3">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="button" class="btn btn-primary" id="submitStudentReschedule">Submit Request</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Outgoing Reschedule Requests (Student-initiated) -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <i class="fas fa-arrow-right me-2"></i> Your Outgoing Reschedule Requests
                        </div>
                        <div class="card-body">
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
                                echo '<thead><tr><th>Date Requested</th><th>Lesson Date</th><th>Tutor</th><th>Status</th><th>Actions</th></tr></thead>';
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
                                    
                                    // Format the original date for display
                                    $formatted_original = !empty($original_date) ? date('M j, Y', strtotime($original_date)) . ' at ' . date('g:i A', strtotime($original_time)) : 'N/A';
                                    
                                    // Set status badge
                                    $status_badge = '';
                                    if ($status === 'confirmed') {
                                        $status_badge = '<span class="badge bg-success">Confirmed</span>';
                                    } elseif ($status === 'denied') {
                                        $status_badge = '<span class="badge bg-danger">Denied</span>';
                                    } else {
                                        $status_badge = '<span class="badge bg-warning">Pending</span>';
                                    }
                                    
                                    echo '<tr>';
                                    echo '<td>' . esc_html($request_date) . '</td>';
                                    echo '<td>' . esc_html($formatted_original) . '</td>';
                                    echo '<td>' . esc_html($tutor_name) . '</td>';
                                    echo '<td>' . $status_badge . '</td>';
                                    echo '<td>';
                                    
                                    // Only show edit/delete buttons for pending requests
                                    if ($status !== 'confirmed' && $status !== 'denied') {
                                        echo '<button type="button" class="btn btn-sm btn-primary me-1 edit-request-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editRescheduleRequestModal" 
                                            data-request-id="' . $request_id . '"
                                            data-tutor-name="' . esc_attr($tutor_name) . '"
                                            data-original-date="' . esc_attr($original_date) . '"
                                            data-original-time="' . esc_attr($original_time) . '"
                                            data-reason="' . esc_attr($reason) . '">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>';
                                        
                                        echo '<form method="post" class="d-inline delete-request-form">';
                                        echo '<input type="hidden" name="delete_student_request" value="1">';
                                        echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                                        echo '<button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>';
                                        echo '</form>';
                                    } else {
                                        echo '<span class="text-muted">No actions available</span>';
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
                    
                    <!-- Incoming Reschedule Requests (Tutor-initiated) -->
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <i class="fas fa-arrow-right me-2"></i> Incoming Reschedule Requests
                        </div>
                        <div class="card-body">
                            <?php
                            // Get tutor-initiated reschedule requests
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
                                        'value'   => 'reschedule',
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
                            
                            $tutor_requests = get_posts($tutor_requests_args);
                            
                            if (!empty($tutor_requests)) {
                                echo '<div class="table-responsive">';
                                echo '<table class="table table-striped">';
                                echo '<thead><tr><th>Date Requested</th><th>Original Lesson</th><th>Proposed New Time</th><th>Tutor</th><th>Action</th></tr></thead>';
                                echo '<tbody>';
                                
                                foreach ($tutor_requests as $request) {
                                    $request_id = $request->ID;
                                    $tutor_name = get_post_meta($request_id, 'tutor_name', true);
                                    $original_date = get_post_meta($request_id, 'original_date', true);
                                    $original_time = get_post_meta($request_id, 'original_time', true);
                                    $new_date = get_post_meta($request_id, 'new_date', true);
                                    $new_time = get_post_meta($request_id, 'new_time', true);
                                    $request_date = get_the_date('M j, Y', $request_id);
                                    
                                    // Format dates for display
                                    $formatted_original = !empty($original_date) ? date('M j, Y', strtotime($original_date)) . ' at ' . date('g:i A', strtotime($original_time)) : 'N/A';
                                    $formatted_new = !empty($new_date) ? date('M j, Y', strtotime($new_date)) . ' at ' . date('g:i A', strtotime($new_time)) : 'N/A';
                                    
                                    echo '<tr>';
                                    echo '<td>' . esc_html($request_date) . '</td>';
                                    echo '<td>' . esc_html($formatted_original) . '</td>';
                                    echo '<td>' . esc_html($formatted_new) . '</td>';
                                    echo '<td>' . esc_html($tutor_name) . '</td>';
                                    echo '<td>';
                                    echo '<form method="post" class="d-inline">';
                                    echo '<input type="hidden" name="confirm_reschedule" value="1">';
                                    echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                                    echo '<button type="submit" class="btn btn-sm btn-success me-1">Accept</button>';
                                    echo '</form>';
                                    
                                    echo '<form method="post" class="d-inline">';
                                    echo '<input type="hidden" name="decline_reschedule" value="1">';
                                    echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                                    echo '<button type="submit" class="btn btn-sm btn-danger">Decline</button>';
                                    echo '</form>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                                
                                echo '</tbody></table>';
                                echo '</div>';
                            } else {
                                echo '<p>No incoming reschedule requests from tutors at this time.</p>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Alternative Times Section -->
                    <?php
                    // Get alternative reschedule requests
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
                            )
                        ),
                        'order'          => 'DESC',
                        'orderby'        => 'date'
                    );
                    
                    $alternative_requests = get_posts($alternative_args);
                    
                    if (!empty($alternative_requests)) {
                        echo '<div class="card mb-4">';
                        echo '<div class="card-header bg-primary text-white">';
                        echo '<i class="fas fa-exchange-alt me-2"></i> Alternative Lesson Times';
                        echo '</div>';
                        echo '<div class="card-body">';
                        
                        // Check for new (unviewed) alternatives
                        $has_new_alternatives = false;
                        foreach ($alternative_requests as $request) {
                            $viewed = get_post_meta($request->ID, 'viewed_by_student', true);
                            $status = get_post_meta($request->ID, 'status', true);
                            if (empty($viewed) && $status === 'pending') {
                                $has_new_alternatives = true;
                                break;
                            }
                        }
                        
                        // Show notification for new alternatives
                        if ($has_new_alternatives) {
                            echo '<div class="alert alert-info">';
                            echo '<i class="fas fa-bell me-2"></i> <strong>New!</strong> Your tutor has provided alternative lesson times for you to review.';
                            echo '</div>';
                        }
                        
                        echo '<p>Your tutor has provided alternative times for lessons you were unavailable for. Please select one of the options below:</p>';
                        
                        echo '<div class="accordion" id="alternativeAccordion">';
                        $counter = 1;
                        
                        foreach ($alternative_requests as $request) {
                            $request_id = $request->ID;
                            $original_request_id = get_post_meta($request_id, 'original_request_id', true);
                            $tutor_name = get_post_meta($request_id, 'tutor_name', true);
                            $alternatives = get_post_meta($request_id, 'alternatives', true);
                            $message = get_post_meta($request_id, 'message', true);
                            $status = get_post_meta($request_id, 'status', true);
                            $request_date = get_the_date('F j, Y', $request_id);
                            $viewed = get_post_meta($request_id, 'viewed_by_student', true);
                            
                            // Get tutor's first and last name
                            $tutor_user = get_user_by('login', $tutor_name);
                            $tutor_display_name = $tutor_name;
                            if ($tutor_user) {
                                $first_name = get_user_meta($tutor_user->ID, 'first_name', true);
                                $last_name = get_user_meta($tutor_user->ID, 'last_name', true);
                                if (!empty($first_name) && !empty($last_name)) {
                                    $tutor_display_name = $first_name . ' ' . $last_name;
                                } else {
                                    $tutor_display_name = $tutor_user->display_name;
                                }
                            }
                            
                            // Get original request details
                            $original_date = get_post_meta($original_request_id, 'original_date', true);
                            $original_time = get_post_meta($original_request_id, 'original_time', true);
                            
                            // Format the original date for display
                            $formatted_original_date = !empty($original_date) ? date('l, jS \of F, Y', strtotime($original_date)) : 'N/A';
                            $formatted_original_time = !empty($original_time) ? date('g:i A', strtotime($original_time)) : '';
                            
                            // Set status badge
                            $status_badge = '';
                            if ($status === 'confirmed') {
                                $status_badge = '<span class="badge bg-success">Confirmed</span>';
                            } else {
                                $status_badge = '<span class="badge bg-warning">Pending</span>';
                            }
                            
                            // Add "New" badge for unviewed alternatives
                            $new_badge = '';
                            if (empty($viewed) && $status === 'pending') {
                                $new_badge = '<span class="badge bg-danger ms-2">New</span>';
                            }
                            
                            echo '<div class="accordion-item' . (empty($viewed) && $status === 'pending' ? ' border-danger' : '') . '">';
                            echo '<h2 class="accordion-header" id="alternativeHeading' . $counter . '">';
                            echo '<button class="accordion-button' . (empty($viewed) && $status === 'pending' ? '' : ' collapsed') . '" type="button" data-bs-toggle="collapse" data-bs-target="#alternativeCollapse' . $counter . '" aria-expanded="' . (empty($viewed) && $status === 'pending' ? 'true' : 'false') . '" aria-controls="alternativeCollapse' . $counter . '">';
                            echo 'Alternative Times - ' . $request_date . ' ' . $status_badge . $new_badge;
                            echo '</button>';
                            echo '</h2>';
                            
                            echo '<div id="alternativeCollapse' . $counter . '" class="accordion-collapse collapse' . (empty($viewed) && $status === 'pending' ? ' show' : '') . '" aria-labelledby="alternativeHeading' . $counter . '" data-bs-parent="#alternativeAccordion">';
                            echo '<div class="accordion-body">';
                            
                            echo '<div class="card mb-3">';
                            echo '<div class="card-header bg-light">Original Lesson</div>';
                            echo '<div class="card-body">';
                            echo '<p><strong>Date:</strong> ' . $formatted_original_date . '</p>';
                            if (!empty($formatted_original_time)) {
                                echo '<p><strong>Time:</strong> ' . $formatted_original_time . '</p>';
                            }
                            echo '<p><strong>Tutor:</strong> ' . esc_html($tutor_display_name) . '</p>';
                            echo '</div>';
                            echo '</div>';
                            
                            if (!empty($message)) {
                                echo '<div class="alert alert-info">';
                                echo '<p><strong>Message from tutor:</strong> ' . esc_html($message) . '</p>';
                                echo '</div>';
                            }
                            
                            if ($status !== 'confirmed') {
                                echo '<form method="post" class="mt-3">';
                                echo '<input type="hidden" name="select_alternative" value="1">';
                                echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                                
                                echo '<div class="list-group mb-3">';
                                foreach ($alternatives as $index => $alternative) {
                                    $alt_date = $alternative['date'];
                                    $alt_time = $alternative['time'];
                                    
                                    $formatted_alt_date = date('l, jS \of F, Y', strtotime($alt_date));
                                    $formatted_alt_time = date('g:i A', strtotime($alt_time));
                                    
                                    echo '<div class="list-group-item">';
                                    echo '<div class="form-check">';
                                    echo '<input class="form-check-input" type="radio" name="selected_alternative" value="' . $index . '" id="alt' . $request_id . '_' . $index . '" ' . ($index === 0 ? 'checked' : '') . '>';
                                    echo '<label class="form-check-label" for="alt' . $request_id . '_' . $index . '">';
                                    echo 'Option ' . ($index + 1) . ': ' . $formatted_alt_date . ' at ' . $formatted_alt_time;
                                    echo '</label>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                                echo '</div>';
                                
                                echo '<button type="submit" class="btn btn-success">Confirm Selected Time</button>';
                                echo '</form>';
                            } else {
                                // Show the confirmed alternative
                                $selected_index = get_post_meta($request_id, 'selected_alternative', true);
                                $selected_alternative = $alternatives[$selected_index];
                                
                                $formatted_selected_date = date('l, jS \of F, Y', strtotime($selected_alternative['date']));
                                $formatted_selected_time = date('g:i A', strtotime($selected_alternative['time']));
                                
                                echo '<div class="alert alert-success">';
                                echo '<p><strong>Confirmed Time:</strong> ' . $formatted_selected_date . ' at ' . $formatted_selected_time . '</p>';
                                echo '</div>';
                            }
                            
                            echo '</div>'; // End accordion-body
                            echo '</div>'; // End accordion-collapse
                            echo '</div>'; // End accordion-item
                            
                            $counter++;
                        }
                        
                        echo '</div>'; // End accordion
                        echo '</div>'; // End card-body
                        echo '</div>'; // End card
                    }
                    ?>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal for editing a reschedule request -->
<div class="modal fade" id="editRescheduleRequestModal" tabindex="-1" aria-labelledby="editRescheduleRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRescheduleRequestModalLabel">Edit Reschedule Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="editRescheduleSuccessMessage" class="alert alert-success" style="display: none;">
                    <p>Your reschedule request has been successfully updated.</p>
                </div>
                <form id="editRescheduleRequestForm" method="post">
                    <input type="hidden" name="update_student_reschedule_request" value="1">
                    <input type="hidden" name="request_id" id="edit_request_id" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">Tutor</label>
                        <input type="text" class="form-control" id="edit_tutor_name" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Original Lesson Date/Time</label>
                        <input type="text" class="form-control" id="edit_original_datetime" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_reason" class="form-label">Reason for Reschedule <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit_reason" name="reason" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Preferred Alternative Times</label>
                        <p class="text-muted small">Please select up to 3 preferred alternative dates and times.</p>
                        
                        <div id="edit-preferred-times-container">
                            <!-- Preferred Time 1 -->
                            <div class="preferred-time-row mb-2">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label small">Preferred Date 1:</label>
                                        <input type="date" class="form-control" name="preferred_date_1" id="edit_preferred_date_1">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Preferred Time 1:</label>
                                        <input type="time" class="form-control" name="preferred_time_1" id="edit_preferred_time_1">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Preferred Time 2 -->
                            <div class="preferred-time-row mb-2">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label small">Preferred Date 2:</label>
                                        <input type="date" class="form-control" name="preferred_date_2" id="edit_preferred_date_2">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Preferred Time 2:</label>
                                        <input type="time" class="form-control" name="preferred_time_2" id="edit_preferred_time_2">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Preferred Time 3 -->
                            <div class="preferred-time-row mb-2">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label small">Preferred Date 3:</label>
                                        <input type="date" class="form-control" name="preferred_date_3" id="edit_preferred_date_3">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Preferred Time 3:</label>
                                        <input type="time" class="form-control" name="preferred_time_3" id="edit_preferred_time_3">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="updateStudentReschedule">Update Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
} else {
    echo "Access denied. You don't have permission to view this page.";
}
?>

<?php get_footer(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle reschedule request submission
    const submitButton = document.getElementById('submitReschedule');
    if (submitButton) {
        submitButton.addEventListener('click', function() {
            // Get form data
            const form = document.getElementById('rescheduleForm');
            const formData = new FormData(form);
            
            // Submit the form using fetch
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    // Show success message
                    const successMessage = document.getElementById('rescheduleSuccessMessage');
                    successMessage.style.display = 'block';
                    
                    // Clear form fields
                    document.getElementById('student_select').value = '';
                    document.getElementById('original_date').value = '';
                    document.getElementById('original_time').value = '';
                    document.getElementById('new_date').value = '';
                    document.getElementById('new_time').value = '';
                    
                    // Hide the form
                    form.style.display = 'none';
                    
                    // Set a timeout to close the modal after 3 seconds
                    setTimeout(function() {
                        // Close the modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('newRescheduleModal'));
                        modal.hide();
                        
                        // Reload the page to show the updated list of reschedule requests
                        window.location.reload();
                    }, 3000);
                } else {
                    alert('There was an error submitting your request. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('There was an error submitting your request. Please try again.');
            });
        });
    }
    
    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
    // Handle tab switching to mark confirmed reschedules as viewed
    const scheduleTab = document.getElementById('schedule-tab');
    if (scheduleTab) {
        scheduleTab.addEventListener('click', function() {
            // Use AJAX to mark all confirmed reschedules as viewed
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'mark_viewed=1'
            })
            .then(response => {
                if (response.ok) {
                    // Remove the notification badge
                    const badge = scheduleTab.querySelector('.badge');
                    if (badge) {
                        badge.remove();
                    }
                }
            });
        });
    }
    
    // Mark alternative times as viewed when Tutor Comms tab is opened
    const tutorCommsTab = document.getElementById('tutor-comms-tab');
    if (tutorCommsTab) {
        tutorCommsTab.addEventListener('click', function() {
            // Send AJAX request to mark alternatives as viewed
            fetch('<?php echo add_query_arg(array("mark_alternatives_viewed" => "1"), get_permalink()); ?>', {
                method: 'GET',
                credentials: 'same-origin'
            }).then(response => {
                // Remove the notification badge after viewing
                const badge = this.querySelector('.badge');
                if (badge) {
                    badge.style.display = 'none';
                }
            }).catch(error => {
                console.error('Error marking alternatives as viewed:', error);
            });
        });
    }
    
    // Handle unavailable for all button
    document.querySelectorAll('button[name="unavailable_all"]').forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you are unavailable for all these alternative times?')) {
                e.preventDefault();
            }
        });
    });
    
    // Handle student reschedule request submission
    const submitStudentRescheduleButton = document.getElementById('submitStudentReschedule');
    if (submitStudentRescheduleButton) {
        submitStudentRescheduleButton.addEventListener('click', function() {
            // Get form data
            const form = document.getElementById('rescheduleRequestForm');
            
            // Validate required fields
            const tutorSelect = document.getElementById('tutor_select');
            const lessonDate = document.getElementById('lesson_date');
            const lessonTime = document.getElementById('lesson_time');
            const reason = document.getElementById('reason');
            const errorMessage = document.getElementById('rescheduleRequestErrorMessage');
            
            // Check if required fields are filled
            if (!tutorSelect.value || !lessonDate.value || !lessonTime.value || !reason.value) {
                errorMessage.style.display = 'block';
                return; // Stop form submission
            } else {
                errorMessage.style.display = 'none';
            }
            
            const formData = new FormData(form);
            
            // Submit the form using fetch
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    // Show success message
                    const successMessage = document.getElementById('rescheduleRequestSuccessMessage');
                    successMessage.style.display = 'block';
                    
                    // Clear form fields
                    document.getElementById('tutor_select').value = '';
                    document.getElementById('lesson_date').value = '';
                    document.getElementById('lesson_time').value = '';
                    document.getElementById('reason').value = '';
                    
                    // Clear preferred times
                    for (let i = 1; i <= 3; i++) {
                        document.querySelector(`input[name="preferred_date_${i}"]`).value = '';
                        document.querySelector(`input[name="preferred_time_${i}"]`).value = '';
                    }
                    
                    // Hide the form
                    form.style.display = 'none';
                    errorMessage.style.display = 'none';
                    
                    // Set a timeout to close the modal after 3 seconds
                    setTimeout(function() {
                        // Close the modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('newRescheduleRequestModal'));
                        modal.hide();
                        
                        // Reload the page to show the updated list of reschedule requests
                        window.location.reload();
                    }, 3000);
                } else {
                    alert('There was an error submitting your request. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('There was an error submitting your request. Please try again.');
            });
        });
    }
    
    // Handle edit request button clicks
    const editButtons = document.querySelectorAll('.edit-request-btn');
    if (editButtons.length > 0) {
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.getAttribute('data-request-id');
                const tutorName = this.getAttribute('data-tutor-name');
                const originalDate = this.getAttribute('data-original-date');
                const originalTime = this.getAttribute('data-original-time');
                const reason = this.getAttribute('data-reason');
                
                // Set values in the edit form
                document.getElementById('edit_request_id').value = requestId;
                document.getElementById('edit_tutor_name').value = tutorName;
                document.getElementById('edit_original_datetime').value = 
                    new Date(originalDate).toLocaleDateString() + ' at ' + 
                    new Date('1970-01-01T' + originalTime).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                document.getElementById('edit_reason').value = reason;
                
                // Fetch preferred times for this request
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_preferred_times&request_id=' + requestId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.preferred_times) {
                        const preferredTimes = data.preferred_times;
                        
                        // Fill in the preferred times fields
                        for (let i = 0; i < preferredTimes.length && i < 3; i++) {
                            document.getElementById('edit_preferred_date_' + (i+1)).value = preferredTimes[i].date;
                            document.getElementById('edit_preferred_time_' + (i+1)).value = preferredTimes[i].time;
                        }
                    }
                });
            });
        });
    }
    
    // Handle delete request confirmation
    const deleteForms = document.querySelectorAll('.delete-request-form');
    if (deleteForms.length > 0) {
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to delete this reschedule request?')) {
                    e.preventDefault();
                }
            });
        });
    }
    
    // Handle update reschedule request submission
    const updateButton = document.getElementById('updateStudentReschedule');
    if (updateButton) {
        updateButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get form data
            const form = document.getElementById('editRescheduleRequestForm');
            const formData = new FormData(form);
            
            // Submit the form using fetch
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    // Show success message
                    const successMessage = document.getElementById('editRescheduleSuccessMessage');
                    successMessage.style.display = 'block';
                    
                    // Set a timeout to close the modal after 2 seconds
                    setTimeout(function() {
                        // Close the modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editRescheduleRequestModal'));
                        modal.hide();
                        
                        // Reload the page to show the updated list
                        window.location.reload();
                    }, 2000);
                } else {
                    alert('There was an error updating your request. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('There was an error updating your request. Please try again.');
            });
        });
    }
});
</script>

<style>
.nav-tabs .nav-link .badge {
    margin-left: 5px;
    font-size: 0.7em;
    vertical-align: top;
    position: relative;
    top: -1px;
}

/* Styling for request cards */
.card-header.bg-info {
    background-color: #17a2b8 !important;
}

.card-header.bg-warning {
    background-color: #ffc107 !important;
}

/* Icons for direction */
.fas.fa-arrow-right, .fas.fa-arrow-left, .fas.fa-exchange-alt {
    margin-right: 5px;
}

/* Action buttons styling */
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.edit-request-btn, .delete-request-form button {
    white-space: nowrap;
}

/* Confirmation dialog styling */
.modal-footer {
    justify-content: space-between;
}
</style>