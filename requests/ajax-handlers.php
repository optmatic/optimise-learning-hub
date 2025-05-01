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
        wp_die(); // Explicitly die after sending error
    }
    error_log('[AJAX STUDENT] Nonce verified.');

    // Check user role and ID AFTER nonce
    if (!is_user_logged_in() || !current_user_can('student')) {
        error_log('[AJAX STUDENT ERROR] User not logged in or not a student.');
        status_header(403);
        wp_send_json_error(['message' => 'Access denied. You must be logged in as a student.'], 403);
        wp_die(); // Explicitly die after sending error
    }
    $student_id = get_current_user_id();
    error_log('[AJAX STUDENT] Student ID: ' . $student_id);


    // --- Query 1: Pending Tutor-Initiated Requests ---
    $tutor_requests_args = [
        'post_type'      => 'progress_report',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [
            'relation' => 'AND',
            ['key' => 'student_id', 'value' => $student_id, 'compare' => '='],
            ['key' => 'request_type', 'value' => 'tutor_reschedule', 'compare' => '='],
            ['key' => 'status', 'value' => 'pending', 'compare' => '='],
             // Check if student has viewed it
             [
                 'relation' => 'OR',
                 [ 'key' => 'viewed_by_student', 'compare' => 'NOT EXISTS' ],
                 [ 'key' => 'viewed_by_student', 'value' => '1', 'compare' => '!=' ]
             ]
        ],
        'fields'         => 'ids'
    ];
    error_log('[AJAX STUDENT] Querying pending tutor requests...');
    $pending_tutor_requests = get_posts($tutor_requests_args);
    if (is_wp_error($pending_tutor_requests)) {
         error_log('[AJAX STUDENT ERROR] WP_Error querying pending tutor requests: ' . $pending_tutor_requests->get_error_message());
        wp_send_json_error(['message' => 'Database error fetching requests.'], 500);
        wp_die();
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
        wp_die();
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
         wp_die();
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
            // NOTE: We are NOT sending back HTML snippets here.
            // The JS in student-requests.js should handle updating the badge ONLY.
            // Displaying requests is handled by the PHP template (requests/student-requests.php).
        ]
    ];

    error_log('[AJAX STUDENT] Sending JSON success response.');
    wp_send_json_success($response_data); // Send success implicitly adds success:true if not present

    wp_die(); // Explicitly terminate script execution after sending JSON
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

// --- NEW Tutor AJAX Load Handlers ---

/**
 * AJAX handler for Tutors to load notifications.
 * Action: load_tutor_notifications
 * Nonce: check_tutor_notifications_nonce
 */
