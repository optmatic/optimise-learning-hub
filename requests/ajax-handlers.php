<?php
/**
 * AJAX Handlers for Reschedule Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Ensure helper functions are available
require_once __DIR__ . '/request-functions.php'; 

// --- Common AJAX Handlers ---

/**
 * AJAX handler to get preferred times for a specific request.
 * Used by both students (editing their request) and tutors (viewing student request).
 */
function get_preferred_times_ajax() {
    check_ajax_referer('reschedule_request_nonce', 'nonce'); // Add nonce check

    if (!isset($_POST['request_id'])) {
        wp_send_json_error(['message' => 'Missing request ID.']);
    }

    $request_id = intval($_POST['request_id']);
    $request = get_post($request_id);

    if (!$request || $request->post_type !== 'progress_report') {
        wp_send_json_error(['message' => 'Invalid request ID.']);
    }

    // Security Check: Ensure the current user has permission to view these times.
    // Students can view their own requests, tutors can view requests assigned to them.
    $current_user_id = get_current_user_id();
    $student_id = get_post_meta($request_id, 'student_id', true);
    $tutor_id = get_post_meta($request_id, 'tutor_id', true);
    $tutor_name = get_post_meta($request_id, 'tutor_name', true); // Fallback

    $can_view = false;
    if (current_user_can('student') && $student_id == $current_user_id) {
        $can_view = true;
    } elseif (current_user_can('tutor') && ($tutor_id == $current_user_id || $tutor_name == wp_get_current_user()->user_login)) {
         $can_view = true;
    } 
    // Add capability check for admins/other roles if necessary
    // elseif (current_user_can('manage_options')) { $can_view = true; }

    if (!$can_view) {
        wp_send_json_error(['message' => 'Permission denied.']);
    }

    $preferred_times = get_post_meta($request_id, 'preferred_times', true);
    
    // Ensure preferred_times is an array
    if (!is_array($preferred_times)) {
        $preferred_times = [];
    }
    
    wp_send_json_success(['preferred_times' => $preferred_times]);
}
add_action('wp_ajax_get_preferred_times', 'get_preferred_times_ajax');


// --- Tutor Specific AJAX Handlers ---

/**
 * AJAX handler for tutors to check for incoming student reschedule requests
 * and student alternative time suggestions.
 */
