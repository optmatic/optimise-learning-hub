    <!-- Incoming Reschedule Requests (Student-initiated) -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <i class="fas fa-arrow-right me-2"></i> Incoming Reschedule Requests
        </div>
        <div class="card-body">
            <?php
            $student_requests = get_student_initiated_requests();
            
            if (!empty($student_requests)) : ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date Requested</th>
                                <th>Original Lesson</th>
                                <th>Preferred Times</th>
                                <th>Student</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($student_requests as $request) :
                                $request_id = $request->ID;
                                $student_id = get_post_meta($request_id, 'student_id', true);
                                $student_name_meta = get_post_meta($request_id, 'student_name', true); // Fallback if ID is missing
                                $original_date = get_post_meta($request_id, 'original_date', true);
                                $original_time = get_post_meta($request_id, 'original_time', true);
                                $request_date = get_the_date('M j, Y', $request_id);
                                $reason = get_post_meta($request_id, 'reason', true);
                                $preferred_times = get_post_meta($request_id, 'preferred_times', true); // Assumes this is an array
                                $status = get_post_meta($request_id, 'status', true);

                                // Use helper function to get display name robustly
                                $student_display_name = get_student_display_name($student_id ?: $student_name_meta);
                                $tutor_user = wp_get_current_user();
                            ?>
                                <tr>
                                    <td><?php echo esc_html($request_date); ?></td>
                                    <td><?php echo esc_html(format_datetime($original_date, $original_time)); ?></td>
                                    <td>
                                        <?php if (!empty($preferred_times) && is_array($preferred_times)) : ?>
                                            <?php foreach ($preferred_times as $index => $time) : ?>
                                                <?php if (!empty($time['date']) && !empty($time['time'])) : ?>
                                                    Option <?php echo ($index + 1); ?>: <?php echo esc_html(format_datetime($time['date'], $time['time'])); ?><br>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            No preferred times specified
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($student_display_name); ?></td>
                                    <td>
                                        <?php if (!empty($reason)) :
                                            $truncated_reason = strlen($reason) > 30 ? substr($reason, 0, 30) . '...' : $reason;
                                        ?>
                                            <span class="reason-text text-warning" style="cursor: pointer;"
                                                  data-bs-toggle="modal" data-bs-target="#reasonModal"
                                                  data-reason="<?php echo esc_attr($reason); ?>"
                                                  data-bs-toggle="tooltip" title="Click to view full reason">
                                                <?php echo esc_html($truncated_reason); ?>
                                            </span>
                                        <?php else : ?>
                                            <em>No reason provided</em>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo get_status_badge($status); // Assumes this function generates HTML ?></td>
                                    <td>
                                        <?php if ($status == 'pending') : ?>
                                            <form method="post" class="d-inline me-1">
                                                <?php wp_nonce_field('confirm_reschedule_action', 'confirm_reschedule_nonce'); ?>
                                                <input type="hidden" name="action" value="confirm_reschedule">
                                                <input type="hidden" name="request_id" value="<?php echo esc_attr($request_id); ?>">
                                                <input type="hidden" name="student_id" value="<?php echo esc_attr($student_id); ?>">
                                                <input type="hidden" name="active_tab" value="requests"> <!-- Consider if still needed -->
                                                <button type="submit" class="btn btn-sm btn-success">Accept</button>
                                            </form>

                                            <button type="button" class="btn btn-sm btn-warning"
                                                    data-bs-toggle="modal" data-bs-target="#unavailableModal"
                                                    data-request-id="<?php echo esc_attr($request_id); ?>"
                                                    data-student-id="<?php echo esc_attr($student_id); ?>"
                                                    data-student-name="<?php echo esc_attr($student_display_name); ?>"
                                                    data-original-date="<?php echo esc_attr($original_date); ?>"
                                                    data-original-time="<?php echo esc_attr($original_time); ?>"
                                                    data-reason="<?php echo esc_attr($reason); ?>"
                                                    data-preferred-times='<?php echo esc_attr(json_encode($preferred_times)); ?>'>
                                                Unavailable
                                            </button>
                                        <?php else : ?>
                                            <span class="text-muted">No actions available</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p class="text-center text-muted mt-3">No incoming reschedule requests from students at this time.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for Providing Alternative Times -->
    <div class="modal fade" id="unavailableModal" tabindex="-1" aria-labelledby="unavailableModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg"> <!-- Consider larger modal for better layout -->
            <div class="modal-content">
                <form id="unavailableForm" method="post">
                    <?php wp_nonce_field('decline_reschedule_action', 'decline_reschedule_nonce'); ?>
                    <input type="hidden" name="action" value="decline_reschedule">
                    <input type="hidden" name="request_id" id="unavailable_request_id">
                    <input type="hidden" name="student_id" id="unavailable_student_id">
                    <input type="hidden" name="active_tab" value="requests"> <!-- Consider if still needed -->

                    <div class="modal-header">
                        <h5 class="modal-title" id="unavailableModalLabel">Suggest Alternative Times</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="unavailableErrorMessage" class="alert alert-danger" style="display: none;">
                            Please provide at least one valid alternative date and time.
                        </div>

                        <p class="lead mb-3">You've indicated you're unavailable for the student's preferred times. Please suggest your own alternatives.</p>

                        <!-- Student Request Details -->
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-light">
                                <i class="fas fa-user-clock me-2"></i> <strong>Student's Request</strong>
                            </div>
                            <div class="card-body">
                                <p><strong>Student:</strong> <span id="unavailable_student_name" class="text-primary fw-bold"></span></p>
                                <p><strong>Original Lesson:</strong> <span id="unavailable_original_time" class="text-secondary"></span></p>

                                <div id="student_preferred_times_container" class="mb-2">
                                    <p class="mb-1"><strong>Student's Preferred Alternatives:</strong></p>
                                    <ul id="preferred_times_list" class="list-unstyled ps-3"></ul>
                                </div>

                                <div id="student_reason_container">
                                    <p class="mb-1"><strong>Reason:</strong></p>
                                    <blockquote class="blockquote blockquote-sm mb-0 border-start border-3 ps-3">
                                        <p id="unavailable_reason" class="mb-0"></p>
                                    </blockquote>
                                </div>
                            </div>
                        </div>

                        <!-- Tutor Alternative Times -->
                        <h5 class="mt-4"><i class="fas fa-calendar-alt me-2"></i> Your Alternative Times</h5>
                        <p class="text-muted small mb-3">Provide up to 3 alternative times that work for you. The first option is required.</p>

                        <div id="alternative-times-container">
                            <?php
                            // Use the helper function from functions.php
                            // Assumes render_preferred_time_inputs generates appropriate HTML structure
                            // including labels, inputs (date & time), required attributes, and classes.
                            if (function_exists('render_preferred_time_inputs')) {
                                render_preferred_time_inputs('alt_', 3, true); // prefix, count, first required
                            } else {
                                // Fallback or error message if function doesn't exist
                                echo '<p class="text-danger">Error: Could not render time input fields.</p>';
                                // Basic fallback (less ideal)
                                for ($i = 1; $i <= 3; $i++) {
                                    echo '<div class="mb-2 row">';
                                    echo '<div class="col-md-6"><label class="form-label small">Alternative Date ' . $i . ':</label><input type="date" class="form-control alt-date" name="alt_date_' . $i . '" id="alt_date_' . $i . '" ' . ($i == 1 ? 'required' : '') . '></div>';
                                    echo '<div class="col-md-6"><label class="form-label small">Alternative Time ' . $i . ':</label><input type="time" class="form-control alt-time" name="alt_time_' . $i . '" id="alt_time_' . $i . '" ' . ($i == 1 ? 'required' : '') . '></div>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitUnavailable"><i class="fas fa-paper-plane me-2"></i> Submit Alternatives</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for Displaying Full Reason Text -->
    <div class="modal fade" id="reasonModal" tabindex="-1" aria-labelledby="reasonModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reasonModalLabel"><i class="fas fa-info-circle me-2"></i> Full Reschedule Reason</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="fullReasonText"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>