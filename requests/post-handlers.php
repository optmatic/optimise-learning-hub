<?php
/**
 * POST Request Handlers for Reschedule Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Ensure helper functions are available
require_once __DIR__ . '/request-functions.php'; 

/**
 * Main handler function hooked to 'init' to process all reschedule POST requests.
 */
function handle_reschedule_post_requests() {
    // Ensure user is logged in
    if (!is_user_logged_in()) {
        return;
    }

    // --- Tutor Actions ---
    if (current_user_can('tutor')) {
        // Tutor accepts student request
        if (isset($_POST['confirm_reschedule'], $_POST['request_id']) && check_admin_referer('confirm_reschedule_' . $_POST['request_id'])) {
            handle_tutor_confirm_student_request();
        }
        // Tutor marks student request as unavailable (provides alternatives)
        if (isset($_POST['decline_reschedule'], $_POST['request_id']) && check_admin_referer('decline_reschedule_' . $_POST['request_id'])) {
            handle_tutor_decline_student_request();
        }
        // Tutor submits a new reschedule request
        if (isset($_POST['submit_tutor_reschedule_request']) && check_admin_referer('submit_tutor_reschedule_request_nonce', 'submit_tutor_reschedule_request_nonce')) {
            handle_tutor_submit_request();
        }
         // Tutor updates their own reschedule request
         if (isset($_POST['update_tutor_reschedule_request'], $_POST['request_id']) && check_admin_referer('update_tutor_reschedule_request_nonce', 'update_tutor_reschedule_request_nonce')) {
            handle_tutor_update_request();
        }
        // Tutor deletes their own reschedule request (Form submission, not AJAX)
        // Note: AJAX delete is handled in ajax-handlers.php
        if (isset($_POST['delete_tutor_request'], $_POST['request_id']) && check_admin_referer('delete_tutor_request_' . $_POST['request_id'])) {
             handle_tutor_delete_request();
        }
        // Tutor selects an alternative time suggested by a student
         if (isset($_POST['select_alternative'], $_POST['request_id']) && check_admin_referer('select_alternative_' . $_POST['request_id'])) {
             handle_tutor_select_student_alternative();
         }
    }

    // --- Student Actions ---
    if (current_user_can('student')) {
        // Student submits a new reschedule request
        if (isset($_POST['submit_student_reschedule_request']) && check_admin_referer('submit_student_reschedule_request_nonce', 'submit_student_reschedule_request_nonce')) {
            handle_student_submit_request();
        }
         // Student updates their own reschedule request
         if (isset($_POST['update_student_reschedule_request'], $_POST['request_id']) && check_admin_referer('update_student_reschedule_request_nonce', 'update_student_reschedule_request_nonce')) {
             handle_student_update_request();
         }
        // Student deletes their own reschedule request (Form submission, not AJAX)
        // Note: AJAX delete is handled in ajax-handlers.php
         if (isset($_POST['delete_student_request'], $_POST['request_id']) && check_admin_referer('delete_student_request_' . $_POST['request_id'])) {
             handle_student_delete_request();
         }
        // Student accepts a tutor-initiated request
         if (isset($_POST['confirm_tutor_reschedule'], $_POST['request_id']) && check_admin_referer('confirm_tutor_reschedule_' . $_POST['request_id'])) {
             handle_student_confirm_tutor_request();
         }
        // Student declines a tutor-initiated request
         if (isset($_POST['decline_tutor_reschedule'], $_POST['request_id']) && check_admin_referer('decline_tutor_reschedule_' . $_POST['request_id'])) {
             handle_student_decline_tutor_request();
         }
         // Student selects an alternative time proposed by tutor (tutor_unavailable flow)
         if (isset($_POST['accept_tutor_alternative'], $_POST['request_id']) && check_admin_referer('accept_tutor_alternative_' . $_POST['request_id'])) {
             handle_student_select_tutor_alternative();
         }
         // Student marks themselves unavailable for ALL tutor alternatives (tutor_unavailable flow)
         if (isset($_POST['unavailable_all'], $_POST['request_id']) && check_admin_referer('unavailable_all_' . $_POST['request_id'])) {
             handle_student_unavailable_all_alternatives();
         }
         // Student marks themselves unavailable for a specific tutor-initiated request and provides alternatives
         // This seems to be covered by `handle_student_decline_tutor_request` now? Or maybe a different flow?
         // Let's keep the handler stub in case it's needed from the `unavailableModal` in student-requests.php
         if (isset($_POST['mark_unavailable_provide_alternatives'], $_POST['request_id']) && check_admin_referer('mark_unavailable_provide_alternatives_' . $_POST['request_id'])) { 
             handle_student_unavailable_provide_alternatives();
         }
    }
}
add_action('init', 'handle_reschedule_post_requests');

// --- Individual Handler Functions ---

