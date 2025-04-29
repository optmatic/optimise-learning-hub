<?php
/**
 * Template part for displaying tutor reschedule request interface.
 *
 * @package Understrap
 */

// Prevent direct file access
defined( 'ABSPATH' ) || exit;

// Ensure we're on the tutor dashboard page and the user is a tutor
if ( ! is_page_template( 'tutor-dashboard.php' ) || ! current_user_can( 'tutor' ) ) {
    // Optionally, redirect or display an error message
    // For example: wp_safe_redirect( home_url() ); exit;
    // Or: echo "<p>Access Denied</p>";
    return; // Exit if accessed directly or by wrong user role
}

// Fetch current tutor data
$current_user = wp_get_current_user();
$tutor_id     = $current_user->ID;

// Get outgoing requests initiated by the tutor
$outgoing_args = [
    'request_type' => 'tutor_reschedule',
    'author'       => $tutor_id,
    'status'       => ['pending', 'approved', 'rejected', 'cancelled', 'alternatives_proposed']
];
$outgoing_requests = get_reschedule_requests($outgoing_args);

// Get alternative time suggestions from students for this tutor's requests
// We need the original request IDs to filter alternatives
$tutor_request_ids = !empty($outgoing_requests) ? wp_list_pluck($outgoing_requests, 'ID') : [];
$alternative_args = [
    'request_type' => 'reschedule_alternatives',
    'status'       => 'pending', // Only show pending alternatives needing tutor action
    'meta_query' => [
        [
            'key'     => 'original_request_id',
            'value'   => $tutor_request_ids,
            'compare' => 'IN',
        ]
    ]
];
// Ensure we only query if there are tutor requests to reference
$alternative_requests = !empty($tutor_request_ids) ? get_reschedule_requests($alternative_args) : [];

?>

