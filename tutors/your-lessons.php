                <!-- ===========================
                     YOUR LESSONS TAB
                     =========================== -->
                <div class="tab-pane fade" id="classroom-url" role="tabpanel" aria-labelledby="classroom-url-tab">
                
                    <h5>Access your classrooms here</h5>
                    <?php
                    $user_id = get_current_user_id();
                    
                    // Try to get from ACF first
                    $math_classroom = get_field('mathematics_classroom', 'user_' . $user_id);
                    $english_classroom = get_field('english_classroom', 'user_' . $user_id);
                    $custom_classroom_name = get_field('custom_classroom_name', 'user_' . $user_id);
                    $custom_classroom_url = get_field('custom_classroom_url', 'user_' . $user_id);
                    
                    // Fallback to user_meta if ACF fields are empty
                    if (empty($math_classroom)) {
                        $math_classroom = get_user_meta($user_id, 'mathematics_classroom', true);
                    }
                    if (empty($english_classroom)) {
                        $english_classroom = get_user_meta($user_id, 'english_classroom', true);
                    }
                    if (empty($custom_classroom_name)) {
                        $custom_classroom_name = get_user_meta($user_id, 'custom_classroom_name', true);
                    }
                    if (empty($custom_classroom_url)) {
                        $custom_classroom_url = get_user_meta($user_id, 'custom_classroom_url', true);
                    }
                    
                    // Also check for old classroom_url as fallback
                    $old_classroom_url = get_user_meta($user_id, 'classroom_url', true);
                    
                    if (!empty($math_classroom) || !empty($english_classroom) || !empty($custom_classroom_url) || !empty($old_classroom_url)) {
                        echo '<ul class="classroom-urls">';
                        
                        if (!empty($math_classroom)) {
                            echo '<li><strong>Mathematics Classroom</strong><br><a href="' . esc_url($math_classroom) . '" target="_blank">' . esc_html($math_classroom) . '</a></li>';
                        }
                        
                        if (!empty($english_classroom)) {
                            echo '<li><strong>English Classroom</strong><br><a href="' . esc_url($english_classroom) . '" target="_blank">' . esc_html($english_classroom) . '</a></li>';
                        }
                        
                        if (!empty($custom_classroom_url) && !empty($custom_classroom_name)) {
                            echo '<li><strong>' . esc_html($custom_classroom_name) . ' Classroom</strong><br><a href="' . esc_url($custom_classroom_url) . '" target="_blank">' . esc_html($custom_classroom_url) . '</a></li>';
                        } elseif (!empty($custom_classroom_url)) {
                            echo '<li><strong>Custom Classroom</strong><br><a href="' . esc_url($custom_classroom_url) . '" target="_blank">' . esc_html($custom_classroom_url) . '</a></li>';
                        }
                        
                        // Display old classroom URL if it exists
                        if (!empty($old_classroom_url)) {
                            echo '<li><strong>Classroom</strong><br><a href="' . esc_url($old_classroom_url) . '" target="_blank">' . esc_html($old_classroom_url) . '</a></li>';
                        }
                        
                        echo '</ul>';
                    } else {
                        echo '<p>No classroom URLs have been set. Please contact an administrator or update your profile to set up your classroom URLs.</p>';
                    }
                    ?>
                      <h5>Your Schedule</h5>
                    <?php
                    // Retrieve the Google Sheet ID from the ACF field
                    $google_sheet_id = get_field('schedule', 'user_' . get_current_user_id());
                    ?>
                    <iframe src="https://docs.google.com/spreadsheets/d/e/<?php echo esc_attr($google_sheet_id); ?>/pubhtml?widget=true&amp;headers=false" 
                            style="width: 100%; height: 500px; border: none;"></iframe>

                </div>