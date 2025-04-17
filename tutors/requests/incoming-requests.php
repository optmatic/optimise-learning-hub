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
    