function check_tutor_incoming_requests_ajax() {
    check_ajax_referer('check_tutor_requests_nonce', 'nonce');

    $tutor_id = get_current_user_id();
    if (!$tutor_id || !current_user_can('tutor')) {
        wp_send_json_error(['message' => 'Invalid tutor or permissions.']);
        return;
    }

    // 1. Get pending student-initiated requests
    $student_requests = get_reschedule_requests('student_reschedule', $tutor_id, 'tutor', 'pending');
    $pending_student_request_count = count($student_requests);

    // 2. Get pending student alternative time suggestions
    // Uses 'reschedule_alternatives' type, check for viewed status
    $alternatives_meta_query = [
        [
            'relation' => 'OR', // Not viewed yet
            [ 'key' => 'viewed_by_tutor', 'compare' => 'NOT EXISTS' ],
            [ 'key' => 'viewed_by_tutor', 'value' => '1', 'compare' => '!=' ]
        ]
    ];
    $alternative_suggestions = get_reschedule_requests('reschedule_alternatives', $tutor_id, 'tutor', 'pending', $alternatives_meta_query);
    $pending_alternatives_count = count($alternative_suggestions);

    $total_pending_count = $pending_student_request_count + $pending_alternatives_count;

    // Generate HTML for the notifications section
    ob_start();
    if ($pending_student_request_count > 0 || $pending_alternatives_count > 0) {
        ?>
        <div class="alert alert-info mb-4" id="tutorRequestNotifications">
             <h5><i class="fas fa-bell me-2"></i>Notifications</h5>
             <ul class="mb-0" style="list-style: none; padding-left: 0;">
                 <?php if ($pending_student_request_count > 0): ?>
                     <li>
                         <i class="fas fa-arrow-right me-1 text-warning"></i> You have <strong><?php echo $pending_student_request_count; ?></strong> pending reschedule request<?php echo ($pending_student_request_count > 1 ? 's' : ''); ?> from students.
                         <a href="#incomingRequestsSection" class="btn btn-sm btn-primary ms-2">View</a>
                     </li>
                 <?php endif; ?>
                 <?php if ($pending_alternatives_count > 0): ?>
                     <li class="mt-2"> 
                         <i class="fas fa-exchange-alt me-1 text-primary"></i> You have <strong><?php echo $pending_alternatives_count; ?></strong> alternative time suggestion<?php echo ($pending_alternatives_count > 1 ? 's' : ''); ?> from students.
                         <a href="#alternativeAccordion" class="btn btn-sm btn-primary ms-2">View</a>
                     </li>
                 <?php endif; ?>
             </ul>
        </div>
        <?php
    }
    $notifications_html = ob_get_clean();

    // Generate HTML for the incoming requests table body
    ob_start();
    if (!empty($student_requests)) {
        foreach ($student_requests as $request) {
            $request_id = $request->ID;
            $student_id = get_post_meta($request_id, 'student_id', true);
            $student_name = get_post_meta($request_id, 'student_name', true);
            $original_date = get_post_meta($request_id, 'original_date', true);
            $original_time = get_post_meta($request_id, 'original_time', true);
            $request_date = get_the_date('M j, Y', $request_id);
            $reason = get_post_meta($request_id, 'reason', true);
            $preferred_times = get_post_meta($request_id, 'preferred_times', true);
            $status = get_post_meta($request_id, 'status', true); 
            $student_display_name = get_student_display_name($student_name ?: get_user_by('id', $student_id)->user_login);
            ?>
            <tr>
                <td><?php echo esc_html($request_date); ?></td>
                <td><?php echo esc_html(format_datetime($original_date, $original_time)); ?></td>
                <td>
                    <?php 
                    if (!empty($preferred_times) && is_array($preferred_times)) {
                        foreach ($preferred_times as $index => $time) {
                            if (!empty($time['date']) && !empty($time['time'])) {
                                echo 'Option ' . ($index + 1) . ': ' . esc_html(format_datetime($time['date'], $time['time'])) . '<br>';
                            }
                        }
                    } else {
                        echo 'No preferred times specified';
                    }
                    ?>
                </td>
                <td><?php echo esc_html($student_display_name); ?></td>
                <td>
                    <?php 
                    if (!empty($reason)) {
                        $truncated_reason = strlen($reason) > 30 ? substr($reason, 0, 30) . '...' : $reason;
                        echo '<span class="reason-text" style="cursor: pointer; color: #0d6efd;" 
                               data-bs-toggle="modal" data-bs-target="#reasonModal" 
                               data-reason="' . esc_attr($reason) . '" 
                               data-tooltip="tooltip" title="Click to view full reason">' . esc_html($truncated_reason) . '</span>';
                    } else {
                        echo '<em>No reason provided</em>';
                    }
                    ?>
                </td>
                 <td><?php echo get_status_badge($status); ?></td>
                <td>
                    <?php if ($status == 'pending'): ?>
                        <form method="post" class="d-inline ajax-confirm-form" data-action="confirm_reschedule" data-request-id="<?php echo $request_id; ?>">
                             <?php wp_nonce_field('confirm_reschedule_' . $request_id); ?>
                            <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                             <button type="submit" class="btn btn-sm btn-success me-1">Accept</button>
                         </form>
                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                              data-bs-target="#unavailableModal" 
                              data-request-id="<?php echo $request_id; ?>"
                              data-student-id="<?php echo $student_id; ?>"
                              data-student-name="<?php echo esc_attr($student_display_name); ?>"
                              data-original-date="<?php echo esc_attr($original_date); ?>"
                              data-original-time="<?php echo esc_attr($original_time); ?>">
                              Unavailable
                        </button>
                    <?php else: ?>
                        <span class="text-muted">No actions available</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php 
        }
    } else {
        echo '<tr><td colspan="7"><p>No incoming reschedule requests from students at this time.</p></td></tr>';
    }
    $incoming_requests_html = ob_get_clean();

    wp_send_json_success([
        'count' => $total_pending_count,
        'pendingStudentRequestCount' => $pending_student_request_count,
        'pendingAlternativesCount' => $pending_alternatives_count,
        'notificationsHtml' => $notifications_html,
        'incomingRequestsHtml' => $incoming_requests_html // Send table body HTML
    ]);
}
add_action('wp_ajax_check_tutor_incoming_requests', 'check_tutor_incoming_requests_ajax');

