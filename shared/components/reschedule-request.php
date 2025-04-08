<!-- Reschedule Request Component -->
<?php
function render_reschedule_request_form($user_type = 'student', $current_user_id = null) {
    if (!$current_user_id) {
        $current_user_id = get_current_user_id();
    }
    ?>
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
                                <p>Your reschedule request has been successfully submitted. <?php echo $user_type === 'tutor' ? 'Your student' : 'Your tutor'; ?> will be notified.</p>
                            </div>
                            <div id="rescheduleRequestErrorMessage" class="alert alert-danger" style="display: none;">
                                <p>Please fill in all required fields (<?php echo $user_type === 'tutor' ? 'student' : 'tutor'; ?>, lesson, and reason).</p>
                            </div>
                            <form id="rescheduleRequestForm" method="post">
                                <input type="hidden" name="submit_<?php echo $user_type; ?>_reschedule_request" value="1">
                                <input type="hidden" name="<?php echo $user_type; ?>_id" value="<?php echo $current_user_id; ?>">
                                <input type="hidden" name="active_tab" value="requests">
                                
                                <div class="mb-3">
                                    <label for="<?php echo $user_type === 'tutor' ? 'student' : 'tutor'; ?>_select" class="form-label">
                                        Select <?php echo ucfirst($user_type === 'tutor' ? 'Student' : 'Tutor'); ?> <span class="text-danger">*</span>
                                    </label>
                                    <?php
                                    if ($user_type === 'tutor') {
                                        // Get students assigned to tutor
                                        $students = get_tutor_students($current_user_id);
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
                                    } else {
                                        // Get tutors assigned to student
                                        $tutors = get_student_tutors($current_user_id);
                                        if (!empty($tutors)) {
                                            echo '<select name="tutor_name" id="tutor_select" class="form-select" required>';
                                            echo '<option value="">--Select tutor--</option>';
                                            foreach ($tutors as $tutor) {
                                                echo '<option value="' . esc_attr($tutor['username']) . '">' . esc_html($tutor['display_name']) . '</option>';
                                            }
                                            echo '</select>';
                                        } else {
                                            echo '<div class="alert alert-warning">No tutors assigned to you. Please contact support.</div>';
                                        }
                                    }
                                    ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="lesson_select" class="form-label">Lesson Date to Reschedule <span class="text-danger">*</span></label>
                                    <select class="form-select" id="lesson_select" name="lesson_select" required>
                                        <option value="">--Select a scheduled lesson--</option>
                                        <?php
                                        $upcoming_lessons = get_upcoming_lessons($current_user_id);
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
                                    <button type="button" class="btn btn-primary" id="submitRescheduleRequest">Submit Request</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle user selection to populate hidden field (for tutors only)
        const userSelect = document.getElementById('<?php echo $user_type === "tutor" ? "student" : "tutor"; ?>_select');
        const userNameInput = document.getElementById('<?php echo $user_type === "tutor" ? "student_name" : null; ?>');
        if (userSelect && userNameInput) {
            userSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption) {
                    userNameInput.value = selectedOption.getAttribute('data-username') || '';
                }
            });
        }

        // Handle lesson selection
        const lessonSelect = document.getElementById('lesson_select');
        if (lessonSelect) {
            lessonSelect.addEventListener('change', function() {
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
        }

        // Handle form submission
        const submitBtn = document.getElementById('submitRescheduleRequest');
        const form = document.getElementById('rescheduleRequestForm');
        
        if (submitBtn && form) {
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();

                // Reset error messages
                document.getElementById('rescheduleRequestErrorMessage').style.display = 'none';
                document.getElementById('preferred-times-error').style.display = 'none';

                // Validate form
                const user = document.getElementById('<?php echo $user_type === "tutor" ? "student" : "tutor"; ?>_select').value;
                const lessonDate = document.getElementById('original_date').value;
                const lessonTime = document.getElementById('original_time').value;
                const reason = document.getElementById('reason').value;

                // Check required fields
                if (!user || !lessonDate || !lessonTime || !reason) {
                    document.getElementById('rescheduleRequestErrorMessage').style.display = 'block';
                    return;
                }

                // Validate preferred times
                const preferredDates = document.querySelectorAll('#preferred-times-container .preferred-date');
                const preferredTimes = document.querySelectorAll('#preferred-times-container .preferred-time');
                let hasPreferredTime = false;

                for (let i = 0; i < preferredDates.length; i++) {
                    if (preferredDates[i].value && preferredTimes[i].value) {
                        hasPreferredTime = true;
                        break;
                    }
                }

                if (!hasPreferredTime) {
                    document.getElementById('preferred-times-error').style.display = 'block';
                    return;
                }

                // Disable form elements during submission
                const formElements = form.querySelectorAll('input, select, textarea, button');
                formElements.forEach(el => el.disabled = true);
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';

                // Submit form
                const formData = new FormData(form);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    // Show success message
                    document.getElementById('rescheduleRequestSuccessMessage').style.display = 'block';
                    document.getElementById('rescheduleRequestErrorMessage').style.display = 'none';

                    // Reset form
                    form.reset();

                    // Change modal footer
                    const modalFooter = submitBtn.closest('.modal-footer');
                    modalFooter.innerHTML = `
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    `;

                    // Reload page after 2 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('rescheduleRequestErrorMessage').style.display = 'block';
                    document.getElementById('rescheduleRequestErrorMessage').querySelector('p').textContent = 
                        'An error occurred while submitting your request. Please try again.';
                    
                    // Re-enable form elements
                    formElements.forEach(el => el.disabled = false);
                    submitBtn.innerHTML = 'Submit Request';
                });
            });
        }
    });
    </script>
    <?php
}

