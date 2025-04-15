  <!-- Tutor Alternative Times - with notification badge -->
  <?php
    // Fetch all tutor unavailable requests for the current student
    $current_user_id = get_current_user_id();
    $unavailable_requests_args = array(
        'post_type'      => 'progress_report',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'student_id',
                'value'   => $current_user_id,
                'compare' => '=',
            ),
            array(
                'key'     => 'request_type',
                'value'   => 'tutor_unavailable',
                'compare' => '=',
            )
        ),
        'order'          => 'DESC',
        'orderby'        => 'date'
    );

    $unavailable_requests = get_posts($unavailable_requests_args);

    // Process requests to count pending and prepare data
    $pending_alternatives_count = 0;
    $processed_requests = [];

    foreach ($unavailable_requests as $request) {
        $status = get_post_meta($request->ID, 'status', true);
        if ($status === 'pending') {
            $pending_alternatives_count++;
        }
        $processed_requests[] = $request; // Keep the post object for later use
    }

    if (!empty($processed_requests)) :
?>
<div class="card mb-4" id="alternativeTimesSection">
    <div class="card-header bg-info text-white">
        <div class="d-flex justify-content-between align-items-center">
            <div><i class="fas fa-calendar-alt me-2"></i> Tutor Alternative Times</div>
            <?php if ($pending_alternatives_count > 0) : ?>
                <span class="badge bg-danger"><?php echo $pending_alternatives_count; ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <?php if ($pending_alternatives_count > 0) : ?>
            <div class="alert alert-warning mb-3">
                <i class="fas fa-exclamation-circle me-2"></i> You have <strong><?php echo $pending_alternatives_count; ?></strong> pending alternative time suggestion<?php echo $pending_alternatives_count > 1 ? 's' : ''; ?> that require your response.
            </div>
        <?php endif; ?>

        <p>Your tutor is unavailable for your requested times but has provided alternatives. Please select a time that works for you:</p>

        <div class="accordion" id="unavailableAccordion">
            <?php
            $counter = 1;
            foreach ($processed_requests as $request) :
                $request_id = $request->ID;
                $original_request_id = get_post_meta($request_id, 'original_request_id', true);
                $tutor_name_meta = get_post_meta($request_id, 'tutor_name', true); // Use a different var name
                $alternatives = get_post_meta($request_id, 'alternatives', true);
                $status = get_post_meta($request_id, 'status', true);
                $request_date = get_the_date('F j, Y', $request_id);

                // Get tutor's full name
                $tutor_full_name = $tutor_name_meta; // Default to meta value
                $tutor_user = get_user_by('login', $tutor_name_meta);
                if ($tutor_user) {
                    $first_name = get_user_meta($tutor_user->ID, 'first_name', true);
                    $last_name = get_user_meta($tutor_user->ID, 'last_name', true);
                    $tutor_full_name = (!empty($first_name) && !empty($last_name)) ? $first_name . ' ' . $last_name : $tutor_user->display_name;
                }

                // Get and format original request details
                $original_date_raw = get_post_meta($original_request_id, 'original_date', true);
                $original_time_raw = get_post_meta($original_request_id, 'original_time', true);
                
                // Fallback if meta not on the current post, try the parent
                if (empty($original_date_raw) && !empty($original_request_id)) {
                    $original_date_raw = get_post_meta($original_request_id, 'original_date', true);
                }
                 if (empty($original_time_raw) && !empty($original_request_id)) {
                     $original_time_raw = get_post_meta($original_request_id, 'original_time', true);
                 }

                $formatted_original_date = !empty($original_date_raw) ? date('l, jS \of F, Y', strtotime($original_date_raw)) : 'N/A';
                $formatted_original_time = !empty($original_time_raw) ? date('g:i A', strtotime($original_time_raw)) : '';

                // Set status badge
                $status_badge = ($status === 'confirmed')
                    ? '<span class="badge bg-success custom-badge">Confirmed</span>'
                    : '<span class="badge bg-warning">Pending</span>';

                $accordion_id = 'unavailableCollapse' . $counter;
                $heading_id = 'unavailableHeading' . $counter;
            ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="<?php echo esc_attr($heading_id); ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr($accordion_id); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr($accordion_id); ?>">
                            Alternative Times - <?php echo esc_html($request_date); ?> from <?php echo esc_html($tutor_full_name); ?> <?php echo $status_badge; // Badge already contains HTML ?>
                        </button>
                    </h2>
                    <div id="<?php echo esc_attr($accordion_id); ?>" class="accordion-collapse collapse" aria-labelledby="<?php echo esc_attr($heading_id); ?>" data-bs-parent="#unavailableAccordion">
                        <div class="accordion-body">
                            <div class="card mb-3">
                                <div class="card-header bg-light">Original Requested Lesson</div>
                                <div class="card-body">
                                    <p><strong>Date:</strong> <?php echo esc_html($formatted_original_date); ?></p>
                                    <?php if (!empty($formatted_original_time)) : ?>
                                        <p><strong>Time:</strong> <?php echo esc_html($formatted_original_time); ?></p>
                                    <?php endif; ?>
                                    <p><strong>Tutor:</strong> <?php echo esc_html($tutor_full_name); ?></p>
                                </div>
                            </div>

                            <?php if ($status !== 'confirmed' && is_array($alternatives)) : ?>
                                <form method="post" class="mt-3">
                                    <?php wp_nonce_field('accept_tutor_alternative_nonce', 'accept_tutor_alternative_nonce_field'); ?>
                                    <input type="hidden" name="accept_tutor_alternative" value="1">
                                    <input type="hidden" name="request_id" value="<?php echo esc_attr($request_id); ?>">
                                    <input type="hidden" name="active_tab" value="requests"> <!-- Keep if needed for form processing -->

                                    <div class="list-group mb-3">
                                        <?php foreach ($alternatives as $index => $alternative) :
                                            $alt_date_raw = $alternative['date'] ?? '';
                                            $alt_time_raw = $alternative['time'] ?? '';

                                            if (empty($alt_date_raw) || empty($alt_time_raw)) continue; // Skip invalid alternatives

                                            $formatted_alt_date = date('l, jS \of F, Y', strtotime($alt_date_raw));
                                            $formatted_alt_time = date('g:i A', strtotime($alt_time_raw));
                                            $radio_id = 'unavail' . $request_id . '_' . $index;
                                        ?>
                                            <div class="list-group-item">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="selected_alternative" value="<?php echo esc_attr($index); ?>" id="<?php echo esc_attr($radio_id); ?>" <?php checked($index, 0); ?>>
                                                    <label class="form-check-label" for="<?php echo esc_attr($radio_id); ?>">
                                                        Option <?php echo ($index + 1); ?>: <?php echo esc_html($formatted_alt_date); ?> at <?php echo esc_html($formatted_alt_time); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <button type="submit" class="btn btn-success">Accept Selected Time</button>
                                </form>
                            <?php elseif ($status === 'confirmed') :
                                // Show the confirmed alternative
                                $selected_index = get_post_meta($request_id, 'selected_alternative', true);

                                // Check if selected index and alternative exist
                                if ($selected_index !== '' && isset($alternatives[$selected_index])) {
                                    $selected_alternative = $alternatives[$selected_index];
                                    $selected_date_raw = $selected_alternative['date'] ?? '';
                                    $selected_time_raw = $selected_alternative['time'] ?? '';

                                    if (!empty($selected_date_raw) && !empty($selected_time_raw)) {
                                        $formatted_selected_date = date('l, jS \of F, Y', strtotime($selected_date_raw));
                                        $formatted_selected_time = date('g:i A', strtotime($selected_time_raw));
                            ?>
                                        <div class="alert alert-success">
                                            <p class="mb-0"><strong>Confirmed Time:</strong> <?php echo esc_html($formatted_selected_date); ?> at <?php echo esc_html($formatted_selected_time); ?></p>
                                        </div>
                            <?php
                                    } else {
                                        echo '<div class="alert alert-warning">Confirmed alternative time data is missing or invalid.</div>';
                                    }
                                } else {
                                     echo '<div class="alert alert-warning">Could not retrieve confirmed alternative time details.</div>';
                                }
                                ?>
                            <?php endif; ?>
                        </div><!-- /.accordion-body -->
                    </div><!-- /.accordion-collapse -->
                </div><!-- /.accordion-item -->
            <?php
                $counter++;
            endforeach; // End loop through processed_requests
            ?>
        </div><!-- /#unavailableAccordion -->
    </div><!-- /.card-body -->
</div><!-- /.card -->
<?php
endif; // End check for !empty($processed_requests)

// Removed the entire "Student Alternative Times" section as it seems misplaced in this student-focused file.
?>