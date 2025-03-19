
                <!-- =========================== YOUR CLASSROOMS TAB =========================== -->
                <div class="tab-pane fade" id="classroom" role="tabpanel" aria-labelledby="classroom-tab">
                <h3>Please note</h3>
                    <ul>
                        <li>Remember to log in to your classroom a few minutes before your lesson is due to commence.</li>
                        <li>If after entering your name, you see a pop-up stating there is no presenter, please log out and log in a few minutes later.</li>
                        <li>Our online classrooms function well if <a href="https://www.apple.com/au/safari/" target="_blank" rel="nofollow">Safari</a>, <a href="https://www.google.com.au/intl/en_au/chrome/" target="_blank" rel="nofollow">Google Chrome</a>, or <a href="https://www.mozilla.org/en-US/firefox/new/" target="_blank" rel="nofollow">Firefox</a> is used as the browser. They are not able to function effectively if Internet Explorer is used as the browser.</li>
                    </ul>
                    <div style="background-color: rgba(42, 98, 143, 0.07); padding:25px;">
                    <h3>Access your classrooms here</h3>
                    <?php
                    if (is_user_logged_in()) {
                        $user = wp_get_current_user();
                        if (in_array('student', (array)$user->roles)) {
                            // User is a student, load the embed
                            $english_classroom = get_field('english_classroom', 'user_' . $user->ID);
                            $mathematics_classroom = get_field('mathematics_classroom', 'user_' . $user->ID);
                            $custom_classroom_name = get_field('custom_classroom_name', 'user_' . $user->ID);
                            $custom_classroom_url = get_field('custom_classroom_url', 'user_' . $user->ID);

                            if ($mathematics_classroom) {
                                echo '<h5 style="margin-top: 25px;">Mathematics Classroom</h5>';
                                echo '<a href="' . esc_url($mathematics_classroom) . '" target="_blank">' . esc_url($mathematics_classroom) . '</a>';
                            }
                            
                            if ($english_classroom) {
                                echo '<h5 style="margin-top: 25px;">English Classroom</h5>';
                                echo '<a href="' . esc_url($english_classroom) . '" target="_blank">' . esc_url($english_classroom) . '</a>';
                            }
                            
                            if ($custom_classroom_name && $custom_classroom_url) {
                                echo '<h5 style="margin-top: 25px;">' . esc_html($custom_classroom_name) . ' Classroom</h5>';
                                echo '<a href="' . esc_url($custom_classroom_url) . '" target="_blank">' . esc_url($custom_classroom_url) . '</a>';
                            }
                        }
                    }
                    ?>
                    </div>
                </div>