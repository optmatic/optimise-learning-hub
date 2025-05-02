<?php
error_log('[REQUEST FUNCTIONS FILE] requests/request-functions.php starting.'); // TOP LOG
/**
 * Request Helper Functions
 * 
 * Functions for formatting data, querying requests, and other reusable logic related to reschedule requests.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Check if a user ID has permission to access the tutor dashboard.
 *
 * @param int $user_id User ID to check.
 * @return bool True if user can access, false otherwise.
 */
function ol_hub_can_user_access_tutor_dashboard($user_id) {
    if ( ! $user_id ) {
        return false;
    }
    $user = get_userdata($user_id);
    // Check if user exists and has the 'tutor' role.
    // Add any other specific checks if needed.
    return $user && in_array('tutor', (array) $user->roles);
}

/**
 * Format date and time string.
 *
 * @param string $datetime_string Date and time string.
 * @param string $format PHP date format string.
 * @return string Formatted date/time or 'N/A'.
 */
function format_datetime($datetime_string, $format = 'M j, Y \\a\\t g:i A') {
    if (empty($datetime_string)) {
        return 'N/A';
    }
    // Escape characters for WordPress formatting compatibility if needed
    $format = str_replace('a\\t', '\\a\\t', $format); 
    try {
        // Create DateTime object directly from the input string
        $datetime = new DateTime($datetime_string, new DateTimeZone('Australia/Brisbane')); // Assuming AEST/AEDT
        return $datetime->format($format);
    } catch (Exception $e) {
        // Log error or handle gracefully
        error_log("Error formatting datetime string '{$datetime_string}': " . $e->getMessage());
        return 'Invalid Date/Time';
    }
}

/**
 * Get the display name for a student based on user ID.
 *
 * @param int $student_id Student's user ID.
 * @return string Student's display name or 'Unknown Student' if not found.
 */
function get_student_display_name($student_id) {
    if ( empty($student_id) || !is_numeric($student_id) ) {
        return 'Unknown Student';
    }
    $student_user = get_user_by('id', $student_id); // Changed 'login' to 'id'
    if ($student_user) {
        $first_name = get_user_meta($student_user->ID, 'first_name', true);
        $last_name = get_user_meta($student_user->ID, 'last_name', true);
        
        return (!empty($first_name) && !empty($last_name)) 
            ? esc_html($first_name . ' ' . $last_name)
            : esc_html($student_user->display_name);
    }
    return 'Unknown Student (ID: ' . esc_html($student_id) . ')'; // Return ID if user not found
}

/**
 * Get the display name for a tutor based on login name.
 *
 * @param string $tutor_name Tutor's user_login.
 * @return string Tutor's display name or original login name if not found.
 */
function get_tutor_display_name($tutor_name) {
    $tutor_user = get_user_by('login', $tutor_name);
    if ($tutor_user) {
        $first_name = get_user_meta($tutor_user->ID, 'first_name', true);
        $last_name = get_user_meta($tutor_user->ID, 'last_name', true);
        
        return (!empty($first_name) && !empty($last_name)) 
            ? esc_html($first_name . ' ' . $last_name) 
            : esc_html($tutor_user->display_name);
    }
    return esc_html($tutor_name); // Return escaped input if user not found
}


/**
 * Generate an HTML status badge based on the status string.
 *
 * @param string $status The status string (e.g., 'pending', 'confirmed', 'declined').
 * @return string HTML span element for the badge.
 */
function get_status_badge($status) {
    $status = strtolower(trim($status));
    $badge_class = 'bg-secondary'; // Default
    $badge_text = ucfirst($status);

    switch ($status) {
        case 'pending':
            $badge_class = 'bg-warning';
            break;
        case 'confirmed':
        case 'accepted':
             $badge_class = 'bg-success';
             $badge_text = 'Accepted';
            break;
        case 'declined':
        case 'denied':
            $badge_class = 'bg-danger';
            $badge_text = 'Declined';
            break;
        case 'unavailable':
            $badge_class = 'bg-warning';
             // Distinguish tutor/student unavailability if needed
             // Example: Check who initiated the 'unavailable' status if stored
            $badge_text = 'Unavailable'; 
            break;
        case 'student_unavailable': // Example specific status
             $badge_class = 'bg-info'; 
             $badge_text = 'Student Unavailable';
             break;
        case 'tutor_unavailable': // Example specific status
            $badge_class = 'bg-info'; 
            $badge_text = 'Tutor Unavailable';
             break;
    }

    return '<span class="badge ' . esc_attr($badge_class) . '">' . esc_html($badge_text) . '</span>';
}