// Tutor accepts student's request
function handle_tutor_confirm_student_request() {
    $request_id = intval($_POST['request_id']);
    $current_tutor_id = get_current_user_id();
    
    // Verify tutor owns this request
    $tutor_id = get_post_meta($request_id, 'tutor_id', true);
    $tutor_name = get_post_meta($request_id, 'tutor_name', true);
    $request_type = get_post_meta($request_id, 'request_type', true);

    if ($request_type !== 'student_reschedule' || ($tutor_id != $current_tutor_id && $tutor_name != wp_get_current_user()->user_login)) {
         wp_die('Invalid request or permission denied.');
    }

    // Update original student request status
    update_post_meta($request_id, 'status', 'confirmed'); // Or 'accepted'?

    // Get details needed for notification
    $student_id = get_post_meta($request_id, 'student_id', true);
    $preferred_times = get_post_meta($request_id, 'preferred_times', true);
    $original_date = get_post_meta($request_id, 'original_date', true);
    $original_time = get_post_meta($request_id, 'original_time', true);
    
    // Use the *first* preferred time as the new confirmed time
    $new_date = !empty($preferred_times[0]['date']) ? $preferred_times[0]['date'] : $original_date; // Fallback if needed
    $new_time = !empty($preferred_times[0]['time']) ? $preferred_times[0]['time'] : $original_time; // Fallback if needed

    // Create notification post for the student
    $notification_post = [
        'post_title'   => 'Tutor Accepted Reschedule Request',
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'progress_report', // Use your CPT slug
    ];
    $notification_id = wp_insert_post($notification_post);

    if (!is_wp_error($notification_id)) {
        update_post_meta($notification_id, 'request_type', 'reschedule_confirmed'); // Type for student notification
        update_post_meta($notification_id, 'status', 'pending'); // Student needs to see this
        update_post_meta($notification_id, 'tutor_id', $current_tutor_id);
        update_post_meta($notification_id, 'tutor_name', wp_get_current_user()->user_login);
        update_post_meta($notification_id, 'student_id', $student_id);
        update_post_meta($notification_id, 'original_request_id', $request_id); // Link to original
        update_post_meta($notification_id, 'original_date', $original_date);
        update_post_meta($notification_id, 'original_time', $original_time);
        update_post_meta($notification_id, 'new_date', $new_date); // Confirmed date
        update_post_meta($notification_id, 'new_time', $new_time); // Confirmed time
        update_post_meta($notification_id, 'viewed_by_student', '0'); // Mark as unread for student
    }

    // Redirect back to the tutor dashboard requests tab
    $redirect_url = add_query_arg(['page' => 'tutor-dashboard', 'active_tab' => 'requests', 'reschedule_status' => 'confirmed'], admin_url('admin.php?page=tutor-dashboard')); // Adjust URL if not admin page
     wp_safe_redirect(wp_get_referer() ?: $redirect_url); 
    exit;
}

// Tutor declines student's request (marks unavailable, provides alternatives)
function handle_tutor_decline_student_request() {
    $request_id = intval($_POST['request_id']);
    $current_tutor_id = get_current_user_id();

    // Verify tutor owns this request
    $tutor_id = get_post_meta($request_id, 'tutor_id', true);
    $tutor_name = get_post_meta($request_id, 'tutor_name', true);
    $request_type = get_post_meta($request_id, 'request_type', true);

     if ($request_type !== 'student_reschedule' || ($tutor_id != $current_tutor_id && $tutor_name != wp_get_current_user()->user_login)) {
         wp_die('Invalid request or permission denied.');
     }

    // Update original student request status
    update_post_meta($request_id, 'status', 'unavailable'); // Mark original as unavailable

    // Get details needed for notification
    $student_id = get_post_meta($request_id, 'student_id', true);
    $original_date = get_post_meta($request_id, 'original_date', true);
    $original_time = get_post_meta($request_id, 'original_time', true);

    // Collect alternative times provided by tutor
    $alternatives = [];
    for ($i = 1; $i <= 3; $i++) {
        $alt_date = isset($_POST['alt_date_' . $i]) ? sanitize_text_field($_POST['alt_date_' . $i]) : '';
        $alt_time = isset($_POST['alt_time_' . $i]) ? sanitize_text_field($_POST['alt_time_' . $i]) : '';
        if (!empty($alt_date) && !empty($alt_time)) {
            $alternatives[] = ['date' => $alt_date, 'time' => $alt_time];
        }
    }

    // Create notification post for the student
    $notification_post = [
        'post_title'   => 'Tutor Unavailable - Alternative Times Provided',
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'progress_report',
    ];
    $notification_id = wp_insert_post($notification_post);

    if (!is_wp_error($notification_id)) {
        update_post_meta($notification_id, 'request_type', 'tutor_unavailable'); // Type for student notification
        update_post_meta($notification_id, 'status', 'pending'); // Student needs to act
        update_post_meta($notification_id, 'tutor_id', $current_tutor_id);
        update_post_meta($notification_id, 'tutor_name', wp_get_current_user()->user_login);
        update_post_meta($notification_id, 'student_id', $student_id);
        update_post_meta($notification_id, 'original_request_id', $request_id); // Link to original
        update_post_meta($notification_id, 'original_date', $original_date);
        update_post_meta($notification_id, 'original_time', $original_time);
        update_post_meta($notification_id, 'alternatives', $alternatives); // Store proposed alternatives
        update_post_meta($notification_id, 'viewed_by_student', '0'); // Mark as unread
    }

    // Redirect back to the tutor dashboard requests tab
    $redirect_url = add_query_arg(['page' => 'tutor-dashboard', 'active_tab' => 'requests', 'reschedule_status' => 'declined'], admin_url('admin.php?page=tutor-dashboard')); // Adjust URL
     wp_safe_redirect(wp_get_referer() ?: $redirect_url);
    exit;
}

