<?php
/**
 * AJAX Handlers for Reschedule Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Ensure helper functions are available
require_once __DIR__ . '/request-functions.php'; 

// ======================================
// == Tutor AJAX Handlers
// ======================================

/**
 * AJAX handler for tutors to load their notifications.
 */
function load_tutor_notifications_ajax() {
    error_log('[AJAX TUTOR] load_tutor_notifications_ajax started'); // Logging added
    check_ajax_referer('load_tutor_notifications_action', 'nonce');

    $tutor_id = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : 0;
    if (!$tutor_id || !ol_hub_can_user_access_tutor_dashboard($tutor_id)) {
        error_log('[AJAX TUTOR] Permission denied or invalid tutor ID for notifications.');
        wp_send_json_error(['message' => 'Permission denied.'], 403);
        return;
    }
    error_log('[AJAX TUTOR] Tutor ID for notifications: ' . $tutor_id);

    // Combine counts from different sources
    $total_unread = 0;

    // Count 1: Pending incoming student requests needing action
    error_log('[AJAX TUTOR] Querying pending student requests...');
    $incoming_requests_query_args = [
        'post_type' => 'lesson_reschedule',
        'posts_per_page' => -1,
        'post_status' => 'pending', // Requests initiated by students, awaiting tutor action
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'tutor_id',
                'value' => $tutor_id,
                'compare' => '='
            ],
            [
                'key' => 'viewed_by_tutor',
                'compare' => 'NOT EXISTS' // Only count those not yet viewed
            ],
             [
                'key' => 'initiator',
                'value' => 'student',
                'compare' => '='
            ]
        ]
    ];
    $incoming_requests = new WP_Query($incoming_requests_query_args);
    $incoming_count = $incoming_requests->post_count;
    error_log('[AJAX TUTOR] Pending student requests count: ' . $incoming_count);
    $total_unread += $incoming_count;

    // Count 2: Status changes on tutor-initiated requests (Accepted/Declined by student)
    error_log('[AJAX TUTOR] Querying unread status changes (student responses)...');
    $status_change_query_args = [
        'post_type' => 'lesson_reschedule',
        'posts_per_page' => -1,
        'post_status' => ['accepted', 'declined'], // Statuses set by student action
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'tutor_id',
                'value' => $tutor_id,
                'compare' => '='
            ],
            [
                'key' => 'initiator',
                'value' => 'tutor', // Request was initiated by the tutor
                'compare' => '='
            ],
            [
                'key' => 'viewed_by_tutor',
                'compare' => 'NOT EXISTS' // Only count those not yet viewed
            ]
        ]
    ];
    $status_changes = new WP_Query($status_change_query_args);
    $status_change_count = $status_changes->post_count;
    error_log('[AJAX TUTOR] Unread status changes count: ' . $status_change_count);
    $total_unread += $status_change_count;

    // Count 3: Student responses with alternative times
    error_log('[AJAX TUTOR] Querying unread student alternative time suggestions...');
    $alternatives_query_args = [
        'post_type' => 'lesson_reschedule',
        'posts_per_page' => -1,
        'post_status' => 'alternative_proposed', // Status when student proposes alternatives
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'tutor_id',
                'value' => $tutor_id,
                'compare' => '='
            ],
            [
                'key' => 'initiator',
                'value' => 'tutor', // Request was initiated by the tutor
                'compare' => '='
            ],
            [
                'key' => 'viewed_by_tutor',
                'compare' => 'NOT EXISTS' // Only count those not yet viewed
            ]
        ]
    ];
    $alternatives = new WP_Query($alternatives_query_args);
    $alternatives_count = $alternatives->post_count;
     error_log('[AJAX TUTOR] Unread student alternatives count: ' . $alternatives_count);
    $total_unread += $alternatives_count;

    error_log('[AJAX TUTOR] Total unread count: ' . $total_unread);
    error_log('[AJAX TUTOR] Sending notification count success response.');
    wp_send_json_success(['unread_count' => $total_unread]);
}
add_action('wp_ajax_load_tutor_notifications', 'load_tutor_notifications_ajax');

/**
 * AJAX handler for tutors to load their outgoing requests.
 */