/**
 * Retrieves a list of students assigned to the current tutor.
 * 
 * Checks multiple user meta fields for flexibility.
 *
 * @return array Array of student objects/arrays with 'id', 'username', 'display_name'.
 */
function get_tutor_students() {
    $current_user_id = get_current_user_id();
    $students = [];
    $added_student_ids = []; // Keep track of added IDs to prevent duplicates

    // 1. Check tutor's 'assigned_students' meta
    $assigned_student_ids = get_user_meta($current_user_id, 'assigned_students', true);
    if (!empty($assigned_student_ids)) {
        $student_ids = is_array($assigned_student_ids) ? $assigned_student_ids : array_map('trim', explode(',', $assigned_student_ids));
        foreach ($student_ids as $student_id) {
            if (is_numeric($student_id) && !in_array($student_id, $added_student_ids)) {
                $student = get_user_by('id', $student_id);
                if ($student && in_array('student', $student->roles)) {
                     $first_name = get_user_meta($student->ID, 'first_name', true);
                     $last_name = get_user_meta($student->ID, 'last_name', true);
                     $students[] = [
                         'id' => $student->ID,
                         'username' => $student->user_login,
                         'display_name' => (!empty($first_name) && !empty($last_name)) ? esc_html($first_name . ' ' . $last_name) : esc_html($student->display_name)
                     ];
                     $added_student_ids[] = $student_id;
                }
            }
        }
    }

    // 2. Check students' 'assigned_tutors' meta
    $student_query = new WP_User_Query([
        'role' => 'student',
        'fields' => ['ID', 'user_login', 'display_name']
    ]);
    $all_students = $student_query->get_results();

    foreach ($all_students as $student) {
         if (!in_array($student->ID, $added_student_ids)) { // Only check if not already added
            $assigned_tutors = get_user_meta($student->ID, 'assigned_tutors', true);
             $tutor_ids = is_array($assigned_tutors) ? $assigned_tutors : array_map('trim', explode(',', $assigned_tutors));
            
             if (in_array($current_user_id, $tutor_ids) || in_array(strval($current_user_id), $tutor_ids)) {
                $first_name = get_user_meta($student->ID, 'first_name', true);
                $last_name = get_user_meta($student->ID, 'last_name', true);
                 $students[] = [
                     'id' => $student->ID,
                     'username' => $student->user_login,
                     'display_name' => (!empty($first_name) && !empty($last_name)) ? esc_html($first_name . ' ' . $last_name) : esc_html($student->display_name)
                 ];
                $added_student_ids[] = $student->ID;
            }
        }
    }

    return $students;
}

/**
 * Retrieves a list of students assigned to a specific tutor ID.
 * Used primarily for admin or other contexts where current user isn't the tutor.
 *
 * @param int $tutor_id The ID of the tutor.
 * @return array Array of student objects/arrays with 'id', 'username', 'display_name'.
 */