// Tutor submits a new request
function handle_tutor_submit_request() {
    $tutor_id = get_current_user_id();
    $tutor_name = wp_get_current_user()->user_login;
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0; // Keep student ID from dropdown
    $student_name = ''; // We'll get this from the ID
    // Get original date/time from the new input fields
    $original_date = isset($_POST['original_lesson_date']) ? sanitize_text_field($_POST['original_lesson_date']) : '';
    $original_time = isset($_POST['original_lesson_time']) ? sanitize_text_field($_POST['original_lesson_time']) : '';
    $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
    // Get proposed date/time
    $proposed_date = isset($_POST['proposed_date']) ? sanitize_text_field($_POST['proposed_date']) : ''; 
    $proposed_time = isset($_POST['proposed_time']) ? sanitize_text_field($_POST['proposed_time']) : ''; 

    // Basic validation - Added proposed date/time check
    if (empty($student_id) || empty($original_date) || empty($original_time) || empty($reason) || empty($proposed_date) || empty($proposed_time)) {
        wp_die('Missing required fields for reschedule request. Please ensure student, original date/time, reason, and proposed date/time are provided.');
    }

    // Get student username from ID
    $student_user = get_user_by('id', $student_id);
    if (!$student_user) {
        wp_die('Invalid student selected.');
    }
    $student_name = $student_user->user_login;

    // Create the request post
    $request_post = [
        'post_title'   => 'Tutor Reschedule Request from ' . $tutor_name . ' for ' . $student_name,
        'post_content' => $reason, // Store reason in content maybe?
        'post_status'  => 'publish', // Keep publish? Status meta is 'pending'. Or change to 'pending'? Let's keep publish for now.
        'post_type'    => 'lesson_reschedule', // CORRECTED post type
        'post_author'  => $tutor_id, // Ensure post author is the tutor
    ];
    $request_id = wp_insert_post($request_post);

    if (!is_wp_error($request_id)) {
        // Combine original date and time for storage
        $original_lesson_time_string = trim($original_date . ' ' . $original_time);
        // Combine proposed date and time for storage
        $proposed_lesson_time_string = trim($proposed_date . ' ' . $proposed_time); 

        // Save meta data
        update_post_meta($request_id, 'request_type', 'tutor_reschedule');
        update_post_meta($request_id, 'initiator', 'tutor'); // ADDED initiator meta
        update_post_meta($request_id, 'status', 'pending');
        update_post_meta($request_id, 'tutor_id', $tutor_id);
        update_post_meta($request_id, 'tutor_name', $tutor_name);
        update_post_meta($request_id, 'student_id', $student_id);
        update_post_meta($request_id, 'student_name', $student_name); // Store student login for consistency?
        update_post_meta($request_id, 'reason', $reason);
        
        // Save the combined date/time strings
        update_post_meta($request_id, 'original_lesson_time', $original_lesson_time_string); 
        update_post_meta($request_id, 'proposed_lesson_time', $proposed_lesson_time_string); 

        // Save the individual proposed date/time fields
        update_post_meta($request_id, 'proposed_date', $proposed_date);
        update_post_meta($request_id, 'proposed_time', $proposed_time);
        
        // Save original date/time meta as well (consistency)
        update_post_meta($request_id, 'original_date', $original_date);
        update_post_meta($request_id, 'original_time', $original_time);
        
        update_post_meta($request_id, 'viewed_by_student', '0'); // Mark as unread for student

        // Redirect back to tutor dashboard requests tab
        // Use get_permalink by page slug if available
        $dashboard_page = get_page_by_path('tutor-dashboard'); // Assumes 'tutor-dashboard' is the slug
        $redirect_base_url = $dashboard_page ? get_permalink($dashboard_page->ID) : home_url('/'); // Fallback to home_url
        $redirect_url = add_query_arg(['active_tab' => 'requests', 'request_status' => 'submitted'], $redirect_base_url);
        wp_safe_redirect($redirect_url);
        exit;
    } else {
        wp_die('Error creating reschedule request: ' . $request_id->get_error_message());
    }
}

