<?php
// Helper function to format date and time
function format_lesson_datetime($date_str, $time_str, $format = 'M j, Y \a\t g:i A') {
    if (empty($date_str) || empty($time_str)) {
        return 'N/A';
    }
    try {
        $datetime = new DateTime($date_str . ' ' . $time_str);
        return $datetime->format($format);
    } catch (Exception $e) {
        // Log error or handle gracefully
        return 'Invalid Date/Time';
    }
}

// Helper function to get tutor's full name with caching
function get_tutor_display_name($tutor_login) {
    static $tutor_names_cache = []; // Cache tutor names

    if (isset($tutor_names_cache[$tutor_login])) {
        return $tutor_names_cache[$tutor_login];
    }

    $tutor_user = get_user_by('login', $tutor_login);
    if ($tutor_user) {
        $first_name = get_user_meta($tutor_user->ID, 'first_name', true);
        $last_name = get_user_meta($tutor_user->ID, 'last_name', true);

        if (!empty($first_name) && !empty($last_name)) {
            $full_name = $first_name . ' ' . $last_name;
        } else {
            $full_name = $tutor_user->display_name;
        }
        $tutor_names_cache[$tutor_login] = $full_name; // Store in cache
        return $full_name;
    }
    return $tutor_login; // Fallback to login name
}

// Helper function to generate status badge and notification
function get_status_display($status, $request_id, $tutor_response) {
    $status_badge = '';
    $notification = '';

    switch ($status) {
        case 'confirmed':
        case 'accepted':
            $status_badge = '<span class="badge bg-success">Accepted</span>';
            break;
        case 'denied':
        case 'declined':
            $status_badge = '<span class="badge bg-danger">Declined</span>';
            if (!empty($tutor_response)) {
                $notification = '<div class="mt-1"><small class="text-danger"><i class="fas fa-info-circle"></i> ' . esc_html($tutor_response) . '</small></div>';
            }
            break;
        case 'unavailable':
            $status_badge = '<span class="badge bg-warning">Tutor Unavailable</span>';
            // Check for alternative times (simplified check, assumes existence implies display)
            $alternative_request = get_posts(array(
                'post_type' => 'progress_report', 'posts_per_page' => 1, 'meta_query' => array(
                    array('key' => 'original_request_id', 'value' => $request_id, 'compare' => '='),
                    array('key' => 'request_type', 'value' => 'tutor_unavailable', 'compare' => '=')
                ), 'fields' => 'ids' // More efficient query
            ));
            if (!empty($alternative_request)) {
                 $notification = '<div class="mt-1"><small class="text-info"><i class="fas fa-info-circle"></i> Tutor has provided alternative times</small></div>';
                 $notification .= '<a href="#alternativeTimesSection" class="btn btn-sm btn-outline-primary mt-1">View Alternative Times</a>';
            } else {
                 $notification = '<div class="mt-1"><small class="text-warning"><i class="fas fa-info-circle"></i> Tutor is unavailable for this time</small></div>';
            }
            break;
        default: // Pending or other statuses
            $status_badge = '<span class="badge bg-warning">Pending</span>';
            break;
    }
    return $status_badge . $notification;
}

// Helper function to format preferred times
function format_preferred_times_display($preferred_times) {
    if (empty($preferred_times) || !is_array($preferred_times)) {
        return 'No preferred times specified';
    }

    $output = '';
    foreach ($preferred_times as $index => $time) {
         if (!empty($time['date']) && !empty($time['time'])) {
            $formatted_time = format_lesson_datetime($time['date'], $time['time']);
            $output .= 'Option ' . ($index + 1) . ': ' . esc_html($formatted_time) . '<br>';
        }
    }
    return $output ?: 'No valid preferred times specified';
}

