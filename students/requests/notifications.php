    <!-- Notifications Section -->
    <div class="mb-4" id="requestNotifications">
        <?php
        $current_user_id = get_current_user_id();
        $pending_reschedule_count = 0;
        $alternatives_count = 0;

        // Fetch all relevant pending requests in a single query
        $pending_requests_query = new WP_Query([
            'post_type'      => 'progress_report',
            'posts_per_page' => -1,
            'meta_query'     => [
                'relation' => 'AND',
                ['key' => 'student_id', 'value' => $current_user_id, 'compare' => '='],
                ['key' => 'status', 'value' => 'pending', 'compare' => '='],
                [
                    'key'     => 'request_type',
                    'value'   => ['tutor_reschedule', 'tutor_unavailable'],
                    'compare' => 'IN'
                ]
            ],
            'fields' => 'ids' // Fetch only IDs initially for performance
        ]);

        // Process the fetched posts to count types
        if ($pending_requests_query->have_posts()) {
            foreach ($pending_requests_query->posts as $post_id) {
                $request_type = get_post_meta($post_id, 'request_type', true);
                if ($request_type === 'tutor_reschedule') {
                    $pending_reschedule_count++;
                } elseif ($request_type === 'tutor_unavailable') {
                    $alternatives_count++;
                }
            }
        }
        wp_reset_postdata(); // Reset post data after custom query

        // Display notifications if any counts are greater than 0
        if ($pending_reschedule_count > 0 || $alternatives_count > 0) : ?>
            <div class="alert alert-info">
                <h5><i class="fas fa-bell me-2"></i>Notifications</h5>
                <ul class="mb-0">
                    <?php if ($pending_reschedule_count > 0) : ?>
                        <li>
                            You have <strong><?php echo $pending_reschedule_count; ?></strong> pending reschedule
                            request<?php echo $pending_reschedule_count > 1 ? 's' : ''; ?> from your tutor.
                            <a href="#incomingRescheduleSection" class="btn btn-sm btn-primary ms-2">View</a>
                        </li>
                    <?php endif; ?>

                    <?php if ($alternatives_count > 0) : ?>
                        <li>
                            You have <strong><?php echo $alternatives_count; ?></strong> alternative time
                            suggestion<?php echo $alternatives_count > 1 ? 's' : ''; ?> from your tutor.
                            <a href="#alternativeTimesSection" class="btn btn-sm btn-primary ms-2">View</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    