// Tutor updates their own request
function handle_tutor_update_request() {
    $request_id = intval($_POST['request_id']);
    $current_tutor_id = get_current_user_id();
    $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

    // Verify ownership
    $tutor_id = get_post_meta($request_id, 'tutor_id', true);
    $tutor_name = get_post_meta($request_id, 'tutor_name', true);
    $request_type = get_post_meta($request_id, 'request_type', true);

     if ($request_type !== 'tutor_reschedule' || ($tutor_id != $current_tutor_id && $tutor_name != wp_get_current_user()->user_login)) {
         wp_die('Invalid request or permission denied.');
     }
     
     // Don't allow editing if not pending?
     $status = get_post_meta($request_id, 'status', true);
     if ($status !== 'pending') {
          wp_die('Cannot edit a request that is not pending.');
     }

    // Collect preferred times (ensure names match the edit modal: edit_preferred_date_1, etc.)
    $preferred_times = [];
    for ($i = 1; $i <= 3; $i++) {
        $date = isset($_POST['edit_preferred_date_' . $i]) ? sanitize_text_field($_POST['edit_preferred_date_' . $i]) : '';
        $time = isset($_POST['edit_preferred_time_' . $i]) ? sanitize_text_field($_POST['edit_preferred_time_' . $i]) : '';
        if (!empty($date) && !empty($time)) {
            $preferred_times[] = ['date' => $date, 'time' => $time];
        }
    }
    
    // Require at least one preferred time
    if (empty($preferred_times)) {
        wp_die('Please provide at least one preferred alternative time.');
    }

    // Update meta
    update_post_meta($request_id, 'reason', $reason);
    update_post_meta($request_id, 'preferred_times', $preferred_times);
     // Maybe mark as unread for student again?
     update_post_meta($request_id, 'viewed_by_student', '0');

    // Redirect
    $redirect_url = add_query_arg(['page' => 'tutor-dashboard', 'active_tab' => 'requests', 'reschedule_status' => 'updated'], admin_url('admin.php?page=tutor-dashboard')); // Adjust URL
     wp_safe_redirect(wp_get_referer() ?: $redirect_url);
    exit;
}

// Tutor deletes their own request (handles form submission)
function handle_tutor_delete_request() {
    $request_id = intval($_POST['request_id']);
    $current_tutor_id = get_current_user_id();

    // Verify ownership
    $tutor_id = get_post_meta($request_id, 'tutor_id', true);
    $tutor_name = get_post_meta($request_id, 'tutor_name', true);
    $request_type = get_post_meta($request_id, 'request_type', true);

     // Allow deleting own requests regardless of type? Or only tutor_reschedule?
     if (($tutor_id != $current_tutor_id && $tutor_name != wp_get_current_user()->user_login)) {
          wp_die('Permission denied.');
     }

    $result = wp_delete_post($request_id, true); // Force delete

    // Redirect
    $status = $result ? 'deleted' : 'delete_failed';
    $redirect_url = add_query_arg(['page' => 'tutor-dashboard', 'active_tab' => 'requests', 'reschedule_status' => $status], admin_url('admin.php?page=tutor-dashboard')); // Adjust URL
     wp_safe_redirect(wp_get_referer() ?: $redirect_url);
    exit;
}

// Tutor selects an alternative time suggested by a student (request_type = reschedule_alternatives)
function handle_tutor_select_student_alternative() {
    $alternative_request_id = intval($_POST['request_id']);
    $selected_index = isset($_POST['selected_alternative']) ? intval($_POST['selected_alternative']) : -1;
    $current_tutor_id = get_current_user_id();

    // Verify tutor owns this alternative request
    $tutor_id = get_post_meta($alternative_request_id, 'tutor_id', true);
    $tutor_name = get_post_meta($alternative_request_id, 'tutor_name', true);
    $request_type = get_post_meta($alternative_request_id, 'request_type', true);

    if ($request_type !== 'reschedule_alternatives' || ($tutor_id != $current_tutor_id && $tutor_name != wp_get_current_user()->user_login) || $selected_index < 0) {
        wp_die('Invalid request, permission denied, or no alternative selected.');
    }

    // Update the alternative request status
    update_post_meta($alternative_request_id, 'status', 'confirmed');
    update_post_meta($alternative_request_id, 'selected_alternative', $selected_index); // Record which one was chosen
    update_post_meta($alternative_request_id, 'viewed_by_tutor', '1'); // Mark as viewed now

    // Get details for notification
    $student_id = get_post_meta($alternative_request_id, 'student_id', true);
    $original_request_id = get_post_meta($alternative_request_id, 'original_request_id', true); // The request the student was originally responding to (tutor unavailable)
    $alternatives = get_post_meta($alternative_request_id, 'alternatives', true);
    $selected_alternative = $alternatives[$selected_index];
    
     // Get original lesson details from the *student's* initial request if possible
     // This might require tracing back further depending on the flow
    $original_date = get_post_meta($original_request_id, 'original_date', true); 
    $original_time = get_post_meta($original_request_id, 'original_time', true);

    // Create notification for the student
    $notification_post = [
        'post_title'   => 'Tutor Confirmed Your Alternative Time',
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'progress_report',
    ];
    $notification_id = wp_insert_post($notification_post);

    if (!is_wp_error($notification_id)) {
        update_post_meta($notification_id, 'request_type', 'reschedule_confirmed'); // Use same confirmation type?
        update_post_meta($notification_id, 'status', 'pending'); // Student needs to see this
        update_post_meta($notification_id, 'tutor_id', $current_tutor_id);
        update_post_meta($notification_id, 'tutor_name', wp_get_current_user()->user_login);
        update_post_meta($notification_id, 'student_id', $student_id);
        update_post_meta($notification_id, 'original_request_id', $alternative_request_id); // Link to the alternative request
        update_post_meta($notification_id, 'original_date', $original_date); // Original lesson
        update_post_meta($notification_id, 'original_time', $original_time); // Original lesson
        update_post_meta($notification_id, 'new_date', $selected_alternative['date']); // Confirmed date
        update_post_meta($notification_id, 'new_time', $selected_alternative['time']); // Confirmed time
        update_post_meta($notification_id, 'viewed_by_student', '0');
    }

    // Redirect
    $redirect_url = add_query_arg(['page' => 'tutor-dashboard', 'active_tab' => 'requests', 'reschedule_status' => 'alternative_confirmed'], admin_url('admin.php?page=tutor-dashboard')); // Adjust URL
     wp_safe_redirect(wp_get_referer() ?: $redirect_url);
    exit;
}