function get_students_for_tutor($tutor_id) {
    if (empty($tutor_id) || !is_numeric($tutor_id)) {
        return [];
    }

    $students = [];
    $added_student_ids = []; 

    // 1. Check tutor's 'assigned_students' meta
    $assigned_student_ids = get_user_meta($tutor_id, 'assigned_students', true);
    if (!empty($assigned_student_ids)) {
        $student_ids = is_array($assigned_student_ids) ? $assigned_student_ids : array_map('trim', explode(',', $assigned_student_ids));
        foreach ($student_ids as $student_id) {
            if (is_numeric($student_id) && !in_array($student_id, $added_student_ids)) {
                $student = get_user_by('id', $student_id);
                 if ($student && in_array('student', $student->roles)) {
                     $first_name = get_user_meta($student->ID, 'first_name', true);
                     $last_name = get_user_meta($student->ID, 'last_name', true);
                     $students[] = [
                         'id' => $student->ID,
                         'username' => $student->user_login,
                         'display_name' => (!empty($first_name) && !empty($last_name)) ? esc_html($first_name . ' ' . $last_name) : esc_html($student->display_name)
                     ];
                     $added_student_ids[] = $student_id;
                 }
            }
        }
    }

    // 2. Check students' 'assigned_tutors' meta (less efficient but covers other direction)
    $student_query = new WP_User_Query([
        'role' => 'student',
        'fields' => ['ID', 'user_login', 'display_name']
    ]);
    $all_students = $student_query->get_results();

    foreach ($all_students as $student) {
        if (!in_array($student->ID, $added_student_ids)) { 
            $assigned_tutors = get_user_meta($student->ID, 'assigned_tutors', true);
            $tutor_ids = is_array($assigned_tutors) ? $assigned_tutors : array_map('trim', explode(',', $assigned_tutors));
            
            if (in_array($tutor_id, $tutor_ids) || in_array(strval($tutor_id), $tutor_ids)) {
                 $first_name = get_user_meta($student->ID, 'first_name', true);
                 $last_name = get_user_meta($student->ID, 'last_name', true);
                 $students[] = [
                     'id' => $student->ID,
                     'username' => $student->user_login,
                     'display_name' => (!empty($first_name) && !empty($last_name)) ? esc_html($first_name . ' ' . $last_name) : esc_html($student->display_name)
                 ];
                 $added_student_ids[] = $student->ID;
            }
        }
    }

    return $students;
}

/**
 * Retrieves a list of tutors assigned to a specific student ID.
 * Checks both student's 'assigned_tutors' meta and tutor's 'assigned_students' meta.
 *
 * @param int $student_id The ID of the student.
 * @return array Array of tutor data ['id' => ID, 'user_login' => user_login, 'display_name' => display_name].
 */
function get_tutors_for_student($student_id) {
    if (empty($student_id) || !is_numeric($student_id)) {
        return [];
    }

    $tutor_ids_found = [];

    // 1. Check the student's 'assigned_tutors' meta
    $assigned_tutors_meta = get_user_meta($student_id, 'assigned_tutors', true);
    if (!empty($assigned_tutors_meta)) {
        $tutor_ids_from_student = is_array($assigned_tutors_meta) ? $assigned_tutors_meta : array_map('trim', explode(',', $assigned_tutors_meta));
        foreach ($tutor_ids_from_student as $tutor_id) {
            if (is_numeric($tutor_id) && !in_array($tutor_id, $tutor_ids_found)) {
                $tutor_ids_found[] = intval($tutor_id);
            }
        }
    }

    // 2. Check all tutors' 'assigned_students' meta
    $tutor_query_args = [
        'role' => 'tutor',
        'fields' => ['ID'] // Only need IDs for this check
    ];
    $all_tutor_users = get_users($tutor_query_args);

    foreach ($all_tutor_users as $tutor_user) {
        if (!in_array($tutor_user->ID, $tutor_ids_found)) { // Only check if not already found
            $assigned_students_meta = get_user_meta($tutor_user->ID, 'assigned_students', true);
            if (!empty($assigned_students_meta)) {
                $student_ids_for_tutor = is_array($assigned_students_meta) ? $assigned_students_meta : array_map('trim', explode(',', $assigned_students_meta));
                if (in_array($student_id, $student_ids_for_tutor) || in_array(strval($student_id), $student_ids_for_tutor)) {
                    $tutor_ids_found[] = $tutor_user->ID;
                }
            }
        }
    }

    // 3. Get the final tutor user data
    $tutors_available = [];
    if (!empty($tutor_ids_found)) {
        $final_tutor_query = new WP_User_Query([
            'include' => $tutor_ids_found,
            'role' => 'tutor', // Re-affirm role just in case IDs were added incorrectly elsewhere
            'fields' => ['ID', 'user_login', 'display_name']
        ]);
        $tutor_results = $final_tutor_query->get_results();

        // Format the results consistently
        foreach ($tutor_results as $tutor) {
            $first_name = get_user_meta($tutor->ID, 'first_name', true);
            $last_name = get_user_meta($tutor->ID, 'last_name', true);
            $display_name = (!empty($first_name) && !empty($last_name)) ? esc_html($first_name . ' ' . $last_name) : esc_html($tutor->display_name);
            $tutors_available[] = [
                'id' => $tutor->ID,
                'user_login' => $tutor->user_login,
                'display_name' => $display_name
            ];
        }
    }
    
    error_log('[get_tutors_for_student] Found ' . count($tutors_available) . ' tutors for student ID: ' . $student_id); // Debug log

    return $tutors_available;
}