/**
 * AJAX handler for tutors to mark alternative suggestions as viewed.
 */
function mark_alternatives_viewed_ajax() {
    check_ajax_referer('mark_alternatives_viewed_nonce', 'nonce');

    $tutor_id = get_current_user_id();
    if (!$tutor_id || !current_user_can('tutor')) {
        wp_send_json_error(['message' => 'Invalid tutor or permissions.']);
        return;
    }

    if (isset($_POST['request_ids']) && is_array($_POST['request_ids'])) {
        $request_ids = array_map('intval', $_POST['request_ids']);
        $marked_count = 0;
        foreach ($request_ids as $request_id) {
            // Verify tutor owns this request before marking
            $req_tutor_id = get_post_meta($request_id, 'tutor_id', true);
            $req_tutor_name = get_post_meta($request_id, 'tutor_name', true);
             if ($req_tutor_id == $tutor_id || $req_tutor_name == wp_get_current_user()->user_login) {
                 update_post_meta($request_id, 'viewed_by_tutor', '1');
                 $marked_count++;
             }
        }
        wp_send_json_success(['message' => $marked_count . ' items marked as viewed.']);
    } else {
        wp_send_json_error(['message' => 'No request IDs provided.']);
    }
}
add_action('wp_ajax_mark_alternatives_viewed', 'mark_alternatives_viewed_ajax');


/**
 * AJAX handler for Tutors to delete their own reschedule requests.
 */
function delete_tutor_request_ajax() {
    check_ajax_referer('delete_tutor_request_nonce', 'nonce');

    if (!isset($_POST['request_id'])) {
        wp_send_json_error(['message' => 'Missing request ID.']);
    }

    $request_id = intval($_POST['request_id']);
    $current_user_id = get_current_user_id();

    if (!$current_user_id || !current_user_can('tutor')) {
        wp_send_json_error(['message' => 'Permission denied.']);
    }

    $tutor_id = get_post_meta($request_id, 'tutor_id', true);
    $tutor_name = get_post_meta($request_id, 'tutor_name', true);

    // Verify the request belongs to the current tutor
    if ($tutor_id == $current_user_id || $tutor_name == wp_get_current_user()->user_login) {
        $result = wp_delete_post($request_id, true); // true forces delete, false moves to trash
        if ($result) {
            wp_send_json_success(['message' => 'Request deleted successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete request.']);
        }
    } else {
        wp_send_json_error(['message' => 'You do not have permission to delete this request.']);
    }
}
add_action('wp_ajax_delete_tutor_request', 'delete_tutor_request_ajax');


// --- Student Specific AJAX Handlers ---

/**
 * AJAX handler for students to check for incoming tutor reschedule requests
 * and status changes on their own requests.
 */
