<!-- =========================== REQUESTS TAB =========================== -->
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
                                                <p>Please fill in all required fields (tutor, lesson, and reason).</p>
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
                                                        'fields' => array('ID', 'user_login', 'display_name')
                                                    ));
                                                    
                                                    // Get all tutors
                                                    $all_tutors = $tutor_query->get_results();
                                                    
                                                    // Check each tutor to see if the current student is assigned to them
                                                    foreach ($all_tutors as $tutor) {
                                                        $assigned_students = get_user_meta($tutor->ID, 'assigned_students', true);
                                                        if (!empty($assigned_students)) {
                                                            $student_ids = explode(',', $assigned_students);
                                                            if (in_array($current_user_id, $student_ids)) {
                                                                // Get tutor's first and last name
                                                                $first_name = get_user_meta($tutor->ID, 'first_name', true);
                                                                $last_name = get_user_meta($tutor->ID, 'last_name', true);
                                                                
                                                                // Use full name if available, otherwise use display name
                                                                $display_name = (!empty($first_name) && !empty($last_name)) 
                                                                    ? $first_name . ' ' . $last_name 
                                                                    : $tutor->display_name;
                                                                
                                                                $tutors[] = array(
                                                                    'id' => $tutor->ID,
                                                                    'username' => $tutor->user_login,
                                                                    'display_name' => $display_name
                                                                );
                                                            }
                                                        }
                                                    }
                                                    
                                                    if (!empty($tutors)) {
                                                        echo '<select name="tutor_name" id="tutor_select" class="form-select" required>';
                                                        echo '<option value="">--Select tutor--</option>';
                                                        foreach ($tutors as $tutor) {
                                                            // Store username as value but display full name to user
                                                            echo '<option value="' . esc_attr($tutor['username']) . '">' . esc_html($tutor['display_name']) . '</option>';
                                                        }
                                                        echo '</select>';
                                                    } else {
                                                        echo '<div class="alert alert-warning">No tutors assigned to you. Please contact support.</div>';
                                                    }
                                                    ?>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="lesson_select" class="form-label">Lesson Date to Reschedule <span class="text-danger">*</span></label>
                                                    <select class="form-select" id="lesson_select" name="lesson_select" required>
                                                        <option value="">--Select a scheduled lesson--</option>
                            <?php
                                                        // Get current date for comparison
                                                        $now = new DateTime('now', new DateTimeZone('Australia/Brisbane'));
                                                        
                                                        // Get the student's lesson schedule
                                                        $lesson_schedule = get_user_meta(get_current_user_id(), 'lesson_schedule_list', true);
                                                        
                                                        if (!empty($lesson_schedule)) {
                                                            $lessons = explode("\n", $lesson_schedule);
                                                            $upcoming_lessons = [];
                                                            
                                                            // Process each lesson in the schedule
                                                            foreach ($lessons as $lesson) {
                                                                if (!empty(trim($lesson))) {
                                                                    // Extract date and time using regex
                                                                    if (preg_match('/on ([A-Za-z]+) (\d+) ([A-Za-z]+) (\d{4}) at (\d{2}:\d{2})/', $lesson, $matches)) {
                                                                        $date_string = $matches[1] . ' ' . $matches[2] . ' ' . $matches[3] . ' ' . $matches[4] . ' ' . $matches[5];
                                                                        $lesson_date = DateTime::createFromFormat('l d F Y H:i', $date_string, new DateTimeZone('Australia/Brisbane'));
                                                                        
                                                                        // Only include future lessons
                                                                        if ($lesson_date > $now) {
                                                                            // Determine subject
                                                                            $subject = 'Lesson';
                                                                            if (stripos($lesson, 'mathematics') !== false) {
                                                                                $subject = 'Mathematics';
                                                                            } elseif (stripos($lesson, 'english') !== false) {
                                                                                $subject = 'English';
                                                                            } elseif (stripos($lesson, 'chemistry') !== false) {
                                                                                $subject = 'Chemistry';
                                                                            } elseif (stripos($lesson, 'physics') !== false) {
                                                                                $subject = 'Physics';
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
                                                            }
                                                            
                                                            // Sort lessons by date
                                                            usort($upcoming_lessons, function($a, $b) {
                                                                return $a['date']->getTimestamp() - $b['date']->getTimestamp();
                                                            });
                                                            
                                                            // Output options for the select dropdown
                                                            foreach ($upcoming_lessons as $lesson) {
                                                                echo '<option value="' . $lesson['date_value'] . '|' . $lesson['time_value'] . '">' 
                                                                    . $lesson['subject'] . ' - ' . $lesson['formatted'] . '</option>';
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                    
                                                    <!-- Hidden fields to store the selected date and time -->
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
                                                        <!-- Preferred Time 1 -->
                                                        <div class="preferred-time-row mb-2">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <label class="form-label small">Preferred Date 1:</label>
                                                                    <input type="date" class="form-control preferred-date" name="preferred_date_1" id="preferred_date_1" required>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label small">Preferred Time 1:</label>
                                                                    <input type="time" class="form-control preferred-time" name="preferred_time_1" id="preferred_time_1" required>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Preferred Time 2 -->
                                                        <div class="preferred-time-row mb-2">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <label class="form-label small">Preferred Date 2:</label>
                                                                    <input type="date" class="form-control preferred-date" name="preferred_date_2" id="preferred_date_2">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label small">Preferred Time 2:</label>
                                                                    <input type="time" class="form-control preferred-time" name="preferred_time_2" id="preferred_time_2">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Preferred Time 3 -->
                                                        <div class="preferred-time-row mb-2">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <label class="form-label small">Preferred Date 3:</label>
                                                                    <input type="date" class="form-control preferred-date" name="preferred_date_3" id="preferred_date_3">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label small">Preferred Time 3:</label>
                                                                    <input type="time" class="form-control preferred-time" name="preferred_time_3" id="preferred_time_3">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="preferred-times-error" class="text-danger mt-2" style="display: none;">
                                                    Please provide at least one preferred alternative time.
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
                            <i class="fas fa-arrow-left me-2"></i> Your Outgoing Reschedule Requests
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
                                echo '<thead><tr><th>Date Requested</th><th>Lesson Date</th><th>Preferred Times</th><th>Tutor</th><th>Status</th><th>Actions</th></tr></thead>';
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
                                    $tutor_response = get_post_meta($request_id, 'tutor_response', true);
                                    
                                    // Format the original date for display
                                    $formatted_original = !empty($original_date) ? date('M j, Y', strtotime($original_date)) . ' at ' . date('g:i A', strtotime($original_time)) : 'N/A';
                                    
                                    // Get tutor's full name
                                    $tutor_full_name = $tutor_name;
                                    $tutor_user = get_user_by('login', $tutor_name);
                                    if ($tutor_user) {
                                        $first_name = get_user_meta($tutor_user->ID, 'first_name', true);
                                        $last_name = get_user_meta($tutor_user->ID, 'last_name', true);
                                        
                                        if (!empty($first_name) && !empty($last_name)) {
                                            $tutor_full_name = $first_name . ' ' . $last_name;
                                        } else {
                                            $tutor_full_name = $tutor_user->display_name;
                                        }
                                    }
                                    
                                    // Set status badge
                                    $status_badge = '';
                                    $notification = ''; // Initialize notification variable to prevent undefined variable error
                                    
                                    if ($status === 'confirmed' || $status === 'accepted') {
                                        $status_badge = '<span class="badge bg-success">Accepted</span>';
                                    } elseif ($status === 'denied' || $status === 'declined') {
                                        $status_badge = '<span class="badge bg-danger">Declined</span>';
                                        
                                        // Add notification for declined requests
                                        if (!empty($tutor_response)) {
                                            $notification = '<div class="mt-1"><small class="text-danger"><i class="fas fa-info-circle"></i> ' . esc_html($tutor_response) . '</small></div>';
                                        }
                                    } elseif ($status === 'unavailable') {
                                        $status_badge = '<span class="badge bg-warning">Tutor Unavailable</span>';
                                        
                                        // Check if there are alternative times provided by the tutor
                                        $alternative_requests_args = array(
                                            'post_type'      => 'progress_report',
                                            'posts_per_page' => 1,
                                            'meta_query'     => array(
                                                'relation' => 'AND',
                                                array(
                                                    'key'     => 'original_request_id',
                                                    'value'   => $request_id,
                                                    'compare' => '=',
                                                ),
                                                array(
                                                    'key'     => 'request_type',
                                                    'value'   => 'tutor_unavailable',
                                                    'compare' => '=',
                                                )
                                            )
                                        );
                                        
                                        $alternative_requests = get_posts($alternative_requests_args);
                                        
                                        if (!empty($alternative_requests)) {
                                            $notification = '<div class="mt-1"><small class="text-info"><i class="fas fa-info-circle"></i> Tutor has provided alternative times</small></div>';
                                            $notification .= '<a href="#alternativeTimesSection" class="btn btn-sm btn-outline-primary mt-1">View Alternative Times</a>';
                                        } else {
                                            $notification = '<div class="mt-1"><small class="text-warning"><i class="fas fa-info-circle"></i> Tutor is unavailable for this time</small></div>';
                                        }
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
                                    
                                    echo '<td>' . esc_html($tutor_full_name) . '</td>';
                                    echo '<td>' . $status_badge . $notification . '</td>';
                                    echo '<td>';
                                    
                                    // Only show edit/delete buttons for pending requests
                                    if ($status !== 'confirmed' && $status !== 'denied' && $status !== 'accepted' && $status !== 'declined') {
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
                                        echo '<input type="hidden" name="active_tab" value="tutor-comms">';
                                        echo '<button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>';
                                        echo '</form>';
                                    } else {
                                        // Allow archiving for declined/denied requests regardless of date
                                        if ($status === 'denied' || $status === 'declined') {
                                            echo '<form method="post" class="d-inline delete-request-form">';
                                            echo '<input type="hidden" name="delete_student_request" value="1">';
                                            echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                                            echo '<input type="hidden" name="active_tab" value="tutor-comms">';
                                            echo '<button type="submit" class="btn btn-sm btn-secondary"><i class="fas fa-archive"></i> Archive</button>';
                                            echo '</form>';
                                        } else {
                                            // For accepted/confirmed requests, check if they're expired
                                            $now = new DateTime('now', new DateTimeZone('Australia/Brisbane'));
                                            $lesson_date = new DateTime($original_date . ' ' . $original_time, new DateTimeZone('Australia/Brisbane'));
                                            $is_expired = $lesson_date < $now;
                                            
                                            // Check if all preferred times have passed too
                                            $all_times_passed = true;
                                            if (!empty($preferred_times) && is_array($preferred_times)) {
                                                foreach ($preferred_times as $time) {
                                                    if (!empty($time['date']) && !empty($time['time'])) {
                                                        $preferred_date = new DateTime($time['date'] . ' ' . $time['time'], new DateTimeZone('Australia/Brisbane'));
                                                        if ($preferred_date > $now) {
                                                            $all_times_passed = false;
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            // Show delete button if the request is completed and expired
                                            if ($is_expired && $all_times_passed) {
                                                echo '<form method="post" class="d-inline delete-request-form">';
                                                echo '<input type="hidden" name="delete_student_request" value="1">';
                                                echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                                                echo '<input type="hidden" name="active_tab" value="tutor-comms">';
                                                echo '<button type="submit" class="btn btn-sm btn-secondary"><i class="fas fa-archive"></i> Archive</button>';
                                                echo '</form>';
                                            } else {
                                                echo '<span class="text-muted">No actions available</span>';
                                            }
                                        }
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
                                        'value'   => 'tutor_reschedule',
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
                                    echo '<input type="hidden" name="active_tab" value="tutor-comms">';
                                    echo '<button type="submit" class="btn btn-sm btn-success me-1">Accept</button>';
                                    echo '</form>';
                                    
                                    echo '<form method="post" class="d-inline">';
                                    echo '<input type="hidden" name="decline_reschedule" value="1">';
                                    echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                                    echo '<input type="hidden" name="active_tab" value="tutor-comms">';
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
                    
                    <!-- Get tutor unavailable responses -->
                    <?php
                    // Get tutor unavailable responses
                    $unavailable_requests_args = array(
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
                                'value'   => 'tutor_unavailable',
                                'compare' => '=',
                            )
                        ),
                        'order'          => 'DESC',
                        'orderby'        => 'date'
                    );

                    $unavailable_requests = get_posts($unavailable_requests_args);

                    if (!empty($unavailable_requests)) {
                        echo '<div class="card mb-4" id="alternativeTimesSection">';
                        echo '<div class="card-header bg-info text-white">';
                        echo '<i class="fas fa-calendar-alt me-2"></i> Tutor Alternative Times';
                        echo '</div>';
                        echo '<div class="card-body">';
                        
                        echo '<p>Your tutor is unavailable for your requested times but has provided alternatives. Please select a time that works for you:</p>';
                        
                        echo '<div class="accordion" id="unavailableAccordion">';
                        $counter = 1;
                        
                        foreach ($unavailable_requests as $request) {
                            $request_id = $request->ID;
                            $original_request_id = get_post_meta($request_id, 'original_request_id', true);
                            $tutor_name = get_post_meta($request_id, 'tutor_name', true);
                            $alternatives = get_post_meta($request_id, 'alternatives', true);
                            $status = get_post_meta($request_id, 'status', true);
                            $request_date = get_the_date('F j, Y', $request_id);
                            
                            // Get tutor's full name
                            $tutor_full_name = $tutor_name;
                            $tutor_user = get_user_by('login', $tutor_name);
                            if ($tutor_user) {
                                $first_name = get_user_meta($tutor_user->ID, 'first_name', true);
                                $last_name = get_user_meta($tutor_user->ID, 'last_name', true);
                                
                                if (!empty($first_name) && !empty($last_name)) {
                                    $tutor_full_name = $first_name . ' ' . $last_name;
                                } else {
                                    $tutor_full_name = $tutor_user->display_name;
                                }
                            }
                            
                            // Get original request details
                            $original_date = get_post_meta($original_request_id, 'original_date', true);
                            $original_time = get_post_meta($original_request_id, 'original_time', true);
                            
                            // Format the original date for display - making sure to handle empty values
                            $formatted_original_date = !empty($original_date) ? date('l, jS \of F, Y', strtotime($original_date)) : 'N/A';
                            $formatted_original_time = !empty($original_time) ? date('g:i A', strtotime($original_time)) : '';
                            
                            // If the original date is not available from the meta, try to get it from the parent request
                            if ($formatted_original_date === 'N/A' && !empty($original_request_id)) {
                                $parent_original_date = get_post_meta($original_request_id, 'original_date', true);
                                $parent_original_time = get_post_meta($original_request_id, 'original_time', true);
                                
                                if (!empty($parent_original_date)) {
                                    $formatted_original_date = date('l, jS \of F, Y', strtotime($parent_original_date));
                                }
                                
                                if (!empty($parent_original_time)) {
                                    $formatted_original_time = date('g:i A', strtotime($parent_original_time));
                                }
                            }
                            
                            // Set status badge
                            $status_badge = '';
                            if ($status === 'confirmed') {
                                $status_badge = '<span class="badge bg-success">Confirmed</span>';
                            } else {
                                $status_badge = '<span class="badge bg-warning">Pending</span>';
                            }
                            
                            echo '<div class="accordion-item">';
                            echo '<h2 class="accordion-header" id="unavailableHeading' . $counter . '">';
                            echo '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#unavailableCollapse' . $counter . '" aria-expanded="false" 
                                    aria-controls="unavailableCollapse' . $counter . '">';
                            echo 'Alternative Times - ' . $request_date . ' from ' . $tutor_full_name . ' ' . $status_badge;
                            echo '</button>';
                            echo '</h2>';
                            
                            echo '<div id="unavailableCollapse' . $counter . '" class="accordion-collapse collapse" 
                                    aria-labelledby="unavailableHeading' . $counter . '" data-bs-parent="#unavailableAccordion">';
                            echo '<div class="accordion-body">';
                            
                            echo '<div class="card mb-3">';
                            echo '<div class="card-header bg-light">Original Requested Lesson</div>';
                            echo '<div class="card-body">';
                            echo '<p><strong>Date:</strong> ' . $formatted_original_date . '</p>';
                            if (!empty($formatted_original_time)) {
                                echo '<p><strong>Time:</strong> ' . $formatted_original_time . '</p>';
                            }
                            echo '<p><strong>Tutor:</strong> ' . esc_html($tutor_full_name) . '</p>';
                            echo '</div>';
                            echo '</div>';
                            
                            if ($status !== 'confirmed') {
                                echo '<form method="post" class="mt-3">';
                                echo '<input type="hidden" name="accept_tutor_alternative" value="1">';
                                echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                                
                                echo '<div class="list-group mb-3">';
                                foreach ($alternatives as $index => $alternative) {
                                    $alt_date = $alternative['date'];
                                    $alt_time = $alternative['time'];
                                    
                                    $formatted_alt_date = date('l, jS \of F, Y', strtotime($alt_date));
                                    $formatted_alt_time = date('g:i A', strtotime($alt_time));
                                    
                                    echo '<div class="list-group-item">';
                                    echo '<div class="form-check">';
                                    echo '<input class="form-check-input" type="radio" name="selected_alternative" 
                                            value="' . $index . '" id="unavail' . $request_id . '_' . $index . '" ' . ($index === 0 ? 'checked' : '') . '>';
                                    echo '<label class="form-check-label" for="unavail' . $request_id . '_' . $index . '">';
                                    echo 'Option ' . ($index + 1) . ': ' . $formatted_alt_date . ' at ' . $formatted_alt_time;
                                    echo '</label>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                                echo '</div>';
                                
                                echo '<button type="submit" class="btn btn-success">Accept Selected Time</button>';
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
                                        <input type="date" class="form-control preferred-date" name="preferred_date_1" id="edit_preferred_date_1">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Preferred Time 1:</label>
                                        <input type="time" class="form-control preferred-time" name="preferred_time_1" id="edit_preferred_time_1">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Preferred Time 2 -->
                            <div class="preferred-time-row mb-2">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label small">Preferred Date 2:</label>
                                        <input type="date" class="form-control preferred-date" name="preferred_date_2" id="edit_preferred_date_2">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Preferred Time 2:</label>
                                        <input type="time" class="form-control preferred-time" name="preferred_time_2" id="edit_preferred_time_2">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Preferred Time 3 -->
                            <div class="preferred-time-row mb-2">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label small">Preferred Date 3:</label>
                                        <input type="date" class="form-control preferred-date" name="preferred_date_3" id="edit_preferred_date_3">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Preferred Time 3:</label>
                                        <input type="time" class="form-control preferred-time" name="preferred_time_3" id="edit_preferred_time_3">
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