// --- Student Handler Functions ---

// Student submits a new request
function handle_student_submit_request() {
    $student_id = get_current_user_id();
    $student_name = wp_get_current_user()->user_login; // Use login name for consistency?
    $tutor_id = 0; // We might get tutor_name instead
    $tutor_name = isset($_POST['tutor_name']) ? sanitize_text_field($_POST['tutor_name']) : ''; // Expecting tutor *username* from dropdown
    $original_date = isset($_POST['original_date']) ? sanitize_text_field($_POST['original_date']) : '';
    $original_time = isset($_POST['original_time']) ? sanitize_text_field($_POST['original_time']) : '';
    $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

    // Basic validation
    if (empty($tutor_name) || empty($original_date) || empty($original_time) || empty($reason)) {
        wp_die('Missing required fields for reschedule request.');
    }

    // Get tutor ID from username
    $tutor_user = get_user_by('login', $tutor_name);
    if ($tutor_user) {
        $tutor_id = $tutor_user->ID;
    } else {
        wp_die('Selected tutor not found.');
    }

    // Collect preferred times
    $preferred_times = [];
    for ($i = 1; $i <= 3; $i++) {
        $date = isset($_POST['preferred_date_' . $i]) ? sanitize_text_field($_POST['preferred_date_' . $i]) : '';
        $time = isset($_POST['preferred_time_' . $i]) ? sanitize_text_field($_POST['preferred_time_' . $i]) : '';
        if (!empty($date) && !empty($time)) {
            $preferred_times[] = ['date' => $date, 'time' => $time];
        }
    }
    
     // Require at least one preferred time from the student
    if (empty($preferred_times)) {
        wp_die('Please provide at least one preferred alternative time.');
    }

    // Create the request post
    $request_post = [
        'post_title'   => 'Student Reschedule Request from ' . $student_name . ' for ' . $tutor_name,
        'post_content' => $reason,
        'post_status'  => 'publish',
        'post_type'    => 'lesson_reschedule', // CORRECTED post type
    ];
    $request_id = wp_insert_post($request_post);

    if (!is_wp_error($request_id)) {
        update_post_meta($request_id, 'request_type', 'student_reschedule');
        update_post_meta($request_id, 'initiator', 'student'); // ADDED initiator meta
        update_post_meta($request_id, 'status', 'pending');
        update_post_meta($request_id, 'student_id', $student_id);
        update_post_meta($request_id, 'student_name', $student_name);
        update_post_meta($request_id, 'tutor_id', $tutor_id);
        update_post_meta($request_id, 'tutor_name', $tutor_name);
        update_post_meta($request_id, 'original_date', $original_date);
        update_post_meta($request_id, 'original_time', $original_time);
        update_post_meta($request_id, 'reason', $reason);
        update_post_meta($request_id, 'preferred_times', $preferred_times);
        update_post_meta($request_id, 'viewed_by_tutor', '0');
    } else {
        wp_die('Error creating reschedule request: ' . $request_id->get_error_message());
    }

    // Redirect
    $redirect_url = add_query_arg(['page' => 'student-dashboard', 'active_tab' => 'requests', 'reschedule_status' => 'submitted'], admin_url('admin.php?page=student-dashboard')); // Adjust URL
     wp_safe_redirect(wp_get_referer() ?: $redirect_url);
    exit;
}

// Student updates their own request
function handle_student_update_request() {
    $request_id = intval($_POST['request_id']);
    $current_student_id = get_current_user_id();
    $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

    // Verify ownership
    $student_id = get_post_meta($request_id, 'student_id', true);
    $request_type = get_post_meta($request_id, 'request_type', true);

    if ($request_type !== 'student_reschedule' || $student_id != $current_student_id) {
         wp_die('Invalid request or permission denied.');
     }
     
     // Don't allow editing if not pending?
     $status = get_post_meta($request_id, 'status', true);
     if ($status !== 'pending') {
         wp_die('Cannot edit a request that is not pending.');
     }

    // Collect preferred times (ensure names match the edit modal: edit_preferred_date_1, etc.)
    $preferred_times = [];
    for ($i = 1; $i <= 3; $i++) {
        $date = isset($_POST['preferred_date_' . $i]) ? sanitize_text_field($_POST['preferred_date_' . $i]) : ''; // Check if edit modal uses different prefix
        $time = isset($_POST['preferred_time_' . $i]) ? sanitize_text_field($_POST['preferred_time_' . $i]) : '';
        if (!empty($date) && !empty($time)) {
            $preferred_times[] = ['date' => $date, 'time' => $time];
        }
    }
    
     // Require at least one preferred time
    if (empty($preferred_times)) {
        wp_die('Please provide at least one preferred alternative time.');
    }

    // Update meta
    update_post_meta($request_id, 'reason', $reason);
    update_post_meta($request_id, 'preferred_times', $preferred_times);
     // Mark as unread for tutor again?
     update_post_meta($request_id, 'viewed_by_tutor', '0');

    // Redirect
    $redirect_url = add_query_arg(['page' => 'student-dashboard', 'active_tab' => 'requests', 'reschedule_status' => 'updated'], admin_url('admin.php?page=student-dashboard')); // Adjust URL
     wp_safe_redirect(wp_get_referer() ?: $redirect_url);
    exit;
}