function check_student_incoming_requests_ajax() {
    error_log('[AJAX STUDENT] check_student_incoming_requests_ajax started');

    // Verify nonce FIRST
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'check_student_incoming_requests')) {
        error_log('[AJAX STUDENT ERROR] Nonce verification failed.');
        status_header(403); // Forbidden
        wp_send_json_error(['message' => 'Nonce verification failed. Please refresh the page and try again.'], 403);
        // exit after wp_send_json_error is automatic
    }
    error_log('[AJAX STUDENT] Nonce verified.');

    // Check user role and ID AFTER nonce
    if (!is_user_logged_in() || !current_user_can('student')) {
        error_log('[AJAX STUDENT ERROR] User not logged in or not a student.');
        status_header(403);
        wp_send_json_error(['message' => 'Access denied. You must be logged in as a student.'], 403);
    }
    $student_id = get_current_user_id();
    error_log('[AJAX STUDENT] Student ID: ' . $student_id);


    // --- Query 1: Pending Tutor-Initiated Requests ---
    $tutor_requests_args = [
        'post_type'      => 'progress_report',
        'posts_per_page' => -1,
        'post_status'    => 'publish', // Ensure we query published posts
        'meta_query'     => [
            'relation' => 'AND',
            ['key' => 'student_id', 'value' => $student_id, 'compare' => '='],
            ['key' => 'request_type', 'value' => 'tutor_reschedule', 'compare' => '='],
            ['key' => 'status', 'value' => 'pending', 'compare' => '=']
            // We don't need 'viewed_by_student' here if we just want the count of pending ones
        ],
        'fields'         => 'ids'
    ];
    error_log('[AJAX STUDENT] Querying pending tutor requests...');
    $pending_tutor_requests = get_posts($tutor_requests_args);
    if (is_wp_error($pending_tutor_requests)) {
         error_log('[AJAX STUDENT ERROR] WP_Error querying pending tutor requests: ' . $pending_tutor_requests->get_error_message());
        wp_send_json_error(['message' => 'Database error fetching requests.'], 500);
    }
    $pending_tutor_request_count = is_array($pending_tutor_requests) ? count($pending_tutor_requests) : 0;
    error_log('[AJAX STUDENT] Pending tutor requests count: ' . $pending_tutor_request_count);


    // --- Query 2: Unread Status Updates on Student's Outgoing Requests ---
    $status_changes_args = [
        'post_type'      => 'progress_report',
        'posts_per_page' => -1,
        'author'         => $student_id, // Requests initiated BY the student
        'post_status'    => 'publish',
        'meta_query'     => [
            'relation' => 'AND',
            ['key' => 'request_type', 'value' => 'student_reschedule', 'compare' => '='],
            // Check for statuses that require student notification (approved, rejected, alternatives_proposed by tutor)
            ['key' => 'status', 'value' => ['approved', 'rejected', 'alternatives_proposed'], 'compare' => 'IN'],
             [
                 'relation' => 'OR', // Not viewed yet
                 [ 'key' => 'viewed_by_student', 'compare' => 'NOT EXISTS' ],
                 [ 'key' => 'viewed_by_student', 'value' => '1', 'compare' => '!=' ]
             ]
        ],
        'fields'         => 'ids'
    ];
    error_log('[AJAX STUDENT] Querying unread status changes...');
    $unread_status_changes = get_posts($status_changes_args);
     if (is_wp_error($unread_status_changes)) {
         error_log('[AJAX STUDENT ERROR] WP_Error querying unread status changes: ' . $unread_status_changes->get_error_message());
        wp_send_json_error(['message' => 'Database error fetching updates.'], 500);
    }
    $unread_status_changes_count = is_array($unread_status_changes) ? count($unread_status_changes) : 0;
    error_log('[AJAX STUDENT] Unread status changes count: ' . $unread_status_changes_count);

     // --- Query 3: Unread Alternative Proposals from Tutor (response to student unavailability) ---
     $tutor_alternatives_args = [
        'post_type'      => 'progress_report',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [
            'relation' => 'AND',
            ['key' => 'student_id', 'value' => $student_id, 'compare' => '='],
            // This type is created when a tutor responds to a student's 'unavailable' action
            ['key' => 'request_type', 'value' => 'tutor_alternatives', 'compare' => '='],
            ['key' => 'status', 'value' => 'pending', 'compare' => '='], // Tutor alternatives are pending student review
             [
                 'relation' => 'OR', // Not viewed yet
                 [ 'key' => 'viewed_by_student', 'compare' => 'NOT EXISTS' ],
                 [ 'key' => 'viewed_by_student', 'value' => '1', 'compare' => '!=' ]
             ]
        ],
        'fields'         => 'ids'
    ];
     error_log('[AJAX STUDENT] Querying unread tutor alternatives...');
     $unread_tutor_alternatives = get_posts($tutor_alternatives_args);
     if (is_wp_error($unread_tutor_alternatives)) {
         error_log('[AJAX STUDENT ERROR] WP_Error querying unread tutor alternatives: ' . $unread_tutor_alternatives->get_error_message());
         wp_send_json_error(['message' => 'Database error fetching alternatives.'], 500);
     }
     $unread_tutor_alternatives_count = is_array($unread_tutor_alternatives) ? count($unread_tutor_alternatives) : 0;
     error_log('[AJAX STUDENT] Unread tutor alternatives count: ' . $unread_tutor_alternatives_count);


    // Total count for badge update (pending incoming requests + unread status changes + unread tutor alternatives)
    $total_unread_count = $pending_tutor_request_count + $unread_status_changes_count + $unread_tutor_alternatives_count;
    error_log('[AJAX STUDENT] Total unread count: ' . $total_unread_count);

    // Prepare data for JSON response
    $response_data = [
        // 'success' => true, // Implicitly added by wp_send_json_success
        'data' => [
            'count' => $total_unread_count,
            'pendingTutorRequestCount' => $pending_tutor_request_count,
            'unreadStatusChangeCount' => $unread_status_changes_count,
            'unreadTutorAlternativesCount' => $unread_tutor_alternatives_count
            // Add HTML snippets here if needed for dynamic updates, similar to tutor AJAX
        ]
    ];

    error_log('[AJAX STUDENT] Sending JSON success response.');
    wp_send_json_success($response_data); // Send success implicitly adds success:true if not present

    // No exit needed after wp_send_json
}
add_action('wp_ajax_check_student_incoming_requests', 'check_student_incoming_requests_ajax');

