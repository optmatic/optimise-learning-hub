    
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
                                <input type="hidden" name="student_id" value="<?php echo $current_user_id; ?>">
                                <input type="hidden" name="student_name" value="<?php echo wp_get_current_user()->user_login; ?>">
                                
                                <div class="mb-3">
                                    <label for="tutor_select" class="form-label">Select Tutor <span class="text-danger">*</span></label>
                                    <?php
                                    // Get student's assigned tutors
                                    $tutors = [];
                                    
                                    // Query tutors
                                    $tutor_query = new WP_User_Query([
                                        'role' => 'tutor',
                                        'fields' => ['ID', 'user_login', 'display_name']
                                    ]);
                                    
                                    // Check if current student is assigned to each tutor
                                    foreach ($tutor_query->get_results() as $tutor) {
                                        $assigned_students = get_user_meta($tutor->ID, 'assigned_students', true);
                                        if (!empty($assigned_students)) {
                                            $student_ids = explode(',', $assigned_students);
                                            if (in_array($current_user_id, $student_ids)) {
                                                $first_name = get_user_meta($tutor->ID, 'first_name', true);
                                                $last_name = get_user_meta($tutor->ID, 'last_name', true);
                                                $display_name = (!empty($first_name) && !empty($last_name)) 
                                                    ? $first_name . ' ' . $last_name 
                                                    : $tutor->display_name;
                                                
                                                $tutors[] = [
                                                    'id' => $tutor->ID,
                                                    'username' => $tutor->user_login,
                                                    'display_name' => $display_name
                                                ];
                                            }
                                        }
                                    }
                                    
                                    if (!empty($tutors)) {
                                        echo '<select name="tutor_name" id="tutor_select" class="form-select" required>';
                                        echo '<option value="">--Select tutor--</option>';
                                        foreach ($tutors as $tutor) {
                                            echo '<option value="' . esc_attr($tutor['username']) . '" data-tutor-id="' . esc_attr($tutor['id']) . '">' 
                                                . esc_html($tutor['display_name']) . '</option>';
                                        }
                                        echo '</select>';
                                        echo '<input type="hidden" name="tutor_id" id="tutor_id_input">';
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
                                        // Get current date
                                        $now = new DateTime('now', new DateTimeZone('Australia/Brisbane'));
                                        
                                        // Get lesson schedule
                                        $lesson_schedule = get_user_meta($current_user_id, 'lesson_schedule_list', true);
                                        
                                        if (!empty($lesson_schedule)) {
                                            $lessons = explode("\n", $lesson_schedule);
                                            $upcoming_lessons = [];
                                            
                                            // Extract future lessons
                                            foreach ($lessons as $lesson) {
                                                if (!empty(trim($lesson)) && preg_match('/on ([A-Za-z]+) (\d+) ([A-Za-z]+) (\d{4}) at (\d{2}:\d{2})/', $lesson, $matches)) {
                                                    $date_string = $matches[1] . ' ' . $matches[2] . ' ' . $matches[3] . ' ' . $matches[4] . ' ' . $matches[5];
                                                    $lesson_date = DateTime::createFromFormat('l d F Y H:i', $date_string, new DateTimeZone('Australia/Brisbane'));
                                                    
                                                    if ($lesson_date > $now) {
                                                        // Determine subject
                                                        $subject = 'Lesson';
                                                        if (stripos($lesson, 'mathematics') !== false) $subject = 'Mathematics';
                                                        elseif (stripos($lesson, 'english') !== false) $subject = 'English';
                                                        elseif (stripos($lesson, 'chemistry') !== false) $subject = 'Chemistry';
                                                        elseif (stripos($lesson, 'physics') !== false) $subject = 'Physics';
                                                        
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
                                            
                                            // Sort lessons by date
                                            usort($upcoming_lessons, function($a, $b) {
                                                return $a['date']->getTimestamp() - $b['date']->getTimestamp();
                                            });
                                            
                                            // Output options
                                            foreach ($upcoming_lessons as $lesson) {
                                                echo '<option value="' . $lesson['date_value'] . '|' . $lesson['time_value'] . '">' 
                                                    . $lesson['subject'] . ' - ' . $lesson['formatted'] . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                    
                                    <!-- Hidden fields for date/time -->
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
                                        <?php render_preferred_time_inputs(); ?>
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
                            <?php render_preferred_time_inputs('edit_', 3, false); // Don't require the first input in the edit modal ?>
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