// Student deletes their own request (handles form submission)
function handle_student_delete_request() {
    $request_id = intval($_POST['request_id']);
    $current_student_id = get_current_user_id();

    // Verify ownership
    $student_id = get_post_meta($request_id, 'student_id', true);
    $request_type = get_post_meta($request_id, 'request_type', true);

    // Allow deleting own requests regardless of type? Only student_reschedule?
     if ($student_id != $current_student_id) {
         wp_die('Permission denied.');
     }

    $result = wp_delete_post($request_id, true); // Force delete

    // Redirect
    $status = $result ? 'deleted' : 'delete_failed';
    $redirect_url = add_query_arg(['page' => 'student-dashboard', 'active_tab' => 'requests', 'reschedule_status' => $status], admin_url('admin.php?page=student-dashboard')); // Adjust URL
     wp_safe_redirect(wp_get_referer() ?: $redirect_url);
    exit;
}

// Student accepts tutor's request
function handle_student_confirm_tutor_request() {
    $request_id = intval($_POST['request_id']);
    $current_student_id = get_current_user_id();

    // Verify student owns this request
    $student_id = get_post_meta($request_id, 'student_id', true);
    $request_type = get_post_meta($request_id, 'request_type', true);

     if ($request_type !== 'tutor_reschedule' || $student_id != $current_student_id) {
         wp_die('Invalid request or permission denied.');
     }

    // Update the tutor request status
    update_post_meta($request_id, 'status', 'confirmed'); // Or 'accepted'?
    update_post_meta($request_id, 'viewed_by_student', '1'); // Mark as viewed

    // Get details for notification
    $tutor_id = get_post_meta($request_id, 'tutor_id', true);
    $tutor_name = get_post_meta($request_id, 'tutor_name', true);
    $original_date = get_post_meta($request_id, 'original_date', true);
    $original_time = get_post_meta($request_id, 'original_time', true);
    $preferred_times = get_post_meta($request_id, 'preferred_times', true); // Tutor's proposed times
    
    // The accepted time is the first one proposed by the tutor
    $new_date = !empty($preferred_times[0]['date']) ? $preferred_times[0]['date'] : $original_date;
    $new_time = !empty($preferred_times[0]['time']) ? $preferred_times[0]['time'] : $original_time;

    // Create notification for the tutor
    $notification_post = [
        'post_title'   => 'Student Accepted Your Reschedule Request',
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'progress_report',
    ];
    $notification_id = wp_insert_post($notification_post);

    if (!is_wp_error($notification_id)) {
        update_post_meta($notification_id, 'request_type', 'student_accepted'); // Type for tutor notification
        update_post_meta($notification_id, 'status', 'pending'); // Tutor needs to see this
        update_post_meta($notification_id, 'student_id', $current_student_id);
        update_post_meta($notification_id, 'student_name', wp_get_current_user()->user_login);
        update_post_meta($notification_id, 'tutor_id', $tutor_id);
        update_post_meta($notification_id, 'tutor_name', $tutor_name);
        update_post_meta($notification_id, 'original_request_id', $request_id); // Link to tutor's request
        update_post_meta($notification_id, 'original_date', $original_date);
        update_post_meta($notification_id, 'original_time', $original_time);
        update_post_meta($notification_id, 'new_date', $new_date); // Confirmed date
        update_post_meta($notification_id, 'new_time', $new_time); // Confirmed time
        update_post_meta($notification_id, 'viewed_by_tutor', '0');
    }

    // Redirect
    $redirect_url = add_query_arg(['page' => 'student-dashboard', 'active_tab' => 'requests', 'reschedule_status' => 'confirmed'], admin_url('admin.php?page=student-dashboard')); // Adjust URL
     wp_safe_redirect(wp_get_referer() ?: $redirect_url);
    exit;
}

// Student declines tutor's request
function handle_student_decline_tutor_request() {
    $request_id = intval($_POST['request_id']);
    $current_student_id = get_current_user_id();

    // Verify student owns this request
    $student_id = get_post_meta($request_id, 'student_id', true);
    $request_type = get_post_meta($request_id, 'request_type', true);

     if ($request_type !== 'tutor_reschedule' || $student_id != $current_student_id) {
         wp_die('Invalid request or permission denied.');
     }

    // Update the tutor request status
    update_post_meta($request_id, 'status', 'declined');
    update_post_meta($request_id, 'viewed_by_student', '1');

    // Get details for notification
    $tutor_id = get_post_meta($request_id, 'tutor_id', true);
    $tutor_name = get_post_meta($request_id, 'tutor_name', true);
    $original_date = get_post_meta($request_id, 'original_date', true);
    $original_time = get_post_meta($request_id, 'original_time', true);
    
    // Create notification for the tutor
    $notification_post = [
        'post_title'   => 'Student Declined Your Reschedule Request',
        'post_content' => '', // Could add a reason field later if needed
        'post_status'  => 'publish',
        'post_type'    => 'progress_report',
    ];
    $notification_id = wp_insert_post($notification_post);

    if (!is_wp_error($notification_id)) {
        update_post_meta($notification_id, 'request_type', 'student_declined'); // Type for tutor notification
        update_post_meta($notification_id, 'status', 'pending'); // Tutor needs to see this
        update_post_meta($notification_id, 'student_id', $current_student_id);
        update_post_meta($notification_id, 'student_name', wp_get_current_user()->user_login);
        update_post_meta($notification_id, 'tutor_id', $tutor_id);
        update_post_meta($notification_id, 'tutor_name', $tutor_name);
        update_post_meta($notification_id, 'original_request_id', $request_id); // Link to tutor's request
        update_post_meta($notification_id, 'original_date', $original_date);
        update_post_meta($notification_id, 'original_time', $original_time);
        update_post_meta($notification_id, 'viewed_by_tutor', '0');
    }

    // Redirect
    $redirect_url = add_query_arg(['page' => 'student-dashboard', 'active_tab' => 'requests', 'reschedule_status' => 'declined'], admin_url('admin.php?page=student-dashboard')); // Adjust URL
     wp_safe_redirect(wp_get_referer() ?: $redirect_url);
    exit;
}

