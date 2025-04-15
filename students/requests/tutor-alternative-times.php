  <!-- Tutor Alternative Times - with notification badge -->
  <?php
    // Get tutor unavailable responses
    $unavailable_requests_args = array(
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
                'value'   => 'tutor_unavailable',
                'compare' => '=',
            )
        ),
        'order'          => 'DESC',
        'orderby'        => 'date'
    );

    $unavailable_requests = get_posts($unavailable_requests_args);

    if (!empty($unavailable_requests)) {
        echo '<div class="card mb-4" id="alternativeTimesSection">';
        echo '<div class="card-header bg-info text-white">';
        echo '<div class="d-flex justify-content-between align-items-center">';
        echo '<div><i class="fas fa-calendar-alt me-2"></i> Tutor Alternative Times</div>';
        
        // Add notification badge for pending alternatives
        $pending_alternatives = count(get_posts(array(
            'post_type'      => 'progress_report',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array('key' => 'student_id', 'value' => get_current_user_id(), 'compare' => '='),
                array('key' => 'request_type', 'value' => 'tutor_unavailable', 'compare' => '='),
                array('key' => 'status', 'value' => 'pending', 'compare' => '=')
            ),
            'fields'         => 'ids'
        )));
        
        if ($pending_alternatives > 0) {
            echo '<span class="badge bg-danger">' . $pending_alternatives . '</span>';
        }
        
        echo '</div>'; // End d-flex
        echo '</div>'; // End card-header
        echo '<div class="card-body">';
        
        // Display a highlighted message if there are pending alternatives
        if ($pending_alternatives > 0) {
            echo '<div class="alert alert-warning mb-3">';
            echo '<i class="fas fa-exclamation-circle me-2"></i> You have <strong>' . $pending_alternatives . '</strong> pending alternative time suggestion';
            echo $pending_alternatives > 1 ? 's' : '';
            echo ' that require your response.';
            echo '</div>';
        }
        
        echo '<p>Your tutor is unavailable for your requested times but has provided alternatives. Please select a time that works for you:</p>';
        
        echo '<div class="accordion" id="unavailableAccordion">';
        $counter = 1;
        
        foreach ($unavailable_requests as $request) {
            $request_id = $request->ID;
            $original_request_id = get_post_meta($request_id, 'original_request_id', true);
            $tutor_name = get_post_meta($request_id, 'tutor_name', true);
            $alternatives = get_post_meta($request_id, 'alternatives', true);
            $status = get_post_meta($request_id, 'status', true);
            $request_date = get_the_date('F j, Y', $request_id);
            
            // Get tutor's full name
            $tutor_full_name = $tutor_name;
            $tutor_user = get_user_by('login', $tutor_name);
            if ($tutor_user) {
                $first_name = get_user_meta($tutor_user->ID, 'first_name', true);
                $last_name = get_user_meta($tutor_user->ID, 'last_name', true);
                
                if (!empty($first_name) && !empty($last_name)) {
                    $tutor_full_name = $first_name . ' ' . $last_name;
                } else {
                    $tutor_full_name = $tutor_user->display_name;
                }
            }
            
            // Get original request details
            $original_date = get_post_meta($original_request_id, 'original_date', true);
            $original_time = get_post_meta($original_request_id, 'original_time', true);
            
            // Format the original date for display - making sure to handle empty values
            $formatted_original_date = !empty($original_date) ? date('l, jS \of F, Y', strtotime($original_date)) : 'N/A';
            $formatted_original_time = !empty($original_time) ? date('g:i A', strtotime($original_time)) : '';
            
            // If the original date is not available from the meta, try to get it from the parent request
            if ($formatted_original_date === 'N/A' && !empty($original_request_id)) {
                $parent_original_date = get_post_meta($original_request_id, 'original_date', true);
                $parent_original_time = get_post_meta($original_request_id, 'original_time', true);
                
                if (!empty($parent_original_date)) {
                    $formatted_original_date = date('l, jS \of F, Y', strtotime($parent_original_date));
                }
                
                if (!empty($parent_original_time)) {
                    $formatted_original_time = date('g:i A', strtotime($parent_original_time));
                }
            }
            
            // Set status badge
            $status_badge = '';
            if ($status === 'confirmed') {
                $status_badge = '<span class="badge bg-success custom-badge">Confirmed</span>';
            } else {
                $status_badge = '<span class="badge bg-warning">Pending</span>';
            }
            
            echo '<div class="accordion-item">';
            echo '<h2 class="accordion-header" id="unavailableHeading' . $counter . '">';
            echo '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#unavailableCollapse' . $counter . '" aria-expanded="false" 
                    aria-controls="unavailableCollapse' . $counter . '">';
            echo 'Alternative Times - ' . $request_date . ' from ' . $tutor_full_name . ' ' . $status_badge;
            echo '</button>';
            echo '</h2>';
            
            echo '<div id="unavailableCollapse' . $counter . '" class="accordion-collapse collapse" 
                    aria-labelledby="unavailableHeading' . $counter . '" data-bs-parent="#unavailableAccordion">';
            echo '<div class="accordion-body">';
            
            echo '<div class="card mb-3">';
            echo '<div class="card-header bg-light">Original Requested Lesson</div>';
            echo '<div class="card-body">';
            echo '<p><strong>Date:</strong> ' . $formatted_original_date . '</p>';
            if (!empty($formatted_original_time)) {
                echo '<p><strong>Time:</strong> ' . $formatted_original_time . '</p>';
            }
            echo '<p><strong>Tutor:</strong> ' . esc_html($tutor_full_name) . '</p>';
            echo '</div>';
            echo '</div>';
            
            if ($status !== 'confirmed') {
                echo '<form method="post" class="mt-3">';
                echo '<input type="hidden" name="accept_tutor_alternative" value="1">';
                echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                
                echo '<div class="list-group mb-3">';
                foreach ($alternatives as $index => $alternative) {
                    $alt_date = $alternative['date'];
                    $alt_time = $alternative['time'];
                    
                    $formatted_alt_date = date('l, jS \of F, Y', strtotime($alt_date));
                    $formatted_alt_time = date('g:i A', strtotime($alt_time));
                    
                    echo '<div class="list-group-item">';
                    echo '<div class="form-check">';
                    echo '<input class="form-check-input" type="radio" name="selected_alternative" 
                            value="' . $index . '" id="unavail' . $request_id . '_' . $index . '" ' . ($index === 0 ? 'checked' : '') . '>';
                    echo '<label class="form-check-label" for="unavail' . $request_id . '_' . $index . '">';
                    echo 'Option ' . ($index + 1) . ': ' . $formatted_alt_date . ' at ' . $formatted_alt_time;
                    echo '</label>';
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
                
                echo '<button type="submit" class="btn btn-success">Accept Selected Time</button>';
                echo '</form>';
            } else {
                // Show the confirmed alternative
                $selected_index = get_post_meta($request_id, 'selected_alternative', true);
                $selected_alternative = $alternatives[$selected_index];
                
                $formatted_selected_date = date('l, jS \of F, Y', strtotime($selected_alternative['date']));
                $formatted_selected_time = date('g:i A', strtotime($selected_alternative['time']));
                
                echo '<div class="alert alert-success">';
                echo '<p><strong>Confirmed Time:</strong> ' . $formatted_selected_date . ' at ' . $formatted_selected_time . '</p>';
                echo '</div>';
            }
            
            echo '</div>'; // End accordion-body
            echo '</div>'; // End accordion-collapse
            echo '</div>'; // End accordion-item
            
            $counter++;
        }
        
        echo '</div>'; // End accordion
        echo '</div>'; // End card-body
        echo '</div>'; // End card
    }
    ?>

    <?php
    // Get student alternative times requests
    $student_alternative_args = array(
        'post_type'      => 'progress_report',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'tutor_id',
                'value'   => get_current_user_id(),
                'compare' => '=',
            ),
            array(
                'key'     => 'request_type',
                'value'   => 'student_unavailable',
                'compare' => '=',
            )
        ),
        'order'          => 'DESC',
        'orderby'        => 'date'
    );

    $student_alternative_requests = get_posts($student_alternative_args);

    if (!empty($student_alternative_requests)) {
        echo '<div class="card mb-4" id="studentAlternativeTimesSection">';
        echo '<div class="card-header bg-info text-white">';
        echo '<div class="d-flex justify-content-between align-items-center">';
        echo '<div><i class="fas fa-calendar-alt me-2"></i> Student Alternative Times</div>';
        
        // Add notification badge for pending alternatives
        $pending_student_alternatives = count(get_posts(array(
            'post_type'      => 'progress_report',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array('key' => 'tutor_id', 'value' => get_current_user_id(), 'compare' => '='),
                array('key' => 'request_type', 'value' => 'student_unavailable', 'compare' => '='),
                array('key' => 'status', 'value' => 'pending', 'compare' => '=')
            ),
            'fields'         => 'ids'
        )));
        
        if ($pending_student_alternatives > 0) {
            echo '<span class="badge bg-danger">' . $pending_student_alternatives . '</span>';
        }
        
        echo '</div>'; // End d-flex
        echo '</div>'; // End card-header
        echo '<div class="card-body">';
        
        // Display a highlighted message if there are pending alternatives
        if ($pending_student_alternatives > 0) {
            echo '<div class="alert alert-warning mb-3">';
            echo '<i class="fas fa-exclamation-circle me-2"></i> You have <strong>' . $pending_student_alternatives . '</strong> pending student alternative time suggestion';
            echo $pending_student_alternatives > 1 ? 's' : '';
            echo ' that require your response.';
            echo '</div>';
        }
        
        echo '<p>Your students are unavailable for the originally requested times and have provided alternative times. Please review and select a time that works for you:</p>';
        
        echo '<div class="accordion" id="studentAlternativeAccordion">';
        $counter = 1;
        
        foreach ($student_alternative_requests as $request) {
            $request_id = $request->ID;
            $original_request_id = get_post_meta($request_id, 'original_request_id', true);
            $student_name = get_post_meta($request_id, 'student_name', true);
            $student_id = get_post_meta($request_id, 'student_id', true);
            $alternatives = get_post_meta($request_id, 'alternatives', true);
            $status = get_post_meta($request_id, 'status', true);
            $request_date = get_the_date('F j, Y', $request_id);
            
            // Get student's full name
            $student_full_name = get_student_display_name($student_name);
            
            // Get original request details
            $original_date = get_post_meta($original_request_id, 'original_date', true);
            $original_time = get_post_meta($original_request_id, 'original_time', true);
            
            // Format the original date for display
            $formatted_original_date = !empty($original_date) ? date('l, jS \of F, Y', strtotime($original_date)) : 'N/A';
            $formatted_original_time = !empty($original_time) ? date('g:i A', strtotime($original_time)) : '';
            
            // Set status badge
            $status_badge = '';
            if ($status === 'confirmed') {
                $status_badge = '<span class="badge bg-success">Confirmed</span>';
            } else {
                $status_badge = '<span class="badge bg-warning">Pending</span>';
            }
            
            echo '<div class="accordion-item">';
            echo '<h2 class="accordion-header" id="studentAlternativeHeading' . $counter . '">';
            echo '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#studentAlternativeCollapse' . $counter . '" aria-expanded="false" 
                    aria-controls="studentAlternativeCollapse' . $counter . '">';
            echo 'Alternative Times - ' . $request_date . ' from ' . $student_full_name . ' ' . $status_badge;
            echo '</button>';
            echo '</h2>';
            
            echo '<div id="studentAlternativeCollapse' . $counter . '" class="accordion-collapse collapse" 
                    aria-labelledby="studentAlternativeHeading' . $counter . '" data-bs-parent="#studentAlternativeAccordion">';
            echo '<div class="accordion-body">';
            
            echo '<div class="card mb-3">';
            echo '<div class="card-header bg-light">Original Requested Lesson</div>';
            echo '<div class="card-body">';
            echo '<p><strong>Date:</strong> ' . $formatted_original_date . '</p>';
            if (!empty($formatted_original_time)) {
                echo '<p><strong>Time:</strong> ' . $formatted_original_time . '</p>';
            }
            echo '<p><strong>Student:</strong> ' . esc_html($student_full_name) . '</p>';
            echo '</div>';
            echo '</div>';
            
            if ($status !== 'confirmed') {
                echo '<form method="post" class="mt-3">';
                echo '<input type="hidden" name="select_alternative" value="1">';
                echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                echo '<input type="hidden" name="active_tab" value="requests">';
                
                echo '<div class="list-group mb-3">';
                foreach ($alternatives as $index => $alternative) {
                    $alt_date = $alternative['date'];
                    $alt_time = $alternative['time'];
                    
                    $formatted_alt_date = date('l, jS \of F, Y', strtotime($alt_date));
                    $formatted_alt_time = date('g:i A', strtotime($alt_time));
                    
                    echo '<div class="list-group-item">';
                    echo '<div class="form-check">';
                    echo '<input class="form-check-input" type="radio" name="selected_alternative" 
                            value="' . $index . '" id="studentAlt' . $request_id . '_' . $index . '" ' . ($index === 0 ? 'checked' : '') . '>';
                    echo '<label class="form-check-label" for="studentAlt' . $request_id . '_' . $index . '">';
                    echo 'Option ' . ($index + 1) . ': ' . $formatted_alt_date . ' at ' . $formatted_alt_time;
                    echo '</label>';
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
                
                echo '<button type="submit" class="btn btn-success">Confirm Selected Time</button>';
                echo '</form>';
            } else {
                // Show the confirmed alternative
                $selected_index = get_post_meta($request_id, 'selected_alternative', true);
                $selected_alternative = $alternatives[$selected_index];
                
                $formatted_selected_date = date('l, jS \of F, Y', strtotime($selected_alternative['date']));
                $formatted_selected_time = date('g:i A', strtotime($selected_alternative['time']));
                
                echo '<div class="alert alert-success">';
                echo '<p><strong>Confirmed Time:</strong> ' . $formatted_selected_date . ' at ' . $formatted_selected_time . '</p>';
                echo '</div>';
            }
            
            echo '</div>'; // End accordion-body
            echo '</div>'; // End accordion-collapse
            echo '</div>'; // End accordion-item
            
            $counter++;
        }
        
        echo '</div>'; // End accordion
        echo '</div>'; // End card-body
        echo '</div>'; // End card
    }
    ?>