function load_tutor_notifications_ajax() {
    check_ajax_referer('check_tutor_notifications_nonce', 'nonce');

    $tutor_id = get_current_user_id();
    if (!$tutor_id || !current_user_can('tutor')) {
        wp_send_json_error(['message' => 'Invalid permissions.']);
        return;
    }

    // 1. Pending incoming requests from students (student_reschedule, status: pending)
    $pending_student_requests = get_reschedule_requests('student_reschedule', $tutor_id, 'tutor', 'pending');
    $pending_student_count = count($pending_student_requests);

    // 2. Pending alternative suggestions from students (student_unavailable, status: pending)
    // This means the student responded to a tutor's proposal indicating unavailability and proposed alternatives
    $pending_alternatives = get_reschedule_requests('student_unavailable', $tutor_id, 'tutor', 'pending'); 
    $pending_alternatives_count = count($pending_alternatives);

    $has_notifications = $pending_student_count > 0 || $pending_alternatives_count > 0;

    ob_start();
    if ($has_notifications) {
        ?>
        <div class="alert alert-info mb-4">
            <h5><i class="fas fa-bell me-2"></i>Notifications</h5>
            <ul class="mb-0 list-unstyled">
                <?php if ($pending_student_count > 0): ?>
                    <li class="mb-2">
                        <i class="fas fa-arrow-right me-1 text-warning"></i> You have <strong><?php echo $pending_student_count; ?></strong> pending request<?php echo ($pending_student_count > 1 ? 's' : ''); ?> from students.
                        <a href="#tutor-incoming-requests-container" class="btn btn-sm btn-outline-primary ms-2 scroll-to">View Incoming</a>
                    </li>
                <?php endif; ?>
                <?php if ($pending_alternatives_count > 0): ?>
                    <li>
                        <i class="fas fa-exchange-alt me-1 text-primary"></i> You have <strong><?php echo $pending_alternatives_count; ?></strong> alternative suggestion<?php echo ($pending_alternatives_count > 1 ? 's' : ''); ?> from students to review.
                        <a href="#tutor-student-alternatives-container" class="btn btn-sm btn-outline-primary ms-2 scroll-to">View Alternatives</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php
    } else {
        // Provide feedback even if there are no notifications
        echo '<div class="alert alert-light text-center"><i class="fas fa-check-circle text-success"></i> No pending notifications.</div>';
    }
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_load_tutor_notifications', 'load_tutor_notifications_ajax');

/**
 * AJAX handler for Tutors to load their outgoing requests.
 * Action: load_tutor_outgoing_requests
 * Nonce: tutor_load_requests_nonce
 */
function load_tutor_outgoing_requests_ajax() {
    check_ajax_referer('tutor_load_requests_nonce', 'nonce');

    $tutor_id = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : get_current_user_id();
    if (!$tutor_id || !current_user_can('tutor')) {
         wp_send_json_error(['message' => 'Invalid permissions.']);
         return;
    }

    // Fetch tutor-initiated requests (type: tutor_reschedule)
    $outgoing_requests = get_reschedule_requests('tutor_reschedule', $tutor_id, 'tutor'); // Fetch all relevant statuses

    ob_start();
    if (!empty($outgoing_requests)) {
        ?>
        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
             <table class="table table-striped table-hover request-table">
                 <thead class="table-light">
                     <tr>
                        <th>Student</th>
                        <th>Original Lesson</th>
                        <th>Proposed Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($outgoing_requests as $request): ?>
                         <?php
                         $request_id = $request->ID;
                         $student_id = get_post_meta($request_id, 'student_id', true);
                         $student_name = get_student_display_name($student_id);
                         $original_date = get_post_meta($request_id, 'original_date', true);
                         $original_time = get_post_meta($request_id, 'original_time', true);
                         $proposed_date = get_post_meta($request_id, 'proposed_date', true);
                         $proposed_time = get_post_meta($request_id, 'proposed_time', true);
                         $reason = get_post_meta($request_id, 'reason', true);
                         $status = get_post_meta($request_id, 'status', true);
                         $status_badge = get_status_badge($status);
                         $formatted_original = format_datetime($original_date, $original_time);
                         $formatted_proposed = format_datetime($proposed_date, $proposed_time);
                        
                         // Check if student responded 
                         $student_response_info = ''; 
                         if ($status === 'student_responded') { 
                            $alternative_request_query = new WP_Query([
                                 'post_type' => 'progress_report',
                                 'posts_per_page' => 1,
                                 'post_status' => 'publish', 
                                 'meta_query' => [
                                     'relation' => 'AND',
                                     ['key' => 'original_request_id', 'value' => $request_id, 'compare' => '='],
                                     ['key' => 'request_type', 'value' => 'student_unavailable', 'compare' => '='],
                                 ],
                                 'fields' => 'ids'
                             ]);
                            if ($alternative_request_query->have_posts()) {
                                $alt_request_id = $alternative_request_query->posts[0];
                                $alt_status = get_post_meta($alt_request_id, 'status', true);
                                if ($alt_status === 'pending') {
                                    $student_response_info = '<div class="mt-1 small"><span class="badge bg-info text-dark">Student Proposed Alternatives</span> <a href="#tutor-student-alternatives-container" class="btn btn-xs btn-outline-info ms-1 scroll-to">Review</a></div>'; // Use badge
                                } elseif ($alt_status === 'declined_by_student') {
                                    $student_response_info = '<div class="mt-1 small"><span class="badge bg-warning text-dark">Student Declined Alternatives</span></div>'; // Use badge
                                }
                            }
                         }
                         elseif ($status === 'declined'){ 
                              $response_reason = get_post_meta($request_id, 'response_reason', true);
                              if ($response_reason) {
                                  $student_response_info = '<div class="mt-1 small text-danger" data-bs-toggle="tooltip" title="' . esc_attr($response_reason) . '"><i class="fas fa-info-circle me-1"></i>Student Reason</div>';
                              }
                         }
                         ?>
                         <tr data-request-id="<?php echo esc_attr($request_id); ?>">
                             <td><?php echo esc_html($student_name); ?></td>
                             <td><?php echo esc_html($formatted_original); ?></td>
                             <td><?php echo esc_html($formatted_proposed); ?></td>
                             <td>
                                 <?php if (!empty($reason)):
                                     $trimmed_reason = wp_trim_words($reason, 10, '...');
                                 ?>
                                     <span data-bs-toggle="tooltip" title="<?php echo esc_attr($reason); ?>">
                                         <?php echo esc_html($trimmed_reason); ?>
                                     </span>
                                 <?php else: ?>
                                     <em>-</em>
                                 <?php endif; ?>
                             </td>
                             <td>
                                 <?php echo $status_badge; ?>
                                 <?php echo $student_response_info; ?>
                              </td>
                              <td class="request-actions text-center">
                                 <?php if ($status === 'pending'): ?>
                                     <button class="btn btn-sm btn-danger delete-tutor-request-btn" 
                                             data-request-id="<?php echo esc_attr($request_id); ?>" 
                                             data-nonce="<?php echo wp_create_nonce('tutor_delete_request_' . $request_id); ?>" 
                                             data-bs-toggle="tooltip" title="Cancel Request">
                                         <i class="fas fa-trash-alt"></i> <span class="d-none d-md-inline">Cancel</span>
                                     </button>
                                 <?php elseif ($status === 'student_responded' && strpos($student_response_info, 'Review') !== false ): ?>
                                     <a href="#tutor-student-alternatives-container" class="btn btn-sm btn-primary scroll-to" data-bs-toggle="tooltip" title="Review Student Alternatives">
                                         <i class="fas fa-eye"></i> <span class="d-none d-md-inline">Review</span>
                                     </a>
                                 <?php else: ?>
                                     <span class="text-muted">-</span>
                                 <?php endif; ?>
                             </td>
                         </tr>
                     <?php endforeach; ?>
                 </tbody>
             </table>
         </div>
         <?php
     } else {
         echo '<p class="text-center text-muted p-3">You have not initiated any reschedule requests.</p>';
     }
     $html = ob_get_clean();
     wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_load_tutor_outgoing_requests', 'load_tutor_outgoing_requests_ajax');


/**
 * AJAX handler for Tutors to load incoming requests from students.
 * Action: load_tutor_incoming_requests
 * Nonce: tutor_load_requests_nonce
 */
function load_tutor_incoming_requests_ajax() {
    check_ajax_referer('tutor_load_requests_nonce', 'nonce');

     $tutor_id = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : get_current_user_id();
    if (!$tutor_id || !current_user_can('tutor')) {
         wp_send_json_error(['message' => 'Invalid permissions.']);
         return;
    }

    // Get student-initiated requests for this tutor (type: student_reschedule)
    // Fetch all relevant ones, but identify pending for actions and count
    $student_requests = get_reschedule_requests('student_reschedule', $tutor_id, 'tutor'); 
    $pending_student_requests = array_filter($student_requests, function($req) {
        return get_post_meta($req->ID, 'status', true) === 'pending';
    });
    $pending_count = count($pending_student_requests);

    ob_start();
    if (!empty($student_requests)) {
         ?>
         <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
             <table class="table table-striped table-hover request-table">
                 <thead class="table-light">
                     <tr>
                         <th>Date</th>
                         <th>Student</th>
                         <th>Original Lesson</th>
                         <th>Preferred Times</th>
                         <th>Reason</th>
                         <th>Status</th>
                         <th class="text-center">Action</th>
                     </tr>
                 </thead>
                 <tbody>
                     <?php foreach ($student_requests as $request): ?>
                         <?php
                         $request_id = $request->ID;
                         $student_id = get_post_meta($request_id, 'student_id', true);
                         $student_name = get_student_display_name($student_id);
                         $original_date = get_post_meta($request_id, 'original_date', true);
                         $original_time = get_post_meta($request_id, 'original_time', true);
                         $request_date = get_the_date('M j, Y', $request_id);
                         $reason = get_post_meta($request_id, 'reason', true);
                         $preferred_times = get_post_meta($request_id, 'preferred_times', true);
                         $status = get_post_meta($request_id, 'status', true);
                         $status_badge = get_status_badge($status);
                         $formatted_original = format_datetime($original_date, $original_time);
                         
                         $tutor_response = get_post_meta($request_id, 'tutor_response', true);
                         $alternatives = [];
                         $tutor_alt_info = '';
                         if ($status === 'tutor_unavailable') { 
                             $alt_req_query = new WP_Query([
                                'post_type' => 'progress_report',
                                'posts_per_page' => 1,
                                'meta_query' => [
                                    'relation' => 'AND',
                                    ['key' => 'original_request_id', 'value' => $request_id, 'compare' => '='],
                                    ['key' => 'request_type', 'value' => 'tutor_unavailable', 'compare' => '='],
                                ],
                                'fields' => 'ids'
                             ]);
                             if ($alt_req_query->have_posts()) {
                                 $alternatives = get_post_meta($alt_req_query->posts[0], 'alternatives', true);
                                 if (!empty($alternatives) && is_array($alternatives)) {
                                      $tutor_alt_info = '<div class="mt-1 small"><span class="badge bg-warning text-dark">Alternatives Proposed</span></div>';
                                 }
                             }
                         }
                          elseif ($status === 'declined' && $tutor_response) {
                               $tutor_alt_info = '<div class="mt-1 small text-danger" data-bs-toggle="tooltip" title="' . esc_attr($tutor_response) . '"><i class="fas fa-info-circle me-1"></i>Your Decline Reason</div>';
                          }
                          ?>
                          <tr data-request-id="<?php echo esc_attr($request_id); ?>">
                              <td><?php echo esc_html($request_date); ?></td>
                              <td><?php echo esc_html($student_name); ?></td>
                              <td><?php echo esc_html($formatted_original); ?></td>
                              <td>
                                  <?php
                                  if (!empty($preferred_times) && is_array($preferred_times)) {
                                      echo '<ul class="list-unstyled mb-0 small">';
                                      foreach ($preferred_times as $index => $time) {
                                          if (!empty($time['date']) && !empty($time['time'])) {
                                              echo '<li><i class="far fa-clock me-1 text-muted"></i> ' . esc_html(format_datetime($time['date'], $time['time'])) . '</li>'; // Added icon
                                          }
                                      }
                                      echo '</ul>';
                                  } else {
                                      echo '<em>-</em>';
                                  }
                                  ?>
                              </td>
                              <td>
                                  <?php if (!empty($reason)):
                                       $trimmed_reason = wp_trim_words($reason, 10, '...');
                                   ?>
                                      <span data-bs-toggle="tooltip" title="<?php echo esc_attr($reason); ?>">
                                          <?php echo esc_html($trimmed_reason); ?>
                                      </span>
                                  <?php else: ?>
                                      <em>-</em>
                                  <?php endif; ?>
                              </td>
                              <td>
                                 <?php echo $status_badge; ?>
                                 <?php echo $tutor_alt_info; // Display info about tutor response ?>
                              </td>
                              <td class="request-actions text-center">
                                  <?php if ($status === 'pending'): ?>
                                      <div class="btn-group btn-group-sm">
                                         <button type="button" class="btn btn-success handle-student-request-btn" 
                                                 data-action="accept" data-request-id="<?php echo $request_id; ?>" 
                                                 data-bs-toggle="tooltip" title="Accept Request">
                                             <i class="fas fa-check"></i> <span class="d-none d-lg-inline">Accept</span>
                                         </button>
                                          <button type="button" class="btn btn-danger handle-student-request-btn" 
                                                  data-action="decline" data-request-id="<?php echo $request_id; ?>" 
                                                  data-bs-toggle="tooltip" title="Decline Request">
                                              <i class="fas fa-times"></i> <span class="d-none d-lg-inline">Decline</span>
                                          </button>
                                          <button type="button" class="btn btn-warning handle-student-request-btn" 
                                                  data-action="unavailable" data-request-id="<?php echo $request_id; ?>" 
                                                  data-bs-toggle="tooltip" title="Unavailable for Preferred Times (Propose Alternatives)">
                                             <i class="fas fa-calendar-alt"></i> <span class="d-none d-lg-inline">Propose</span>
                                          </button>
                                      </div>
                                  <?php else: ?>
                                      <span class="text-muted">-</span>
                                  <?php endif; ?>
                              </td>
                          </tr>
                      <?php endforeach; ?>
                  </tbody>
              </table>
          </div>
          <?php
     } else {
         echo '<p class="text-center text-muted p-3">No incoming reschedule requests from students.</p>';
     }
     $html = ob_get_clean();
 
     wp_send_json_success(['html' => $html, 'pending_count' => $pending_count]); 
}
add_action('wp_ajax_load_tutor_incoming_requests', 'load_tutor_incoming_requests_ajax');

/**
 * AJAX handler for Tutors to load pending student alternative suggestions.
 * Action: load_tutor_student_alternatives
 * Nonce: tutor_load_requests_nonce 
 */
function load_tutor_student_alternatives_ajax() {
     check_ajax_referer('tutor_load_requests_nonce', 'nonce');

     $tutor_id = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : get_current_user_id();
     if (!$tutor_id || !current_user_can('tutor')) {
          wp_send_json_error(['message' => 'Invalid permissions.']);
          return;
     }

    // Get pending alternative suggestions initiated by students (type 'student_unavailable', status 'pending')
    // These are responses to a tutor's initial proposal
     $alternative_requests = get_reschedule_requests('student_unavailable', $tutor_id, 'tutor', 'pending');
     $pending_count = count($alternative_requests);

     ob_start();
     if (!empty($alternative_requests)) {
         ?>
         <div class="accordion" id="studentAlternativesAccordion">
             <?php foreach ($alternative_requests as $index => $alt_request): ?>
                 <?php
                 $alt_request_id = $alt_request->ID;
                 $original_tutor_request_id = get_post_meta($alt_request_id, 'original_request_id', true);
                 $student_id = $alt_request->post_author;
                 $student_name = get_student_display_name($student_id);
                 $original_lesson_date = get_post_meta($original_tutor_request_id, 'original_date', true);
                 $original_lesson_time = get_post_meta($original_tutor_request_id, 'original_time', true);
                 $tutor_proposed_date = get_post_meta($original_tutor_request_id, 'proposed_date', true);
                 $tutor_proposed_time = get_post_meta($original_tutor_request_id, 'proposed_time', true);
                 $student_reason = get_post_meta($alt_request_id, 'reason', true);
                 $student_alternatives = get_post_meta($alt_request_id, 'preferred_times', true);
                 $status = get_post_meta($alt_request_id, 'status', true);
                 $status_badge = get_status_badge($status);
                 $formatted_original = format_datetime($original_lesson_date, $original_lesson_time);
                 $formatted_tutor_proposed = format_datetime($tutor_proposed_date, $tutor_proposed_time);
                 ?>
                 <div class="accordion-item">
                     <h2 class="accordion-header" id="headingStudentAlternative<?php echo esc_attr($alt_request_id); ?>">
                         <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                 data-bs-target="#collapseStudentAlternative<?php echo esc_attr($alt_request_id); ?>" aria-expanded="false" 
                                 aria-controls="collapseStudentAlternative<?php echo esc_attr($alt_request_id); ?>">
                             Alternatives from <?php echo esc_html($student_name); ?> (Regarding Lesson: <?php echo esc_html($formatted_original); ?>)
                             <span class="ms-auto me-3"><?php echo $status_badge; ?></span>
                         </button>
                     </h2>
                     <div id="collapseStudentAlternative<?php echo esc_attr($alt_request_id); ?>" class="accordion-collapse collapse" 
                          aria-labelledby="headingStudentAlternative<?php echo esc_attr($alt_request_id); ?>" data-bs-parent="#studentAlternativesAccordion">
                         <div class="accordion-body border-top">
                             <div class="alert alert-secondary small p-2 mb-3 border">
                                 <p class="mb-1"><strong>Original Lesson:</strong> <?php echo esc_html($formatted_original); ?></p>
                                 <p class="mb-0"><strong>Your Proposed Time:</strong> <?php echo esc_html($formatted_tutor_proposed); ?></p>
                             </div>
                             <?php if (!empty($student_reason)): ?>
                                 <p><strong>Student Reason:</strong> <?php echo nl2br(esc_html($student_reason)); ?></p>
                             <?php endif; ?>
 
                             <?php if (!empty($student_alternatives) && is_array($student_alternatives)):
                                 $has_valid_alt = false;
                                 foreach ($student_alternatives as $time) { if (!empty($time['date']) && !empty($time['time'])) { $has_valid_alt = true; break; } }
                                 if ($has_valid_alt): 
                                 ?>
                                 <p><strong>Student's Suggested Alternatives:</strong></p>
                                 <form method="post" class="handle-student-alternatives-form" data-alt-request-id="<?php echo esc_attr($alt_request_id); ?>">
                                     <?php wp_nonce_field('tutor_respond_student_alternative_' . $alt_request_id, 'tutor_respond_alt_nonce'); ?>
                                     <input type="hidden" name="alt_request_id" value="<?php echo esc_attr($alt_request_id); ?>">
                                     <div class="list-group list-group-flush mb-3 border-top border-bottom">
                                         <?php foreach ($student_alternatives as $idx => $time):
                                              if (!empty($time['date']) && !empty($time['time'])):
                                                  $value = $time['date'] . '|' . $time['time']; 
                                              ?>
                                             <label class="list-group-item list-group-item-action ps-2" for="student_alt_<?php echo esc_attr($alt_request_id . '_' . $idx); ?>">
                                                  <input class="form-check-input me-2 tutor-accept-alternative-radio" type="radio" name="selected_alternative" 
                                                         value="<?php echo esc_attr($value); ?>" 
                                                         data-alt-request-id="<?php echo esc_attr($alt_request_id); ?>"
                                                          data-selected-index="<?php echo esc_attr($idx); ?>"
                                                         id="student_alt_<?php echo esc_attr($alt_request_id . '_' . $idx); ?>" required>
                                                 <?php echo esc_html(format_datetime($time['date'], $time['time'], 'l, M j, Y @ g:i A')); ?>
                                             </label>
                                             <?php endif; ?>
                                         <?php endforeach; ?>
                                     </div>
                                     <button type="button" class="btn btn-sm btn-success me-2 respond-to-student-alternative-btn" 
                                             data-action="accept" 
                                             data-alt-request-id="<?php echo esc_attr($alt_request_id); ?>" 
                                             data-bs-toggle="tooltip" title="Accept Selected Alternative Time"
                                             disabled> 
                                         <i class="fas fa-check"></i> <span class="d-none d-md-inline">Accept Selected</span>
                                     </button>
                                     <button type="button" class="btn btn-sm btn-danger respond-to-student-alternative-btn"
                                             data-action="decline_all"
                                             data-alt-request-id="<?php echo esc_attr($alt_request_id); ?>"
                                             data-bs-toggle="tooltip" title="Decline All Alternatives & Cancel Original Request">
                                         <i class="fas fa-times"></i> <span class="d-none d-md-inline">Decline All</span>
                                     </button>
                                     <div class="ajax-modal-response mt-2 small"></div>
                                 </form>
                                 <?php else: // No valid alternatives provided ?>
                                    <p class="text-muted fst-italic">Student indicated unavailability but did not provide valid alternative times.</p>
                                    <form method="post" class="handle-student-alternatives-form" data-alt-request-id="<?php echo esc_attr($alt_request_id); ?>">
                                        <?php wp_nonce_field('tutor_respond_student_alternative_' . $alt_request_id, 'tutor_respond_alt_nonce'); ?>
                                         <input type="hidden" name="alt_request_id" value="<?php echo esc_attr($alt_request_id); ?>">
                                        <button type="button" class="btn btn-sm btn-secondary respond-to-student-alternative-btn"
                                                data-action="cancel_original"
                                                data-alt-request-id="<?php echo esc_attr($alt_request_id); ?>"
                                                data-bs-toggle="tooltip" title="Acknowledge Unavailability & Cancel Original Request">
                                            <i class="fas fa-ban"></i> <span class="d-none d-md-inline">Acknowledge & Cancel</span>
                                        </button>
                                         <div class="ajax-modal-response mt-2 small"></div>
                                    </form>
                                 <?php endif; ?>
                             <?php else: // No alternatives array found ?>
                                 <p class="text-muted fst-italic">Student indicated unavailability but did not suggest alternative times.</p>
                                 <form method="post" class="handle-student-alternatives-form" data-alt-request-id="<?php echo esc_attr($alt_request_id); ?>">
                                     <?php wp_nonce_field('tutor_respond_student_alternative_' . $alt_request_id, 'tutor_respond_alt_nonce'); ?>
                                     <input type="hidden" name="alt_request_id" value="<?php echo esc_attr($alt_request_id); ?>">
                                     <button type="button" class="btn btn-sm btn-secondary respond-to-student-alternative-btn"
                                            data-action="cancel_original"
                                            data-alt-request-id="<?php echo esc_attr($alt_request_id); ?>"
                                            data-bs-toggle="tooltip" title="Acknowledge Unavailability & Cancel Original Request">
                                         <i class="fas fa-ban"></i> <span class="d-none d-md-inline">Acknowledge & Cancel</span>
                                      </button>
                                      <div class="ajax-modal-response mt-2 small"></div>
                                 </form>
                             <?php endif; ?>
                         </div> 
                     </div> 
                 </div> 
             <?php endforeach; ?>
         </div> 
         <script type="text/javascript">
            jQuery(document).ready(function($) {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('#studentAlternativesAccordion [data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
                
                $('.tutor-accept-alternative-radio').on('change', function() {
                    var form = $(this).closest('form');
                    var acceptButton = form.find('.respond-to-student-alternative-btn[data-action="accept"]');
                    if ($(this).is(':checked')) {
                        acceptButton.prop('disabled', false);
                    } else {
                        acceptButton.prop('disabled', true);
                    }
                });
            });
         </script>
         <?php
     } else {
         echo '<p class="text-center text-muted p-3">No pending alternative time suggestions from students require your review.</p>';
     }
     $html = ob_get_clean();
     wp_send_json_success(['html' => $html, 'pending_count' => $pending_count]); 
}
add_action('wp_ajax_load_tutor_student_alternatives', 'load_tutor_student_alternatives_ajax');


/**
 * AJAX handler for tutor submitting the "initiate reschedule" form.
 * Mirrors the logic in post-handlers.php but uses AJAX response.
 * Action: tutor_initiate_reschedule_ajax
 * Nonce: submit_tutor_reschedule_request_nonce
 */
function tutor_initiate_reschedule_ajax() {
     check_ajax_referer('submit_tutor_reschedule_request_nonce', 'submit_tutor_reschedule_request_nonce');

    // Retrieve and sanitize all expected fields
    $tutor_id = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : 0;
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0; // Correct student ID from hidden field
    $original_date = isset($_POST['original_date']) ? sanitize_text_field($_POST['original_date']) : '';
    $original_time = isset($_POST['original_time']) ? sanitize_text_field($_POST['original_time']) : '';
    $proposed_date = isset($_POST['proposed_date']) ? sanitize_text_field($_POST['proposed_date']) : '';
    $proposed_time = isset($_POST['proposed_time']) ? sanitize_text_field($_POST['proposed_time']) : '';
    $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

    // Basic validation
    if (empty($tutor_id) || empty($student_id) || empty($original_date) || empty($original_time) || empty($proposed_date) || empty($proposed_time) || empty($reason)) {
         wp_send_json_error(['message' => 'Please fill in all required fields.']);
         return;
    }

    // Security check: Ensure the logged-in user is the tutor submitting the request
    if (get_current_user_id() != $tutor_id || !current_user_can('tutor')) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    // Security check: Ensure the tutor is assigned to the student
    // Re-fetch assigned students for the current tutor for validation
     $assigned_students_meta = get_user_meta($tutor_id, 'assigned_students', true);
     $assigned_students_array = is_array($assigned_students_meta) ? $assigned_students_meta : array_map('trim', explode(',', $assigned_students_meta));
    // Ensure IDs are integers for comparison
     $assigned_students_array = array_map('intval', $assigned_students_array);

    if (!in_array($student_id, $assigned_students_array, true)) {
       wp_send_json_error(['message' => 'Error: You are not assigned to this student.']);
       return;
    }
    
    $student_user = get_user_by('id', $student_id);
    if (!$student_user) {
        wp_send_json_error(['message' => 'Error: Invalid student selected.']);
       return;
    }

    // Create the reschedule request post (tutor_reschedule)
    $post_data = array(
        'post_title' => 'Reschedule Request: Tutor (' . wp_get_current_user()->user_login . ') -> Student (' . $student_user->user_login . ') - ' . date('Y-m-d'),
        'post_content' => $reason,
        'post_status' => 'publish', // Or 'private'?
        'post_type' => 'progress_report', 
        'post_author' => $tutor_id,
        'meta_input' => array(
            'request_type' => 'tutor_reschedule',
            'status' => 'pending', // Initial status
            'tutor_id' => $tutor_id,
            'tutor_name' => wp_get_current_user()->user_login, 
            'student_id' => $student_id,
            'student_name' => $student_user->user_login,
            'original_date' => $original_date,
            'original_time' => $original_time,
            'proposed_date' => $proposed_date,
            'proposed_time' => $proposed_time,
            'reason' => $reason,
            'viewed_by_student' => '0', // Mark as unread for student
             'viewed_by_tutor' => '1', // Tutor has implicitly viewed it
        ),
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => 'Failed to create request: ' . $post_id->get_error_message()]);
    } elseif ($post_id) {
        // Trigger notification
        if (function_exists('send_reschedule_notification')) {
             send_reschedule_notification($post_id, 'student'); 
        }
        wp_send_json_success(['message' => 'Request submitted successfully and student notified.', 'request_id' => $post_id]);
    } else {
        wp_send_json_error(['message' => 'Failed to create request. Unknown error.']);
    }
}
add_action('wp_ajax_tutor_initiate_reschedule_ajax', 'tutor_initiate_reschedule_ajax');

/**
 * AJAX handler for tutor accepting a student's request (from modal form).
 * Action: tutor_accept_student_request
 * Nonce: tutor_accept_student_request_{request_id}
 */
function tutor_accept_student_request_ajax() {
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    $nonce = $_POST['tutor_accept_nonce'] ?? '';
    
    if (!$request_id || !wp_verify_nonce($nonce, 'tutor_accept_student_request_' . $request_id)) {
        wp_send_json_error(['message' => 'Nonce verification failed or invalid request ID.']);
    }
    
    if (empty($_POST['selected_time'])) {
        wp_send_json_error(['message' => 'Please select one of the preferred times.']);
    }

    $selected_time_parts = explode('|', sanitize_text_field($_POST['selected_time']));
    if (count($selected_time_parts) !== 2) {
        wp_send_json_error(['message' => 'Invalid selected time format.']);
    }
    $new_date = $selected_time_parts[0];
    $new_time = $selected_time_parts[1];

    // Delegate to the common confirmation function from post-handlers.php (or replicate logic here)
    // Ensure handle_reschedule_confirmation does permission checks
    $result = handle_reschedule_confirmation($request_id, $new_date, $new_time); // Assumes this function exists and handles everything

     if ($result['success']) {
        // Optionally trigger notification back to student
        if (function_exists('send_reschedule_notification')) {
             send_reschedule_notification($request_id, 'student', 'confirmed'); 
        }
        wp_send_json_success(['message' => $result['message']]);
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}
add_action('wp_ajax_tutor_accept_student_request', 'tutor_accept_student_request_ajax');


/**
 * AJAX handler for tutor declining a student's request (from modal form).
 * Action: tutor_decline_student_request
 * Nonce: tutor_decline_student_request_{request_id}
 */
function tutor_decline_student_request_ajax() {
     $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
     $nonce = $_POST['tutor_decline_nonce'] ?? '';
    
    if (!$request_id || !wp_verify_nonce($nonce, 'tutor_decline_student_request_' . $request_id)) {
        wp_send_json_error(['message' => 'Nonce verification failed or invalid request ID.']);
    }

    $response_reason = isset($_POST['response_reason']) ? sanitize_textarea_field($_POST['response_reason']) : '';

    // Delegate to the common decline function
    $result = handle_reschedule_decline($request_id, 'tutor', $response_reason);

    if ($result['success']) {
         // Optionally trigger notification back to student
        if (function_exists('send_reschedule_notification')) {
             send_reschedule_notification($request_id, 'student', 'declined'); 
        }
        wp_send_json_success(['message' => $result['message']]);
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}
add_action('wp_ajax_tutor_decline_student_request', 'tutor_decline_student_request_ajax');

/**
 * AJAX handler for tutor proposing alternatives for a student request (from modal form).
 * Action: tutor_propose_alternatives_for_student
 * Nonce: tutor_propose_alternatives_{request_id}
 */
function tutor_propose_alternatives_for_student_ajax() {
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0; // Original student request ID
    $nonce = $_POST['tutor_propose_nonce'] ?? '';
    
    if (!$request_id || !wp_verify_nonce($nonce, 'tutor_propose_alternatives_' . $request_id)) {
        wp_send_json_error(['message' => 'Nonce verification failed or invalid request ID.']);
    }

    // Extract proposed alternatives using the helper function (prefix 'tutor_alt_')
    $proposed_alternatives = extract_preferred_times_from_post('tutor_alt_');
    if (empty($proposed_alternatives)) {
         wp_send_json_error(['message' => 'Please provide at least one alternative time option.']);
    }
    
    $response_reason = isset($_POST['response_reason']) ? sanitize_textarea_field($_POST['response_reason']) : '';
    // Student ID, original date/time should be passed from the modal form
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    $original_date = isset($_POST['original_date']) ? sanitize_text_field($_POST['original_date']) : '';
    $original_time = isset($_POST['original_time']) ? sanitize_text_field($_POST['original_time']) : '';

    if (empty($student_id) || empty($original_date) || empty($original_time)) {
         wp_send_json_error(['message' => 'Missing student or original lesson details.']);
    }

    // Delegate to the common "unavailable" handler from post-handlers.php
    $result = handle_reschedule_unavailable($request_id, 'tutor', $response_reason, $proposed_alternatives, $student_id, $original_date, $original_time);

     if ($result['success']) {
         // Optionally trigger notification to student about proposed alternatives
        if (function_exists('send_reschedule_notification')) {
            // Need the ID of the newly created 'tutor_unavailable' post if the handler returns it
            $new_request_id = $result['new_request_id'] ?? 0;
            if ($new_request_id) {
                send_reschedule_notification($new_request_id, 'student', 'alternatives_proposed'); 
            }
        }
        wp_send_json_success(['message' => $result['message']]);
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}
add_action('wp_ajax_tutor_propose_alternatives_for_student', 'tutor_propose_alternatives_for_student_ajax');


/**
 * AJAX handler for tutor accepting a specific alternative from a student.
 * Action: tutor_accept_student_alternative
 * Nonce: tutor_respond_student_alternative_{alt_request_id} (or the modal nonce)
 */
function tutor_accept_student_alternative_ajax() {
     $alt_request_id = isset($_POST['alt_request_id']) ? intval($_POST['alt_request_id']) : 0; // student_unavailable post ID
     $nonce = $_POST['nonce'] ?? ''; // Use nonce sent from JS (which got it from the modal/accordion)
    
    // Verify nonce - choose one method. Using the specific request ID is better.
    // if (!wp_verify_nonce($nonce, 'tutor_respond_alt_nonce_modal')) { // If using modal nonce
    if (!wp_verify_nonce($nonce, 'tutor_respond_student_alternative_' . $alt_request_id)) { // If using nonce from accordion item
        wp_send_json_error(['message' => 'Nonce verification failed.']);
    }

    $selected_index = isset($_POST['selected_index']) ? intval($_POST['selected_index']) : null;
    if ($selected_index === null) {
        wp_send_json_error(['message' => 'No alternative index selected.']);
    }

    // Delegate to the common handler function from post-handlers.php
    $result = handle_accept_student_alternative($alt_request_id, $selected_index);

     if ($result['success']) {
         // Notify student of confirmation
         if (function_exists('send_reschedule_notification')) {
            // Need original student request ID if possible, or the alt request ID
            $original_student_request_id = get_post_meta($alt_request_id, 'original_request_id', true); 
             send_reschedule_notification($original_student_request_id ?: $alt_request_id, 'student', 'confirmed_by_tutor'); 
        }
        wp_send_json_success(['message' => $result['message']]);
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}
add_action('wp_ajax_tutor_accept_student_alternative', 'tutor_accept_student_alternative_ajax');


/**
 * AJAX handler for tutor declining all alternatives suggested by a student.
 * Action: tutor_decline_student_alternatives
 * Nonce: tutor_respond_student_alternative_{alt_request_id} (or the modal nonce)
 */
function tutor_decline_student_alternatives_ajax() {
     $alt_request_id = isset($_POST['alt_request_id']) ? intval($_POST['alt_request_id']) : 0; // student_unavailable post ID
     $nonce = $_POST['nonce'] ?? ''; // Use nonce sent from JS
    
     if (!wp_verify_nonce($nonce, 'tutor_respond_student_alternative_' . $alt_request_id)) { 
        wp_send_json_error(['message' => 'Nonce verification failed.']);
    }

    $response_reason = isset($_POST['response_reason']) ? sanitize_textarea_field($_POST['response_reason']) : '';

    // Delegate to the common handler function from post-handlers.php
    $result = handle_decline_student_alternatives($alt_request_id, $response_reason);

    if ($result['success']) {
        // Notify student of decline
         if (function_exists('send_reschedule_notification')) {
             $original_student_request_id = get_post_meta($alt_request_id, 'original_request_id', true); 
             send_reschedule_notification($original_student_request_id ?: $alt_request_id, 'student', 'alternatives_declined_by_tutor'); 
        }
        wp_send_json_success(['message' => $result['message']]);
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}
add_action('wp_ajax_tutor_decline_student_alternatives', 'tutor_decline_student_alternatives_ajax');


/**
 * AJAX handler for tutor cancelling original request when student was unavailable but offered no alternatives.
 * Action: tutor_cancel_original_from_unavailable
 * Nonce: tutor_respond_student_alternative_{alt_request_id} (or the modal nonce)
 */
function tutor_cancel_original_from_unavailable_ajax() {
     $alt_request_id = isset($_POST['alt_request_id']) ? intval($_POST['alt_request_id']) : 0; // student_unavailable post ID
     $nonce = $_POST['nonce'] ?? ''; // Use nonce sent from JS
    
     if (!wp_verify_nonce($nonce, 'tutor_respond_student_alternative_' . $alt_request_id)) { 
        wp_send_json_error(['message' => 'Nonce verification failed.']);
    }

     // Delegate to a handler function (might be similar to decline_student_alternatives)
     // Assumes handle_cancel_original_request_from_unavailable exists in post-handlers.php
     $result = handle_cancel_original_request_from_unavailable($alt_request_id);

     if ($result['success']) {
         // Notify student? Maybe not necessary if just acknowledging.
        wp_send_json_success(['message' => $result['message']]);
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}
add_action('wp_ajax_tutor_cancel_original_from_unavailable', 'tutor_cancel_original_from_unavailable_ajax');


// --- Student Specific AJAX Handlers ---

/**
 * AJAX handler for students to check for incoming tutor reschedule requests
 * or alternative time proposals from tutors.
 * Action: check_student_incoming_requests
 * Nonce: check_student_requests_nonce
 */
// ... existing check_student_incoming_requests_ajax ...

/**
 * AJAX handler for students to mark tutor alternative suggestions as viewed.
 * Action: mark_tutor_alternatives_viewed
 * Nonce: mark_tutor_alternatives_viewed_nonce
 */
// ... existing mark_tutor_alternatives_viewed_ajax ...

/**
 * AJAX handler for Students to delete their own reschedule requests.
 * Action: delete_student_request
 * Nonce: delete_student_request_{request_id}
 */
// ... existing delete_student_request_ajax ...

?> 