/**
 * Retrieves upcoming lessons for a given user (student or tutor).
 * Parses lesson schedule from user meta.
 *
 * @param int $user_id The ID of the user.
 * @return array Array of upcoming lessons with date objects and formatted strings.
 */
function get_upcoming_lessons_for_user($user_id) {
    $now = new DateTime('now', new DateTimeZone('Australia/Brisbane')); // Consider making timezone configurable
    $lesson_schedule = get_user_meta($user_id, 'lesson_schedule_list', true);
    $upcoming_lessons = [];

    if (!empty($lesson_schedule) && is_string($lesson_schedule)) {
        $lessons = explode("\n", trim($lesson_schedule));
        
        foreach ($lessons as $lesson) {
            $lesson = trim($lesson);
            if (empty($lesson)) continue;
            
            // Regex to capture variations like "Subject on Day DD Month YYYY at HH:MM"
             // Making subject capture optional and more flexible
             if (preg_match('/(?:([A-Za-z\s]+)\s+on\s+)?([A-Za-z]+)\s+(\d{1,2})(?:st|nd|rd|th)?\s+([A-Za-z]+)\s+(\d{4})\s+at\s+(\d{1,2}:\d{2})/', $lesson, $matches)) {
                
                 $subject = !empty(trim($matches[1])) ? trim($matches[1]) : 'Lesson'; // Default subject
                $day_name = $matches[2];
                $day = $matches[3];
                $month = $matches[4];
                $year = $matches[5];
                $time = $matches[6];
                
                 // Attempt to create a DateTime object
                 try {
                     // Construct a string parsable by DateTime - be careful with month names
                     $date_string = $day . ' ' . $month . ' ' . $year . ' ' . $time;
                    // Use createFromFormat for better reliability if format is consistent
                    // Or rely on DateTime constructor's flexibility
                    $lesson_date = new DateTime($date_string, new DateTimeZone('Australia/Brisbane')); 

                    // Ensure the parsed day name matches (optional safety check)
                    // if (strtolower($lesson_date->format('l')) != strtolower($day_name)) {
                    //     continue; // Skip if day name doesn't match parsed date
                    // }

                    if ($lesson_date >= $now) { // Include lessons starting now or later
                        $upcoming_lessons[] = [
                             'date' => $lesson_date, // DateTime object
                             'formatted' => $lesson_date->format('l, jS F Y \\a\\t g:i A'), // User-friendly format
                             'subject' => esc_html($subject),
                             'date_value' => $lesson_date->format('Y-m-d'), // For form values
                             'time_value' => $lesson_date->format('H:i:s') // For form values
                        ];
                    }
                 } catch (Exception $e) {
                     error_log("Error parsing lesson date string: '{$lesson}'. Error: " . $e->getMessage());
                     continue; // Skip if date parsing fails
                 }
            } else {
                 error_log("Lesson schedule regex failed for line: '{$lesson}'");
            }
        }
        
        // Sort lessons chronologically
        usort($upcoming_lessons, function($a, $b) {
            return $a['date']->getTimestamp() - $b['date']->getTimestamp();
        });
    }

    return $upcoming_lessons;
}


/**
 * Retrieves reschedule requests based on type, user role, and optional status.
 * Flexible query arguments based on user context.
 *
 * @param string $type The request_type meta value (e.g., 'student_reschedule', 'tutor_reschedule').
 * @param int $user_id The ID of the user (student or tutor).
 * @param string $user_role 'student' or 'tutor'.
 * @param string|null $status Optional status to filter by (e.g., 'pending', 'confirmed').
 * @param array $extra_meta_query Additional meta query clauses.
 * @return array Array of WP_Post objects for the requests.
 */
