
             <!-- =========================== YOUR LEARNING PLAN TAB =========================== -->
                <div class="tab-pane fade" id="learning-goals" role="tabpanel" aria-labelledby="learning-goals-tab">
                  <!--  <h3>Your Learning Plan</h3> -->
<p>Please see your child's <strong>Individual Learning Plan</strong> below, which provides an overview of the curriculum content your child will be studying during their tutoring lessons, and their specific learning goals. This plan is based upon the goals you have for your child's learning and the academic objectives we have developed based on our initial observations and assessments.</p>
<p>Your child's personalised <strong>Learning Plan</strong> is fully aligned with the Australian National Curriculum and will be regularly reviewed and updated to ensure it supports your child's ongoing academic progress and development.</p>
       <blockquote>
    <div style="background-color: rgba(42, 98, 143, 0.07); padding: 1.5rem 1.5rem .5rem 1.5rem;">
        <?php
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if (in_array('student', (array)$user->roles)) {
                // User is a student, load the plan
                echo '<p><strong>STUDENT NAME:</strong> ' . $current_user->display_name . '</p>';
                echo '<p><strong>YEAR LEVEL:</strong> ' . get_field('year', 'user_' . $user->ID) . '</p>';
                // echo '<p><strong>LESSON SCHEDULE:</strong> ' . get_field('lesson_schedule', 'user_' . $user->ID) . '</p>';
                echo '<p><strong>CURRICULUM OVERVIEW:</strong><br> ' . get_field('overarching_learning_goals', 'user_' . $user->ID) . '</p>';
                echo '<p><strong>SPECIFIC LEARNING GOALS:</strong><br> ' . get_field('specific_learning_goals', 'user_' . $user->ID) . '</p>';  
            }
        }
        ?>
    </div>
 	</blockquote>
					<p>
						If you have any questions or require further clarification, please do not hesitate to <a href="mailto:info@optimiselearning.com">contact us</a>.
					</p>
        
                </div>