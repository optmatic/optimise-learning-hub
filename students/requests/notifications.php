    <!-- Notifications Section -->
    <div class="mb-4" id="requestNotifications">
        <?php
        $current_user_id = get_current_user_id();
        
        // Get counts of pending requests
        $pending_reschedule_count = count(get_posts([
            'post_type' => 'progress_report',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'AND',
                ['key' => 'student_id', 'value' => $current_user_id, 'compare' => '='],
                ['key' => 'request_type', 'value' => 'tutor_reschedule', 'compare' => '='],
                ['key' => 'status', 'value' => 'pending', 'compare' => '=']
            ],
            'fields' => 'ids'
        ]));
        
        $alternatives_count = count(get_posts([
            'post_type' => 'progress_report',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'AND',
                ['key' => 'student_id', 'value' => $current_user_id, 'compare' => '='],
                ['key' => 'request_type', 'value' => 'tutor_unavailable', 'compare' => '='],
                ['key' => 'status', 'value' => 'pending', 'compare' => '=']
            ],
            'fields' => 'ids'
        ]));
        
        // Display notifications
        if ($pending_reschedule_count > 0 || $alternatives_count > 0): ?>
            <div class="alert alert-info">
                <h5><i class="fas fa-bell me-2"></i>Notifications</h5>
                <ul class="mb-0">
                    <?php if ($pending_reschedule_count > 0): ?>
                    <li>
                        You have <strong><?php echo $pending_reschedule_count; ?></strong> pending reschedule 
                        request<?php echo $pending_reschedule_count > 1 ? 's' : ''; ?> from your tutor.
                        <a href="#incomingRescheduleSection" class="btn btn-sm btn-primary ms-2">View</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($alternatives_count > 0): ?>
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
    