function get_reschedule_requests($type, $user_id, $user_role, $status = null, $extra_meta_query = []) {
    if (empty($user_id) || !in_array($user_role, ['student', 'tutor'])) {
        return [];
    }

    $meta_query = [
        'relation' => 'AND',
        [
            'key' => 'request_type',
            'value' => $type,
            'compare' => '='
        ],
    ];

    // Add user ID condition based on role
    if ($user_role === 'student') {
        $meta_query[] = [
            'key' => 'student_id',
            'value' => $user_id,
            'compare' => '='
        ];
    } else { // tutor
        // Tutors might be identified by ID or name, check both for robustness
         $user_login = get_user_by('id', $user_id)->user_login;
         $meta_query[] = [
            'relation' => 'OR',
             [
                 'key' => 'tutor_id',
                 'value' => $user_id,
                 'compare' => '=',
             ],
             [
                 'key' => 'tutor_name', // Fallback or alternative storage method
                 'value' => $user_login,
                 'compare' => '=',
             ]
         ];
    }

    // Add status filter if provided
    if ($status) {
        $meta_query[] = [
            'key' => 'status',
            'value' => $status,
            'compare' => '='
        ];
    }

    // Merge extra meta query conditions
    if (!empty($extra_meta_query)) {
        $meta_query = array_merge($meta_query, $extra_meta_query);
    }

    $args = [
        'post_type' => 'lesson_reschedule', // CORRECTED POST TYPE
        'posts_per_page' => -1, // Get all matching requests
        'meta_query' => $meta_query,
        'orderby' => 'date',
        'order' => 'DESC'
    ];

    $query = new WP_Query($args);
    return $query->get_posts();
}

/**
 * Renders HTML input fields for preferred date/time selection.
 * Used in modals for creating/editing requests.
 *
 * @param string $prefix Optional prefix for input IDs and names (e.g., 'edit_').
 * @param bool $required Whether the first time slot is required.
 */
function render_preferred_time_inputs($prefix = '', $required = false) {
    $req_attr = $required ? 'required' : '';
    $output = '<div id="' . esc_attr($prefix) . 'preferred-times-container">'; // Wrapper div
    for ($i = 1; $i <= 3; $i++) {
        $is_first = ($i === 1);
        $output .= '<div class="preferred-time-row mb-2"><div class="row g-2"> <!-- Added g-2 for gutter -->
            <div class="col-md-6">
                <label for="' . esc_attr($prefix) . 'preferred_date_' . $i . '" class="form-label small">Preferred Date ' . $i . ($is_first && $required ? ' <span class="text-danger">*</span>' : '') . ':</label>
                <input type="date" class="form-control preferred-date" 
                       name="' . esc_attr($prefix) . 'preferred_date_' . $i . '" id="' . esc_attr($prefix) . 'preferred_date_' . $i . '" ' . ($is_first ? $req_attr : '') . '>
            </div>
            <div class="col-md-6">
                <label for="' . esc_attr($prefix) . 'preferred_time_' . $i . '" class="form-label small">Preferred Time ' . $i . ($is_first && $required ? ' <span class="text-danger">*</span>' : '') . ':</label>
                <input type="time" class="form-control preferred-time" 
                       name="' . esc_attr($prefix) . 'preferred_time_' . $i . '" id="' . esc_attr($prefix) . 'preferred_time_' . $i . '" ' . ($is_first ? $req_attr : '') . '>
            </div>
        </div></div>';
    }
     $output .= '</div>'; // Close wrapper div
     // Add error message placeholder
     $output .= '<div id="' . esc_attr($prefix) . 'preferred-times-error" class="text-danger mt-2" style="display: none;">Please provide at least one preferred alternative time.</div>';
     echo $output;
}

/**
 * Renders HTML input fields for alternative date/time selection (Tutor Unavailable flow).
 *
 * @param string $prefix Optional prefix for input IDs and names.
 * @param bool $required Whether the first time slot is required.
 */
