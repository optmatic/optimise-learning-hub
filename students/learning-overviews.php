
    <!-- =========================== YOUR LEARNING OVERVIEWS =========================== -->
                <div class="tab-pane fade" id="my-progress" role="tabpanel" aria-labelledby="my-progress-tab">
    <?php
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;

    $progress_reports = get_student_progress_reports($user_id);

    if (!empty($progress_reports)) {
        echo '<div class="accordion" id="progressAccordion">';
        $counter = 1;
        foreach ($progress_reports as $report) {
            $report_id = $report->ID;
            $tutor_name = get_post_meta($report_id, 'tutor_name', true);
            $lesson_date = get_post_meta($report_id, 'lesson_date', true);
            $datetime = DateTime::createFromFormat('Y-m-d', $lesson_date);
            $formatted_date = $datetime->format('jS \of F, Y');
            $lesson_focus = get_post_meta($report_id, 'lesson_focus', true);
            $content_covered = get_post_meta($report_id, 'content_covered', true);
            $student_progress = get_post_meta($report_id, 'student_progress', true);
            $next_focus = get_post_meta($report_id, 'next_focus', true);
            $resources = get_post_meta($report_id, 'lesson_resources', true);

            echo '<div class="accordion-item">';
            echo '<h2 class="accordion-header" id="heading' . $counter . '">';
            echo '<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $counter . '" aria-expanded="true" aria-controls="collapse' . $counter . '">';
            echo 'Learning Overview - ' . $formatted_date;
            echo '</button>';
            echo '</h2>';
            echo '<div id="collapse' . $counter . '" class="accordion-collapse collapse" aria-labelledby="heading' . $counter . '" data-bs-parent="#progressAccordion">';
            echo '<div class="accordion-body">';
            echo '<p><strong>Tutor Name:</strong> ' . $tutor_name . '</p>';
            echo '<p><strong>Lesson Date:</strong> ' . $formatted_date . '</p>';
            echo '<p><strong>Lesson Focus:</strong> ' . $lesson_focus . '</p>';
            echo '<p><strong>Content Covered During the Lesson:</strong> ' . $content_covered . '</p>';
            echo '<p><strong>Student Progress:</strong> ' . $student_progress . '</p>';
            echo '<p><strong>Focus for Next Lesson:</strong> ' . $next_focus . '</p>';
            
            // Display multiple resources if they exist
            if (!empty($resources)) {
                echo '<p><strong>Resources:</strong></p>';
                echo '<ul class="list-unstyled" style="padding:0 !important;">';
                foreach ((array)$resources as $resource_url) {
                    $filename = basename($resource_url);
                    echo '<li><i class="fas fa-file"></i> <a href="' . esc_url($resource_url) . '" target="_blank">' . esc_html($filename) . '</a></li>';
                }
                echo '</ul>';
            }
            
            echo '</div>';
            echo '</div>';
            echo '</div>';

            $counter++;
        }
        echo '</div>';
    } else {
        echo '<p>No lesson overviews found.</p>';
    }
    ?>
</div>