function load_tutor_outgoing_requests_ajax() {
    error_log('[AJAX TUTOR] load_tutor_outgoing_requests_ajax started'); // Logging added
    check_ajax_referer('load_tutor_outgoing_action', 'nonce');

    $tutor_id = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : 0;
    if (!$tutor_id || !ol_hub_can_user_access_tutor_dashboard($tutor_id)) {
        error_log('[AJAX TUTOR] Permission denied or invalid tutor ID for outgoing requests.');
        wp_send_json_error(['message' => 'Permission denied.'], 403);
        return;
    }
    error_log('[AJAX TUTOR] Tutor ID for outgoing requests: ' . $tutor_id);

    $outgoing_requests = get_reschedule_requests('tutor', $tutor_id, ['pending', 'accepted', 'declined', 'alternative_proposed']); // Fetch relevant statuses

    ob_start();
    if (!empty($outgoing_requests)) {
        echo '<div class="list-group">';
        foreach ($outgoing_requests as $request) {
            $request_id = $request->ID;
            $student_id = get_post_meta($request_id, 'student_id', true);
            $student_info = get_userdata($student_id);
            $student_name = $student_info ? esc_html($student_info->display_name) : 'Unknown Student';
            $original_lesson_time = get_post_meta($request_id, 'original_lesson_time', true);
            $proposed_lesson_time = get_post_meta($request_id, 'proposed_lesson_time', true);
            $request_status = $request->post_status;
            $reason = get_post_meta($request_id, 'reason', true);
            $viewed_by_tutor = get_post_meta($request_id, 'viewed_by_tutor', true);
            $created_date = $request->post_date;

            // Mark as viewed if it's a student response and not viewed yet
            $is_student_response = in_array($request_status, ['accepted', 'declined', 'alternative_proposed']);
            if ($is_student_response && !$viewed_by_tutor) {
                 // TODO: Consider if marking viewed should happen on load or specific action
                 // update_post_meta($request_id, 'viewed_by_tutor', 'yes');
            }

            $status_badge = get_status_badge($request_status, 'tutor');
            $formatted_original = format_datetime($original_lesson_time);
            $formatted_proposed = format_datetime($proposed_lesson_time);
            $formatted_created = format_datetime($created_date);

            echo '<div class="list-group-item list-group-item-action flex-column align-items-start request-item-' . esc_attr($request_id) . '">';
            echo '<div class="d-flex w-100 justify-content-between">';
            echo '<h5 class="mb-1">Request to ' . $student_name . '</h5>';
            echo '<div>' . $status_badge . ($is_student_response && !$viewed_by_tutor ? ' <span class="badge bg-danger">New</span>' : '') . '</div>';
            echo '</div>';
            echo '<p class="mb-1"><strong>Original Lesson:</strong> ' . $formatted_original . '</p>';
            echo '<p class="mb-1"><strong>Proposed New Time:</strong> ' . $formatted_proposed . '</p>';
            echo '<p class="mb-1"><strong>Reason:</strong> ' . esc_html($reason) . '</p>';

            // Show student response if available (only for accepted/declined/alternative)
            if (in_array($request_status, ['accepted', 'declined', 'alternative_proposed'])) {
                $student_response = get_post_meta($request_id, 'student_response_comment', true);
                if ($student_response) {
                    echo '<p class="mb-1 fst-italic text-muted"><strong>Student Comment:</strong> ' . esc_html($student_response) . '</p>';
                }
                // If alternative proposed, show the alternative times
                if ($request_status === 'alternative_proposed') {
                    $alt_times = get_post_meta($request_id, 'student_preferred_times', true);
                    if (is_array($alt_times) && !empty($alt_times)) {
                        echo '<p class="mb-1"><strong>Student Suggested Times:</strong></p><ul>';
                        foreach ($alt_times as $alt_time) {
                            if (isset($alt_time['date']) && isset($alt_time['time'])) {
                                $alt_dt = $alt_time['date'] . ' ' . $alt_time['time'];
                                echo '<li>' . format_datetime($alt_dt) . '</li>';
                            }
                        }
                        echo '</ul>';
                        // Add button to view/respond to alternatives
                         echo '<button class="btn btn-sm btn-info mt-2 respond-to-alternatives-btn" data-request-id="' . esc_attr($request_id) . '">Respond to Alternatives</button>';
                    } else {
                        // Log if expected alternatives are missing
                        error_log("[AJAX TUTOR] Alternative times missing or not an array for request ID: $request_id");
                        echo '<p class="mb-1 text-danger">Error: Could not load student suggested times.</p>';
                    }
                }
            }

            echo '<small class="text-muted">Sent: ' . $formatted_created . '</small>';

            // Actions for tutor (e.g., delete pending/old requests)
            // Allow deletion only for pending or old completed requests
            if ($request_status === 'pending' || in_array($request_status, ['accepted', 'declined'])) { // Simplified condition
                echo '<div class="mt-2 request-actions">';
                echo '<button class="btn btn-sm btn-danger delete-tutor-request-btn" data-request-id="' . esc_attr($request_id) . '" data-nonce="' . wp_create_nonce('delete_tutor_request_' . $request_id) . '">Delete Request</button>';
                 // Add mark as viewed button for completed/responded items if not already viewed
                 if ($is_student_response && !$viewed_by_tutor) {
                     echo '<button class="btn btn-sm btn-secondary ms-2 mark-tutor-item-viewed-btn" data-request-id="' . esc_attr($request_id) . '" data-item-type="outgoing_request" data-nonce="' . wp_create_nonce('mark_tutor_item_viewed_' . $request_id) . '">Mark as Read</button>';
                 }
                echo '</div>';
            }

            echo '</div>'; // End list-group-item
        }
        echo '</div>'; // End list-group
    } else {
        echo '<div class="alert alert-info">You have no outgoing reschedule requests.</div>';
    }

    $html = ob_get_clean();
    error_log('[AJAX TUTOR] Sending outgoing requests HTML success response.');
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_load_tutor_outgoing_requests', 'load_tutor_outgoing_requests_ajax');

/**
 * AJAX handler for tutors to load incoming requests from students.
 */
function load_tutor_incoming_requests_ajax() {
     error_log('[AJAX TUTOR] load_tutor_incoming_requests_ajax started'); // Logging added
    check_ajax_referer('check_tutor_incoming_action', 'nonce');

    $tutor_id = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : 0;
    if (!$tutor_id || !ol_hub_can_user_access_tutor_dashboard($tutor_id)) {
        error_log('[AJAX TUTOR] Permission denied or invalid tutor ID for incoming requests.');
        wp_send_json_error(['message' => 'Permission denied.'], 403);
        return;
    }
    error_log('[AJAX TUTOR] Tutor ID for incoming requests: ' . $tutor_id);

    $incoming_requests = get_reschedule_requests('tutor', $tutor_id, ['pending'], 'student'); // Only pending requests initiated by students

    ob_start();
    if (!empty($incoming_requests)) {
        echo '<div class="list-group">';
        foreach ($incoming_requests as $request) {
            $request_id = $request->ID;
            $student_id = get_post_meta($request_id, 'student_id', true);
            $student_info = get_userdata($student_id);
            $student_name = $student_info ? esc_html($student_info->display_name) : 'Unknown Student';
            $original_lesson_time = get_post_meta($request_id, 'original_lesson_time', true);
            $proposed_lesson_time = get_post_meta($request_id, 'proposed_lesson_time', true);
            $reason = get_post_meta($request_id, 'reason', true);
            $viewed_by_tutor = get_post_meta($request_id, 'viewed_by_tutor', true);
            $created_date = $request->post_date;

            $formatted_original = format_datetime($original_lesson_time);
            $formatted_proposed = format_datetime($proposed_lesson_time);
            $formatted_created = format_datetime($created_date);

            echo '<div class="list-group-item list-group-item-action flex-column align-items-start request-item-' . esc_attr($request_id) . '">';
            echo '<div class="d-flex w-100 justify-content-between">';
            echo '<h5 class="mb-1">Request from ' . $student_name . '</h5>';
            echo '<div><span class="badge bg-warning text-dark">Pending Action</span>' . (!$viewed_by_tutor ? ' <span class="badge bg-danger">New</span>' : '') . '</div>';
            echo '</div>';
            echo '<p class="mb-1"><strong>Original Lesson:</strong> ' . $formatted_original . '</p>';
            echo '<p class="mb-1"><strong>Requested New Time:</strong> ' . $formatted_proposed . '</p>';
            echo '<p class="mb-1"><strong>Student\'s Reason:</strong> ' . esc_html($reason) . '</p>';
            echo '<small class="text-muted">Received: ' . $formatted_created . '</small>';

            // Action buttons for the tutor
            echo '<div class="mt-2 request-actions btn-group btn-group-sm" role="group" aria-label="Request Actions">';
            // Button to trigger modal/form for accepting
            echo '<button class="btn btn-success handle-student-request-btn" data-action="accept" data-request-id="' . esc_attr($request_id) . '" data-student-name="' . $student_name . '" data-original-lesson="' . $formatted_original . '" data-proposed-lesson="' . $formatted_proposed . '" data-reason="' . esc_attr(htmlspecialchars($reason)) . '" data-bs-toggle="modal" data-bs-target="#tutorHandleStudentRequestModal">Accept</button>';
            // Button to trigger modal/form for declining
            echo '<button class="btn btn-danger handle-student-request-btn" data-action="decline" data-request-id="' . esc_attr($request_id) . '" data-student-name="' . $student_name . '" data-original-lesson="' . $formatted_original . '" data-proposed-lesson="' . $formatted_proposed . '" data-bs-toggle="modal" data-bs-target="#tutorHandleStudentRequestModal">Decline</button>';
            // Button to trigger modal/form for proposing alternative times
            echo '<button class="btn btn-warning handle-student-request-btn" data-action="propose_alt" data-request-id="' . esc_attr($request_id) . '" data-student-name="' . $student_name . '" data-original-lesson="' . $formatted_original . '" data-proposed-lesson="' . $formatted_proposed . '" data-bs-toggle="modal" data-bs-target="#tutorHandleStudentRequestModal">Propose Alternative Times</button>';
            // Mark as viewed button
             if (!$viewed_by_tutor) {
                 echo '<button class="btn btn-secondary ms-2 mark-tutor-item-viewed-btn" data-request-id="' . esc_attr($request_id) . '" data-item-type="incoming_request" data-nonce="' . wp_create_nonce('mark_tutor_item_viewed_' . $request_id) . '">Mark as Read</button>';
             }
            echo '</div>';

            echo '</div>'; // End list-group-item
        }
        echo '</div>'; // End list-group
    } else {
        echo '<div class="alert alert-info">No incoming reschedule requests from students requiring your attention.</div>';
    }

    $html = ob_get_clean();
    error_log('[AJAX TUTOR] Sending incoming requests HTML success response.');
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_load_tutor_incoming_requests', 'load_tutor_incoming_requests_ajax');

/**
 * AJAX handler for tutors to load student alternative time suggestions.
 */
function load_tutor_student_alternatives_ajax() {
    error_log('[AJAX TUTOR] load_tutor_student_alternatives_ajax started'); // Logging added
    check_ajax_referer('load_tutor_student_alternatives_action', 'nonce');

    $tutor_id = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : 0;
    if (!$tutor_id || !ol_hub_can_user_access_tutor_dashboard($tutor_id)) {
        error_log('[AJAX TUTOR] Permission denied or invalid tutor ID for student alternatives.');
        wp_send_json_error(['message' => 'Permission denied.'], 403);
        return;
    }
     error_log('[AJAX TUTOR] Tutor ID for student alternatives: ' . $tutor_id);

    $alternative_requests = get_reschedule_requests('tutor', $tutor_id, ['alternative_proposed']); // Fetch only requests where student proposed alternatives

    ob_start();
    if (!empty($alternative_requests)) {
        echo '<div class="list-group">';
        foreach ($alternative_requests as $request) {
            $request_id = $request->ID;
            $student_id = get_post_meta($request_id, 'student_id', true);
            $student_info = get_userdata($student_id);
            $student_name = $student_info ? esc_html($student_info->display_name) : 'Unknown Student';
            $original_lesson_time = get_post_meta($request_id, 'original_lesson_time', true);
            $tutor_proposed_time = get_post_meta($request_id, 'proposed_lesson_time', true); // Tutor's original proposal
            $student_preferred_times = get_post_meta($request_id, 'student_preferred_times', true);
            $student_comment = get_post_meta($request_id, 'student_response_comment', true);
            $viewed_by_tutor = get_post_meta($request_id, 'viewed_by_tutor', true);
            $created_date = $request->post_date; // Or maybe a modified date?

            $formatted_original = format_datetime($original_lesson_time);
            $formatted_tutor_proposed = format_datetime($tutor_proposed_time);
            $formatted_created = format_datetime($created_date);

            echo '<div class="list-group-item list-group-item-action flex-column align-items-start request-item-' . esc_attr($request_id) . '">';
            echo '<div class="d-flex w-100 justify-content-between">';
            echo '<h5 class="mb-1">Alternative Times from ' . $student_name . '</h5>';
            echo '<div><span class="badge bg-info text-dark">Student Proposed Alternatives</span>' . (!$viewed_by_tutor ? ' <span class="badge bg-danger">New</span>' : '') . '</div>';
            echo '</div>';
            echo '<p class="mb-1"><strong>Original Lesson:</strong> ' . $formatted_original . '</p>';
            echo '<p class="mb-1"><strong>Your Proposed Time:</strong> ' . $formatted_tutor_proposed . '</p>';
            if ($student_comment) {
                echo '<p class="mb-1 fst-italic text-muted"><strong>Student Comment:</strong> ' . esc_html($student_comment) . '</p>';
            }

            // Display the alternative times proposed by the student
            if (is_array($student_preferred_times) && !empty($student_preferred_times)) {
                 echo '<p class="mb-1"><strong>Student Suggested Times:</strong></p>';
                 echo '<div class="student-alternatives-list mt-2 mb-3">'; // Wrapper for styling/JS targeting
                 foreach ($student_preferred_times as $index => $alt_time) {
                     if (isset($alt_time['date']) && isset($alt_time['time'])) {
                         $alt_dt_value = $alt_time['date'] . ' ' . $alt_time['time'];
                         $formatted_alt = format_datetime($alt_dt_value);
                         echo '<div class="form-check">';
                         echo '<input class="form-check-input tutor-select-alternative-radio" type="radio" name="selected_alternative_' . esc_attr($request_id) . '" id="alt_time_' . esc_attr($request_id) . '_' . $index . '" value="' . esc_attr($alt_dt_value) . '">';
                         echo '<label class="form-check-label" for="alt_time_' . esc_attr($request_id) . '_' . $index . '">' . $formatted_alt . '</label>';
                         echo '</div>';
                     }
                 }
                 echo '</div>';

                 // Actions: Accept one of the alternatives or Decline all
                 echo '<div class="request-actions btn-group btn-group-sm" role="group">';
                 echo '<button class="btn btn-success tutor-accept-alternative-btn" data-request-id="' . esc_attr($request_id) . '" disabled>Accept Selected Time</button>'; // Disabled until a radio is chosen
                 echo '<button class="btn btn-danger tutor-decline-alternatives-btn" data-request-id="' . esc_attr($request_id) . '">Decline Alternatives</button>'; // Maybe opens a comment modal?
                 echo '</div>';
             } else {
                 echo '<p class="text-danger">Could not load student suggested times.</p>';
                 error_log("[AJAX TUTOR] Student preferred times missing or not an array for request ID: $request_id");
             }

            echo '<small class="text-muted">Received: ' . $formatted_created . '</small>';

             // Mark as viewed button
             if (!$viewed_by_tutor) {
                 echo '<button class="btn btn-sm btn-secondary ms-2 mt-2 mark-tutor-item-viewed-btn" data-request-id="' . esc_attr($request_id) . '" data-item-type="student_alternative" data-nonce="' . wp_create_nonce('mark_tutor_item_viewed_' . $request_id) . '">Mark as Read</button>';
             }

            echo '</div>'; // End list-group-item
        }
        echo '</div>'; // End list-group
    } else {
        echo '<div class="alert alert-info">No alternative time suggestions from students requiring your action.</div>';
    }

    $html = ob_get_clean();
    error_log('[AJAX TUTOR] Sending student alternatives HTML success response.');
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_load_tutor_student_alternatives', 'load_tutor_student_alternatives_ajax');

/**
 * AJAX handler for tutors to mark an item (notification source) as viewed.
 */
function mark_tutor_item_viewed_ajax() {
    error_log('[AJAX TUTOR] mark_tutor_item_viewed_ajax started'); // Logging added
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    $item_type = isset($_POST['item_type']) ? sanitize_text_field($_POST['item_type']) : ''; // e.g., 'incoming_request', 'outgoing_request', 'student_alternative'
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';

    // Verify nonce specific to this item
    if (!$request_id || !wp_verify_nonce($nonce, 'mark_tutor_item_viewed_' . $request_id)) {
         error_log('[AJAX TUTOR] Mark viewed nonce verification failed. Request ID: ' . $request_id . ' Item Type: ' . $item_type);
        wp_send_json_error(['message' => 'Nonce verification failed.'], 403);
        return;
    }

    // Permission check: Ensure the current user is the tutor associated with this request
    $tutor_id = get_current_user_id();
    $post_tutor_id = get_post_meta($request_id, 'tutor_id', true);

    if ($tutor_id != $post_tutor_id || !ol_hub_can_user_access_tutor_dashboard($tutor_id)) {
         error_log('[AJAX TUTOR] Mark viewed permission denied. User: ' . $tutor_id . ' Post Tutor: ' . $post_tutor_id . ' Request ID: ' . $request_id);
        wp_send_json_error(['message' => 'Permission denied.'], 403);
        return;
    }

     // Check if the post exists and is a lesson_reschedule post
    $post = get_post($request_id);
    if (!$post || $post->post_type !== 'lesson_reschedule') {
        error_log('[AJAX TUTOR] Mark viewed failed - Invalid request ID: ' . $request_id);
        wp_send_json_error(['message' => 'Invalid request ID.'], 400);
        return;
    }

    // Add or update the 'viewed_by_tutor' meta field
    $updated = update_post_meta($request_id, 'viewed_by_tutor', 'yes');

    if ($updated) {
        error_log('[AJAX TUTOR] Marked item as viewed. Request ID: ' . $request_id . ' Item Type: ' . $item_type);
        wp_send_json_success(['message' => 'Item marked as read.']);
    } else {
        // update_post_meta returns false if the value is unchanged or if an error occurred.
        // Check if it already exists with the value 'yes'
        if (get_post_meta($request_id, 'viewed_by_tutor', true) === 'yes') {
            error_log('[AJAX TUTOR] Item already marked as viewed. Request ID: ' . $request_id . ' Item Type: ' . $item_type);
             wp_send_json_success(['message' => 'Item already marked as read.']); // Still success
        } else {
             error_log('[AJAX TUTOR] Failed to mark item as viewed. Request ID: ' . $request_id . ' Item Type: ' . $item_type);
            wp_send_json_error(['message' => 'Failed to mark item as read.'], 500);
        }
    }
}
add_action('wp_ajax_mark_tutor_item_viewed', 'mark_tutor_item_viewed_ajax');


/**
 * AJAX handler for tutors to delete their outgoing reschedule requests.
 */
function delete_tutor_request_ajax() {
    error_log('[AJAX TUTOR] delete_tutor_request_ajax started'); // Logging added
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';

    if (!$request_id || !wp_verify_nonce($nonce, 'delete_tutor_request_' . $request_id)) {
        error_log('[AJAX TUTOR] Delete request nonce failed. Request ID: ' . $request_id);
        wp_send_json_error(['message' => 'Nonce verification failed.'], 403);
        return;
    }

    // Permission check: Ensure the current user is the tutor who initiated this request
    $tutor_id = get_current_user_id();
    $post_tutor_id = get_post_meta($request_id, 'tutor_id', true);
    $initiator = get_post_meta($request_id, 'initiator', true);

    if ($tutor_id != $post_tutor_id || $initiator !== 'tutor' || !ol_hub_can_user_access_tutor_dashboard($tutor_id)) {
        error_log('[AJAX TUTOR] Delete request permission denied. User: ' . $tutor_id . ' Post Tutor: ' . $post_tutor_id . ' Initiator: ' . $initiator . ' Request ID: ' . $request_id);
        wp_send_json_error(['message' => 'Permission denied.'], 403);
        return;
    }

    // Check post exists and type
     $post = get_post($request_id);
     if (!$post || $post->post_type !== 'lesson_reschedule') {
         error_log('[AJAX TUTOR] Delete request failed - Invalid request ID: ' . $request_id);
         wp_send_json_error(['message' => 'Invalid request ID.'], 400);
         return;
     }

    // Delete the post (move to trash first)
    $deleted = wp_trash_post($request_id);

    if ($deleted) {
        error_log('[AJAX TUTOR] Deleted (trashed) request. Request ID: ' . $request_id);
        wp_send_json_success(['message' => 'Request deleted successfully.']);
    } else {
        error_log('[AJAX TUTOR] Failed to delete (trash) request. Request ID: ' . $request_id);
        wp_send_json_error(['message' => 'Failed to delete request.'], 500);
    }
}
add_action('wp_ajax_delete_tutor_request', 'delete_tutor_request_ajax');

// ======================================
// == Student AJAX Handlers
// ======================================

/**
 * AJAX handler for students to check for incoming items (notifications).
 */
function check_student_incoming_requests_ajax() {
    error_log('[AJAX STUDENT] check_student_incoming_requests_ajax started');

    check_ajax_referer('ol_hub_check_student_incoming_action', 'nonce'); 
    error_log('[AJAX STUDENT] Nonce verified.');

    if (!is_user_logged_in() || !current_user_can('student')) {
        error_log('[AJAX STUDENT ERROR] User not logged in or not a student.');
        wp_send_json_error(['message' => 'Access denied.'], 403);
        wp_die();
    }
    $student_id = get_current_user_id();
    error_log('[AJAX STUDENT] Student ID: ' . $student_id);

    // --- Fetch Data --- 
    // Note: Using 'lesson_reschedule' post type based on other handlers.
    // Adjust post_type and meta_keys if your structure is different.

    // Query 1: Pending Tutor-Initiated Requests for this student
    $pending_tutor_requests_args = [
        'post_type'      => 'lesson_reschedule', 
        'posts_per_page' => -1,
        'post_status'    => 'pending', 
        'meta_query'     => [
            'relation' => 'AND',
            ['key' => 'student_id', 'value' => $student_id, 'compare' => '='],
            ['key' => 'initiator', 'value' => 'tutor', 'compare' => '='],
            // Count only if not viewed by student
            [
                'relation' => 'OR',
                ['key' => 'viewed_by_student', 'compare' => 'NOT EXISTS'],
                ['key' => 'viewed_by_student', 'value' => 'yes', 'compare' => '!= ']
            ]
        ]
        // Fetch full post objects now
    ];
    $pending_tutor_requests_query = new WP_Query($pending_tutor_requests_args);
    $pending_tutor_requests = $pending_tutor_requests_query->get_posts();
    $pending_tutor_request_count = $pending_tutor_requests_query->post_count;
    error_log('[AJAX STUDENT] Pending tutor requests count: ' . $pending_tutor_request_count);

    // Query 2: Status Updates on Student-Initiated Requests (accepted/declined/alternatives)
    $status_changes_args = [
        'post_type'      => 'lesson_reschedule',
        'posts_per_page' => -1,
        'author'         => $student_id, // Initiated by student
        'post_status'    => ['accepted', 'declined', 'tutor_alternative_proposed'], // Statuses set by tutor
        'meta_query'     => [
            'relation' => 'AND',
            ['key' => 'initiator', 'value' => 'student', 'compare' => '='],
             // Count only if not viewed by student
            [
                'relation' => 'OR',
                ['key' => 'viewed_by_student', 'compare' => 'NOT EXISTS'],
                ['key' => 'viewed_by_student', 'value' => 'yes', 'compare' => '!= ']
            ]
        ]
        // Fetch full post objects if needed for notifications HTML
        // For now, just counting them for the total badge
        // 'fields'         => 'ids' 
    ];
    $unread_status_changes_query = new WP_Query($status_changes_args);
    $unread_status_changes_count = $unread_status_changes_query->post_count;
    error_log('[AJAX STUDENT] Unread status changes count: ' . $unread_status_changes_count);

    // Query 3: Tutor Responses with Alternative Times (Student initiated, tutor proposed alt)
     $tutor_alternatives_args = [
        'post_type'      => 'lesson_reschedule',
        'posts_per_page' => -1,
        'author'         => $student_id, // Request initially from student
        'post_status'    => 'tutor_alternative_proposed', 
        'meta_query'     => [
            'relation' => 'AND',
            ['key' => 'initiator', 'value' => 'student', 'compare' => '='],
             [
                 'relation' => 'OR', // Not viewed yet
                 [ 'key' => 'viewed_by_student', 'compare' => 'NOT EXISTS' ],
                 [ 'key' => 'viewed_by_student', 'value' => 'yes', 'compare' => '!=' ]
             ]
        ]
        // Fetch full posts if needed for notification HTML
    ];
    $unread_tutor_alternatives_query = new WP_Query($tutor_alternatives_args);
    $unread_tutor_alternatives_count = $unread_tutor_alternatives_query->post_count;
    error_log('[AJAX STUDENT] Unread tutor alternatives count: ' . $unread_tutor_alternatives_count);

    // Total unread for main badge
    $total_unread_count = $pending_tutor_request_count + $unread_status_changes_count; // Alternatives are included in status changes count
    error_log('[AJAX STUDENT] Total unread count: ' . $total_unread_count);

    // --- Generate Notifications HTML --- 
    ob_start();
    error_log('[AJAX STUDENT HTML GEN] Starting notifications HTML generation.');
    try {
        if ($pending_tutor_request_count > 0 || $unread_status_changes_count > 0) {
            ?>
            <div class="alert alert-info">
                <h5><i class="fas fa-bell me-2"></i>Notifications</h5>
                <ul class="mb-0" style="list-style: none; padding-left: 0;">
                    <?php if ($pending_tutor_request_count > 0): ?>
                        <li class="mb-2">
                            <i class="fas fa-arrow-right me-1 text-warning"></i> You have <strong><?php echo $pending_tutor_request_count; ?></strong> pending request<?php echo ($pending_tutor_request_count > 1 ? 's' : ''); ?> from your tutor.
                            <a href="#incomingRescheduleSection" class="btn btn-sm btn-outline-primary ms-2 scroll-to">View Requests</a>
                        </li>
                    <?php endif; ?>
                    <?php 
                    // Combine accepted/declined/alternative notifications
                    if ($unread_status_changes_count > 0) {
                        // Check counts for specific statuses if needed for different messages
                        $accepted_count = 0;
                        $declined_count = 0;
                        $alt_proposed_count = $unread_tutor_alternatives_count; // From query above
                        // We might need to iterate through $unread_status_changes_query->get_posts() if we didn't fetch IDs only
                        // For now, using the total count

                        if ($alt_proposed_count > 0) {
                            echo '<li class="mb-2">';
                            echo '<i class="fas fa-exchange-alt me-1 text-primary"></i> Your tutor proposed alternative times for <strong>' . $alt_proposed_count . '</strong> request' . ($alt_proposed_count > 1 ? 's' : '') . '.';
                            echo ' <a href="#tutorAlternativesSection" class="btn btn-sm btn-outline-primary ms-2 scroll-to">View Alternatives</a>';
                            echo '</li>';
                        }
                        // Add separate lines for accepted/declined if required
                        $other_status_updates_count = $unread_status_changes_count - $alt_proposed_count;
                        if ($other_status_updates_count > 0) {
                             echo '<li class="mb-2">';
                             echo '<i class="fas fa-info-circle me-1 text-success"></i> You have <strong>' . $other_status_updates_count . '</strong> update' . ($other_status_updates_count > 1 ? 's' : '') . ' on your requests (accepted/declined).';
                             echo ' <a href="#outgoingRescheduleSection" class="btn btn-sm btn-outline-primary ms-2 scroll-to">View Your Requests</a>';
                             echo '</li>';
                        }
                    }
                    ?>
                </ul>
            </div>
            <?php
        } else {
            echo '<div class="alert alert-light text-center"><i class="fas fa-check-circle text-success"></i> No pending notifications.</div>';
        }
        error_log('[AJAX STUDENT HTML GEN] Finished notifications HTML generation (before ob_get_clean).');
    } catch (Throwable $t) { // Catch any potential errors/exceptions
        error_log('[AJAX STUDENT HTML GEN ERROR] Error during notifications HTML: ' . $t->getMessage());
        // Optionally echo an error message into the buffer if needed
    }
    $notifications_html = ob_get_clean();

    // --- Generate Incoming Requests Table HTML --- 
    ob_start();
    error_log('[AJAX STUDENT HTML GEN] Starting incoming requests table HTML generation.');
    try {
        if (!empty($pending_tutor_requests)) {
            error_log('[AJAX STUDENT HTML GEN] Processing ' . count($pending_tutor_requests) . ' incoming requests.');
            foreach ($pending_tutor_requests as $request_index => $request) {
                 error_log('[AJAX STUDENT HTML GEN] Loop ' . $request_index . ' - Request ID: ' . $request->ID);
                $request_id = $request->ID;
                $tutor_id = get_post_meta($request_id, 'tutor_id', true);
                $tutor_info = get_userdata($tutor_id);
                $tutor_display_name = $tutor_info ? esc_html($tutor_info->display_name) : 'Unknown Tutor';
                $original_lesson_time = get_post_meta($request_id, 'original_lesson_time', true);
                $proposed_lesson_time = get_post_meta($request_id, 'proposed_lesson_time', true);
                $reason = get_post_meta($request_id, 'reason', true);
                $created_date = $request->post_date;
                $status = $request->post_status; // Should be 'pending'
                $viewed_by_student = get_post_meta($request_id, 'viewed_by_student', true);
                error_log('[AJAX STUDENT HTML GEN] Loop ' . $request_index . ' - Got meta data.');

                // Call helper functions and log potential issues
                $formatted_original = function_exists('format_datetime') ? format_datetime($original_lesson_time) : 'ERR_FUNC';
                $formatted_proposed = function_exists('format_datetime') ? format_datetime($proposed_lesson_time) : 'ERR_FUNC';
                $formatted_created = function_exists('format_datetime') ? format_datetime($created_date, 'M j, Y') : 'ERR_FUNC';
                $status_badge = function_exists('get_status_badge') ? get_status_badge($status, 'student') : 'ERR_FUNC';
                error_log('[AJAX STUDENT HTML GEN] Loop ' . $request_index . ' - Called helpers. Badge: ' . $status_badge);
                
                // Existing echo statements for the table row...
                ?>
                <tr>
                    <td><?php echo esc_html($formatted_created); ?></td>
                    <td><?php echo esc_html($formatted_original); ?></td>
                    <td><?php echo esc_html($formatted_proposed); ?></td>
                    <td><?php echo esc_html($tutor_display_name); ?></td>
                    <td><?php echo $status_badge . (!$viewed_by_student ? ' <span class="badge bg-danger">New</span>' : ''); ?></td>
                    <td class="request-actions">
                        <button class="btn btn-sm btn-success handle-tutor-request-btn" 
                                data-action="accept" 
                                data-request-id="<?php echo $request_id; ?>" 
                                data-tutor-name="<?php echo esc_attr($tutor_display_name); ?>"
                                data-original-lesson="<?php echo esc_attr($formatted_original); ?>"
                                data-proposed-lesson="<?php echo esc_attr($formatted_proposed); ?>"
                                data-nonce="<?php echo wp_create_nonce('student_accept_tutor_request_' . $request_id); ?>">
                            <i class="fas fa-check"></i> Accept
                        </button>
                        <button class="btn btn-sm btn-danger handle-tutor-request-btn" 
                                data-action="decline" 
                                data-request-id="<?php echo $request_id; ?>" 
                                data-nonce="<?php echo wp_create_nonce('student_decline_tutor_request_' . $request_id); ?>">
                            <i class="fas fa-times"></i> Decline
                        </button>
                         <button class="btn btn-sm btn-warning handle-tutor-request-btn" 
                                data-action="propose_alt" 
                                data-request-id="<?php echo $request_id; ?>" 
                                data-nonce="<?php echo wp_create_nonce('student_propose_alt_tutor_request_' . $request_id); ?>">
                            <i class="fas fa-calendar-alt"></i> Propose Alt.
                        </button>
                         <?php if (!$viewed_by_student): ?>
                            <button class="btn btn-sm btn-secondary ms-1 mark-student-item-viewed-btn" 
                                    data-item-type="incoming_tutor_request"
                                    data-request-id="<?php echo $request_id; ?>" 
                                    data-nonce="<?php echo wp_create_nonce('ol_hub_mark_student_item_viewed_' . $request_id); ?>">
                                <i class="far fa-eye"></i> Mark Read
                            </button>
                         <?php endif; ?>
                    </td>
                </tr>
                <?php
                error_log('[AJAX STUDENT HTML GEN] Loop ' . $request_index . ' - Finished row.');
            }
        } else {
            echo '<tr><td colspan="6">No incoming requests from your tutor.</td></tr>';
        }
         error_log('[AJAX STUDENT HTML GEN] Finished incoming requests table HTML generation (before ob_get_clean).');
    } catch (Throwable $t) {
        error_log('[AJAX STUDENT HTML GEN ERROR] Error during incoming table HTML: ' . $t->getMessage());
    }
    $incoming_requests_html = ob_get_clean();

    // --- Debug: Log the generated HTML before sending --- 
    error_log('[AJAX STUDENT DEBUG] Notifications HTML Length: ' . strlen($notifications_html));
    error_log('[AJAX STUDENT DEBUG] Incoming Requests HTML Length: ' . strlen($incoming_requests_html));
    // Optionally log the first few characters if needed: error_log('[AJAX STUDENT DEBUG] Notifications HTML Start: ' . substr($notifications_html, 0, 100));

    // --- Prepare JSON Response --- 
    // Pass the data array directly to wp_send_json_success
    // It will automatically be wrapped in a 'data' key.
    $response_data = [
        'count' => $total_unread_count,
        'pendingTutorRequestCount' => $pending_tutor_request_count,
        'unreadStatusChangeCount' => $unread_status_changes_count, 
        'unreadTutorAlternativesCount' => $unread_tutor_alternatives_count,
        'notificationsHtml' => $notifications_html,
        'incomingTutorRequestsHtml' => $incoming_requests_html
    ];

    error_log('[AJAX STUDENT] Sending JSON success response with HTML.');
    wp_send_json_success($response_data); 

    wp_die();
}
add_action('wp_ajax_check_student_incoming_requests', 'check_student_incoming_requests_ajax');

/**
 * AJAX handler for students to mark tutor alternative suggestions as viewed.
 * Action: mark_tutor_alternatives_viewed
 * Nonce: mark_tutor_alternatives_viewed_nonce
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
 * Action: delete_student_request
 * Nonce: ol_hub_delete_student_request_action (via localization handle 'deleteStudentRequest')
 */
function delete_student_request_ajax() {
     error_log('[AJAX STUDENT] delete_student_request_ajax started');
    // Use the same prefixed action name as in wp_create_nonce
     check_ajax_referer('ol_hub_delete_student_request_action', 'nonce');
     error_log('[AJAX STUDENT] Delete nonce verified.');

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