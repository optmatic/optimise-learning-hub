<?php
/**
 * Tutor Reschedule Request Component (POST Method)
 * 
 * Provides the UI and form for tutors to request lesson reschedules using standard POST.
 */

// Helper functions (adapted from students/requests/request-reschedule.php)

/**
 * Formats a date and time string.
 *
 * @param string $date   Date string (e.g., 'Y-m-d').
 * @param string $time   Time string (e.g., 'H:i:s').
 * @param string $format PHP date format string.
 * @return string Formatted date/time or 'N/A'.
 */
function format_datetime($date, $time, $format = 'M j, Y \a\t g:i A') {
    if (empty($date) || empty($time)) {
        return 'N/A';
    }
    try {
        $datetime = new DateTime($date . ' ' . $time);
        return $datetime->format($format);
    } catch (Exception $e) {
        // Log error or handle appropriately
        error_log("Error formatting date/time: " . $e->getMessage());
        return 'Invalid Date/Time';
    }
}

/**
 * Gets the display name for a student based on username.
 * Prefers First Last, falls back to display_name.
 *
 * @param string $student_username The student's username.
 * @return string The student's display name.
 */
function get_student_display_name($student_username) {
    if (empty($student_username)) {
        return 'N/A';
    }
    $student_user = get_user_by('login', $student_username);
    if ($student_user) {
        $first_name = get_user_meta($student_user->ID, 'first_name', true);
        $last_name = get_user_meta($student_user->ID, 'last_name', true);
        
        if (!empty($first_name) && !empty($last_name)) {
            return esc_html($first_name . ' ' . $last_name);
        }
        return esc_html($student_user->display_name);
    }
    return esc_html($student_username); // Fallback to username if user not found
}

/**
 * Gets a list of students assigned to the current tutor.
 *
 * @return array List of students with id, username, and display_name.
 */
