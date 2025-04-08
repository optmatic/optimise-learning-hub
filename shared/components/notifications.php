<?php
/**
 * Student notifications component
 */

$current_user_id = get_current_user_id();

// Get notification counts
$notifications = [
    'requests_count' => count(get_posts([
        'post_type' => 'progress_report',
        'posts_per_page' => -1,
        'meta_query' => [
            'relation' => 'AND',
            ['key' => 'student_id', 'value' => $current_user_id, 'compare' => '='],
            ['key' => 'request_type', 'value' => 'tutor_reschedule', 'compare' => '='],
            ['key' => 'status', 'value' => 'pending', 'compare' => '=']
        ],
        'fields' => 'ids'
    ])),
    'alternatives_count' => count(get_posts([
        'post_type' => 'progress_report',
        'posts_per_page' => -1,
        'meta_query' => [
            'relation' => 'AND',
            ['key' => 'student_id', 'value' => $current_user_id, 'compare' => '='],
            ['key' => 'request_type', 'value' => 'tutor_unavailable', 'compare' => '='],
            ['key' => 'status', 'value' => 'pending', 'compare' => '=']
        ],
        'fields' => 'ids'
    ]))
];