// Student selects an alternative time proposed by tutor (tutor_unavailable flow)
function handle_student_select_tutor_alternative() {
    $alternative_request_id = intval($_POST['request_id']); // This is the 'tutor_unavailable' request
    $selected_index = isset($_POST['selected_alternative']) ? intval($_POST['selected_alternative']) : -1;
    $current_student_id = get_current_user_id();

    // Verify student owns this alternative request
    $student_id = get_post_meta($alternative_request_id, 'student_id', true);
    $request_type = get_post_meta($alternative_request_id, 'request_type', true);

    if ($request_type !== 'tutor_unavailable' || $student_id != $current_student_id || $selected_index < 0) {
        wp_die('Invalid request, permission denied, or no alternative selected.');
    }

    // Update the alternative request status
    update_post_meta($alternative_request_id, 'status', 'confirmed');
    update_post_meta($alternative_request_id, 'selected_alternative', $selected_index); // Record choice
    update_post_meta($alternative_request_id, 'viewed_by_student', '1');

    // Get details for notification
    $tutor_id = get_post_meta($alternative_request_id, 'tutor_id', true);
    $tutor_name = get_post_meta($alternative_request_id, 'tutor_name', true);
    $original_request_id = get_post_meta($alternative_request_id, 'original_request_id', true); // Link back to the student's initial request
    $alternatives = get_post_meta($alternative_request_id, 'alternatives', true);
    $selected_alternative = $alternatives[$selected_index];
    
    // Get original lesson details from the *student's* initial request
    $original_date = get_post_meta($original_request_id, 'original_date', true);
    $original_time = get_post_meta($original_request_id, 'original_time', true);

    // Create notification for the tutor
    $notification_post = [
        'post_title'   => 'Student Selected Alternative Time',
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'progress_report',
    ];
    $notification_id = wp_insert_post($notification_post);

    if (!is_wp_error($notification_id)) {
        update_post_meta($notification_id, 'request_type', 'student_accepted_alternative'); // Type for tutor notification
        update_post_meta($notification_id, 'status', 'pending'); // Tutor needs to see this
        update_post_meta($notification_id, 'student_id', $current_student_id);
        update_post_meta($notification_id, 'student_name', wp_get_current_user()->user_login);
        update_post_meta($notification_id, 'tutor_id', $tutor_id);
        update_post_meta($notification_id, 'tutor_name', $tutor_name);
        update_post_meta($notification_id, 'original_request_id', $alternative_request_id); // Link to the alternative request
        update_post_meta($notification_id, 'original_date', $original_date); // Original lesson
        update_post_meta($notification_id, 'original_time', $original_time); // Original lesson
        update_post_meta($notification_id, 'new_date', $selected_alternative['date']); // Confirmed date
        update_post_meta($notification_id, 'new_time', $selected_alternative['time']); // Confirmed time
        update_post_meta($notification_id, 'viewed_by_tutor', '0');
    }

    // Redirect
    $redirect_url = add_query_arg(['page' => 'student-dashboard', 'active_tab' => 'requests', 'reschedule_status' => 'alternative_selected'], admin_url('admin.php?page=student-dashboard')); // Adjust URL
     wp_safe_redirect(wp_get_referer() ?: $redirect_url);
    exit;
}

