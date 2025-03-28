<!-- =========================== REQUESTS TAB =========================== -->
<div class="tab-pane fade" id="requests" role="tabpanel" aria-labelledby="requests">
    <h4>Reschedule Requests</h4>
    
    <!-- Notifications Section -->
    <div class="mb-4" id="requestNotifications">
        <?php
        // Count pending reschedule requests
        $pending_reschedule_count = count(get_posts(array(
            'post_type'      => 'progress_report',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array('key' => 'student_id', 'value' => get_current_user_id(), 'compare' => '='),
                array('key' => 'request_type', 'value' => 'tutor_reschedule', 'compare' => '='),
                array('key' => 'status', 'value' => 'pending', 'compare' => '=')
            ),
            'fields'         => 'ids'
        )));
        
        // Count pending alternative times
        $alternatives_count = count(get_posts(array(
            'post_type'      => 'progress_report',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array('key' => 'student_id', 'value' => get_current_user_id(), 'compare' => '='),
                array('key' => 'request_type', 'value' => 'tutor_unavailable', 'compare' => '='),
                array('key' => 'status', 'value' => 'pending', 'compare' => '=')
            ),
            'fields'         => 'ids'
        )));
        
        // Display notifications if any exist
        if ($pending_reschedule_count > 0 || $alternatives_count > 0):
        ?>
            <div class="alert alert-info">
                <h5><i class="fas fa-bell me-2"></i>Notifications</h5>
                <ul class="mb-0">
                    <?php if ($pending_reschedule_count > 0): ?>
                    <li>
                        You have <strong><?php echo $pending_reschedule_count; ?></strong> pending reschedule 
                        request<?php echo $pending_reschedule_count > 1 ? 's' : ''; ?> from your tutor.
                        <a href="#incomingRescheduleSection" class="btn btn-sm btn-primary ms-2">View</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($alternatives_count > 0): ?>
                    <li>
                        You have <strong><?php echo $alternatives_count; ?></strong> alternative time 
                        suggestion<?php echo $alternatives_count > 1 ? 's' : ''; ?> from your tutor.
                        <a href="#alternativeTimesSection" class="btn btn-sm btn-primary ms-2">View</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    
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
                        echo '<input type="hidden" name="active_tab" value="requests">';
                        echo '<button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>';
                        echo '</form>';
                    } else {
                        // Allow archiving for declined/denied requests regardless of date
                        if ($status === 'denied' || $status === 'declined') {
                            echo '<form method="post" class="d-inline delete-request-form">';
                            echo '<input type="hidden" name="delete_student_request" value="1">';
                            echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                            echo '<input type="hidden" name="active_tab" value="requests">';
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
                                echo '<input type="hidden" name="active_tab" value="requests">';
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
    <div class="card mb-4" id="incomingRescheduleSection">
        <div class="card-header bg-warning text-dark">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-arrow-right me-2"></i> Incoming Reschedule Requests
                </div>
                <?php 
                // Count pending reschedule requests
                $pending_reschedule_count = count(get_posts(array(
                    'post_type'      => 'progress_report',
                    'posts_per_page' => -1,
                    'meta_query'     => array(
                        'relation' => 'AND',
                        array('key' => 'student_id', 'value' => get_current_user_id(), 'compare' => '='),
                        array('key' => 'request_type', 'value' => 'tutor_reschedule', 'compare' => '='),
                        array('key' => 'status', 'value' => 'pending', 'compare' => '=')
                    ),
                    'fields'         => 'ids'
                )));
                
                if ($pending_reschedule_count > 0): 
                ?>
                <span class="badge bg-danger"><?php echo $pending_reschedule_count; ?></span>
                <?php endif; ?>
            </div>
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
                    
                    // Check all possible field names for new date/time
                    $new_date = get_post_meta($request_id, 'new_date', true);
                    if (empty($new_date)) {
                        $new_date = get_post_meta($request_id, 'proposed_date', true);
                    }
                    
                    $new_time = get_post_meta($request_id, 'new_time', true);
                    if (empty($new_time)) {
                        $new_time = get_post_meta($request_id, 'proposed_time', true);
                    }
                    
                    // Try to get proposed time from a different structure
                    if (empty($new_date) || empty($new_time)) {
                        $proposed_time = get_post_meta($request_id, 'proposed_time_slot', true);
                        if (!empty($proposed_time) && is_array($proposed_time)) {
                            if (isset($proposed_time['date'])) {
                                $new_date = $proposed_time['date'];
                            }
                            if (isset($proposed_time['time'])) {
                                $new_time = $proposed_time['time'];
                            }
                        }
                    }
                    
                    // Check for alternative times array
                    if (empty($new_date) || empty($new_time)) {
                        $alternatives = get_post_meta($request_id, 'alternatives', true);
                        if (!empty($alternatives) && is_array($alternatives) && isset($alternatives[0])) {
                            if (isset($alternatives[0]['date'])) {
                                $new_date = $alternatives[0]['date'];
                            }
                            if (isset($alternatives[0]['time'])) {
                                $new_time = $alternatives[0]['time'];
                            }
                        }
                    }
                    
                    $request_date = get_the_date('M j, Y', $request_id);
                    
                    // Format dates for display - add debugging info if both are empty
                    $formatted_original = !empty($original_date) ? date('M j, Y', strtotime($original_date)) . ' at ' . date('g:i A', strtotime($original_time)) : 'N/A';
                    
                    // Format new date and add debug info if needed
                    if (!empty($new_date) && !empty($new_time)) {
                        $formatted_new = date('M j, Y', strtotime($new_date)) . ' at ' . date('g:i A', strtotime($new_time));
                    } else {
                        // For debugging - dump the post meta to help identify where the data might be stored
                        $all_meta = get_post_meta($request_id);
                        $formatted_new = 'N/A';
                        
                        // Try to find the proposed time in any field
                        foreach ($all_meta as $meta_key => $meta_value) {
                            // Look for keys that might contain date/time information
                            if (strpos($meta_key, 'date') !== false || 
                                strpos($meta_key, 'time') !== false || 
                                strpos($meta_key, 'proposed') !== false || 
                                strpos($meta_key, 'new') !== false) {
                                
                                $value = maybe_unserialize($meta_value[0]);
                                
                                // If we found a serialized array with date/time
                                if (is_array($value) && 
                                    (isset($value['date']) || isset($value['time']) || 
                                     isset($value[0]['date']) || isset($value[0]['time']))) {
                                    
                                    if (isset($value['date']) && isset($value['time'])) {
                                        $new_date = $value['date'];
                                        $new_time = $value['time'];
                                        $formatted_new = date('M j, Y', strtotime($new_date)) . ' at ' . date('g:i A', strtotime($new_time));
                                        break;
                                    } else if (isset($value[0]['date']) && isset($value[0]['time'])) {
                                        $new_date = $value[0]['date'];
                                        $new_time = $value[0]['time'];
                                        $formatted_new = date('M j, Y', strtotime($new_date)) . ' at ' . date('g:i A', strtotime($new_time));
                                        break;
                                    }
                                }
                                // Check for simple date/time strings
                                else if (is_string($value) && strtotime($value) !== false) {
                                    // If it's a date field
                                    if (strpos($meta_key, 'date') !== false && empty($new_date)) {
                                        $new_date = $value;
                                    }
                                    // If it's a time field
                                    else if (strpos($meta_key, 'time') !== false && empty($new_time)) {
                                        $new_time = $value;
                                    }
                                    
                                    // If we have both date and time now, format them
                                    if (!empty($new_date) && !empty($new_time)) {
                                        $formatted_new = date('M j, Y', strtotime($new_date)) . ' at ' . date('g:i A', strtotime($new_time));
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    
                    echo '<tr>';
                    echo '<td>' . esc_html($request_date) . '</td>';
                    echo '<td>' . esc_html($formatted_original) . '</td>';
                    echo '<td>' . esc_html($formatted_new) . '</td>';
                    echo '<td>' . esc_html($tutor_name) . '</td>';
                    echo '<td>';
                    echo '<form method="post" class="d-inline">';
                    echo '<input type="hidden" name="confirm_reschedule" value="1">';
                    echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                    echo '<input type="hidden" name="active_tab" value="requests-tab">';
                    echo '<button type="submit" class="btn btn-sm btn-success me-1">Accept</button>';
                    echo '</form>';
                    
                    echo '<form method="post" class="d-inline">
                        <input type="hidden" name="mark_unavailable" value="1">
                        <input type="hidden" name="request_id" value="' . $request_id . '">
                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                data-bs-target="#unavailableModal" 
                                data-request-id="' . $request_id . '"
                                data-tutor-name="' . esc_attr($tutor_name) . '"
                                data-original-date="' . esc_attr($original_date) . '"
                                data-original-time="' . esc_attr($original_time) . '">
                            Unavailable
                        </button>
                    </form>';
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
    
    <!-- Tutor Alternative Times - with notification badge -->
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
        echo '<div class="d-flex justify-content-between align-items-center">';
        echo '<div><i class="fas fa-calendar-alt me-2"></i> Tutor Alternative Times</div>';
        
        // Add notification badge for pending alternatives
        $pending_alternatives = count(get_posts(array(
            'post_type'      => 'progress_report',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array('key' => 'student_id', 'value' => get_current_user_id(), 'compare' => '='),
                array('key' => 'request_type', 'value' => 'tutor_unavailable', 'compare' => '='),
                array('key' => 'status', 'value' => 'pending', 'compare' => '=')
            ),
            'fields'         => 'ids'
        )));
        
        if ($pending_alternatives > 0) {
            echo '<span class="badge bg-danger">' . $pending_alternatives . '</span>';
        }
        
        echo '</div>'; // End d-flex
        echo '</div>'; // End card-header
        echo '<div class="card-body">';
        
        // Display a highlighted message if there are pending alternatives
        if ($pending_alternatives > 0) {
            echo '<div class="alert alert-warning mb-3">';
            echo '<i class="fas fa-exclamation-circle me-2"></i> You have <strong>' . $pending_alternatives . '</strong> pending alternative time suggestion';
            echo $pending_alternatives > 1 ? 's' : '';
            echo ' that require your response.';
            echo '</div>';
        }
        
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
                $status_badge = '<span class="badge bg-success custom-badge">Confirmed</span>';
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

<!-- Add the Unavailable Modal -->
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
                <p>You've indicated you're unavailable for the proposed time. Please provide alternative times that would work for you.</p>
                <p><strong>Tutor:</strong> <span id="unavailable_tutor_name"></span></p>
                <p><strong>Original Time:</strong> <span id="unavailable_original_time"></span></p>
                
                <form id="unavailableForm" method="post">
                    <input type="hidden" name="mark_unavailable" value="1">
                    <input type="hidden" name="request_id" id="unavailable_request_id" value="">
                    
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add fixed height and scrollbars to the request tables
        const requestTablesContainers = [
            document.querySelector('.card-body .table-responsive'), // Outgoing Reschedule Requests
            document.querySelectorAll('.card-body .table-responsive')[1], // Incoming Reschedule Requests
            document.querySelector('#alternativeTimesSection .card-body .accordion') // Tutor Alternative Times
        ];
        
        // Apply styling to each container if it exists
        requestTablesContainers.forEach(container => {
            if (container) {
                container.style.maxHeight = '300px';
                container.style.overflowY = 'auto';
                container.style.overflowX = 'hidden';
                container.style.padding = '5px';
                container.style.border = '1px solid #dee2e6';
                container.style.borderRadius = '5px';
                
                // Add shadow to scrollbar container for better visual separation
                container.style.boxShadow = 'inset 0 0 5px rgba(0,0,0,0.1)';
            }
        });
        
        // Apply better scrollbar styling for WebKit browsers (Chrome, Safari, Edge)
        const style = document.createElement('style');
        style.textContent = `
            .table-responsive::-webkit-scrollbar, .accordion::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }
            .table-responsive::-webkit-scrollbar-track, .accordion::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 4px;
            }
            .table-responsive::-webkit-scrollbar-thumb, .accordion::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 4px;
            }
            .table-responsive::-webkit-scrollbar-thumb:hover, .accordion::-webkit-scrollbar-thumb:hover {
                background: #555;
            }
        `;
        document.head.appendChild(style);

        // Enhanced function to check incoming requests and update notification badges
        function checkIncomingRequests() {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            // Update the main Requests tab badge
                            updateRequestsTabBadge(response.data.count);
                            
                            // If we're not currently on the requests tab, no need to update the content
                            if (!document.getElementById('requests').classList.contains('active')) {
                                return;
                            }
                            
                            // Update the notifications section with fresh content
                            const notificationsSection = document.getElementById('requestNotifications');
                            if (notificationsSection && response.data.notificationsHtml) {
                                notificationsSection.innerHTML = response.data.notificationsHtml;
                            }
                            
                            // Update the incoming requests section
                            const incomingSection = document.querySelector('#incomingRescheduleSection .card-body');
                            if (incomingSection && response.data.incomingHtml) {
                                incomingSection.innerHTML = response.data.incomingHtml;
                            }
                            
                            // Update the badge on the incoming section header
                            const incomingBadge = document.querySelector('#incomingRescheduleSection .card-header .badge');
                            if (response.data.pendingRescheduleCount > 0) {
                                if (incomingBadge) {
                                    incomingBadge.textContent = response.data.pendingRescheduleCount;
                                } else {
                                    const headerDiv = document.querySelector('#incomingRescheduleSection .card-header .d-flex');
                                    if (headerDiv) {
                                        const badge = document.createElement('span');
                                        badge.className = 'badge bg-danger';
                                        badge.textContent = response.data.pendingRescheduleCount;
                                        headerDiv.appendChild(badge);
                                    }
                                }
                            } else if (incomingBadge) {
                                incomingBadge.style.display = 'none';
                            }
                            
                            // Update the alternatives section badge if it exists
                            const alternativesBadge = document.querySelector('#alternativeTimesSection .card-header .badge');
                            if (alternativesBadge && response.data.pendingAlternativesCount) {
                                alternativesBadge.textContent = response.data.pendingAlternativesCount;
                                alternativesBadge.style.display = response.data.pendingAlternativesCount > 0 ? 'inline-block' : 'none';
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing JSON response:', e);
                    }
                }
            };
            
            // Create a nonce for security
            const nonce = document.getElementById('check_student_requests_nonce')?.value || '';
            const studentId = <?php echo get_current_user_id(); ?>;
            
            xhr.send('action=check_incoming_reschedule_requests&nonce=' + nonce + '&student_id=' + studentId);
        }

        // Function to update the badge on the Requests tab
        function updateRequestsTabBadge(count) {
            const requestsTab = document.getElementById('-tab');
            if (!requestsTab) return;
            
            // Find or create the badge element
            let badge = requestsTab.querySelector('.badge');
            
            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'badge rounded-pill bg-danger ms-1';
                    requestsTab.appendChild(badge);
                }
                
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else if (badge) {
                badge.style.display = 'none';
            }
        }
        
        // Add click handler to mark notifications as read when tab is clicked
        const requestsTab = document.getElementById('requests-tab');
        if (requestsTab) {
            requestsTab.addEventListener('click', function() {
                const badge = this.querySelector('.badge');
                if (badge) {
                    badge.style.display = 'none';
                }
                
                // Mark notifications as read via AJAX
                const xhr = new XMLHttpRequest();
                xhr.open('POST', ajaxurl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                const nonce = document.getElementById('mark_student_requests_read_nonce')?.value || '';
                const studentId = <?php echo get_current_user_id(); ?>;
                
                xhr.send('action=mark_student_requests_read&nonce=' + nonce + '&student_id=' + studentId);
            });
        }
        
        // Check for notifications on page load
        checkIncomingRequests();
        
        // Check periodically (every 30 seconds)
        setInterval(checkIncomingRequests, 30000);
    });

    document.getElementById('submitStudentReschedule').addEventListener('click', function(e) {
        e.preventDefault(); // Prevent the default button action
        
        // Check if button is already disabled (preventing duplicate submissions)
        if (this.disabled) {
            return;
        }
        
        const preferredDates = document.querySelectorAll('.preferred-date');
        const preferredTimes = document.querySelectorAll('.preferred-time');
        let hasPreferredTime = false;

        // Check if at least one preferred date and time is provided
        for (let i = 0; i < preferredDates.length; i++) {
            if (preferredDates[i].value && preferredTimes[i].value) {
                hasPreferredTime = true;
                break;
            }
        }

        if (!hasPreferredTime) {
            document.getElementById('preferred-times-error').style.display = 'block';
            return; // Prevent form submission
        }
        
        document.getElementById('preferred-times-error').style.display = 'none';
        
        // Use AJAX to submit the form
        const form = document.getElementById('rescheduleRequestForm');
        const formData = new FormData(form);
        
        // Add a unique submission ID to prevent duplicate processing on the server
        const submissionId = Date.now().toString();
        formData.append('submission_id', submissionId);
        
        // Disable submit button to prevent multiple submissions
        const submitButton = this;
        submitButton.disabled = true;
        submitButton.innerHTML = 'Submitting...';
        
        // Disable other form elements to prevent changes during submission
        const formElements = form.querySelectorAll('input, select, textarea');
        formElements.forEach(el => el.disabled = true);
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Show success message
                document.getElementById('rescheduleRequestSuccessMessage').style.display = 'block';
                document.getElementById('rescheduleRequestErrorMessage').style.display = 'none';
                
                // Change buttons after successful submission
                const footerButtons = document.querySelector('#rescheduleRequestForm .modal-footer');
                footerButtons.innerHTML = `
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                `;
                
                // Refresh page after a delay
                setTimeout(function() {
                    location.reload();
                }, 2000); // 2 seconds delay
            } else {
                // Show error message if something went wrong
                document.getElementById('rescheduleRequestErrorMessage').style.display = 'block';
                document.getElementById('rescheduleRequestErrorMessage').querySelector('p').textContent = 
                    'There was an error submitting your request. Please try again.';
                document.getElementById('rescheduleRequestSuccessMessage').style.display = 'none';
                
                // Re-enable form elements
                formElements.forEach(el => el.disabled = false);
                
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.innerHTML = 'Submit Request';
            }
        };
        
        // Handle network errors
        xhr.onerror = function() {
            document.getElementById('rescheduleRequestErrorMessage').style.display = 'block';
            document.getElementById('rescheduleRequestErrorMessage').querySelector('p').textContent = 
                'Network error. Please check your connection and try again.';
            document.getElementById('rescheduleRequestSuccessMessage').style.display = 'none';
            
            // Re-enable form elements
            formElements.forEach(el => el.disabled = false);
            
            // Re-enable submit button
            submitButton.disabled = false;
            submitButton.innerHTML = 'Submit Request';
        };
        
        // Set a timeout in case the request takes too long
        const timeoutId = setTimeout(function() {
            xhr.abort();
            document.getElementById('rescheduleRequestErrorMessage').style.display = 'block';
            document.getElementById('rescheduleRequestErrorMessage').querySelector('p').textContent = 
                'Request timed out. Please try again.';
            document.getElementById('rescheduleRequestSuccessMessage').style.display = 'none';
            
            // Re-enable form elements
            formElements.forEach(el => el.disabled = false);
            
            // Re-enable submit button
            submitButton.disabled = false;
            submitButton.innerHTML = 'Submit Request';
        }, 30000); // 30 seconds timeout
        
        // Clear timeout if request completes
        xhr.onloadend = function() {
            clearTimeout(timeoutId);
        };
        
        // Send the form data
        xhr.send(formData);
    });

    // Process the lesson_select to populate the hidden date/time fields
    document.getElementById('lesson_select').addEventListener('change', function() {
        const selectedValue = this.value;
        if (selectedValue) {
            const [date, time] = selectedValue.split('|');
            document.getElementById('original_date').value = date;
            document.getElementById('original_time').value = time;
        } else {
            document.getElementById('original_date').value = '';
            document.getElementById('original_time').value = '';
        }
    });

    // Prevent the modal from closing on form submission by default
    const newRescheduleRequestModal = document.getElementById('newRescheduleRequestModal');
    if (newRescheduleRequestModal) {
        // Prevent modal from closing on submit button click
        newRescheduleRequestModal.addEventListener('mousedown', function(e) {
            if (e.target.type === 'submit' || e.target.type === 'button') {
                e.preventDefault();
            }
        });
        
        // Stop form submission events from bubbling
        const modalForm = document.getElementById('rescheduleRequestForm');
        if (modalForm) {
            modalForm.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();
            });
        }
        
        // Reset form when modal is closed
        newRescheduleRequestModal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('rescheduleRequestForm');
            form.reset();
            
            // Reset all error/success messages
            document.getElementById('rescheduleRequestSuccessMessage').style.display = 'none';
            document.getElementById('rescheduleRequestErrorMessage').style.display = 'none';
            document.getElementById('preferred-times-error').style.display = 'none';
            
            // Re-enable submit button
            const submitButton = document.getElementById('submitStudentReschedule');
            submitButton.disabled = false;
            submitButton.innerHTML = 'Submit Request';
            
            // Re-enable other form elements
            const formElements = form.querySelectorAll('input, select, textarea');
            formElements.forEach(el => el.disabled = false);
        });
    }

    // Add validation for edit form
    document.getElementById('updateStudentReschedule').addEventListener('click', function(e) {
        e.preventDefault(); // Prevent default form submission
        
        const editPreferredDates = document.querySelectorAll('#edit-preferred-times-container .preferred-date');
        const editPreferredTimes = document.querySelectorAll('#edit-preferred-times-container .preferred-time');
        let hasPreferredTime = false;

        // Check if at least one preferred date and time is provided
        for (let i = 0; i < editPreferredDates.length; i++) {
            if (editPreferredDates[i].value && editPreferredTimes[i].value) {
                hasPreferredTime = true;
                break;
            }
        }

        if (!hasPreferredTime) {
            alert('Please provide at least one preferred alternative time.');
            return; // Prevent form submission
        }

        // Get the form data
        const form = document.getElementById('editRescheduleRequestForm');
        const formData = new FormData(form);
        
        // Use AJAX to submit the form
        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        
        // Define what happens on successful data submission
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Check if the update was actually successful by looking for indicators in the response
                if (xhr.responseText.includes('update_student_reschedule_request=1')) {
                    // Show success message
                    document.getElementById('editRescheduleSuccessMessage').style.display = 'block';
                    
                    // Change buttons after successful submission
                    const footerButtons = document.querySelector('#editRescheduleRequestForm .modal-footer');
                    footerButtons.innerHTML = `
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="editAgainButton">Edit Again</button>
                    `;
                    
                    // Add event listener to the Edit Again button
                    document.getElementById('editAgainButton').addEventListener('click', function() {
                        // Hide success message
                        document.getElementById('editRescheduleSuccessMessage').style.display = 'none';
                        
                        // Restore original buttons
                        footerButtons.innerHTML = `
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="updateStudentReschedule">Update Request</button>
                        `;
                        
                        // Re-add the event listener to the Update Request button
                        const updateButton = document.getElementById('updateStudentReschedule');
                        if (updateButton) {
                            updateButton.addEventListener('click', arguments.callee.caller);
                        }
                    });
                    
                    // Don't refresh the page automatically - let the user decide
                    // Instead, update the view without reloading
                    const requestId = form.querySelector('#edit_request_id').value;
                    
                    // We could update DOM elements here if needed
                    // For simplicity, we'll let the user manually close the modal
                }
            }
        };
        
        // Send the form data
        xhr.send(formData);
    });

    // Prevent Bootstrap from automatically closing the modal on form submission
    const editModal = document.getElementById('editRescheduleRequestModal');
    if (editModal) {
        editModal.addEventListener('click', function(e) {
            if (e.target.type === 'submit') {
                e.preventDefault(); // Prevent the default action
            }
        });
        
        // When the modal is shown, ensure buttons are in the correct state
        editModal.addEventListener('shown.bs.modal', function() {
            // Hide any previous success message
            document.getElementById('editRescheduleSuccessMessage').style.display = 'none';
            
            // Ensure the form is in edit mode
            const footerButtons = document.querySelector('#editRescheduleRequestForm .modal-footer');
            if (footerButtons) {
                footerButtons.innerHTML = `
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="updateStudentReschedule">Update Request</button>
                `;
                
                // Add the event listener to the button
                const updateButton = document.getElementById('updateStudentReschedule');
                if (updateButton) {
                    // Remove existing event listeners to prevent duplicates
                    const newUpdateButton = updateButton.cloneNode(true);
                    updateButton.parentNode.replaceChild(newUpdateButton, updateButton);
                    
                    // Add the event listener again
                    newUpdateButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        const editPreferredDates = document.querySelectorAll('#edit-preferred-times-container .preferred-date');
                        const editPreferredTimes = document.querySelectorAll('#edit-preferred-times-container .preferred-time');
                        let hasPreferredTime = false;

                        for (let i = 0; i < editPreferredDates.length; i++) {
                            if (editPreferredDates[i].value && editPreferredTimes[i].value) {
                                hasPreferredTime = true;
                                break;
                            }
                        }

                        if (!hasPreferredTime) {
                            alert('Please provide at least one preferred alternative time.');
                            return;
                        }

                        const form = document.getElementById('editRescheduleRequestForm');
                        const formData = new FormData(form);
                        
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', window.location.href, true);
                        xhr.onload = function() {
                            if (xhr.status === 200) {
                                // Show success message
                                document.getElementById('editRescheduleSuccessMessage').style.display = 'block';
                                
                                // Change buttons
                                const footerButtons = document.querySelector('#editRescheduleRequestForm .modal-footer');
                                footerButtons.innerHTML = `
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" id="editAgainButton">Edit Again</button>
                                `;
                                
                                // Add listener to Edit Again button
                                document.getElementById('editAgainButton').addEventListener('click', function() {
                                    document.getElementById('editRescheduleSuccessMessage').style.display = 'none';
                                    
                                    footerButtons.innerHTML = `
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-primary" id="updateStudentReschedule">Update Request</button>
                                    `;
                                    
                                    // We need to reattach the event listener
                                    const newUpdateBtn = document.getElementById('updateStudentReschedule');
                                    if (newUpdateBtn) {
                                        newUpdateBtn.addEventListener('click', arguments.callee.caller);
                                    }
                                });
                            }
                        };
                        xhr.send(formData);
                    });
                }
            }
        });
    }

    const unavailableModal = document.getElementById('unavailableModal');
    if (unavailableModal) {
        unavailableModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const requestId = button.getAttribute('data-request-id');
            const tutorName = button.getAttribute('data-tutor-name');
            const originalDate = button.getAttribute('data-original-date');
            const originalTime = button.getAttribute('data-original-time');
            
            document.getElementById('unavailable_request_id').value = requestId;
            document.getElementById('unavailable_tutor_name').textContent = tutorName;
            
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
<!-- Add hidden fields with nonces for AJAX security -->
<input type="hidden" id="check_student_requests_nonce" value="<?php echo wp_create_nonce('check_student_requests_nonce'); ?>">
<input type="hidden" id="mark_student_requests_read_nonce" value="<?php echo wp_create_nonce('mark_student_requests_read_nonce'); ?>">

<style>
    /* Style for notification badge */
    .notification-badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.4rem;
        border-radius: 50%;
        position: relative;
        top: -5px;
    }
    
    /* Add animation for new notifications */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .card-header .badge {
        animation: pulse 1.5s infinite;
    }
    
    /* Style for alerts in the notification section */
    #requestNotifications .alert {
        border-left: 4px solid #17a2b8;
    }
    
    /* Improve button styling in notification alerts */
    #requestNotifications .btn {
        font-size: 0.75rem;
        padding: 0.15rem 0.5rem;
    }
    
    /* Add some emphasis to the notification count numbers */
    #requestNotifications strong {
        color: #dc3545;
    }
</style>