// Helper function to render the reason cell
function render_reason_cell($reason) {
    if (!empty($reason)) {
        $truncated_reason = strlen($reason) > 30 ? substr($reason, 0, 30) . '...' : $reason;
        return '<span class="reason-text" style="cursor: pointer; color: #0056b3;"
                       data-bs-toggle="modal"
                       data-bs-target="#reasonModal"
                       data-reason="' . esc_attr($reason) . '">'
                       . esc_html($truncated_reason) . '</span>';
    } else {
        return '<em>No reason provided</em>';
    }
}

// Helper function to determine if a request is expired
function is_request_expired($original_date, $original_time, $preferred_times) {
    try {
        $brisbane_tz = new DateTimeZone('Australia/Brisbane');
        $now = new DateTime('now', $brisbane_tz);

        // Check original lesson time
        $lesson_datetime_str = $original_date . ' ' . $original_time;
        if (!empty($original_date) && !empty($original_time)) {
             $lesson_date = DateTime::createFromFormat('Y-m-d H:i:s', $lesson_datetime_str, $brisbane_tz) ?: new DateTime($lesson_datetime_str, $brisbane_tz);
             if ($lesson_date && $lesson_date < $now) {
                 // Original time passed, now check preferred times if any
                 if (!empty($preferred_times) && is_array($preferred_times)) {
                     foreach ($preferred_times as $time) {
                         if (!empty($time['date']) && !empty($time['time'])) {
                             $preferred_datetime_str = $time['date'] . ' ' . $time['time'];
                             $preferred_date = DateTime::createFromFormat('Y-m-d H:i:s', $preferred_datetime_str, $brisbane_tz) ?: new DateTime($preferred_datetime_str, $brisbane_tz);
                             if ($preferred_date && $preferred_date >= $now) {
                                 return false; // Found a future preferred time
                             }
                         }
                     }
                 }
                 return true; // Original time passed and all preferred times (if any) also passed
             }
        }

         // If original date is invalid or hasn't passed, check only preferred times
         if (!empty($preferred_times) && is_array($preferred_times)) {
              $all_preferred_passed = true;
              foreach ($preferred_times as $time) {
                  if (!empty($time['date']) && !empty($time['time'])) {
                       $preferred_datetime_str = $time['date'] . ' ' . $time['time'];
                       $preferred_date = DateTime::createFromFormat('Y-m-d H:i:s', $preferred_datetime_str, $brisbane_tz) ?: new DateTime($preferred_datetime_str, $brisbane_tz);
                       if ($preferred_date && $preferred_date >= $now) {
                           $all_preferred_passed = false;
                           break; // Found a future preferred time
                       }
                  }
              }
              return $all_preferred_passed;
         }


        return false; // Default to not expired if insufficient data or dates are in the future
    } catch (Exception $e) {
        // Log error or handle gracefully
        return false; // Treat exceptions as non-expired
    }
}


