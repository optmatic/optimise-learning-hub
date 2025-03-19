<!-- =========================== YOUR LESSON SCHEDULE TAB =========================== -->
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