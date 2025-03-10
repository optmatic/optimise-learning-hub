<?php
/*
Template Name: Student Dashboard
*/
?>

<?php get_header(); ?>

<?php
if (current_user_can('student')) {
?>

<div class="container mt-4">
    <div class="row">
        <!-- (Navigation) -->
        <div class="col-12">
            <ul class="nav nav-tabs" id="myTab" role="tablist" style="padding-left: 0px !important;">
                <li class="nav-item">
                    <a class="nav-link active" id="home" data-bs-toggle="tab" href="#home-tab">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="learning-goals-tab" data-bs-toggle="tab" href="#learning-goals">Your Learning Plan</a>
                </li>
               <li class="nav-item">
                    <a class="nav-link" id="schedule-tab" data-bs-toggle="tab" href="#schedule">Your Lesson Schedule</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="classroom-tab" data-bs-toggle="tab" href="#classroom">Your Classrooms</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="my-progress-tab" data-bs-toggle="tab" href="#my-progress">Your Learning Overviews</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tutor-comms-tab" data-bs-toggle="tab" href="#tutor-comms">Tutor Comms</a>
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
    <div style="background-color: rgba(42, 98, 143, 0.07); padding: 1.5rem 1.5rem 1.5rem 1.5rem;">
        <div style="margin-bottom: 30px;"> <h4>Your Upcoming Lesson Details</h4> 
        <p style="font-size: 14px; font-style: italic;">Please note that the times displayed below are in <strong>AEST</strong> (Australian Eastern Standard Time)</strong>.</p>
        </div>
        <?php
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
                                    'formatted' => $lesson_date->format('l, jS \o\f F Y \a\t g:i A')
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
                                    'formatted' => $lesson_date->format('l, jS \o\f F Y \a\t g:i A')
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
                                    'formatted' => $lesson_date->format('l, jS \o\f F Y \a\t g:i A')
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
                                    'formatted' => $lesson_date->format('l, jS \o\f F Y \a\t g:i A')
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
                    <h5>Tutor Communications</h5>
                    
                    <?php
                    $current_user = wp_get_current_user();
                    $user_id = $current_user->ID;
                    
                    // Query for reschedule requests
                    $args = array(
                        'post_type'      => 'progress_report',
                        'posts_per_page' => -1,
                        'meta_query'     => array(
                            'relation' => 'AND',
                            array(
                                'key'     => 'student_id',
                                'value'   => $user_id,
                                'compare' => '=',
                            ),
                            array(
                                'key'     => 'request_type',
                                'value'   => 'reschedule',
                                'compare' => '=',
                            )
                        )
                    );
                    
                    $reschedule_requests = get_posts($args);
                    
                    if (!empty($reschedule_requests)) {
                        echo '<div class="accordion" id="rescheduleAccordion">';
                        $counter = 1;
                        
                        foreach ($reschedule_requests as $request) {
                            $request_id = $request->ID;
                            $tutor_name = get_post_meta($request_id, 'tutor_name', true);
                            $new_date = get_post_meta($request_id, 'new_date', true);
                            $reason = get_post_meta($request_id, 'reason', true);
                            
                            // Format the date for display
                            $formatted_date = date('jS \of F, Y', strtotime($new_date));
                            
                            echo '<div class="accordion-item">';
                            echo '<h2 class="accordion-header" id="rescheduleHeading' . $counter . '">';
                            echo '<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#rescheduleCollapse' . $counter . '" aria-expanded="true" aria-controls="rescheduleCollapse' . $counter . '">';
                            echo 'Reschedule Request - ' . $formatted_date;
                            echo '</button>';
                            echo '</h2>';
                            echo '<div id="rescheduleCollapse' . $counter . '" class="accordion-collapse collapse" aria-labelledby="rescheduleHeading' . $counter . '" data-bs-parent="#rescheduleAccordion">';
                            echo '<div class="accordion-body">';
                            echo '<p><strong>Tutor:</strong> ' . $tutor_name . '</p>';
                            echo '<p><strong>Proposed New Date:</strong> ' . $formatted_date . '</p>';
                            echo '<p><strong>Reason:</strong> ' . $reason . '</p>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            
                            $counter++;
                        }
                        
                        echo '</div>';
                    } else {
                        echo '<p>No reschedule requests found.</p>';
                    }
                    ?>
                </div>

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