function get_tutor_students() {
    $current_user_id = get_current_user_id();
    $students = [];

    // Primary method: Check 'assigned_students' meta field
    $assigned_student_ids = get_user_meta($current_user_id, 'assigned_students', true);

    if (!empty($assigned_student_ids)) {
        // Handle both comma-separated string and array formats
        if (is_string($assigned_student_ids)) {
            $student_ids = array_map('intval', array_map('trim', explode(',', $assigned_student_ids)));
        } elseif (is_array($assigned_student_ids)) {
            $student_ids = array_map('intval', $assigned_student_ids);
        } else {
            $student_ids = []; // Unexpected format
        }

        foreach ($student_ids as $student_id) {
            if ($student_id > 0) {
                $student = get_user_by('id', $student_id);
                if ($student) {
                    $first_name = get_user_meta($student->ID, 'first_name', true);
                    $last_name = get_user_meta($student->ID, 'last_name', true);
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
        }
    } else {
        // Fallback: Check students who have this tutor in 'assigned_tutors'
        $student_query = new WP_User_Query([
            'role' => 'student',
            'fields' => ['ID', 'user_login', 'display_name']
        ]);
        
        if (!empty($student_query->get_results())) {
            foreach ($student_query->get_results() as $student) {
                $assigned_tutors = get_user_meta($student->ID, 'assigned_tutors', true);
                $is_assigned = false;

                // Handle different formats for assigned_tutors
                if (is_string($assigned_tutors)) {
                    $tutor_ids = array_map('trim', explode(',', $assigned_tutors));
                    $is_assigned = in_array((string)$current_user_id, $tutor_ids, true);
                } elseif (is_array($assigned_tutors)) {
                    $is_assigned = in_array($current_user_id, $assigned_tutors, true) || in_array((string)$current_user_id, $assigned_tutors, true);
                } elseif ($assigned_tutors == $current_user_id) { // Direct ID match
                    $is_assigned = true;
                }

                if ($is_assigned) {
                    $first_name = get_user_meta($student->ID, 'first_name', true);
                    $last_name = get_user_meta($student->ID, 'last_name', true);
                    $display_name = (!empty($first_name) && !empty($last_name)) 
                                    ? $first_name . ' ' . $last_name 
                                    : $student->display_name;
                                    
                    // Avoid duplicates if found via both methods
                    if (!in_array($student->ID, array_column($students, 'id'))) {
                         $students[] = [
                            'id' => $student->ID,
                            'username' => $student->user_login,
                            'display_name' => $display_name
                        ];
                    }
                }
            }
        }
    }
    
    // Remove duplicates just in case (based on ID)
    $students = array_values(array_map("unserialize", array_unique(array_map("serialize", $students))));

    // Sort students by display name
    usort($students, function($a, $b) {
        return strcmp($a['display_name'], $b['display_name']);
    });

    return $students;
}

// Note: The actual form submission processing (creating the post meta etc.)
// is likely handled via AJAX in functions.php (handle_tutor_reschedule_ajax)
// as hinted in the student file. This file focuses on rendering the form.

?>

<!-- Request Reschedule Section -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        Request Lesson Reschedule
    </div>
    <div class="card-body">
        <p>Use this form to request a reschedule for an upcoming lesson.</p>
        
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#tutorNewRescheduleRequestModal">
            <i class="fas fa-calendar-plus me-2"></i>Request Lesson Reschedule
        </button>
        
        <!-- Modal for creating a new reschedule request -->
        <div class="modal fade" id="tutorNewRescheduleRequestModal" tabindex="-1" aria-labelledby="tutorNewRescheduleRequestModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tutorNewRescheduleRequestModalLabel">Request Lesson Reschedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        
                        <!-- Feedback messages will be shown at the top of the main dashboard page after reload -->

                        <form id="tutorRescheduleRequestForm" method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                            <?php // Nonce for the POST handler ?>
                            <?php wp_nonce_field( 'tutor_reschedule_request_post_action', 'tutor_reschedule_post_nonce' ); ?>
                            
                            <?php // Action hook for admin-post.php handler ?>
                            <input type="hidden" name="action" value="process_tutor_reschedule_post"> 
                            
                            <?php // Hidden fields needed for the handler ?>
                            <input type="hidden" name="tutor_id" value="<?php echo esc_attr(get_current_user_id()); ?>">
                            <input type="hidden" name="tutor_name" value="<?php echo esc_attr(wp_get_current_user()->user_login); ?>">
                            <input type="hidden" name="student_id" id="tutor_student_id_hidden"> <!-- Populated by JS -->
                            
                            <div class="mb-3">
                                <label for="tutor_student_select" class="form-label">Select Student <span class="text-danger">*</span></label>
                                <?php
                                // Assumes get_tutor_students() is available
                                $tutor_students = function_exists('get_tutor_students') ? get_tutor_students() : [];
                                
                                if (!empty($tutor_students)) {
                                    echo '<select name="student_name" id="tutor_student_select" class="form-select" required>';
                                    echo '<option value="">-- Select Student --</option>';
                                    foreach ($tutor_students as $student) {
                                        echo '<option value="' . esc_attr($student['username']) . '" data-id="' . esc_attr($student['id']) . '">' 
                                            . esc_html($student['display_name']) . '</option>';
                                    }
                                    echo '</select>';
                                } else {
                                    echo '<div class="alert alert-warning mb-0">No students found.</div>';
                                }
                                ?>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Lesson to Reschedule <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label for="tutor_original_date" class="form-label small visually-hidden">Date</label>
                                        <input type="date" class="form-control" id="tutor_original_date" name="original_date" required title="Original Lesson Date">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tutor_original_time" class="form-label small visually-hidden">Time</label>
                                        <input type="time" class="form-control" id="tutor_original_time" name="original_time" required title="Original Lesson Time">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="tutor_reason" class="form-label">Reason for Reschedule <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="tutor_reason" name="reason" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Preferred Alternative Times <span class="text-danger">*</span></label>
                                <p class="text-muted small mb-2">Provide at least one preferred alternative date and time.</p>
                                
                                <div id="tutor-preferred-times-container">
                                    <?php 
                                    // Use helper if available, otherwise basic loop
                                    if (function_exists('render_preferred_time_inputs')) {
                                        render_preferred_time_inputs('tutor_', 3, true);
                                    } else {
                                        for ($i = 1; $i <= 3; $i++): ?>
                                        <div class="preferred-time-row mb-2">
                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <label class="form-label small visually-hidden">Preferred Date <?php echo $i; ?></label>
                                                    <input type="date" class="form-control preferred-date" name="preferred_date_<?php echo $i; ?>" id="tutor_preferred_date_<?php echo $i; ?>" <?php echo $i == 1 ? 'required' : ''; ?> title="Alternative Date <?php echo $i; ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small visually-hidden">Preferred Time <?php echo $i; ?></label>
                                                    <input type="time" class="form-control preferred-time" name="preferred_time_<?php echo $i; ?>" id="tutor_preferred_time_<?php echo $i; ?>" <?php echo $i == 1 ? 'required' : ''; ?> title="Alternative Time <?php echo $i; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <?php endfor;
                                    } ?>
                                </div>
                            </div>
                            <div id="tutor-preferred-times-error" class="text-danger mt-n2 mb-3" style="display: none; font-size: 0.875em;">
                                Please provide at least one preferred alternative time (Date and Time).
                            </div>
                            
                            <div class="modal-footer">
                                <!-- Optional Dev Autofill Button -->
                                <button type="button" id="tutorDevAutofill" class="btn btn-outline-secondary me-auto" title="Autofill form with sample data (Dev)">Autofill</button>
                                
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <?php // Standard submit button for POST ?>
                                <button type="submit" class="btn btn-primary" id="submitTutorRescheduleBtn">
                                    Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php // Minimal JavaScript needed for non-AJAX form ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Update hidden student ID when selection changes
    const studentSelect = document.getElementById('tutor_student_select');
    const studentIdHidden = document.getElementById('tutor_student_id_hidden');
    if (studentSelect && studentIdHidden) {
        studentSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            studentIdHidden.value = selectedOption.getAttribute('data-id') || '';
        });
        // Set initial value if needed
        if (studentSelect.selectedIndex > 0) {
             studentIdHidden.value = studentSelect.options[studentSelect.selectedIndex].getAttribute('data-id') || '';
        }
    }
    
    // Basic client-side validation for preferred times before POST submission
    const rescheduleForm = document.getElementById('tutorRescheduleRequestForm');
    const preferredTimesErrorDiv = document.getElementById('tutor-preferred-times-error');
    if (rescheduleForm && preferredTimesErrorDiv) {
         rescheduleForm.addEventListener('submit', function(event) {
            const firstPrefDate = document.getElementById('tutor_preferred_date_1');
            const firstPrefTime = document.getElementById('tutor_preferred_time_1');
            if (!firstPrefDate || !firstPrefTime || !firstPrefDate.value || !firstPrefTime.value) {
                preferredTimesErrorDiv.style.display = 'block';
                firstPrefDate.focus(); 
                event.preventDefault(); // Prevent POST submission if validation fails
            } else {
                preferredTimesErrorDiv.style.display = 'none';
            }
        });
    }

    // Dev Autofill Functionality (Optional) - Kept for convenience
    const autofillButton = document.getElementById('tutorDevAutofill');
    if (autofillButton && studentSelect && studentSelect.options.length > 1) {
         autofillButton.addEventListener('click', function() {
             studentSelect.selectedIndex = 1; 
             studentSelect.dispatchEvent(new Event('change'));
             document.getElementById('tutor_original_date').value = '<?php echo date('Y-m-d', strtotime('+3 days')); ?>';
             document.getElementById('tutor_original_time').value = '14:00';
             document.getElementById('tutor_reason').value = 'Dev autofill: Standard POST Test.';
             document.getElementById('tutor_preferred_date_1').value = '<?php echo date('Y-m-d', strtotime('+4 days')); ?>';
             document.getElementById('tutor_preferred_time_1').value = '15:00';
             document.getElementById('tutor_preferred_date_2').value = '';
             document.getElementById('tutor_preferred_time_2').value = '';
             document.getElementById('tutor_preferred_date_3').value = '';
             document.getElementById('tutor_preferred_time_3').value = '';
             if(preferredTimesErrorDiv) preferredTimesErrorDiv.style.display = 'none';
         });
    }
});
</script>
