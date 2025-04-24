<?php
    function process_confirm_reschedule() {
        if (isset($_POST['confirm_reschedule']) && $_POST['confirm_reschedule'] === '1') {
            $request_id = intval($_POST['request_id']);
            
            // Get necessary data from the request post
            $tutor_name = isset($_POST['tutor_name']) ? sanitize_text_field($_POST['tutor_name']) : wp_get_current_user()->user_login;
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
            
            // Also get these from the original request as a fallback
            if (empty($tutor_name)) {
                $tutor_name = get_post_meta($request_id, 'tutor_name', true);
            }
            if (empty($student_id)) {
                $student_id = get_post_meta($request_id, 'student_id', true);
            }
            
            // Update the request status
            update_post_meta($request_id, 'status', 'confirmed');
            
            // Get preferred times from the request
            $preferred_times = get_post_meta($request_id, 'preferred_times', true);
            
            // Get the first preferred time (or use defaults if not available)
            $new_date = !empty($preferred_times[0]['date']) ? $preferred_times[0]['date'] : '';
            $new_time = !empty($preferred_times[0]['time']) ? $preferred_times[0]['time'] : '';
            
            // Get original date/time
            $original_date = get_post_meta($request_id, 'original_date', true);
            $original_time = get_post_meta($request_id, 'original_time', true);
            
            // Create a notification for the student
            $new_request = array(
                'post_title'   => 'Tutor Accepted Reschedule Request',
                'post_content' => '',
                'post_status'  => 'publish',
                'post_type'    => 'progress_report',
            );
            
            $new_request_id = wp_insert_post($new_request);
            
            if (!is_wp_error($new_request_id)) {
                // Save the request details
                update_post_meta($new_request_id, 'tutor_name', $tutor_name);
                update_post_meta($new_request_id, 'student_id', $student_id);
                update_post_meta($new_request_id, 'request_type', 'reschedule_confirmed');
                update_post_meta($new_request_id, 'original_request_id', $request_id);
                update_post_meta($new_request_id, 'original_date', $original_date);
                update_post_meta($new_request_id, 'original_time', $original_time);
                update_post_meta($new_request_id, 'new_date', $new_date);
                update_post_meta($new_request_id, 'new_time', $new_time);
                update_post_meta($new_request_id, 'status', 'pending');
                
                // Show confirmation message
                echo '<div class="alert alert-success">You have accepted the reschedule request.</div>';
            }
            
            return true;
        }
        return false;
    }
    
    // Process declining of student-initiated reschedule
    function process_decline_reschedule() {
        if (isset($_POST['decline_reschedule']) && $_POST['decline_reschedule'] === '1') {
            $request_id = intval($_POST['request_id']);
            
            // Get necessary data from the request post
            $tutor_name = isset($_POST['tutor_name']) ? sanitize_text_field($_POST['tutor_name']) : wp_get_current_user()->user_login;
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
            
            // Also get these from the original request as a fallback
            if (empty($tutor_name)) {
                $tutor_name = get_post_meta($request_id, 'tutor_name', true);
            }
            if (empty($student_id)) {
                $student_id = get_post_meta($request_id, 'student_id', true);
            }
            
            // Update the request status
            update_post_meta($request_id, 'status', 'unavailable');
            
            // Get the alternative times from POST data
            $alternatives = [];
            for ($i = 1; $i <= 3; $i++) {
                $alt_date = isset($_POST['alt_date_' . $i]) ? sanitize_text_field($_POST['alt_date_' . $i]) : '';
                $alt_time = isset($_POST['alt_time_' . $i]) ? sanitize_text_field($_POST['alt_time_' . $i]) : '';
                
                if (!empty($alt_date) && !empty($alt_time)) {
                    $alternatives[] = [
                        'date' => $alt_date,
                        'time' => $alt_time
                    ];
                }
            }
            
            // Create a notification for the student
            $new_request = array(
                'post_title'   => 'Tutor Unavailable - Alternative Times Provided',
                'post_content' => '',
                'post_status'  => 'publish',
                'post_type'    => 'progress_report',
            );
            
            $new_request_id = wp_insert_post($new_request);
            
            if (!is_wp_error($new_request_id)) {
                // Copy over the original details
                $original_date = get_post_meta($request_id, 'original_date', true);
                $original_time = get_post_meta($request_id, 'original_time', true);
                
                // Save the request details
                update_post_meta($new_request_id, 'tutor_name', $tutor_name);
                update_post_meta($new_request_id, 'student_id', $student_id);
                update_post_meta($new_request_id, 'request_type', 'tutor_unavailable');
                update_post_meta($new_request_id, 'original_request_id', $request_id);
                update_post_meta($new_request_id, 'original_date', $original_date);
                update_post_meta($new_request_id, 'original_time', $original_time);
                update_post_meta($new_request_id, 'alternatives', $alternatives);
                update_post_meta($new_request_id, 'status', 'pending');
                
                // Show confirmation message
                echo '<div class="alert alert-success">You have marked yourself as unavailable for this time and provided alternatives.</div>';
            }
            
            return true;
        }
        return false;
    }
    
    // Add this function to handle the tutor-initiated request submission
    /* COMMENTED OUT - Logic moved to handle_tutor_reschedule_ajax() in functions.php
    function process_tutor_reschedule_request() {
        error_log('process_tutor_reschedule_request called');
        error_log('POST data: ' . print_r($_POST, true));
        
        if (isset($_POST['submit_tutor_reschedule_request']) && $_POST['submit_tutor_reschedule_request'] === '1') {
            error_log('Tutor reschedule request submission detected');
            
            $tutor_id = get_current_user_id();
            $tutor_name = wp_get_current_user()->user_login;
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
            $student_name = isset($_POST['student_name']) ? sanitize_text_field($_POST['student_name']) : '';
            $original_date = isset($_POST['original_date']) ? sanitize_text_field($_POST['original_date']) : '';
            $original_time = isset($_POST['original_time']) ? sanitize_text_field($_POST['original_time']) : '';
            $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
            
            error_log('Processed form data:');
            error_log("tutor_id: $tutor_id");
            error_log("tutor_name: $tutor_name");
            error_log("student_id: $student_id");
            error_log("student_name: $student_name");
            error_log("original_date: $original_date");
            error_log("original_time: $original_time");
            error_log("reason: $reason");
            
            // Validate required fields
            if (empty($student_id) || empty($original_date) || empty($original_time) || empty($reason)) {
                error_log('Validation failed - missing required fields');
                return false;
            }
            
            // Get student username if we only have the ID
            if (empty($student_name) && !empty($student_id)) {
                $student = get_user_by('id', $student_id);
                if ($student) {
                    $student_name = $student->user_login;
                    error_log("Found student name from ID: $student_name");
                }
            }
            
            // Collect preferred times
            $preferred_times = [];
            for ($i = 1; $i <= 3; $i++) {
                $date = isset($_POST['preferred_date_' . $i]) ? sanitize_text_field($_POST['preferred_date_' . $i]) : '';
                $time = isset($_POST['preferred_time_' . $i]) ? sanitize_text_field($_POST['preferred_time_' . $i]) : '';
                
                if (!empty($date) && !empty($time)) {
                    $preferred_times[] = [
                        'date' => $date,
                        'time' => $time
                    ];
                }
            }
            error_log('Preferred times: ' . print_r($preferred_times, true));
            
            // Create the request post
            $request = [
                'post_title'   => 'Tutor Reschedule Request',
                'post_content' => '',
                'post_status'  => 'publish',
                'post_type'    => 'progress_report',
            ];
            
            error_log('Attempting to create post with data: ' . print_r($request, true));
            $request_id = wp_insert_post($request);
            error_log('wp_insert_post result: ' . print_r($request_id, true));
            
            if (!is_wp_error($request_id)) {
                // Save all the necessary meta data
                error_log('Saving post meta data for request ID: ' . $request_id);
                
                update_post_meta($request_id, 'request_type', 'tutor_reschedule');
                update_post_meta($request_id, 'tutor_id', $tutor_id);
                update_post_meta($request_id, 'tutor_name', $tutor_name);
                update_post_meta($request_id, 'student_id', $student_id);
                update_post_meta($request_id, 'student_name', $student_name);
                update_post_meta($request_id, 'original_date', $original_date);
                update_post_meta($request_id, 'original_time', $original_time);
                update_post_meta($request_id, 'reason', $reason);
                update_post_meta($request_id, 'preferred_times', $preferred_times);
                update_post_meta($request_id, 'status', 'pending');
                
                error_log('Successfully saved all post meta');
                echo '<div class="alert alert-success">Your reschedule request has been submitted successfully.</div>';
                return true;
            } else {
                error_log('Error creating post: ' . $request_id->get_error_message());
            }
        } else {
            error_log('Form submission flag not found in POST data');
        }
        return false;
    }
    */
    
    // Add this function near the top with the other process functions
    function process_delete_tutor_request() {
        if (isset($_POST['delete_tutor_request']) && $_POST['delete_tutor_request'] === '1') {
            $request_id = intval($_POST['request_id']);
            
            // Check if it's an AJAX request
            $is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            
            // Verify the request belongs to the current tutor
            $tutor_id = get_post_meta($request_id, 'tutor_id', true);
            if ($tutor_id == get_current_user_id()) {
                wp_delete_post($request_id, true);
                
                if ($is_ajax) {
                    wp_send_json_success(array('message' => 'Request deleted successfully'));
                    exit;
                } else {
                    // For non-AJAX requests, set a session variable to stay on the requests tab
                    if (!session_id()) {
                        session_start();
                    }
                    $_SESSION['active_tab'] = 'requests';
                    echo '<div class="alert alert-success">Request has been deleted successfully.</div>';
                }
            }
            
            return true;
        }
        return false;
    }
    
    // Add this function to handle selecting alternative times
    function process_select_alternative() {
        if (isset($_POST['select_alternative']) && $_POST['select_alternative'] === '1') {
            $request_id = intval($_POST['request_id']);
            $selected_index = intval($_POST['selected_alternative']);
            
            // Get the alternative request details
            $alternatives = get_post_meta($request_id, 'alternatives', true);
            $student_id = get_post_meta($request_id, 'student_id', true);
            $student_name = get_post_meta($request_id, 'student_name', true);
            $original_request_id = get_post_meta($request_id, 'original_request_id', true);
            
            // Validate inputs
            if (empty($alternatives) || !isset($alternatives[$selected_index])) {
                echo '<div class="alert alert-danger">Invalid alternative selected.</div>';
                return false;
            }
            
            // Update the request status and selected alternative
            update_post_meta($request_id, 'status', 'confirmed');
            update_post_meta($request_id, 'selected_alternative', $selected_index);
            update_post_meta($request_id, 'viewed_by_tutor', '1');
            
            // Create a new post to track the confirmed alternative
            $confirmation_post = array(
                'post_title'   => 'Alternative Time Confirmed',
                'post_content' => '',
                'post_status'  => 'publish',
                'post_type'    => 'progress_report',
            );
            
            $confirmation_id = wp_insert_post($confirmation_post);
            
            if (!is_wp_error($confirmation_id)) {
                // Save details of the confirmation
                update_post_meta($confirmation_id, 'request_type', 'reschedule_confirmed');
                update_post_meta($confirmation_id, 'original_request_id', $original_request_id);
                update_post_meta($confirmation_id, 'student_id', $student_id);
                update_post_meta($confirmation_id, 'student_name', $student_name);
                update_post_meta($confirmation_id, 'tutor_id', get_current_user_id());
                update_post_meta($confirmation_id, 'tutor_name', wp_get_current_user()->user_login);
                update_post_meta($confirmation_id, 'selected_alternative', $selected_index);
                update_post_meta($confirmation_id, 'status', 'confirmed');
                
                // Copy over the selected alternative details
                $selected_alternative = $alternatives[$selected_index];
                update_post_meta($confirmation_id, 'new_date', $selected_alternative['date']);
                update_post_meta($confirmation_id, 'new_time', $selected_alternative['time']);
                
                echo '<div class="alert alert-success">You have successfully confirmed an alternative time.</div>';
                return true;
            }
            
            return false;
        }
        return false;
    }
    
    // Process these request actions
    process_confirm_reschedule();
    process_decline_reschedule();
    process_delete_tutor_request();
    process_select_alternative();
    
    // Helper functions for reusability
    function format_datetime($date, $time, $format = 'M j, Y \a\t g:i A') {
        return !empty($date) ? date($format, strtotime($date . ' ' . $time)) : 'N/A';
    }
    
    function get_student_display_name($student_name) {
        $student_user = get_user_by('login', $student_name);
        if ($student_user) {
            $first_name = get_user_meta($student_user->ID, 'first_name', true);
            $last_name = get_user_meta($student_user->ID, 'last_name', true);
            
            return (!empty($first_name) && !empty($last_name)) 
                ? $first_name . ' ' . $last_name 
                : $student_user->display_name;
        }
        return $student_name;
    }
    
    function get_status_badge($status) {
        $badges = [
            'confirmed' => '<span class="badge bg-success">Confirmed</span>',
            'denied' => '<span class="badge bg-danger">Denied</span>',
            'unavailable' => '<span class="badge bg-warning">Unavailable</span>',
            'pending' => '<span class="badge bg-warning">Pending</span>'
        ];
        return $badges[$status] ?? $badges['pending'];
    }
    
    function get_tutor_students() {
        $current_user_id = get_current_user_id();
        $students = [];
        
        // For testing - return some dummy students to ensure the dropdown works
        // Remove this block after testing if the issue is with the query/data retrieval
        $students[] = [
            'id' => 999,
            'username' => 'test_student',
            'display_name' => 'Test Student'
        ];
        
        // Check if the current user has a different method to find students
        $tutor_students = get_user_meta($current_user_id, 'assigned_students', true);
        if (!empty($tutor_students)) {
            if (is_string($tutor_students)) {
                $student_ids = array_map('trim', explode(',', $tutor_students));
                foreach ($student_ids as $student_id) {
                    $student = get_user_by('id', $student_id);
                    if ($student) {
                        $first_name = get_user_meta($student->ID, 'first_name', true);
                        $last_name = get_user_meta($student->ID, 'last_name', true);
                        
                        $students[] = [
                            'id' => $student->ID,
                            'username' => $student->user_login,
                            'display_name' => (!empty($first_name) && !empty($last_name)) 
                                ? $first_name . ' ' . $last_name 
                                : $student->display_name
                        ];
                    }
                }
            } else if (is_array($tutor_students)) {
                foreach ($tutor_students as $student_id) {
                    $student = get_user_by('id', $student_id);
                    if ($student) {
                        $first_name = get_user_meta($student->ID, 'first_name', true);
                        $last_name = get_user_meta($student->ID, 'last_name', true);
                        
                        $students[] = [
                            'id' => $student->ID,
                            'username' => $student->user_login,
                            'display_name' => (!empty($first_name) && !empty($last_name)) 
                                ? $first_name . ' ' . $last_name 
                                : $student->display_name
                        ];
                    }
                }
            }
        }
        
        // Original method - query all students with role 'student'
        if (empty($students)) {
            $student_query = new WP_User_Query([
                'role' => 'student',
                'fields' => ['ID', 'user_login', 'display_name']
            ]);
            
            if (!empty($student_query->get_results())) {
                foreach ($student_query->get_results() as $student) {
                    $assigned_tutors = get_user_meta($student->ID, 'assigned_tutors', true);
                    
                    // Try different formats for assigned_tutors
                    $is_assigned = false;
                    
                    if (is_string($assigned_tutors)) {
                        $tutor_ids = array_map('trim', explode(',', $assigned_tutors));
                        $is_assigned = in_array($current_user_id, $tutor_ids) || in_array(strval($current_user_id), $tutor_ids);
                    } else if (is_array($assigned_tutors)) {
                        $is_assigned = in_array($current_user_id, $assigned_tutors) || in_array(strval($current_user_id), $assigned_tutors);
                    } else if ($assigned_tutors == $current_user_id) {
                        $is_assigned = true;
                    }
                    
                    if ($is_assigned) {
                        $first_name = get_user_meta($student->ID, 'first_name', true);
                        $last_name = get_user_meta($student->ID, 'last_name', true);
                        
                        $students[] = [
                            'id' => $student->ID,
                            'username' => $student->user_login,
                            'display_name' => (!empty($first_name) && !empty($last_name)) 
                                ? $first_name . ' ' . $last_name 
                                : $student->display_name
                        ];
                    }
                }
            }
        }
        
        // For testing - last resort, if no students found through relations, show all students
        if (empty($studesnts)) {
            $student_query = new WP_User_Query([
                'role' => 'student',
                'fields' => ['ID', 'user_login', 'display_name'],
                'number' => 5 // Limit to first 5 for testing
            ]);
            
            if (!empty($student_query->get_results())) {
                foreach ($student_query->get_results() as $student) {
                    $first_name = get_user_meta($student->ID, 'first_name', true);
                    $last_name = get_user_meta($student->ID, 'last_name', true);
                    
                    $students[] = [
                        'id' => $student->ID,
                        'username' => $student->user_login,
                        'display_name' => (!empty($first_name) && !empty($last_name)) 
                            ? $first_name . ' ' . $last_name 
                            : $student->display_name
                    ];
                }
            }
        }
        
        return $students;
    }
    
    function get_upcoming_lessons() {
        $now = new DateTime('now', new DateTimeZone('Australia/Brisbane'));
        $lesson_schedule = get_user_meta(get_current_user_id(), 'lesson_schedule_list', true);
        $upcoming_lessons = [];
        
        if (!empty($lesson_schedule)) {
            $lessons = explode("\n", $lesson_schedule);
            
            foreach ($lessons as $lesson) {
                if (empty(trim($lesson))) continue;
                
                if (preg_match('/on ([A-Za-z]+) (\d+) ([A-Za-z]+) (\d{4}) at (\d{2}:\d{2})/', $lesson, $matches)) {
                    $date_string = $matches[1] . ' ' . $matches[2] . ' ' . $matches[3] . ' ' . $matches[4] . ' ' . $matches[5];
                    $lesson_date = DateTime::createFromFormat('l d F Y H:i', $date_string, new DateTimeZone('Australia/Brisbane'));
                    
                    if ($lesson_date > $now) {
                        // Determine subject
                        $subject = 'Lesson';
                        foreach (['Mathematics', 'English', 'Chemistry', 'Physics'] as $subject_type) {
                            if (stripos($lesson, strtolower($subject_type)) !== false) {
                                $subject = $subject_type;
                                break;
                            }
                        }
                        
                        $upcoming_lessons[] = [
                            'date' => $lesson_date,
                            'formatted' => $lesson_date->format('l, jS \of F Y \a\t g:i A'),
                            'subject' => $subject,
                            'date_value' => $lesson_date->format('Y-m-d'),
                            'time_value' => $lesson_date->format('H:i:s')
                        ];
                    }
                }
            }
            
            usort($upcoming_lessons, function($a, $b) {
                return $a['date']->getTimestamp() - $b['date']->getTimestamp();
            });
        }
        
        return $upcoming_lessons;
    }
    
    function get_reschedule_requests($type, $status = null) {
        $current_user_id = get_current_user_id();
        $current_user_login = wp_get_current_user()->user_login;
        
        error_log("Fetching reschedule requests for user ID: $current_user_id, username: $current_user_login");
        error_log("Request type: $type");
        if ($status) {
            error_log("Status filter: $status");
        }
        
        $args = [
            'post_type' => 'progress_report',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'request_type',
                    'value' => $type,
                    'compare' => '='
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => 'tutor_id',
                        'value' => $current_user_id,
                        'compare' => '='
                    ],
                    [
                        'key' => 'tutor_name',
                        'value' => $current_user_login,
                        'compare' => '='
                    ]
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC'
        ];

        // Add status filter if provided
        if ($status) {
            $args['meta_query'][] = [
                'key' => 'status',
                'value' => $status,
                'compare' => '='
            ];
        }

        error_log('Query args: ' . print_r($args, true));
        $posts = get_posts($args);
        error_log('Found ' . count($posts) . ' requests');
        
        if (empty($posts)) {
            error_log('No requests found. Performing debug query...');
            // Debug query to check all progress reports
            $debug_args = [
                'post_type' => 'progress_report',
                'posts_per_page' => -1,
                'meta_key' => 'request_type',
                'meta_value' => $type
            ];
            $debug_posts = get_posts($debug_args);
            error_log('Debug: Found ' . count($debug_posts) . ' total progress reports of type ' . $type);
            foreach ($debug_posts as $post) {
                $post_tutor_id = get_post_meta($post->ID, 'tutor_id', true);
                $post_tutor_name = get_post_meta($post->ID, 'tutor_name', true);
                error_log("Debug: Post ID {$post->ID} - tutor_id: $post_tutor_id, tutor_name: $post_tutor_name");
            }
        }

        return $posts;
    }
    
    function get_student_initiated_requests() {
        $current_user_id = get_current_user_id();
        $current_user_login = wp_get_current_user()->user_login;
        
        $args = [
            'post_type' => 'progress_report',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'relation' => 'OR',
                    [
                        'key' => 'tutor_id',
                        'value' => $current_user_id,
                        'compare' => '=',
                    ],
                    [
                        'key' => 'tutor_name',
                        'value' => $current_user_login,
                        'compare' => '=',
                    ]
                ],
                [
                    'key' => 'request_type',
                    'value' => 'student_reschedule',
                    'compare' => '=',
                ],
                [
                    'key' => 'status',
                    'value' => 'pending',
                    'compare' => '=',
                ]
            ],
            'order' => 'DESC',
            'orderby' => 'date'
        ];
        
        return get_posts($args);
    }

    // Count confirmed reschedule requests that haven't been viewed
    $confirmed_args = array(
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
            ),
            array(
                'relation' => 'OR',
                array(
                    'key'     => 'viewed_by_tutor',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => 'viewed_by_tutor',
                    'value'   => '1',
                    'compare' => '!=',
                )
            )
        ),
        'fields'         => 'ids'
    );
    $confirmed_count = count(get_posts($confirmed_args));

    // Add this in the Notifications section of your HTML
    if ($confirmed_count > 0 || $requests_notification_count > 0) {
        echo '<div class="alert alert-info">';
        echo '<h5><i class="fas fa-bell me-2"></i>Notifications</h5>';
        echo '<ul class="mb-0">';
        
        if ($confirmed_count > 0) {
            echo '<li>You have <strong>' . $confirmed_count . '</strong> confirmed reschedule ';
            echo 'request' . ($confirmed_count > 1 ? 's' : '') . '. ';
            echo '<a href="#confirmedReschedulesSection" class="btn btn-sm btn-primary ms-2">View</a></li>';
        }
        
        if ($requests_notification_count > 0) {
            echo '<li>You have <strong>' . $requests_notification_count . '</strong> pending student ';
            echo 'request' . ($requests_notification_count > 1 ? 's' : '') . '. ';
            echo '<a href="#incomingRequestsSection" class="btn btn-sm btn-primary ms-2">View</a></li>';
        }
        
        echo '</ul></div>';
    }
    ?>
    
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

            <!-- Dev Autofill Button - REMOVED FROM HERE -->
            <!-- <button type="button" id="devModeCheckbox" class="btn btn-secondary mb-3 ms-2" title="Autofill form with sample data">Autofill Form (Dev)</button> -->
            
            <!-- Modal for creating a new reschedule request -->
            <div class="modal fade" id="newRescheduleRequestModal" tabindex="-1" aria-labelledby="newRescheduleRequestModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="newRescheduleRequestModalLabel">Request Lesson Reschedule</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            
                            <!-- Add success/error message containers -->
                            <div id="tutorRescheduleSuccessMessage" class="alert alert-success" style="display: none;">
                                <p>Your reschedule request has been successfully submitted.</p>
                            </div>
                            <div id="tutorRescheduleErrorMessage" class="alert alert-danger" style="display: none;">
                                <p>There was an error submitting your request. Please check the fields and try again.</p>
                            </div>

                            <!-- Display feedback messages based on URL parameter -->
                            <?php 
                            if (isset($_GET['reschedule_success']) && $_GET['reschedule_success'] === '1'): ?>
                                <div class="alert alert-success">
                                    Reschedule request submitted successfully.
                                </div>
                            <?php endif; 
                            // You could add checks for error parameters here too if needed
                            // elseif (isset($_GET['reschedule_error'])) { ... }
                            ?>

                            <form id="rescheduleRequestForm" method="post" action="">
                                <?php wp_nonce_field( 'tutor_reschedule_request_post_action', 'tutor_reschedule_post_nonce' ); ?>
                                <!-- Keep action hidden field for consistency if needed, but the main check will be the submit button name -->
                                <input type="hidden" name="action" value="submit_tutor_reschedule_post"> 
                                <input type="hidden" name="submit_tutor_reschedule_request" value="1"> <!-- Keep this for potential reuse -->
                                <input type="hidden" name="tutor_id" value="<?php echo get_current_user_id(); ?>">
                                <input type="hidden" name="tutor_name" value="<?php echo wp_get_current_user()->user_login; ?>">
                                <input type="hidden" name="active_tab" value="requests">
                                
                                <div class="mb-3">
                                    <label for="student_select" class="form-label">Select Student <span class="text-danger">*</span></label>
                                    <?php
                                    // Get tutor's assigned students
                                    $students = get_tutor_students();
                                    
                                    if (!empty($students)) {
                                        echo '<select name="student_name" id="student_select" class="form-select" required>';
                                        echo '<option value="">--Select student--</option>';
                                        foreach ($students as $student) {
                                            echo '<option value="' . esc_attr($student['username']) . '" data-id="' . esc_attr($student['id']) . '">' 
                                                . esc_html($student['display_name']) . '</option>';
                                        }
                                        echo '</select>';
                                        echo '<input type="hidden" name="student_id" id="student_id">';
                                    } else {
                                        echo '<div class="alert alert-warning">No students assigned to you. Please contact support.</div>';
                                    }
                                    ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Lesson to Reschedule <span class="text-danger">*</span></label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="original_date" class="form-label small">Date</label>
                                            <input type="date" class="form-control" id="original_date" name="original_date" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="original_time" class="form-label small">Time</label>
                                            <input type="time" class="form-control" id="original_time" name="original_time" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="reason" class="form-label">Reason for Reschedule <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Preferred Alternative Times <span class="text-danger">*</span></label>
                                    <p class="text-muted small">Please select at least one preferred alternative date and time.</p>
                                    
                                    <div id="preferred-times-container">
                                        <?php for ($i = 1; $i <= 3; $i++): ?>
                                        <div class="preferred-time-row mb-2">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label class="form-label small">Preferred Date <?php echo $i; ?>:</label>
                                                    <input type="date" class="form-control preferred-date" name="preferred_date_<?php echo $i; ?>" id="preferred_date_<?php echo $i; ?>" <?php echo $i == 1 ? 'required' : ''; ?>>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small">Preferred Time <?php echo $i; ?>:</label>
                                                    <input type="time" class="form-control preferred-time" name="preferred_time_<?php echo $i; ?>" id="preferred_time_<?php echo $i; ?>" <?php echo $i == 1 ? 'required' : ''; ?>>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div id="preferred-times-error" class="text-danger mt-2" style="display: none;">
                                    Please provide at least one preferred alternative time.
                                </div>
                                
                                <div class="modal-footer">
                                    <!-- ADDED DEV BUTTON HERE -->
                                    <button type="button" id="devModeCheckbox" class="btn btn-outline-secondary me-auto" title="Autofill form with sample data (Dev)">Autofill</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <!-- Change button type to button and add ID -->
                                    <button type="button" class="btn btn-primary" id="submitTutorReschedule">Submit Request</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>