function render_alternative_time_inputs($prefix = 'alt_', $required = true) {
    $req_attr = $required ? 'required' : '';
    $output = '<div id="' . esc_attr($prefix) . 'times-container">'; // Wrapper div
    for ($i = 1; $i <= 3; $i++) {
        $is_first = ($i === 1);
        $output .= '<div class="mb-2"><div class="row g-2">
            <div class="col-md-6">
                <label for="' . esc_attr($prefix) . 'date_' . $i . '" class="form-label small">Alternative Date ' . $i . ($is_first && $required ? ' <span class="text-danger">*</span>' : '') . ':</label>
                <input type="date" class="form-control ' . esc_attr($prefix) . 'date" 
                       name="' . esc_attr($prefix) . 'date_' . $i . '" id="' . esc_attr($prefix) . 'date_' . $i . '" ' . ($is_first ? $req_attr : '') . '>
            </div>
            <div class="col-md-6">
                <label for="' . esc_attr($prefix) . 'time_' . $i . '" class="form-label small">Alternative Time ' . $i . ($is_first && $required ? ' <span class="text-danger">*</span>' : '') . ':</label>
                <input type="time" class="form-control ' . esc_attr($prefix) . 'time" 
                       name="' . esc_attr($prefix) . 'time_' . $i . '" id="' . esc_attr($prefix) . 'time_' . $i . '" ' . ($is_first ? $req_attr : '') . '>
            </div>
        </div></div>';
    }
    $output .= '</div>'; // Close wrapper div
    // Add error message placeholder
    $output .= '<div id="' . esc_attr($prefix) . 'times-error" class="text-danger mt-2" style="display: none;">Please provide at least one alternative time.</div>';
    echo $output;
}


/**
 * Get count of pending reschedule requests for a user.
 *
 * @param int $user_id
 * @param string $user_role 'student' or 'tutor'
 * @param string $request_type The specific request type to count (e.g., 'tutor_reschedule' for student, 'student_reschedule' for tutor)
 * @return int Count of pending requests.
 */
