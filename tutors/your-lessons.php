                <!-- ===========================
                     YOUR LESSONS TAB
                     =========================== -->
                <div class="tab-pane fade" id="classroom-url" role="tabpanel" aria-labelledby="classroom-url-tab">
                
                    <h5>Access your classrooms here</h5>
                    <?php
                    $user_id = get_current_user_id();
                    
                    // Try to get from ACF first
                    $tutor_classroom_name = get_field('tutor_classroom_name', 'user_' . $user_id);
                    $tutor_classroom_url = get_field('tutor_classroom_url', 'user_' . $user_id);
                    
                    // Fallback to user_meta if ACF fields are empty
                   
                    if (empty($tutor_classroom_name)) {
                        $tutor_classroom_name = get_user_meta($user_id, 'tutor_classroom_name', true);
                    }
                    if (empty($tutor_classroom_url)) {
                        $tutor_classroom_url = get_user_meta($user_id, 'tutor_classroom_url', true);
                    }

                    if (!empty($tutor_classroom_url)) {
                        echo '<li style="list-style-type: none;"><em>Tutor Classroom: </em><a href="' . esc_url($tutor_classroom_url) . '" target="_blank">' . esc_html($tutor_classroom_url) . '</a></li>';
                    } else {
                        echo '<p>No classroom URLs have been set. Please contact an administrator or update your profile to set up your classroom URLs.</p>';
                    }
                    ?>
                    <div style="margin-top: 20px;">
                      <h5>Your Schedule</h5>
                    <?php
                    // Retrieve the Google Sheet ID from the ACF field
                    $google_sheet_id = get_field('schedule', 'user_' . get_current_user_id());
                    ?>
                    <iframe src="https://docs.google.com/spreadsheets/d/e/<?php echo esc_attr($google_sheet_id); ?>/pubhtml?widget=true&amp;headers=false" 
                            style="width: 100%; height: 500px; border: none;"></iframe>
                    </div>
                </div>