// Helper functions
function get_tutor_students($tutor_id) {
    $students = [];
    
    // Check assigned_students meta
    $assigned_students = get_user_meta($tutor_id, 'assigned_students', true);
    if (!empty($assigned_students)) {
        $student_ids = is_array($assigned_students) ? $assigned_students : array_map('trim', explode(',', $assigned_students));
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
    }

    // If no students found, query all students and check assigned_tutors meta
    if (empty($students)) {
        $student_query = new WP_User_Query([
            'role' => 'student',
            'fields' => ['ID', 'user_login', 'display_name']
        ]);

        foreach ($student_query->get_results() as $student) {
            $assigned_tutors = get_user_meta($student->ID, 'assigned_tutors', true);
            $is_assigned = false;

            if (is_string($assigned_tutors)) {
                $tutor_ids = array_map('trim', explode(',', $assigned_tutors));
                $is_assigned = in_array($tutor_id, $tutor_ids) || in_array(strval($tutor_id), $tutor_ids);
            } else if (is_array($assigned_tutors)) {
                $is_assigned = in_array($tutor_id, $assigned_tutors) || in_array(strval($tutor_id), $assigned_tutors);
            } else if ($assigned_tutors == $tutor_id) {
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

    return $students;
}

function get_student_tutors($student_id) {
    $tutors = [];
    
    // Check assigned_tutors meta
    $assigned_tutors = get_user_meta($student_id, 'assigned_tutors', true);
    if (!empty($assigned_tutors)) {
        $tutor_ids = is_array($assigned_tutors) ? $assigned_tutors : array_map('trim', explode(',', $assigned_tutors));
        foreach ($tutor_ids as $tutor_id) {
            $tutor = get_user_by('id', $tutor_id);
            if ($tutor) {
                $first_name = get_user_meta($tutor->ID, 'first_name', true);
                $last_name = get_user_meta($tutor->ID, 'last_name', true);
                
                $tutors[] = [
                    'id' => $tutor->ID,
                    'username' => $tutor->user_login,
                    'display_name' => (!empty($first_name) && !empty($last_name)) 
                        ? $first_name . ' ' . $last_name 
                        : $tutor->display_name
                ];
            }
        }
    }

    // If no tutors found, query all tutors and check assigned_students meta
    if (empty($tutors)) {
        $tutor_query = new WP_User_Query([
            'role' => 'tutor',
            'fields' => ['ID', 'user_login', 'display_name']
        ]);

        foreach ($tutor_query->get_results() as $tutor) {
            $assigned_students = get_user_meta($tutor->ID, 'assigned_students', true);
            $is_assigned = false;

            if (is_string($assigned_students)) {
                $student_ids = array_map('trim', explode(',', $assigned_students));
                $is_assigned = in_array($student_id, $student_ids) || in_array(strval($student_id), $student_ids);
            } else if (is_array($assigned_students)) {
                $is_assigned = in_array($student_id, $assigned_students) || in_array(strval($student_id), $assigned_students);
            } else if ($assigned_students == $student_id) {
                $is_assigned = true;
            }

            if ($is_assigned) {
                $first_name = get_user_meta($tutor->ID, 'first_name', true);
                $last_name = get_user_meta($tutor->ID, 'last_name', true);
                
                $tutors[] = [
                    'id' => $tutor->ID,
                    'username' => $tutor->user_login,
                    'display_name' => (!empty($first_name) && !empty($last_name)) 
                        ? $first_name . ' ' . $last_name 
                        : $tutor->display_name
                ];
            }
        }
    }

    return $tutors;
}

function get_upcoming_lessons($user_id) {
    $now = new DateTime('now', new DateTimeZone('Australia/Brisbane'));
    $lesson_schedule = get_user_meta($user_id, 'lesson_schedule_list', true);
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
?> 