function get_pending_request_count($user_id, $user_role, $request_type) {
     $args = [
         'post_type' => 'progress_report',
         'posts_per_page' => -1,
         'meta_query' => [
             'relation' => 'AND',
             [
                 'key' => ($user_role === 'student' ? 'student_id' : 'tutor_id'),
                 'value' => $user_id,
                 'compare' => '='
             ],
             [
                 'key' => 'request_type',
                 'value' => $request_type,
                 'compare' => '='
             ],
             [
                 'key' => 'status',
                 'value' => 'pending',
                 'compare' => '='
             ]
         ],
         'fields' => 'ids' // Only need IDs for counting
     ];

    // Add tutor_name fallback check for tutors
    if ($user_role === 'tutor') {
        $user_login = get_user_by('id', $user_id)->user_login;
        $args['meta_query'] = [
            'relation' => 'AND',
             [
                'relation' => 'OR',
                 [
                     'key' => 'tutor_id',
                     'value' => $user_id,
                     'compare' => '=',
                 ],
                 [
                     'key' => 'tutor_name',
                     'value' => $user_login,
                     'compare' => '=',
                 ]
             ],
             [
                 'key' => 'request_type',
                 'value' => $request_type,
                 'compare' => '=',
             ],
             [
                 'key' => 'status',
                 'value' => 'pending',
                 'compare' => '=',
             ]
        ];
    }
     
     $query = new WP_Query($args);
     return $query->post_count;
 }

 /**
  * Get count of pending alternative time suggestions requiring action from the user.
  *
  * @param int $user_id
  * @param string $user_role 'student' or 'tutor'
  * @return int Count of pending alternative suggestions.
  */
 function get_pending_alternatives_count($user_id, $user_role) {
     $request_type = ($user_role === 'student') ? 'tutor_unavailable' : 'reschedule_alternatives'; // Type depends on who is viewing
     $viewed_meta_key = ($user_role === 'student') ? 'viewed_by_student' : 'viewed_by_tutor';

     $args = [
         'post_type' => 'progress_report',
         'posts_per_page' => -1,
         'meta_query' => [
             'relation' => 'AND',
             [
                 'key' => ($user_role === 'student' ? 'student_id' : 'tutor_id'),
                 'value' => $user_id,
                 'compare' => '='
             ],
             [
                 'key' => 'request_type',
                 'value' => $request_type,
                 'compare' => '='
             ],
             [
                 'key' => 'status',
                 'value' => 'pending',
                 'compare' => '='
             ],
             // Optional: Add check for viewed status if needed
             // [
             //     'relation' => 'OR',
             //     [ 'key' => $viewed_meta_key, 'compare' => 'NOT EXISTS' ],
             //     [ 'key' => $viewed_meta_key, 'value' => '1', 'compare' => '!=' ]
             // ]
         ],
         'fields' => 'ids'
     ];

     // Add tutor_name fallback check for tutors
     if ($user_role === 'tutor') {
        $user_login = get_user_by('id', $user_id)->user_login;
        // Adjust meta query to include tutor_name check
         $args['meta_query'] = [
            'relation' => 'AND',
             [
                'relation' => 'OR',
                 [
                     'key' => 'tutor_id',
                     'value' => $user_id,
                     'compare' => '=',
                 ],
                 [
                     'key' => 'tutor_name',
                     'value' => $user_login,
                     'compare' => '=',
                 ]
             ],
             [
                 'key' => 'request_type',
                 'value' => $request_type, // reschedule_alternatives
                 'compare' => '='
             ],
             [
                 'key' => 'status',
                 'value' => 'pending',
                 'compare' => '='
             ],
         ];
     }

     $query = new WP_Query($args);
     return $query->post_count;
 }

 /**
  * Get count of confirmed requests not yet viewed by the user.
  *
  * @param int $user_id
  * @param string $user_role 'student' or 'tutor'
  * @return int Count of unread confirmed requests.
  */
 function get_unread_confirmed_count($user_id, $user_role) {
     $viewed_meta_key = ($user_role === 'student') ? 'viewed_by_student' : 'viewed_by_tutor';
     $request_type_to_check = 'reschedule'; // Assuming 'reschedule' is used for confirmed requests needing viewing

     $args = array(
         'post_type'      => 'progress_report',
         'posts_per_page' => -1,
         'meta_query'     => array(
             'relation' => 'AND',
             array(
                 'key'     => ($user_role === 'student' ? 'student_id' : 'tutor_id'),
                 'value'   => $user_id,
                 'compare' => '=',
             ),
             array(
                 'key'     => 'request_type',
                 'value'   => $request_type_to_check, 
                 'compare' => '=',
             ),
             array(
                 'key'     => 'status',
                 'value'   => 'confirmed', // Or 'accepted' if that's used
                 'compare' => '=',
             ),
             array(
                 'relation' => 'OR',
                 array(
                     'key'     => $viewed_meta_key,
                     'compare' => 'NOT EXISTS',
                 ),
                 array(
                     'key'     => $viewed_meta_key,
                     'value'   => '1',
                     'compare' => '!=',
                 )
             )
         ),
         'fields'         => 'ids'
     );

     // Add tutor_name fallback check for tutors
     if ($user_role === 'tutor') {
        $user_login = get_user_by('id', $user_id)->user_login;
        // Adjust meta query
        $args['meta_query'][0] = [ // Replace the user ID check
            'relation' => 'OR',
             [
                 'key' => 'tutor_id',
                 'value' => $user_id,
                 'compare' => '=',
             ],
             [
                 'key' => 'tutor_name',
                 'value' => $user_login,
                 'compare' => '=',
             ]
        ];
     }
     
     $query = new WP_Query($args);
     return $query->post_count;
 }

/**
 * Checks if there are pending alternative time suggestions from a student 
 * for a specific original tutor reschedule request.
 *
 * @param int $original_request_id The ID of the original tutor_reschedule request.
 * @return bool True if pending alternatives exist, false otherwise.
 */
function has_pending_student_alternatives(int $original_request_id): bool {
    if ( empty($original_request_id) ) {
        return false;
    }

    $args = [
        'post_type'      => 'progress_report',
        'posts_per_page' => 1, // We only need to know if at least one exists
        'meta_query'     => [
            'relation' => 'AND',
            ['key' => 'original_request_id', 'value' => $original_request_id, 'compare' => '='],
            ['key' => 'request_type', 'value' => 'reschedule_alternatives', 'compare' => '='],
            ['key' => 'status', 'value' => 'pending', 'compare' => '=']
        ],
        'fields'         => 'ids', // Only need the ID
        'post_status'    => 'publish', // Ensure we only check published posts
    ];

    $alternative_requests = get_posts($args);

    return !empty($alternative_requests);
}

error_log('[REQUEST FUNCTIONS FILE] requests/request-functions.php finished.'); // BOTTOM LOG
?> 