/**
 * AJAX handler for students to mark tutor alternative suggestions as viewed.
 */
function mark_tutor_alternatives_viewed_ajax() {
    check_ajax_referer('mark_tutor_alternatives_viewed_nonce', 'nonce');

    $student_id = get_current_user_id();
    if (!$student_id || !current_user_can('student')) {
        wp_send_json_error(['message' => 'Invalid student or permissions.']);
        return;
    }

    if (isset($_POST['request_ids']) && is_array($_POST['request_ids'])) {
        $request_ids = array_map('intval', $_POST['request_ids']);
        $marked_count = 0;
        foreach ($request_ids as $request_id) {
            // Verify student owns this request before marking
            $req_student_id = get_post_meta($request_id, 'student_id', true);
            if ($req_student_id == $student_id) {
                update_post_meta($request_id, 'viewed_by_student', '1');
                $marked_count++;
            }
        }
        wp_send_json_success(['message' => $marked_count . ' items marked as viewed.']);
    } else {
        wp_send_json_error(['message' => 'No request IDs provided.']);
    }
}
add_action('wp_ajax_mark_tutor_alternatives_viewed', 'mark_tutor_alternatives_viewed_ajax');

/**
 * AJAX handler for Students to delete their own reschedule requests.
 */
function delete_student_request_ajax() {
    check_ajax_referer('delete_student_request_nonce', 'nonce');

    if (!isset($_POST['request_id'])) {
        wp_send_json_error(['message' => 'Missing request ID.']);
    }

    $request_id = intval($_POST['request_id']);
    $current_user_id = get_current_user_id();

    if (!$current_user_id || !current_user_can('student')) {
        wp_send_json_error(['message' => 'Permission denied.']);
    }

    $student_id = get_post_meta($request_id, 'student_id', true);

    // Verify the request belongs to the current student
    if ($student_id == $current_user_id) {
        $result = wp_delete_post($request_id, true);
        if ($result) {
            wp_send_json_success(['message' => 'Request deleted successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete request.']);
        }
    } else {
        wp_send_json_error(['message' => 'You do not have permission to delete this request.']);
    }
}
add_action('wp_ajax_delete_student_request', 'delete_student_request_ajax');

?> 