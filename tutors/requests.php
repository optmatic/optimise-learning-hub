<!-- =========================== REQUESTS TAB =========================== -->
<div class="tab-pane fade" id="requests" role="tabpanel" aria-labelledby="requests-tab">
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
    
    // Process these request actions
    process_confirm_reschedule();
    process_decline_reschedule();
    
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
        
        $student_query = new WP_User_Query([
            'role' => 'student',
            'fields' => ['ID', 'user_login', 'display_name']
        ]);
        
        foreach ($student_query->get_results() as $student) {
            $assigned_tutors = get_user_meta($student->ID, 'assigned_tutors', true);
            if (!empty($assigned_tutors) && in_array($current_user_id, explode(',', $assigned_tutors))) {
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
        $args = [
            'post_type' => 'progress_report',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'tutor_id',
                    'value' => get_current_user_id(),
                    'compare' => '=',
                ],
                [
                    'key' => 'request_type',
                    'value' => $type,
                    'compare' => '=',
                ]
            ],
            'order' => 'DESC',
            'orderby' => 'date'
        ];
        
        if ($status) {
            $args['meta_query'][] = [
                'key' => 'status',
                'value' => $status,
                'compare' => '=',
            ];
        }
        
        return get_posts($args);
    }
    
    function get_student_initiated_requests() {
        $args = [
            'post_type' => 'progress_report',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'tutor_name',
                    'value' => wp_get_current_user()->user_login,
                    'compare' => '=',
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
                                
                                <div class="mb-3">
                                    <label for="student_select" class="form-label">Select Student <span class="text-danger">*</span></label>
                                    <?php
                                    $students = get_tutor_students();
                                    
                                    if (!empty($students)) {
                                        echo '<select name="student_name" id="student_select" class="form-select" required>';
                                        echo '<option value="">--Select student--</option>';
                                        foreach ($students as $student) {
                                            echo '<option value="' . esc_attr($student['username']) . '">' 
                                                 . esc_html($student['display_name']) . '</option>';
                                        }
                                        echo '</select>';
                                    } else {
                                        echo '<div class="alert alert-warning">No students assigned to you. Please contact support.</div>';
                                    }
                                    ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="lesson_select" class="form-label">Lesson Date to Reschedule <span class="text-danger">*</span></label>
                                    <select class="form-select" id="lesson_select" name="lesson_select" required>
                                        <option value="">--Select a scheduled lesson--</option>
                                        <?php
                                        $upcoming_lessons = get_upcoming_lessons();
                                        
                                        foreach ($upcoming_lessons as $lesson) {
                                            echo '<option value="' . $lesson['date_value'] . '|' . $lesson['time_value'] . '">' 
                                                 . $lesson['subject'] . ' - ' . $lesson['formatted'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                    
                                    <input type="hidden" id="original_date" name="original_date">
                                    <input type="hidden" id="original_time" name="original_time">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="reason" class="form-label">Reason for Reschedule <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Preferred Alternative Times <span class="text-danger">*</span></label>
                                    <p class="text-muted small">Please select at least one preferred alternative date and time.</p>
                                    
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
            $tutor_requests = get_reschedule_requests('tutor_reschedule');
            
            if (!empty($tutor_requests)) {
                echo '<div class="table-responsive"><table class="table table-striped">';
                echo '<thead><tr><th>Date Requested</th><th>Lesson Date</th><th>Preferred Times</th><th>Student</th><th>Status</th><th>Actions</th></tr></thead>';
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
                    
                    echo '<td>' . esc_html(get_student_display_name($student_name)) . '</td>';
                    echo '<td>' . get_status_badge($status) . '</td>';
                    echo '<td>';
                    
                    // Only show edit/delete buttons for pending requests
                    if ($status !== 'confirmed' && $status !== 'denied') {
                        echo '<button type="button" class="btn btn-sm btn-primary me-1 edit-request-btn" 
                            data-bs-toggle="modal" data-bs-target="#editRescheduleRequestModal" 
                            data-request-id="' . $request_id . '"
                            data-student-name="' . esc_attr($student_name) . '"
                            data-original-date="' . esc_attr($original_date) . '"
                            data-original-time="' . esc_attr($original_time) . '"
                            data-reason="' . esc_attr($reason) . '">
                            <i class="fas fa-edit"></i> Edit
                        </button>';
                        
                        echo '<form method="post" class="d-inline delete-request-form">';
                        echo '<input type="hidden" name="delete_tutor_request" value="1">';
                        echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                        echo '<button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>';
                        echo '</form>';
                    } else {
                        echo '<span class="text-muted">No actions available</span>';
                    }
                    
                    echo '</td></tr>';
                }
                
                echo '</tbody></table></div>';
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
                echo '<thead><tr><th>Date Requested</th><th>Original Lesson</th><th>Preferred Times</th><th>Student</th><th>Status</th><th>Actions</th></tr></thead>';
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
                    
                    echo '<td>' . esc_html(get_student_display_name($student_name)) . '</td>';
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
                              data-original-time="' . esc_attr($original_time) . '">
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
                <h5 class="modal-title" id="unavailableModalLabel">Provide Alternative Times</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="unavailableErrorMessage" class="alert alert-danger" style="display: none;">
                    <p>Please provide at least one alternative time.</p>
                </div>
                <p>You've indicated you're unavailable for the requested time. Please provide alternative times that would work for you.</p>
                <p><strong>Student:</strong> <span id="unavailable_student_name"></span></p>
                <p><strong>Original Time:</strong> <span id="unavailable_original_time"></span></p>
                
                <form id="unavailableForm" method="post">
                    <input type="hidden" name="decline_reschedule" value="1">
                    <input type="hidden" name="request_id" id="unavailable_request_id" value="">
                    <input type="hidden" name="tutor_name" value="<?php echo wp_get_current_user()->user_login; ?>">
                    <input type="hidden" name="student_id" id="unavailable_student_id" value="">
                    <input type="hidden" name="active_tab" value="requests">
                    
                    <div class="mb-3">
                        <label class="form-label">Alternative Times <span class="text-danger">*</span></label>
                        <p class="text-muted small">Please provide at least one alternative date and time.</p>
                        
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
                        <button type="submit" class="btn btn-primary" id="submitUnavailable">Submit Alternative Times</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add JavaScript to handle the unavailable modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set up the unavailable modal data
    const unavailableModal = document.getElementById('unavailableModal');
    if (unavailableModal) {
        unavailableModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const requestId = button.getAttribute('data-request-id');
            const studentId = button.getAttribute('data-student-id');
            const studentName = button.getAttribute('data-student-name');
            const originalDate = button.getAttribute('data-original-date');
            const originalTime = button.getAttribute('data-original-time');
            
            document.getElementById('unavailable_request_id').value = requestId;
            document.getElementById('unavailable_student_id').value = studentId;
            document.getElementById('unavailable_student_name').textContent = studentName;
            
            // Format the date and time
            const dateObj = new Date(originalDate + ' ' + originalTime);
            const formattedDate = dateObj.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric'
            });
            const formattedTime = dateObj.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: 'numeric', 
                hour12: true 
            });
            
            document.getElementById('unavailable_original_time').textContent = formattedDate + ' at ' + formattedTime;
        });
    }
    
    // Form validation
    const unavailableForm = document.getElementById('unavailableForm');
    if (unavailableForm) {
        unavailableForm.addEventListener('submit', function(event) {
            const altDates = document.querySelectorAll('.alt-date');
            const altTimes = document.querySelectorAll('.alt-time');
            let valid = false;
            
            // Check if at least one alternative time is provided
            for (let i = 0; i < altDates.length; i++) {
                if (altDates[i].value && altTimes[i].value) {
                    valid = true;
                    break;
                }
            }
            
            if (!valid) {
                event.preventDefault();
                document.getElementById('unavailableErrorMessage').style.display = 'block';
            }
        });
    }
});
</script>