// Student marks themselves unavailable for ALL tutor alternatives
function handle_student_unavailable_all_alternatives() {
    $alternative_request_id = intval($_POST['request_id']); // This is the 'tutor_unavailable' request
    $current_student_id = get_current_user_id();

    // Verify student owns this alternative request
    $student_id = get_post_meta($alternative_request_id, 'student_id', true);
    $request_type = get_post_meta($alternative_request_id, 'request_type', true);

    if ($request_type !== 'tutor_unavailable' || $student_id != $current_student_id) {
        wp_die('Invalid request or permission denied.');
    }

    // Update the alternative request status
    update_post_meta($alternative_request_id, 'status', 'unavailable'); // Student is unavailable for all options
    update_post_meta($alternative_request_id, 'viewed_by_student', '1');

    // Get details for notification
    $tutor_id = get_post_meta($alternative_request_id, 'tutor_id', true);
    $tutor_name = get_post_meta($alternative_request_id, 'tutor_name', true);
    $original_request_id = get_post_meta($alternative_request_id, 'original_request_id', true);
    $original_date = get_post_meta($original_request_id, 'original_date', true); // Original lesson date
    $original_time = get_post_meta($original_request_id, 'original_time', true);

    // Create notification for the tutor
    $notification_post = [
        'post_title'   => 'Student Unavailable for All Alternative Times',
        'post_content' => 'Student '. wp_get_current_user()->user_login . ' indicated they are unavailable for all suggested alternative times for the lesson originally on ' . $original_date,
        'post_status'  => 'publish',
        'post_type'    => 'progress_report',
    ];
    $notification_id = wp_insert_post($notification_post);

    if (!is_wp_error($notification_id)) {
        update_post_meta($notification_id, 'request_type', 'student_unavailable_all'); // Type for tutor notification
        update_post_meta($notification_id, 'status', 'pending'); // Tutor needs to see this
        update_post_meta($notification_id, 'student_id', $current_student_id);
        update_post_meta($notification_id, 'student_name', wp_get_current_user()->user_login);
        update_post_meta($notification_id, 'tutor_id', $tutor_id);
        update_post_meta($notification_id, 'tutor_name', $tutor_name);
        update_post_meta($notification_id, 'original_request_id', $alternative_request_id); // Link to the alternative request
         update_post_meta($notification_id, 'original_date', $original_date); 
         update_post_meta($notification_id, 'original_time', $original_time);
        update_post_meta($notification_id, 'viewed_by_tutor', '0');
    }

    // Redirect
    $redirect_url = add_query_arg(['page' => 'student-dashboard', 'active_tab' => 'requests', 'reschedule_status' => 'unavailable_all'], admin_url('admin.php?page=student-dashboard')); // Adjust URL
     wp_safe_redirect(wp_get_referer() ?: $redirect_url);
    exit;
}

// Student marks themselves unavailable for a specific tutor-initiated request and provides alternatives
function handle_student_unavailable_provide_alternatives() {
    $request_id = intval($_POST['request_id']); // This is the original 'tutor_reschedule' request
    $current_student_id = get_current_user_id();

    // Verify student owns this request
    $student_id = get_post_meta($request_id, 'student_id', true);
    $request_type = get_post_meta($request_id, 'request_type', true);

    if ($request_type !== 'tutor_reschedule' || $student_id != $current_student_id) {
        wp_die('Invalid request or permission denied.');
    }

    // Update the tutor's original request status
    update_post_meta($request_id, 'status', 'student_unavailable'); // New status indicating student counter-proposed
    update_post_meta($request_id, 'viewed_by_student', '1');

    // Get details for notification
    $tutor_id = get_post_meta($request_id, 'tutor_id', true);
    $tutor_name = get_post_meta($request_id, 'tutor_name', true);
    $original_date = get_post_meta($request_id, 'original_date', true);
    $original_time = get_post_meta($request_id, 'original_time', true);

    // Collect alternative times provided by student (check names used in student-requests.php modal)
    $alternatives = [];
    for ($i = 1; $i <= 3; $i++) {
         $alt_date = isset($_POST['alt_date_' . $i]) ? sanitize_text_field($_POST['alt_date_' . $i]) : ''; // Assuming names like alt_date_1
         $alt_time = isset($_POST['alt_time_' . $i]) ? sanitize_text_field($_POST['alt_time_' . $i]) : '';
        if (!empty($alt_date) && !empty($alt_time)) {
            $alternatives[] = ['date' => $alt_date, 'time' => $alt_time];
        }
    }
    
    // Require at least one alternative time from student
    if (empty($alternatives)) {
        wp_die('Please provide at least one alternative time.');
    }

    // Create notification post for the tutor
    $notification_post = [
        'post_title'   => 'Student Unavailable - Alternative Times Provided',
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'progress_report',
    ];
    $notification_id = wp_insert_post($notification_post);

    if (!is_wp_error($notification_id)) {
        update_post_meta($notification_id, 'request_type', 'reschedule_alternatives'); // Type for tutor notification
        update_post_meta($notification_id, 'status', 'pending'); // Tutor needs to act
        update_post_meta($notification_id, 'student_id', $current_student_id);
        update_post_meta($notification_id, 'student_name', wp_get_current_user()->user_login);
        update_post_meta($notification_id, 'tutor_id', $tutor_id);
        update_post_meta($notification_id, 'tutor_name', $tutor_name);
        update_post_meta($notification_id, 'original_request_id', $request_id); // Link to tutor's original request
        update_post_meta($notification_id, 'original_date', $original_date);
        update_post_meta($notification_id, 'original_time', $original_time);
        update_post_meta($notification_id, 'alternatives', $alternatives); // Store student's proposed alternatives
        update_post_meta($notification_id, 'viewed_by_tutor', '0');
    }

    // Redirect
    $redirect_url = add_query_arg(['page' => 'student-dashboard', 'active_tab' => 'requests', 'reschedule_status' => 'alternatives_provided'], admin_url('admin.php?page=student-dashboard')); // Adjust URL
     wp_safe_redirect(wp_get_referer() ?: $redirect_url);
    exit;
}

?> 