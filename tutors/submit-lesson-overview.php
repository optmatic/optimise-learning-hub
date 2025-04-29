    <!-- ===========================
                     SUBMIT LESSON OVERVIEW TAB
                     =========================== -->
                     <h3>Submit Lesson Overview</h3>
                    <form method="post" enctype="multipart/form-data">
                    <?php
                    global $submission_message;
                    if (!empty($submission_message)) {               
                        echo '<div class="alert alert-success" role="alert">' . $submission_message . '</div>';
                    }
                    ?>
                    
                    <div class="mb-3">
                        <blockquote>
                        <div style="background-color: rgba(42, 98, 143, 0.07); padding: 10px;">
                            <h3>
                                Format for Lesson Overviews
                            </h3>
                            <h5>Lesson Focus</h5>
                            <p>Refer to the student's Learning Plan and write a brief statement outlining what you are intending to focus on for this lesson. * This sentence needs to be the same as the 'Focus for Next Lesson' sentence from the student's last lesson.</p>

                            <h5>Content Covered During the Lesson</h5>
                            <p>Write 2 or 3 sentences to highlight the lesson content that was covered during the lesson.</p>

                            <h5>Student Progress</h5>
                            <p>Write 2 or 3 sentences describing whether the student was able to engage and complete tasks involved in the lesson independently, or whether they were only able to do so with your assistance.</p>

                            <h5>Focus for Next Lesson</h5>
                            <p style="margin-bottom: 0 !important;">Refer to the student's Learning Plan and write a brief statement outlining what you are intending to focus on for the next lesson.</p>
                        </div>
                        </blockquote>
                        
                        <hr>

                        <label for="tutor_name" class="form-label"><h4>Write Your Lesson Overview Here</h4></label>
                        <input type="text" name="tutor_name" id="tutor_name" class="form-control" value="<?php echo wp_get_current_user()->display_name; ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="student_id" class="form-label"><h6>Student</h6></label>
                        <select name="student_id" id="student_id" class="form-select">
                            <option value="">--Select student--</option>
                            <?php
                            $assigned_students = get_user_meta(get_current_user_id(), 'assigned_students', true);
                            $student_ids = !empty($assigned_students) ? explode(',', $assigned_students) : array();

                            foreach ($student_ids as $student_id) {
                                $student_name = get_userdata($student_id)->display_name;
                                echo '<option value="' . $student_id . '">' . $student_name . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="lesson_date" class="form-label"><h6>Lesson Date</h6></label>
                        <input type="date" name="lesson_date" id="lesson_date" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="lesson_focus" class="form-label"><h6>Lesson Focus</h6></label>
                        <?php
                            $settings = array('textarea_name' => 'lesson_focus', 'editor_class' => 'form-control', 'editor_height' => 200);
                            wp_editor('', 'lesson_focus', $settings);
                        ?>
                    </div>

                    <div class="mb-3">
                        <label for="content_covered" class="form-label"><h6>Content Covered During the Lesson</h6></label>
                        <?php
                            $settings = array('textarea_name' => 'content_covered', 'editor_class' => 'form-control', 'editor_height' => 200);
                            wp_editor('', 'content_covered', $settings);
                        ?>
                    </div>

                    <div class="mb-3">
                        <label for="student_progress" class="form-label"><h6>Student Progress</h6></label>
                        <?php
                            $settings = array('textarea_name' => 'student_progress', 'editor_class' => 'form-control', 'editor_height' => 200);
                            wp_editor('', 'student_progress', $settings);
                        ?>
                    </div>

                    <div class="mb-3">
                        <label for="next_focus" class="form-label"><h6>Focus for Next Lesson</h6></label>
                        <?php
                            $settings = array('textarea_name' => 'next_focus', 'editor_class' => 'form-control', 'editor_height' => 200);
                            wp_editor('', 'next_focus', $settings);
                        ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><h6>Upload Learning Task</h6></label>
                        <blockquote>
                            <p style="background-color: rgba(42, 98, 143, 0.07); padding: 10px; font-style: italic;">
                                Upload learning tasks for your student to complete following their lesson, here.
                            </p>
                        </blockquote>
                        
                        <div id="resource-uploads">
                            <div class="resource-upload-field mb-2">
                                <input type="file" name="resources[]" class="form-control">
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-secondary btn-sm mt-2" id="add-resource">
                            <i class="fas fa-plus"></i> Add Another Learning Task
                        </button>
                    </div>

                    <input type="submit" name="submit_progress_report" value="Submit Lesson Overview" class="btn btn-primary">
                    </form>