// Helper function to render action buttons
function render_action_buttons($request_id, $status, $tutor_name, $original_date, $original_time, $reason, $preferred_times) {
    $actions = '';
    $is_pending = !in_array($status, ['confirmed', 'accepted', 'denied', 'declined']);
    $is_archivable_status = in_array($status, ['denied', 'declined']);
    $is_completed_status = in_array($status, ['confirmed', 'accepted']);

    $delete_form_template = '
        <form method="post" class="d-inline delete-request-form">
            <input type="hidden" name="delete_student_request" value="1">
            <input type="hidden" name="request_id" value="%1$d">
            <input type="hidden" name="active_tab" value="requests">
            <button type="submit" class="btn btn-sm %2$s"><i class="fas %3$s"></i> %4$s</button>
        </form>';

    if ($is_pending) {
        $preferred_times_json = !empty($preferred_times) ? esc_attr(json_encode($preferred_times)) : '';
        $actions .= '<button type="button" class="btn btn-sm btn-primary me-1 edit-request-btn"
                           data-bs-toggle="modal"
                           data-bs-target="#editRescheduleRequestModal"
                           data-request-id="' . $request_id . '"
                           data-tutor-name="' . esc_attr($tutor_name) . '"
                           data-original-date="' . esc_attr($original_date) . '"
                           data-original-time="' . esc_attr($original_time) . '"
                           data-reason="' . esc_attr($reason) . '"
                           data-preferred-times="' . $preferred_times_json . '">
                           <i class="fas fa-edit"></i> Edit
                       </button>';
        $actions .= sprintf($delete_form_template, $request_id, 'btn-danger', 'fa-trash', 'Delete');

    } elseif ($is_archivable_status) {
        $actions .= sprintf($delete_form_template, $request_id, 'btn-secondary', 'fa-archive', 'Archive');
    } elseif ($is_completed_status && is_request_expired($original_date, $original_time, $preferred_times)) {
         $actions .= sprintf($delete_form_template, $request_id, 'btn-secondary', 'fa-archive', 'Archive');
    }

    return $actions ?: '<span class="text-muted">No actions available</span>';
}
?>
<!-- Outgoing Reschedule Requests (Student-initiated) -->
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <i class="fas fa-arrow-left me-2"></i> Your Outgoing Reschedule Requests
    </div>
    <div class="card-body">
        <div class="table-container" style="max-height: 400px; overflow-y: auto;">
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
                echo '<thead><tr><th>Date Requested</th><th>Lesson Date</th><th>Preferred Times</th><th>Tutor</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>';
                echo '<tbody>';

                foreach ($student_requests as $request) {
                    $request_id = $request->ID;
                    // Fetch all meta data at once if possible, or fetch individually
                    $tutor_login_name = get_post_meta($request_id, 'tutor_name', true);
                    $original_date = get_post_meta($request_id, 'original_date', true); // Assumes Y-m-d
                    $original_time = get_post_meta($request_id, 'original_time', true); // Assumes H:i:s
                    $status = get_post_meta($request_id, 'status', true);
                    $reason = get_post_meta($request_id, 'reason', true);
                    $preferred_times = get_post_meta($request_id, 'preferred_times', true);
                    $tutor_response = get_post_meta($request_id, 'tutor_response', true);
                    $request_date = get_the_date('M j, Y', $request_id); // Already formatted

                    // Use helper functions
                    $formatted_original = format_lesson_datetime($original_date, $original_time);
                    $tutor_full_name = get_tutor_display_name($tutor_login_name);
                    $status_display = get_status_display($status, $request_id, $tutor_response);
                    $preferred_times_display = format_preferred_times_display($preferred_times);
                    $reason_cell = render_reason_cell($reason);
                    $action_buttons = render_action_buttons($request_id, $status, $tutor_login_name, $original_date, $original_time, $reason, $preferred_times);

                    echo '<tr>';
                    echo '<td>' . esc_html($request_date) . '</td>';
                    echo '<td>' . esc_html($formatted_original) . '</td>';
                    echo '<td>' . $preferred_times_display . '</td>'; // Already escaped in helper if needed, or assumed safe
                    echo '<td>' . esc_html($tutor_full_name) . '</td>';
                    echo '<td>' . $reason_cell . '</td>'; // Already escaped in helper
                    echo '<td>' . $status_display . '</td>'; // Contains HTML, assumed safe
                    echo '<td>' . $action_buttons . '</td>'; // Contains HTML, assumed safe
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
</div>

<!-- Add Modal for displaying full reason text -->
<div class="modal fade" id="reasonModal" tabindex="-1" aria-labelledby="reasonModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reasonModalLabel">Reschedule Reason</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="fullReasonText" class="p-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var reasonModal = document.getElementById('reasonModal');
    if (reasonModal) {
        reasonModal.addEventListener('show.bs.modal', function (event) {
            // Button that triggered the modal
            var button = event.relatedTarget;
            // Extract info from data-* attributes
            var reason = button.getAttribute('data-reason');
            // Update the modal's content.
            var modalBodyInput = reasonModal.querySelector('#fullReasonText');

            // Use textContent to prevent potential XSS if reason contains HTML/JS
            modalBodyInput.textContent = reason ? reason : 'No reason provided.';
        });
    }
});
</script>
