<!-- =========================== REQUESTS TAB =========================== -->
<div class="tab-pane fade <?php echo (isset($_GET['active_tab']) && $_GET['active_tab'] == 'requests') ? 'show active' : ''; ?>" id="requests" role="tabpanel" aria-labelledby="requests-tab">
    <h4>Reschedule Requests</h4>
    
    <?php
    // Process confirmation of reschedule request
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
    function process_tutor_reschedule_request() {
        if (isset($_POST['submit_tutor_reschedule_request']) && $_POST['submit_tutor_reschedule_request'] === '1') {
            $tutor_id = get_current_user_id();
            $tutor_name = wp_get_current_user()->user_login;
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
            $student_name = isset($_POST['student_name']) ? sanitize_text_field($_POST['student_name']) : '';
            $original_date = isset($_POST['original_date']) ? sanitize_text_field($_POST['original_date']) : '';
            $original_time = isset($_POST['original_time']) ? sanitize_text_field($_POST['original_time']) : '';
            $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
            
            // Validate required fields
            if (empty($student_id) || empty($original_date) || empty($original_time) || empty($reason)) {
                return false;
            }
            
            // Get student username if we only have the ID
            if (empty($student_name) && !empty($student_id)) {
                $student = get_user_by('id', $student_id);
                if ($student) {
                    $student_name = $student->user_login;
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
            
            // Create the request post
            $request = [
                'post_title'   => 'Tutor Reschedule Request',
                'post_content' => '',
                'post_status'  => 'publish',
                'post_type'    => 'progress_report',
            ];
            
            $request_id = wp_insert_post($request);
            
            if (!is_wp_error($request_id)) {
                // Save all the necessary meta data
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
                
                // Show success message
                echo '<div class="alert alert-success">Your reschedule request has been submitted successfully.</div>';
                return true;
            }
        }
        return false;
    }
    
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
    
    // Process these request actions
    process_confirm_reschedule();
    process_decline_reschedule();
    process_tutor_reschedule_request();
    process_delete_tutor_request();
    
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
    
    function render_preferred_time_inputs($prefix = '', $required = false) {
        $req = $required ? 'required' : '';
        for ($i = 1; $i <= 3; $i++) {
            echo '<div class="preferred-time-row mb-2"><div class="row">
                <div class="col-md-6">
                    <label class="form-label small">Preferred Date ' . $i . ':</label>
                    <input type="date" class="form-control preferred-date" 
                           name="preferred_date_' . $i . '" id="' . $prefix . 'preferred_date_' . $i . '" ' . ($i == 1 ? $req : '') . '>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Preferred Time ' . $i . ':</label>
                    <input type="time" class="form-control preferred-time" 
                           name="preferred_time_' . $i . '" id="' . $prefix . 'preferred_time_' . $i . '" ' . ($i == 1 ? $req : '') . '>
                </div>
            </div></div>';
        }
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
        if (empty($students)) {
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
                    'key' => 'tutor_id',
                    'value' => $current_user_id,
                    'compare' => '='
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

        return get_posts($args);
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
                                <p>Your reschedule request has been successfully submitted. Your student will be notified.</p>
                            </div>
                            <div id="rescheduleRequestErrorMessage" class="alert alert-danger" style="display: none;">
                                <p>Please fill in all required fields (student, lesson, and reason).</p>
                            </div>
                            <form id="rescheduleRequestForm" method="post">
                                <input type="hidden" name="submit_tutor_reschedule_request" value="1">
                                <input type="hidden" name="tutor_id" value="<?php echo get_current_user_id(); ?>">
                                <input type="hidden" name="active_tab" value="requests">
                                
                                <div class="mb-3">
                                    <label for="student_select" class="form-label">Select Student <span class="text-danger">*</span></label>
                                    <?php
                                    // Using the approach from students/requests.php but adapted for tutors
                                    $current_user_id = get_current_user_id();
                                    $students = [];
                                    
                                    // Query for users with the student role
                                    $student_query = new WP_User_Query([
                                        'role' => 'student',
                                        'fields' => ['ID', 'user_login', 'display_name']
                                    ]);
                                    
                                    // Get all students
                                    $all_students = $student_query->get_results();
                                    
                                    // Check which students are assigned to the current tutor
                                    foreach ($all_students as $student) {
                                        $assigned_tutors = get_user_meta($student->ID, 'assigned_tutors', true);
                                        // Check if current tutor ID is in the assigned tutors list
                                        if (!empty($assigned_tutors)) {
                                            $tutor_ids = explode(',', $assigned_tutors);
                                            if (in_array($current_user_id, $tutor_ids)) {
                                                // Get student's first and last name
                                                $first_name = get_user_meta($student->ID, 'first_name', true);
                                                $last_name = get_user_meta($student->ID, 'last_name', true);
                                                
                                                // Use full name if available, otherwise use display name
                                                $display_name = (!empty($first_name) && !empty($last_name)) 
                                                    ? $first_name . ' ' . $last_name 
                                                    : $student->display_name;
                                                
                                                $students[] = [
                                                    'id' => $student->ID,
                                                    'username' => $student->user_login,
                                                    'display_name' => $display_name
                                                ];
                                            }
                                        }
                                        
                                        // Also check if the tutor is listed in assigned_students meta of the tutor
                                        $assigned_students = get_user_meta($current_user_id, 'assigned_students', true);
                                        if (!empty($assigned_students)) {
                                            $student_ids = explode(',', $assigned_students);
                                            if (in_array($student->ID, $student_ids)) {
                                                // Get student's first and last name
                                                $first_name = get_user_meta($student->ID, 'first_name', true);
                                                $last_name = get_user_meta($student->ID, 'last_name', true);
                                                
                                                // Use full name if available, otherwise use display name
                                                $display_name = (!empty($first_name) && !empty($last_name)) 
                                                    ? $first_name . ' ' . $last_name 
                                                    : $student->display_name;
                                                
                                                // Only add if not already in the array
                                                $exists = false;
                                                foreach ($students as $existing) {
                                                    if ($existing['id'] == $student->ID) {
                                                        $exists = true;
                                                        break;
                                                    }
                                                }
                                                
                                                if (!$exists) {
                                                    $students[] = [
                                                        'id' => $student->ID,
                                                        'username' => $student->user_login,
                                                        'display_name' => $display_name
                                                    ];
                                                }
                                            }
                                        }
                                    }
                                    
                                    if (!empty($students)) {
                                        echo '<select name="student_id" id="student_select" class="form-select" required>';
                                        echo '<option value="">--Select student--</option>';
                                        foreach ($students as $student) {
                                            echo '<option value="' . esc_attr($student['id']) . '" data-username="' . esc_attr($student['username']) . '">' 
                                                 . esc_html($student['display_name']) . '</option>';
                                        }
                                        echo '</select>';
                                        echo '<input type="hidden" name="student_name" id="student_name">';
                                    } else {
                                        echo '<div class="alert alert-warning">No students assigned to you. Please contact support.</div>';
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
                                    <label class="form-label">Preferred Alternative Times</label>
                                    <p class="text-muted small">Please select up to 3 preferred alternative dates and times.</p>
                                    
                                    <div id="preferred-times-container">
                                        <?php render_preferred_time_inputs('', true); ?>
                                    </div>
                                </div>
                                <div id="preferred-times-error" class="text-danger mt-2" style="display: none;">
                                    Please provide at least one preferred alternative time.
                                </div>
                                
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" id="submitTutorReschedule">Submit Request</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Outgoing Reschedule Requests (Tutor-initiated) -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-arrow-left me-2"></i> Your Outgoing Reschedule Requests
        </div>
        <div class="card-body">
            <?php
            // Get tutor's reschedule requests
            $tutor_requests = get_reschedule_requests('tutor_reschedule');
            
            if (!empty($tutor_requests)) {
                echo '<div class="table-responsive">';
                echo '<table class="table table-striped">';
                echo '<thead><tr>
                    <th>Date Requested</th>
                    <th>Lesson Date</th>
                    <th>Preferred Times</th>
                    <th>Student</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr></thead>';
                echo '<tbody>';
                
                foreach ($tutor_requests as $request) {
                    $request_id = $request->ID;
                    $student_name = get_post_meta($request_id, 'student_name', true);
                    $original_date = get_post_meta($request_id, 'original_date', true);
                    $original_time = get_post_meta($request_id, 'original_time', true);
                    $status = get_post_meta($request_id, 'status', true);
                    $request_date = get_the_date('M j, Y', $request_id);
                    $reason = get_post_meta($request_id, 'reason', true);
                    $preferred_times = get_post_meta($request_id, 'preferred_times', true);
                    
                    // Format the original date for display
                    $formatted_original = !empty($original_date) ? date('M j, Y', strtotime($original_date)) . ' at ' . date('g:i A', strtotime($original_time)) : 'N/A';
                    
                    // Set status badge
                    if ($status === 'confirmed' || $status === 'accepted') {
                        $status_badge = '<span class="badge bg-success">Accepted</span>';
                    } elseif ($status === 'denied' || $status === 'declined') {
                        $status_badge = '<span class="badge bg-danger">Declined</span>';
                    } elseif ($status === 'unavailable') {
                        $status_badge = '<span class="badge bg-warning">Student Unavailable</span>';
                    } else {
                        $status_badge = '<span class="badge bg-warning">Pending</span>';
                    }
                    
                    echo '<tr>';
                    echo '<td>' . esc_html($request_date) . '</td>';
                    echo '<td>' . esc_html($formatted_original) . '</td>';
                    
                    // Display preferred times
                    echo '<td>';
                    if (!empty($preferred_times) && is_array($preferred_times)) {
                        foreach ($preferred_times as $index => $time) {
                            if (!empty($time['date']) && !empty($time['time'])) {
                                $formatted_time = date('M j, Y \a\t g:i A', 
                                    strtotime($time['date'] . ' ' . $time['time']));
                                echo 'Option ' . ($index + 1) . ': ' . esc_html($formatted_time) . '<br>';
                            }
                        }
                    } else {
                        echo 'No preferred times specified';
                    }
                    echo '</td>';
                    
                    echo '<td>' . esc_html(get_student_display_name($student_name)) . '</td>';
                    
                    // Add reason column with truncation and modal functionality
                    echo '<td>';
                    if (!empty($reason)) {
                        $truncated_reason = strlen($reason) > 30 ? substr($reason, 0, 30) . '...' : $reason;
                        echo '<span class="reason-text" style="cursor: pointer; color: #fcb31e;" 
                               data-bs-toggle="modal" data-bs-target="#reasonModal" 
                               data-reason="' . esc_attr($reason) . '"
                               data-bs-toggle="tooltip" title="Click to expand">' . esc_html($truncated_reason) . '</span>';
                    } else {
                        echo '<em>No reason provided</em>';
                    }
                    echo '</td>';
                    
                    echo '<td>' . $status_badge . '</td>';
                    echo '<td>';
                    
                    // Only show edit/delete buttons for pending requests
                    if ($status !== 'confirmed' && $status !== 'denied' && $status !== 'accepted' && $status !== 'declined') {
                        echo '<button type="button" class="btn btn-sm btn-primary me-1 edit-request-btn" 
                            data-bs-toggle="modal" data-bs-target="#editRescheduleRequestModal" 
                            data-request-id="' . $request_id . '"
                            data-student-name="' . esc_attr($student_name) . '"
                            data-original-date="' . esc_attr($original_date) . '"
                            data-original-time="' . esc_attr($original_time) . '"
                            data-reason="' . esc_attr($reason) . '">
                            <i class="fas fa-edit"></i> Edit
                        </button>';
                        
                        echo '<button type="button" class="btn btn-sm btn-danger delete-request-btn" 
                            data-request-id="' . $request_id . '">
                            <i class="fas fa-trash"></i> Delete
                        </button>';
                    } else {
                        echo '<span class="text-muted">No actions available</span>';
                    }
                    
                    echo '</td></tr>';
                }
                
                echo '</tbody></table>';
                echo '</div>';
            } else {
                echo '<p>You have not submitted any reschedule requests yet.</p>';
            }
            ?>
        </div>
    </div>
    
    <!-- Incoming Reschedule Requests (Student-initiated) -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <i class="fas fa-arrow-right me-2"></i> Incoming Reschedule Requests
        </div>
        <div class="card-body">
            <?php
            $student_requests = get_student_initiated_requests();
            
            if (!empty($student_requests)) {
                echo '<div class="table-responsive"><table class="table table-striped">';
                echo '<thead><tr><th>Date Requested</th><th>Original Lesson</th><th>Preferred Times</th><th>Student</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>';
                echo '<tbody>';
                
                foreach ($student_requests as $request) {
                    $request_id = $request->ID;
                    $student_name = get_post_meta($request_id, 'student_name', true);
                    $student_id = get_post_meta($request_id, 'student_id', true);
                    $original_date = get_post_meta($request_id, 'original_date', true);
                    $original_time = get_post_meta($request_id, 'original_time', true);
                    $request_date = get_the_date('M j, Y', $request_id);
                    $reason = get_post_meta($request_id, 'reason', true);
                    $preferred_times = get_post_meta($request_id, 'preferred_times', true);
                    $status = get_post_meta($request_id, 'status', true);
                    
                    // Get student name from user data if we only have ID
                    $student_display_name = '';
                    if (!empty($student_id)) {
                        $student_user = get_user_by('id', $student_id);
                        if ($student_user) {
                            $first_name = get_user_meta($student_id, 'first_name', true);
                            $last_name = get_user_meta($student_id, 'last_name', true);
                            $student_display_name = (!empty($first_name) && !empty($last_name)) 
                                ? $first_name . ' ' . $last_name 
                                : $student_user->display_name;
                        }
                    } else if (!empty($student_name)) {
                        $student_display_name = get_student_display_name($student_name);
                    }
                    
                    echo '<tr>';
                    echo '<td>' . esc_html($request_date) . '</td>';
                    echo '<td>' . esc_html(format_datetime($original_date, $original_time)) . '</td>';
                    
                    // Display preferred times
                    echo '<td>';
                    if (!empty($preferred_times) && is_array($preferred_times)) {
                        foreach ($preferred_times as $index => $time) {
                            if (!empty($time['date']) && !empty($time['time'])) {
                                echo 'Option ' . ($index + 1) . ': ' . esc_html(format_datetime($time['date'], $time['time'])) . '<br>';
                            }
                        }
                    } else {
                        echo 'No preferred times specified';
                    }
                    echo '</td>';
                    
                    echo '<td>' . esc_html($student_display_name) . '</td>';
                    
                    // Add reason column
                    echo '<td>';
                    if (!empty($reason)) {
                        // Show first 30 characters with ellipsis if longer
                        $truncated_reason = strlen($reason) > 30 ? substr($reason, 0, 30) . '...' : $reason;
                        // Add a clickable span with tooltip that will open a modal with the full reason
                        echo '<span class="reason-text" style="cursor: pointer; color: #fcb31e;" 
                               data-bs-toggle="modal" data-bs-target="#reasonModal" 
                               data-reason="' . esc_attr($reason) . '"
                               data-bs-toggle="tooltip" title="Click to expand">' . esc_html($truncated_reason) . '</span>';
                    } else {
                        echo '<em>No reason provided</em>';
                    }
                    echo '</td>';
                    
                    echo '<td>' . get_status_badge($status) . '</td>';
                    echo '<td>';
                    
                    // Only show action buttons for pending requests
                    if ($status == 'pending') {
                        echo '<form method="post" class="d-inline">';
                        echo '<input type="hidden" name="confirm_reschedule" value="1">';
                        echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                        echo '<input type="hidden" name="tutor_name" value="' . wp_get_current_user()->user_login . '">';
                        echo '<input type="hidden" name="student_id" value="' . $student_id . '">';
                        echo '<input type="hidden" name="active_tab" value="requests">';
                        echo '<button type="submit" class="btn btn-sm btn-success me-1">Accept</button>';
                        echo '</form>';
                        
                        echo '<button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                              data-bs-target="#unavailableModal" 
                              data-request-id="' . $request_id . '"
                              data-student-id="' . $student_id . '"
                              data-student-name="' . esc_attr(get_student_display_name($student_name)) . '"
                              data-original-date="' . esc_attr($original_date) . '"
                              data-original-time="' . esc_attr($original_time) . '"
                              data-reason="' . esc_attr($reason) . '"
                              data-preferred-times="' . esc_attr(json_encode($preferred_times)) . '">
                              Unavailable
                              </button>';
                    } else {
                        echo '<span class="text-muted">No actions available</span>';
                    }
                    echo '</td></tr>';
                }
                
                echo '</tbody></table></div>';
            } else {
                echo '<p>No incoming reschedule requests from students at this time.</p>';
            }
            ?>
        </div>
    </div>
    
    <!-- Alternative Times Section -->
    <?php
    $alternative_requests = get_reschedule_requests('reschedule_alternatives');
    
    if (!empty($alternative_requests)) {
        // Check for new (unviewed) alternatives
        $has_new_alternatives = false;
        foreach ($alternative_requests as $request) {
            $viewed = get_post_meta($request->ID, 'viewed_by_tutor', true);
            $status = get_post_meta($request->ID, 'status', true);
            if (empty($viewed) && $status === 'pending') {
                $has_new_alternatives = true;
                break;
            }
        }
        ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-exchange-alt me-2"></i> Alternative Lesson Times
            </div>
            <div class="card-body">
                <?php if ($has_new_alternatives) : ?>
                <div class="alert alert-info">
                    <i class="fas fa-bell me-2"></i> <strong>New!</strong> Your student has provided alternative lesson times for you to review.
                </div>
                <?php endif; ?>
                
                <p>Your student has provided alternative times for lessons you were unavailable for. Please select one of the options below:</p>
                
                <div class="accordion" id="alternativeAccordion">
                    <?php 
                    $counter = 1;
                    foreach ($alternative_requests as $request) {
                        $request_id = $request->ID;
                        $original_request_id = get_post_meta($request_id, 'original_request_id', true);
                        $student_name = get_post_meta($request_id, 'student_name', true);
                        $alternatives = get_post_meta($request_id, 'alternatives', true);
                        $message = get_post_meta($request_id, 'message', true);
                        $status = get_post_meta($request_id, 'status', true);
                        $request_date = get_the_date('F j, Y', $request_id);
                        $viewed = get_post_meta($request_id, 'viewed_by_tutor', true);
                        
                        // Get original request details
                        $original_date = get_post_meta($original_request_id, 'original_date', true);
                        $original_time = get_post_meta($original_request_id, 'original_time', true);
                        
                        $is_new = empty($viewed) && $status === 'pending';
                        $student_display_name = get_student_display_name($student_name);
                        
                        // Format dates
                        $formatted_original_date = !empty($original_date) ? date('l, jS \of F, Y', strtotime($original_date)) : 'N/A';
                        $formatted_original_time = !empty($original_time) ? date('g:i A', strtotime($original_time)) : '';
                        
                        // Add "New" badge for unviewed alternatives
                        $new_badge = $is_new ? '<span class="badge bg-danger ms-2">New</span>' : '';
                        ?>
                        
                        <div class="accordion-item<?php echo $is_new ? ' border-danger' : ''; ?>">
                            <h2 class="accordion-header" id="alternativeHeading<?php echo $counter; ?>">
                                <button class="accordion-button<?php echo $is_new ? '' : ' collapsed'; ?>" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#alternativeCollapse<?php echo $counter; ?>" 
                                        aria-expanded="<?php echo $is_new ? 'true' : 'false'; ?>" 
                                        aria-controls="alternativeCollapse<?php echo $counter; ?>">
                                    Alternative Times - <?php echo $request_date; ?> 
                                    <?php echo get_status_badge($status) . $new_badge; ?>
                                </button>
                            </h2>
                            
                            <div id="alternativeCollapse<?php echo $counter; ?>" 
                                 class="accordion-collapse collapse<?php echo $is_new ? ' show' : ''; ?>" 
                                 aria-labelledby="alternativeHeading<?php echo $counter; ?>" 
                                 data-bs-parent="#alternativeAccordion">
                                <div class="accordion-body">
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">Original Lesson</div>
                                        <div class="card-body">
                                            <p><strong>Date:</strong> <?php echo $formatted_original_date; ?></p>
                                            <?php if (!empty($formatted_original_time)) : ?>
                                                <p><strong>Time:</strong> <?php echo $formatted_original_time; ?></p>
                                            <?php endif; ?>
                                            <p><strong>Student:</strong> <?php echo esc_html($student_display_name); ?></p>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($message)) : ?>
                                        <div class="alert alert-info">
                                            <p><strong>Message from student:</strong> <?php echo esc_html($message); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($status !== 'confirmed') : ?>
                                        <form method="post" class="mt-3">
                                            <input type="hidden" name="select_alternative" value="1">
                                            <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                                            <input type="hidden" name="active_tab" value="requests">
                                            
                                            <div class="list-group mb-3">
                                                <?php foreach ($alternatives as $index => $alternative) : 
                                                    $alt_date = $alternative['date'];
                                                    $alt_time = $alternative['time'];
                                                    
                                                    $formatted_alt_date = date('l, jS \of F, Y', strtotime($alt_date));
                                                    $formatted_alt_time = date('g:i A', strtotime($alt_time));
                                                ?>
                                                    <div class="list-group-item">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="selected_alternative" 
                                                                value="<?php echo $index; ?>" id="alt<?php echo $request_id; ?>_<?php echo $index; ?>" 
                                                                <?php echo ($index === 0) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="alt<?php echo $request_id; ?>_<?php echo $index; ?>">
                                                                Option <?php echo ($index + 1); ?>: <?php echo $formatted_alt_date; ?> at <?php echo $formatted_alt_time; ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-success">Confirm Selected Time</button>
                                        </form>
                                    <?php else : 
                                        // Show the confirmed alternative
                                        $selected_index = get_post_meta($request_id, 'selected_alternative', true);
                                        $selected_alternative = $alternatives[$selected_index];
                                        
                                        $formatted_selected_date = date('l, jS \of F, Y', strtotime($selected_alternative['date']));
                                        $formatted_selected_time = date('g:i A', strtotime($selected_alternative['time']));
                                    ?>
                                        <div class="alert alert-success">
                                            <p><strong>Confirmed Time:</strong> <?php echo $formatted_selected_date; ?> at <?php echo $formatted_selected_time; ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php $counter++; ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php } ?>
    
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
                        <input type="hidden" name="update_tutor_reschedule_request" value="1">
                        <input type="hidden" name="request_id" id="edit_request_id" value="">
                        <input type="hidden" name="active_tab" value="requests">
                        
                        <div class="mb-3">
                            <label class="form-label">Student</label>
                            <input type="text" class="form-control" id="edit_student_name" disabled>
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
                                <?php render_preferred_time_inputs('edit_'); ?>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="updateTutorReschedule">Update Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for providing alternative times -->
<div class="modal fade" id="unavailableModal" tabindex="-1" aria-labelledby="unavailableModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="unavailableModalLabel">Provide Your Preferred Alternative Times</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="unavailableErrorMessage" class="alert alert-danger" style="display: none;">
                    <p>Please provide at least one alternative time.</p>
                </div>
                <p class="lead">You've indicated you're unavailable for the student's requested time. Please provide your alternative times.</p>
                
                <!-- Student Information Section -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong>Student's Request Details</strong>
                    </div>
                    <div class="card-body">
                        <p><strong>Student:</strong> <span id="unavailable_student_name" class="text-primary"></span></p>
                        
                        <p><strong>Student's Original Lesson Time:</strong> 
                            <span id="unavailable_original_time" class="text-muted"></span>
                        </p>
                        
                        <div id="student_preferred_times_container">
                            <p><strong>Student's Preferred Alternative Times:</strong></p>
                            <ul id="preferred_times_list" class="list-group"></ul>
                        </div>
                        
                        <div id="student_reason_container">
                            <p><strong>Student's Reason:</strong> 
                                <span id="unavailable_reason" class="text-muted"></span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <h5 class="mt-4">Your Alternative Times</h5>
                <p class="text-muted small">Please provide your preferred alternative times that work for you.</p>
                
                <form id="unavailableForm" method="post">
                    <input type="hidden" name="decline_reschedule" value="1">
                    <input type="hidden" name="request_id" id="unavailable_request_id" value="">
                    <input type="hidden" name="tutor_name" value="<?php echo wp_get_current_user()->user_login; ?>">
                    <input type="hidden" name="student_id" id="unavailable_student_id" value="">
                    <input type="hidden" name="active_tab" value="requests">
                    
                    <div class="mb-3">
                        <label class="form-label">Your Alternative Times <span class="text-danger">*</span></label>
                        <p class="text-muted small">Please provide at least one alternative date and time that works for you.</p>
                        
                        <div id="alternative-times-container">
                            <?php for ($i = 1; $i <= 3; $i++) { ?>
                                <div class="mb-2">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label small">Alternative Date <?php echo $i; ?>:</label>
                                            <input type="date" class="form-control alt-date" 
                                                   name="alt_date_<?php echo $i; ?>" id="alt_date_<?php echo $i; ?>" 
                                                   <?php echo ($i == 1) ? 'required' : ''; ?>>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Alternative Time <?php echo $i; ?>:</label>
                                            <input type="time" class="form-control alt-time" 
                                                   name="alt_time_<?php echo $i; ?>" id="alt_time_<?php echo $i; ?>" 
                                                   <?php echo ($i == 1) ? 'required' : ''; ?>>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitUnavailable">Submit Your Alternative Times</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal for displaying full reason text -->
<div class="modal fade" id="reasonModal" tabindex="-1" aria-labelledby="reasonModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reasonModalLabel">Reschedule Reason</h5>
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

<!-- Add JavaScript to handle the unavailable modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Existing tooltip initialization code
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        const tooltip = new bootstrap.Tooltip(tooltipTriggerEl, {
            delay: { show: 0, hide: 0 }
        });

        tooltipTriggerEl.addEventListener('mouseenter', function() {
            tooltip.show();
        });

        tooltipTriggerEl.addEventListener('mouseleave', function() {
            tooltip.hide();
        });
    });

    // Debug function to log modal population
    function debugLog(message) {
        console.log('Modal Debug: ' + message);
    }

    // Handle the Unavailable button click
    document.querySelectorAll('.btn-warning[data-bs-target="#unavailableModal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            debugLog('Unavailable button clicked');

            // Gather all data attributes
            const requestId = this.getAttribute('data-request-id');
            const studentId = this.getAttribute('data-student-id');
            const studentName = this.getAttribute('data-student-name');
            const originalDate = this.getAttribute('data-original-date');
            const originalTime = this.getAttribute('data-original-time');
            const reason = this.getAttribute('data-reason');
            const preferredTimesAttr = this.getAttribute('data-preferred-times');

            debugLog('Student Name: ' + studentName);
            debugLog('Original Date: ' + originalDate);
            debugLog('Original Time: ' + originalTime);
            debugLog('Reason: ' + reason);
            debugLog('Preferred Times: ' + preferredTimesAttr);

            // Get modal elements
            const studentNameEl = document.getElementById('unavailable_student_name');
            const originalLessonTimeEl = document.getElementById('unavailable_original_time');
            const preferredTimesListEl = document.getElementById('preferred_times_list');
            const studentReasonEl = document.getElementById('unavailable_reason');
            const requestIdInput = document.getElementById('unavailable_request_id');
            const studentIdInput = document.getElementById('unavailable_student_id');

            // Set student name
            if (studentNameEl) {
                studentNameEl.textContent = studentName || 'N/A';
                debugLog('Set student name to: ' + studentNameEl.textContent);
            } else {
                debugLog('Student name element not found');
            }

            // Format and set original lesson time
            if (originalLessonTimeEl) {
                const formattedOriginalTime = formatDateTime(originalDate, originalTime);
                originalLessonTimeEl.textContent = formattedOriginalTime || 'N/A';
                debugLog('Set original lesson time to: ' + originalLessonTimeEl.textContent);
            } else {
                debugLog('Original lesson time element not found');
            }

            // Set student's reason
            if (studentReasonEl) {
                studentReasonEl.textContent = reason || 'No reason provided';
                debugLog('Set reason to: ' + studentReasonEl.textContent);
            } else {
                debugLog('Student reason element not found');
            }

            // Set hidden inputs
            if (requestIdInput) {
                requestIdInput.value = requestId;
                debugLog('Set request ID to: ' + requestId);
            } else {
                debugLog('Request ID input not found');
            }

            if (studentIdInput) {
                studentIdInput.value = studentId;
                debugLog('Set student ID to: ' + studentId);
            } else {
                debugLog('Student ID input not found');
            }

            // Populate preferred times list
            if (preferredTimesListEl) {
                preferredTimesListEl.innerHTML = ''; // Clear previous entries
                debugLog('Cleared preferred times list');

                try {
                    const preferredTimes = JSON.parse(preferredTimesAttr);
                    debugLog('Parsed preferred times: ' + JSON.stringify(preferredTimes));
                    
                    if (preferredTimes && preferredTimes.length > 0) {
                        preferredTimes.forEach((time, index) => {
                            const li = document.createElement('li');
                            li.className = 'list-group-item';
                            const formattedTime = formatDateTime(time.date, time.time);
                            li.textContent = `Option ${index + 1}: ${formattedTime}`;
                            preferredTimesListEl.appendChild(li);
                            debugLog('Added preferred time: ' + li.textContent);
                        });
                    } else {
                        const li = document.createElement('li');
                        li.className = 'list-group-item text-muted';
                        li.textContent = 'No preferred times provided';
                        preferredTimesListEl.appendChild(li);
                        debugLog('No preferred times found');
                    }
                } catch (error) {
                    console.error('Error parsing preferred times:', error);
                    const li = document.createElement('li');
                    li.className = 'list-group-item text-danger';
                    li.textContent = 'Error loading preferred times';
                    preferredTimesListEl.appendChild(li);
                    debugLog('Error parsing preferred times');
                }
            } else {
                debugLog('Preferred times list element not found');
            }
        });
    });

    // Helper function to format date and time
    function formatDateTime(date, time) {
        if (!date || !time) return '';
        const dateObj = new Date(`${date}T${time}`);
        return dateObj.toLocaleString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    }

    // Rest of the existing code...
});
</script>