<div class="tutor-requests-section">

    <!-- Notifications Placeholder -->
    <div id="tutor-notifications-container">
        <!-- Notifications will be loaded here via AJAX -->
        <div class="text-center"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading notifications...</div>
    </div>
    <hr/>

    <!-- Initiate Lesson Reschedule Request -->
    <div class="initiate-request-section mb-4 text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tutorRescheduleModal">
            <i class="fa-regular fa-calendar-plus me-2"></i>Initiate Lesson Reschedule Request
        </button>
    </div>

    <!-- Outgoing Reschedule Requests Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">My Outgoing Reschedule Requests</h5>
        </div>
        <div class="card-body">
            <?php if ( ! empty( $outgoing_requests ) ) : ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover request-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Original Lesson</th>
                                <th>Reason</th>
                                <th>Proposed Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $outgoing_requests as $request ) : ?>
                                <?php
                                $request_id       = $request->ID;
                                $student_id       = get_post_meta( $request_id, 'student_id', true );
                                $student_name     = get_student_display_name($student_id);
                                $original_lesson_time = get_post_meta( $request_id, 'original_lesson_time', true );
                                $reason           = get_post_meta( $request_id, 'reason', true );
                                $proposed_time    = get_post_meta( $request_id, 'proposed_reschedule_time', true );
                                $status           = get_post_meta( $request_id, 'status', true );
                                $status_badge     = get_status_badge( $status );

                                // Check if the student has proposed alternative times for this request
                                $has_alternatives = has_pending_student_alternatives($request_id);
                                ?>
                                <tr>
                                    <td><?php echo esc_html( $student_name ); ?></td>
                                    <td><?php echo esc_html( format_datetime( $original_lesson_time ) ); ?></td>
                                    <td><?php echo esc_html( $reason ); ?></td>
                                    <td><?php echo esc_html( format_datetime( $proposed_time ) ); ?></td>
                                    <td><?php echo $status_badge; ?></td>
                                    <td>
                                        <?php if ( $status === 'pending' ) : ?>
                                            <button class="btn btn-sm btn-warning me-1 edit-tutor-request-btn" data-request-id="<?php echo esc_attr($request_id); ?>" data-bs-toggle="tooltip" title="Edit Request">
                                                <i class="fa-solid fa-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-tutor-request-btn" data-request-id="<?php echo esc_attr($request_id); ?>" data-bs-toggle="tooltip" title="Cancel Request">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        <?php elseif ( $status === 'alternatives_proposed' && $has_alternatives ) : ?>
                                            <span class="badge bg-info">Pending Student Alternatives</span>
                                            <!-- Maybe add a link/button to jump to the alternatives section -->
                                            <a href="#student-alternatives-card" class="btn btn-sm btn-info ms-1" data-bs-toggle="tooltip" title="View Alternatives">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        <?php else : ?>
                                            <span class="text-muted">No actions</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p>You haven't initiated any reschedule requests yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Incoming Requests Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Incoming Requests from Students</h5>
        </div>
        <div class="card-body" id="tutor-incoming-requests-container">
            <!-- Incoming requests will be loaded here via AJAX -->
            <div class="text-center"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading incoming requests...</div>
        </div>
    </div>

    <!-- Student Alternative Time Suggestions Card -->
    <?php if ( ! empty( $alternative_requests ) ) : ?>
    <div class="card mb-4" id="student-alternatives-card">
        <div class="card-header">
            <h5 class="mb-0">Student Alternative Time Suggestions</h5>
        </div>
        <div class="card-body">
            <div class="accordion" id="studentAlternativesAccordion">
                <?php foreach ( $alternative_requests as $index => $alt_request ) : ?>
                    <?php
                    $alt_request_id       = $alt_request->ID;
                    $original_request_id  = get_post_meta( $alt_request_id, 'original_request_id', true );
                    $student_id           = $alt_request->post_author; // Student initiated this
                    $student_name         = get_student_display_name($student_id);
                    $original_lesson_time = get_post_meta( $original_request_id, 'original_lesson_time', true ); // Fetch from original request
                    $reason               = get_post_meta( $alt_request_id, 'reason', true ); // Reason for alternatives
                    $suggested_times      = get_post_meta( $alt_request_id, 'suggested_times', true ); // Array of times
                    $status               = get_post_meta( $alt_request_id, 'status', true );
                    $status_badge         = get_status_badge( $status );

                    // Get the tutor's originally proposed time from the parent request
                    $tutor_proposed_time = get_post_meta( $original_request_id, 'proposed_reschedule_time', true );
                    ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingAlternative<?php echo esc_attr($index); ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAlternative<?php echo esc_attr($index); ?>" aria-expanded="false" aria-controls="collapseAlternative<?php echo esc_attr($index); ?>">
                                Suggestion from <?php echo esc_html( $student_name ); ?> regarding lesson on <?php echo esc_html( format_datetime( $original_lesson_time ) ); ?>
                                <span class="ms-auto me-3"><?php echo $status_badge; ?></span>
                            </button>
                        </h2>
                        <div id="collapseAlternative<?php echo esc_attr($index); ?>" class="accordion-collapse collapse" aria-labelledby="headingAlternative<?php echo esc_attr($index); ?>" data-bs-parent="#studentAlternativesAccordion">
                            <div class="accordion-body">
                                <p><strong>Student:</strong> <?php echo esc_html( $student_name ); ?></p>
                                <p><strong>Original Lesson Time:</strong> <?php echo esc_html( format_datetime( $original_lesson_time ) ); ?></p>
                                <p><strong>Your Proposed Time:</strong> <?php echo esc_html( format_datetime( $tutor_proposed_time ) ); ?></p>
                                <p><strong>Student's Reason:</strong> <?php echo esc_html( $reason ); ?></p>
                                <p><strong>Student's Suggested Times:</strong></p>
                                <?php if ( is_array( $suggested_times ) && ! empty( $suggested_times ) ) : ?>
                                    <ul>
                                        <?php foreach ( $suggested_times as $time ) : ?>
                                            <li><?php echo esc_html( format_datetime( $time ) ); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else : ?>
                                    <p>No specific times suggested.</p>
                                <?php endif; ?>

                                <?php if ($status === 'pending'): ?>
                                <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="mt-3 handle-alternative-request-form">
                                    <?php wp_nonce_field( 'handle_alternative_action_' . $alt_request_id, 'handle_alternative_nonce' ); ?>
                                    <input type="hidden" name="action" value="handle_alternative_request">
                                    <input type="hidden" name="alternative_request_id" value="<?php echo esc_attr( $alt_request_id ); ?>">
                                    <input type="hidden" name="original_request_id" value="<?php echo esc_attr( $original_request_id ); ?>">
                                    <input type="hidden" name="user_role" value="tutor">

                                    <p><strong>Accept one of the student's times:</strong></p>
                                     <?php if ( is_array( $suggested_times ) && ! empty( $suggested_times ) ) : ?>
                                        <div class="mb-3">
                                            <?php foreach ( $suggested_times as $time_key => $time_value ) : ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="selected_time" id="time_<?php echo esc_attr($alt_request_id . '_' . $time_key); ?>" value="<?php echo esc_attr($time_value); ?>" required>
                                                <label class="form-check-label" for="time_<?php echo esc_attr($alt_request_id . '_' . $time_key); ?>">
                                                    <?php echo esc_html( format_datetime( $time_value ) ); ?>
                                                </label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button type="submit" name="alternative_action" value="approve" class="btn btn-success me-2">
                                            <i class="fa-solid fa-check me-1"></i> Accept Selected Time
                                        </button>
                                    <?php else : ?>
                                        <p class="text-muted">No times available to select.</p>
                                    <?php endif; ?>

                                    <button type="submit" name="alternative_action" value="reject" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject these suggestions? The original request will remain pending.');">
                                        <i class="fa-solid fa-times me-1"></i> Reject Suggestions
                                    </button>
                                </form>
                                <?php else: ?>
                                     <p class="mt-3"><em>This suggestion has already been actioned.</em></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; // End check for alternative_requests ?>
</div>

<!-- Include the Tutor Reschedule Modal -->
<?php get_template_part('template-parts/modals/tutor', 'reschedule-modal'); ?>