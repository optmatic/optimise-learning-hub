<?php
/*
Template Name: Tutor Dashboard
*/
?>
<?php
get_header();
?>

<?php
if (current_user_can('tutor')) {
?>

<div class="container mt-4">
    <div class="row">
        <!-- (Navigation) -->
        <div class="col-12">
        <ul class="nav nav-tabs" id="myTab" role="tablist" style="
    padding-left: 0px !important;">
                <li class="nav-item">
                    <a class="nav-link active" id="progress-report-tab" data-bs-toggle="tab" href="#progress-report">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="your-students-tab" data-bs-toggle="tab" href="#your-students">Your Students</a>
                </li>
                
               
                <li class="nav-item">
    <a class="nav-link position-relative" id="classroom-url-tab" data-bs-toggle="tab" href="#classroom-url">
        Your Lessons
        <?php
        // Count unavailable reschedule requests that need alternatives
        $unavailable_args = array(
            'post_type'      => 'progress_report',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'tutor_name',
                        'value'   => wp_get_current_user()->display_name,
                        'compare' => '=',
                    ),
                    array(
                        'key'     => 'request_type',
                        'value'   => 'reschedule',
                        'compare' => '=',
                    ),
                    array(
                        'key'     => 'status',
                        'value'   => 'unavailable',
                        'compare' => '=',
                    ),
                    array(
                        'key'     => 'alternatives_provided',
                        'compare' => 'NOT EXISTS',
                    )
                ),
                array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'tutor_name',
                        'value'   => wp_get_current_user()->display_name,
                        'compare' => '=',
                    ),
                    array(
                        'key'     => 'request_type',
                        'value'   => 'reschedule_unavailable_all',
                        'compare' => '=',
                    ),
                    array(
                        'key'     => 'status',
                        'value'   => 'pending',
                        'compare' => '=',
                    )
                )
            )
        );
        
        $unavailable_requests = get_posts($unavailable_args);
        $unavailable_count = count($unavailable_requests);
        ?>
        <?php if ($unavailable_count > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?php echo $unavailable_count; ?>
            <span class="visually-hidden">unconfirmed requests</span>
        </span>
        <?php endif; ?>
    </a>
  </li>
            
  <li class="nav-item">
                    <a class="nav-link" id="curriculum-links-tab" data-bs-toggle="tab" href="#curriculum-links">Curriculum Links</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="submit-progress-report-tab" data-bs-toggle="tab" href="#submit-progress-report">Submit Lesson Overview</a>
                </li>
           
			  <li class="nav-item">
        <a class="nav-link" id="sample-overviews-tab" data-bs-toggle="tab" href="#sample-overviews">Sample Lesson Overviews</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="sample-reports-tab" data-bs-toggle="tab" href="#sample-reports">Sample Progress Comments</a>
    </li>
    <?php echo add_requests_tab_to_navigation(); ?>
            </ul>
        </div>
        <!-- Main Content -->
        <div class="tab-content" id="myTabContent" style="padding-top: 20px;">

                <!-- Home Tab -->
                <div class="tab-pane fade show active" id="progress-report" role="tabpanel" aria-labelledby="progress-report-tab">
                    <!-- Your home tab content here -->
					 <h3>Welcome to the Tutor Dashboard!</h3>
<p>This platform is the designated area for writing and submitting lesson overviews based on your students' tutoring lessons.</p>
<p>It is of utmost importance that you log in and promptly complete the overviews following each student's lesson, as parents eagerly anticipate reading about their child's progress within 24 hours of the lesson's conclusion.</p>
<p>As you write your overviews, please bear in mind that parents will be reviewing them closely. Therefore, it is crucial to adhere to the following guidelines:</p>
<ul>
  <li>Ensure accurate spelling of the student's name.</li>
  <li>Observe appropriate capitalisation rules, including using a capital letter at the beginning of the student's name.</li>
  <li>Maintain a consistently positive tone, refraining from any negative comments about the student.</li>
  <li>Alternate between using the child's name and pronouns (she/he) as the first word in each sentence, as exemplified below:</li>
</ul>
<blockquote>
  <p style="background-color: rgba(42, 98, 143, 0.07); padding: 10px; font-style: italic;">"Jane focused very well this afternoon. She demonstrated a solid understanding of the distinction between prime and composite numbers. Jane diligently completed several exercises to reinforce and expand her knowledge of factors. She would benefit from additional practice to further develop her understanding of factors."</p>
</blockquote>
<ul>
	
					
<li>When writing the lesson focus, please check the student's Learning Plan and reference their learning goals.</li>
<li>Ensure that your writing is appropriately punctuated to enhance clarity and coherence.</li>
	</ul>
<p>You can refer to the sample lesson overview tab or the lists of sample comments tab to assist you with your lesson overviews. Please do not hesitate to contact Tracey or Roxanne if you have any concerns or questions regarding your overviews.</p>
<p>Thank you for your commitment to writing personalised and positive lesson overviews that enable parents to read about how their child is progressing towards their learning goals.</p>


</div>
                <!-- Your Students Tab -->
<div class="tab-pane fade" id="your-students" role="tabpanel" aria-labelledby="your-students-tab">
    <!-- Your "Your Students" tab content here -->
    <?php
    // Get the IDs of the students assigned to the current user.
    $assigned_students = get_user_meta(get_current_user_id(), 'assigned_students', true);

    // If the current user has students assigned to them, display them.
    if (!empty($assigned_students)) {
        // Convert the string of student IDs into an array.
        $student_ids = explode(',', $assigned_students);

        echo '<div class="accordion" id="studentAccordion">'; // Start accordion container
        $counter = 1;
        // Loop through the array of student IDs.
        foreach ($student_ids as $student_id) {
          // Get the user data of the student.
          $student = get_userdata($student_id);
		$year = get_field('year', 'user_' . $student_id);  // Fetch the year for each student

      
          // Display the student's name as accordion header.
          echo '<div class="accordion-item">';
          echo '<h2 class="accordion-header" id="heading' . $counter . '">';
          echo '<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $counter . '" aria-expanded="true" aria-controls="collapse' . $counter . '">';
          echo $student->display_name . ' - Year ' . $year; // Display year from ACF field
          echo '</button>';
          echo '</h2>';
          echo '<div id="collapse' . $counter . '" class="accordion-collapse collapse" aria-labelledby="heading' . $counter . '" data-bs-parent="#studentAccordion">';
          echo '<div class="accordion-body">';
          
          // Start new section: display the student's info
          echo '<p><strong>Student Name:</strong> ' . $student->display_name . '</p>';
          echo '<p><strong>Year:</strong> ' . get_field('year', 'user_' . $student_id) . '</p>';
          echo '<p><strong>Lesson Schedule:</strong> ' . get_field('lesson_schedule', 'user_' . $student_id) . '</p>';
          echo '<p><strong>Overarching Learning Goals:</strong><br> ' . get_field('overarching_learning_goals', 'user_' . $student_id) . '</p>';
          echo '<p><strong>Specific Learning Goals:</strong><br> ' . get_field('specific_learning_goals', 'user_' . $student_id) . '</p>';  
          
          echo '</div>';
          echo '</div>';
          echo '</div>';
      
          $counter++;
      }

        echo '</div>'; // End accordion container
    } else {
        // If the current user doesn't have any students assigned to them, display a message.
        echo "<p>You don't have any students assigned to you.</p>";
    }
    ?>
</div>
                <!-- Submit Progress Report Tab -->
                <div class="tab-pane fade" id="submit-progress-report" role="tabpanel" aria-labelledby="submit-progress-report-tab">
                    <!-- Your "Submit Progress Report" tab content here -->
                     <!-- Your "Submit Progress Report" tab content here -->
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
                </div>


	<!-- Curriculum Links Tab -->
<div class="tab-pane fade" id="curriculum-links" role="tabpanel" aria-labelledby="curriculum-links-tab">

<style>

    .nav-pills .nav-link {
        color: #103351;
        margin-top: 16px;
        background-color: rgba(252, 179, 30, 0.25);
    }

    #pills-tab li {
      margin: 0 5px 0 0;
    }

    .nav-pills .nav-link.active {
        background-color: #fcb31e;
        color: #fff;
    }

    .nav-pills .nav-link.active:hover {
        color: #fff;
    }

    .tab-pane {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
    }
    .tab-pane a {
        margin-right: 10px;
    }
</style>

<p>It is important for you to make sure you are teaching your students the correct content for their subject and year level. When planning lesson content for your students, please refer to the relevant curriculum areas at the links below.</p>
<p>For example, if your Year 7 student's learning goals are to improve their understanding of Algebra, you will need to click through to the Australian Year 7 Mathematics Curriculum and familiarise yourself with the Content Descriptions involving Algebra.</p>

<div style="margin-top: 40px; margin-bottom: 40px;">
<h3>Lesson Resources</h3>
      <a href="https://tutorproresources.com" target="_blank">TutorPro Resources</a>
      </div>

<h3 style="margin-top:10px;">Links to Australian National Curriculum</h3>

<ul class="nav nav-pills mb-3" id="pills-tab" role="tablist" style="padding-left: 0 !important;">
    <li class="nav-item" role="presentation">
        <a class="nav-link active" id="pills-english-tab" data-bs-toggle="pill" href="#pills-english" role="tab" aria-controls="pills-english" aria-selected="true">English</a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link" id="pills-mathematics-tab" data-bs-toggle="pill" href="#pills-mathematics" role="tab" aria-controls="pills-mathematics" aria-selected="false">Mathematics</a>
    </li>
</ul>

<div class="tab-content" id="pills-tabContent">
    <div class="tab-pane fade show active" id="pills-english" role="tabpanel" aria-labelledby="pills-english-tab">
        <a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/english/foundation-year?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Foundation - English</a>
		
        <a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/english/year-1?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 1 - English</a>
        <a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/english/year-2?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 2 - English</a>
<a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/english/year-3?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 3 - English</a>
<a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/english/year-4?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 4 - English</a>
<a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/english/year-5?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 5 - English</a>
<a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/english/year-6?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 6 - English</a>
<a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/english/year-7?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 7 - English</a>
<a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/english/year-8?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 8 - English</a>
<a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/english/year-9?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 9 - English</a>
<a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/english/year-10?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 10 - English</a>
<a href="https://australiancurriculum.edu.au/senior-secondary-curriculum/english/" target="_blank" rel="noopener noreferrer">Senior English</a>	
    </div>
    <div class="tab-pane fade" id="pills-mathematics" role="tabpanel" aria-labelledby="pills-mathematics-tab">
        <a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/mathematics/foundation-year?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Foundation - Mathematics</a>
       <a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/mathematics/year-1?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 1 - Mathematics</a>
		<a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/mathematics/year-2?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 2 - Mathematics</a>
		<a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/mathematics/year-3?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 3 - Mathematics</a>
		<a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/mathematics/year-4?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 4 - Mathematics</a>
		<a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/mathematics/year-5?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 5 - Mathematics</a>
		<a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/mathematics/year-6?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 6 - Mathematics</a>
		<a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/mathematics/year-7?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 7 - Mathematics</a>
		<a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/mathematics/year-8?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 8 - Mathematics</a>
		<a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/mathematics/year-9?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 9 - Mathematics</a>
		<a href="https://v9.australiancurriculum.edu.au/f-10-curriculum/learning-areas/mathematics/year-10?view=quick&detailed-content-descriptions=0&hide-ccp=0&hide-gc=0&side-by-side=1&strands-start-index=0" target="_blank" rel="noopener noreferrer">Year 10 - Mathematics</a>
		<a href="https://australiancurriculum.edu.au/f-10-curriculum/mathematics/?year=11762&strand=Number+and+Algebra&strand=Measurement+and+Geometry&strand=Statistics+and+Probability&capability=ignore&capability=Literacy&capability=Numeracy&capability=Information+and+Communication+Technology+%28ICT%29+Capability&capability=Critical+and+Creative+Thinking&capability=Personal+and+Social+Capability&capability=Ethical+Understanding&capability=Intercultural+Understanding&priority=ignore&priority=Aboriginal+and+Torres+Strait+Islander+Histories+and+Cultures&priority=Asia+and+Australia%E2%80%99s+Engagement+with+Asia&priority=Sustainability&elaborations=true&elaborations=false&scotterms=false&isFirstPageLoad=false" target="_blank" rel="noopener noreferrer">Year 10A - Mathematics</a>
		<a href="https://australiancurriculum.edu.au/senior-secondary-curriculum/mathematics/" target="_blank" rel="noopener noreferrer">Senior Mathematics</a>
    </div>
</div>

</div>

			
			<!-- Sample Lesson Overviews -->
    <div class="tab-pane fade" id="sample-overviews" role="tabpanel" aria-labelledby="sample-overviews-tab">
        <!-- Content for Sample Lesson Overviews goes here -->
		<h3>Sample Lesson Overview</h3>
<p>Lesson Overview for Individual <strong>Year 5 English</strong> Tutoring Lesson - 30 Minutes</p>
<h4>1. Lesson Focus</h4>
<p>Enhancing <em>{student's name}</em> narrative writing skills.</p>
<h4>2. Lesson Content</h4>
<p>We began by reviewing the elements of a well-developed narrative, such as engaging introductions, descriptive language and sequential organisation. Next, we analysed a sample narrative text, discussing its structure, character development and use of dialogue to convey emotions. <em>{Student's name}</em> actively participated in brainstorming ideas for her/his own narrative, focusing on plot development, character traits and setting details. We worked on constructing strong and varied sentence structures and incorporating appropriate transitions to improve the flow of the narrative.</p>
<h4>3. Student Progress</h4>
<p><em>{Student's name}</em> demonstrated steady progress in their narrative writing skills during this lesson. (She/He) exhibited a stronger understanding of story elements and is beginning to apply that knowledge to her/his own writing. <em>{Student's name}</em> is beginning to incorporate descriptive language, dialogue and engaging introductions to enhance the quality of his/her narratives.</p>
<h4>4. Focus for Next Lesson</h4>
<p>Develop <em>{Student's name}'s</em> use of figurative language, such as similes, metaphors, and personification.</p>
<hr>
<h3>Sample Lesson Overview</h3>
<p>Lesson Overview for Individual <strong>Year 4 Mathematics</strong> Tutoring Lesson - 30 Minutes</p>
<h4>1. Lesson Focus</h4>
<p>Enhancing <em>{student's name}</em> understanding and knowledge of Fractions</p>
<h4>2. Lesson Content</h4>
<p>Reviewed the concept of fractions, including numerator, denominator, and the relationship between parts and whole. We practiced identifying and representing fractions using visuals, such as fraction bars and circles. <em>{Student's name}</em> engaged in activities involving comparing and ordering fractions, with the same denominators. (She/He) was introduced to the concept of equivalent fractions.</p>
<h4>3. Student Progress</h4>
<p><em>{Student's name}</em> showed improved confidence in identifying and representing fractions accurately. (She/He) effectively applied strategies to compare and order fractions, demonstrating her/his deeper understanding of this concept. <em>{Student's name}</em> successfully performed several equivalent fraction conversions, showing increased confidence, proficiency, and accuracy.</p>
<h4>4. Focus for Next Lesson</h4>
<p>Convert fractions to equivalent fractions</p>
    </div>

    <!-- Sample Progress Reports -->
    <div class="tab-pane fade" id="sample-reports" role="tabpanel" aria-labelledby="sample-reports-tab">
        <!-- Content for Sample Progress Reports goes here -->
		<h3>Sample Progress Comments</h3>
<p>Please vary the comments you use for each student's lesson overviews from week to week. These sample comments are designed to assist you when you are writing about the progress a student is making. You do not have to use them; however, please feel free to do so.</p>
		
		<!-- Megaccordion goes here -->
		
	<div class="accordion" id="sampleProgressAccordion">
  <div class="accordion-item">
    <h2 class="accordion-header" id="sampleProgressHeader">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sampleProgressDetails" aria-expanded="false" aria-controls="sampleProgressDetails">
        EXCELLENT PROGRESS
      </button>
    </h2>
    <div id="sampleProgressDetails" class="accordion-collapse collapse" aria-labelledby="sampleProgressHeader" data-bs-parent="#sampleProgressAccordion">
      <div class="accordion-body">
        <ol>
          <li>I am pleased to report that (Student's name) has shown exceptional progress during our individualised online lessons.</li>
          <li>I have observed remarkable improvement in (Student's name)'s engagement and active participation in our online sessions.</li>
          <li>(Student's name) consistently demonstrates outstanding dedication and commitment to their personalised online learning.</li>
          <li>I am impressed with (Student's name)'s strong work ethic and their consistent pursuit of excellence in our online lessons.</li>
          <li>Excellent progress is evident as (Student's name) consistently exhibits enthusiasm and a positive attitude towards their individualised online learning.</li>
          <li>(Student's name) has made remarkable strides in adapting to the individualised online learning environment and making the most of our sessions.</li>
          <li>I have noticed outstanding progress in (Student's name)'s ability to effectively communicate her/his understandings during our online lessons.</li>
          <li>(Student's name) consistently shows a keen interest in their personalised online lessons and actively participates in all learning tasks.</li>
          <li>Remarkable progress has been made by (Student's name) in terms of focus and concentration during our individualised online lessons.</li>
          <li>I am pleased to see (Student's name) actively applying the feedback and guidance provided, resulting in substantial progress.</li>
          <li>(Student's name) consistently demonstrates a strong desire to explore new concepts and ideas, contributing to their exceptional progress in our online lessons.</li>
          <li>(Student's name) consistently exhibits exceptional problem-solving abilities and critical thinking skills.</li>
          <li>Excellent progress is observed as (Student's name) actively engages in discussions and activities tailored to their specific learning needs.</li>
          <li>I am pleased to note outstanding progress in (Student's name)'s ability to apply their knowledge and skills to real-life situations during our online lessons.</li>
          <li>Remarkable progress has been made by (Student's name) in terms of demonstrating creativity and originality in their online learning tasks.</li>
          <li>Outstanding progress is evident as (Student's name) reflects on their learning and sets goals for continuous improvement in our online sessions.</li>
          <li>Remarkable progress has been made by (Student's name) in effectively utilising online tools and technologies to enhance their learning outcomes.</li>
          <li>(Student's name) consistently demonstrates exceptional articulation of thoughts and ideas during our online sessions.</li>
          <li>I appreciate (Student's name)'s active engagement, as they seek clarification and...</li>
          <li>I am pleased to note remarkable growth in (Student's name)'s ability to...</li>
          <li>Excellent progress is observed as (Student's name) actively seeks additional resources and opportunities for learning beyond our online sessions.</li>
          <li>(Student's name) has shown remarkable improvement in their ability to think critically and solve problems independently during our online lessons.</li>
          <li>I am impressed with (Student's name)'s consistent effort to apply new skills and knowledge to real-world scenarios, showcasing their exceptional progress.</li>
          <li>Remarkable progress has been made by (Student's name) in terms of their active participation in discussions and contributions to our online sessions.</li>
          <li>I am pleased to see outstanding progress in (Student's name)'s ability to engage with the learning material and ask insightful questions during our individualised online sessions.</li>
			 <li>(Student's name) has shown remarkable growth in their self-motivation and independent learning skills throughout our online lessons.</li>
  <li>Excellent progress is evident as (Student's name) consistently applies feedback to refine their work, demonstrating an eagerness to improve.</li>
  <li>(Student's name) consistently demonstrates exceptional initiative in seeking additional practice and materials to supplement our online lessons.</li>
  <li>I am impressed with (Student's name)'s ability to express themselves clearly and concisely during our online sessions.</li>
  <li>Outstanding progress is evident in (Student's name)'s ability to think creatively and generate innovative ideas during our individualised online lessons.</li>
  <li>(Student's name) consistently exhibits exceptional research skills and uses reliable sources to enhance their online learning experience.</li>
  <li>I am pleased to see (Student's name) actively engage in self-reflection, as they identify areas for improvement.</li>
  <li>(Student's name) consistently demonstrates exceptional focus and concentration, maximising their learning potential during our individualised online lessons.</li>
  <li>I am impressed with (Student's name)'s ability to apply their knowledge and skills in different contexts, showcasing their understandings in our online sessions.</li>
  <li>I appreciate (Student's name)'s dedication to our online lessons, as they consistently come prepared and ready to engage in meaningful learning activities.</li>
  <li>Remarkable progress has been made by (Student's name) in terms of their active listening skills and their ability to respond thoughtfully during our online sessions.</li>
  <li>(Student's name) consistently exhibits exceptional organisation of their learning materials, allowing for efficient and effective online lessons.</li>
  <li>I am pleased to note outstanding progress in (Student's name)'s ability to take ownership of their learning and set goals for self-improvement in our individualised online sessions.</li>
  <li>Excellent progress is evident as (Student's name) actively seeks feedback and implements strategies to enhance their learning experience in our online lessons.</li>
  <li>(Student's name) has shown remarkable improvement in their ability to analyse complex information and demonstrate critical thinking skills during our online sessions.</li>
  <li>(Student's name) consistently demonstrates exceptional creativity and originality in their online work, bringing a unique perspective to our lessons.</li>
  <li>I am pleased to see outstanding growth in (Student's name)'s ability to evaluate their own progress and seek additional support when needed during our online sessions.</li>
  <li>(Student's name) has shown remarkable dedication to the learning process, actively seeking opportunities to extend their knowledge beyond the online lessons.</li>
  <li>I appreciate (Student's name)'s willingness to take risks and explore new ideas, which has led to exceptional progress in our individualised online sessions.</li>
  <li>Remarkable progress has been made by (Student's name) in terms of their ability to reflect on their learning journey and set goals for future growth in our online lessons.</li>
  <li>(Student's name) consistently exhibits exceptional problem-solving skills and a resourceful approach to challenges encountered during our online sessions.</li>
  <li>I am impressed with (Student's name)'s ability to maintain a positive and growth-oriented mindset, even when faced with difficult concepts in our online lessons.</li>
        </ol>
      </div>
    </div>
  </div>
  <div class="accordion-item">
    <h2 class="accordion-header" id="veryGoodProgressHeader">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#veryGoodProgressDetails" aria-expanded="false" aria-controls="veryGoodProgressDetails">
        VERY GOOD PROGRESS
      </button>
    </h2>
    <div id="veryGoodProgressDetails" class="accordion-collapse collapse" aria-labelledby="veryGoodProgressHeader" data-bs-parent="#sampleProgressAccordion">
      <div class="accordion-body">
        <ol>
          <li>(Student's name) consistently demonstrates strong dedication and enthusiasm towards their learning.</li>
          <li>I am highly impressed by (Student's name)'s progress and commitment to their learning.</li>
          <li>(Student's name) consistently exhibits a strong work ethic and shows great initiative in their online lessons.</li>
          <li>I have been thoroughly impressed by (Student's name)'s consistent growth and development throughout our sessions.</li>
          <li>(Student's name) consistently displays a positive attitude and eagerness to learn, which greatly contributes to their progress.</li>
          <li>I am delighted to see how (Student's name) has blossomed academically, showing significant improvement in their understanding and skills.</li>
          <li>(Student's name) consistently demonstrates excellent focus and concentration during our lessons, resulting in very good progress.</li>
          <li>I am pleased to see (Student's name)'s unwavering determination to excel and the significant strides they have made.</li>
          <li>(Student's name) consistently exhibits a high level of engagement and active participation, which has greatly enhanced their learning.</li>
          <li>I am impressed by (Student's name)'s ability to grasp complex concepts quickly and effectively apply them in their work.</li>
          <li>(Student's name) consistently exhibits very good problem-solving skills and the ability to think critically.</li>
          <li>I am pleased to see (Student's name)'s consistent growth in their confidence as a learner.</li>
          <li>(Student's name) consistently demonstrates a genuine thirst for knowledge, consistently seeking out additional resources to deepen their understanding.</li>
          <li>I am impressed by (Student's name)'s ability to effectively communicate their thoughts and ideas during our sessions.</li>
          <li>I am delighted to see how (Student's name) actively applies feedback and implements suggested strategies to enhance their progress.</li>
          <li>(Student's name) consistently shows resilience and perseverance, overcoming challenges with a positive attitude.</li>
          <li>(Student's name) consistently demonstrates exceptional organisation of their learning materials, allowing for efficient and effective online lessons.</li>
          <li>I am pleased to see (Student's name)'s consistent growth in their ability to think critically and approach problems from different angles.</li>
          <li>(Student's name) consistently exhibits a strong sense of curiosity and a genuine love for learning.</li>
          <li>(Student's name) consistently displays exceptional creativity and innovative thinking in their lessons.</li>
          <li>I am delighted by (Student's name)'s consistent progress in their analytical skills and ability to evaluate information critically.</li>
          <li>(Student's name) consistently demonstrates exceptional attention to detail and produces work of high quality.</li>
          <li>I am pleased to see (Student's name)'s consistent growth in their ability to effectively apply their knowledge in practical situations.</li>
          <li>I am pleased to see (Student's name)'s consistent growth in their ability to articulate their thoughts and ideas clearly.</li>
          <li>(Student's name) consistently exhibits exceptional problem-solving skills and the ability to think outside the box.</li>
          <li>I am impressed by (Student's name)'s consistent growth in their ability to analyse complex data and draw meaningful conclusions.</li>
          <li>I am pleased to see (Student's name)'s consistent growth in their understanding and application of key concepts.</li>
          <li>(Student's name) consistently demonstrates a strong grasp of the material covered in our lessons, showing very good progress.</li>
          <li>I am delighted to witness (Student's name)'s continuous development and improvement in their academic skills.</li>
          <li>(Student's name) consistently exhibits an eagerness to learn and actively engages in the learning process, leading to very good progress.</li>
          <li>I am impressed by (Student's name)'s ability to tackle challenging tasks with confidence and achieve very good results.</li>
          <li>(Student's name) consistently shows dedication to their learning, resulting in steady and commendable progress.</li>
          <li>I am pleased to see (Student's name)'s consistent growth in their ability to apply their knowledge effectively in various contexts.</li>
          <li>(Student's name) consistently demonstrates a proactive approach to their studies and willingly takes on additional challenges, resulting in very good progress.</li>
          <li>I am impressed by (Student's name)'s ability to ask insightful questions and seek clarification, reflecting their commitment to understanding the material thoroughly.</li>
          <li>I am pleased to see (Student's name)'s consistent growth in their critical thinking skills and their ability to approach problems from different perspectives.</li>
          <li>(Student's name) consistently displays a positive attitude towards feedback and actively applies it to enhance their learning, resulting in very good progress.</li>
          <li>I am delighted to see (Student's name)'s continuous development in their communication skills, effectively expressing their thoughts and ideas.</li>
          <li>I am impressed by (Student's name)'s consistent effort and determination to complete their lesson tasks, resulting in very good progress.</li>
          <li>(Student's name) consistently exhibits excellent comprehension skills, quickly grasping new concepts and demonstrating very good progress.</li>
        </ol>
      </div>
    </div>
  </div>
<div class="accordion-item">
    <h2 class="accordion-header" id="goodProgressHeader">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#goodProgressDetails" aria-expanded="false" aria-controls="goodProgressDetails">
        GOOD PROGRESS
      </button>
    </h2>
    <div id="goodProgressDetails" class="accordion-collapse collapse" aria-labelledby="goodProgressHeader" data-bs-parent="#sampleProgressAccordion">
      <div class="accordion-body">
        <ol>
          <li>(Student's name) consistently demonstrates good effort and dedication towards their learning.</li>
<li>I am pleased to see (Student's name)'s progress and commitment to their studies.</li>
<li>(Student's name) consistently exhibits a solid work ethic and shows initiative in their online lessons.</li>
<li>I have noticed a steady growth in (Student's name)'s understanding and skills throughout our sessions.</li>
<li>(Student's name) displays a positive attitude and willingness to learn, which contributes to their progress.</li>
<li>I am glad to see how (Student's name) is developing academically, showing improvement in their understanding and skills.</li>
<li>(Student's name) demonstrates good focus and concentration during our lessons, leading to good progress.</li>
<li>I am satisfied with (Student's name)'s determination to excel and the strides they have made.</li>
<li>(Student's name) actively engages and participates in our lessons, enhancing their learning experience.</li>
<li>(Student's name) exhibits good problem-solving skills and the ability to think critically.</li>
<li>I am glad to see (Student's name)'s growing confidence and independence as a learner.</li>
<li>(Student's name) shows a genuine interest in learning and seeks additional resources to deepen their understanding.</li>
<li>(Student's name) effectively communicates their thoughts and ideas during our sessions.</li>
<li>(Student's name) implements feedback and strategies to enhance their progress.</li>
<li>(Student's name) demonstrates resilience and perseverance, overcoming challenges with a positive attitude.</li>
<li>I am pleased to see (Student's name)'s growth in critical thinking and approaching problems from different angles.</li>
<li>(Student's name) shows curiosity and a love for learning.</li>
<li>(Student's name) is demonstrating good progress in using analytical skills and evaluating information critically.</li>
<li>(Student's name) pays attention to detail and produces work of good quality.</li>
<li>I am happy to see (Student's name)'s application of knowledge in practical situations.</li>
<li>(Student's name) is able to articulate their thoughts and ideas clearly.</li>
<li>(Student's name) shows good problem-solving skills and the ability to think outside the box.</li>
<li>(Student's name) is showing good progress in analysing complex data and drawing meaningful conclusions.</li>
<li>I am glad to see (Student's name)'s growth in their ability to express themselves confidently.</li>
<li>(Student's name) applies learned concepts effectively in different contexts.</li>
<li>It has been great to see (Student's name)'s growth in understanding and applying new concepts.</li>
<li>(Student's name) consistently demonstrates good effort and commitment to their learning.</li>
<li>I am pleased to see (Student's name)'s progress and engagement in our lessons.</li>
<li>(Student's name) shows initiative and actively participates in their learning.</li>
<li>(Student's name) displays a positive attitude and willingness to learn, contributing to their progress.</li>
<li>It is great to see (Student's name) making strides in their academic development.</li>
<li>(Student's name) demonstrates good focus and attention during our lessons, leading to progress.</li>
<li>(Student's name) actively engages in our lessons and contributes to discussions.</li>
<li>(Student's name)'s ability to think critically and solve problems is developing well.</li>
<li>(Student's name) shows a genuine interest in learning and seeks additional resources to enhance their understanding.</li>
<li>(Student's name) effectively communicates their ideas and demonstrates good understanding of the subject matter.</li>
        </ol>
      </div>
    </div>
  </div>
		<div class="accordion-item">
  <h2 class="accordion-header" id="minimalProgressHeader">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#minimalProgressDetails" aria-expanded="false" aria-controls="minimalProgressDetails">
      MINIMAL PROGRESS
    </button>
  </h2>
  <div id="minimalProgressDetails" class="accordion-collapse collapse" aria-labelledby="minimalProgressHeader" data-bs-parent="#sampleProgressAccordion">
    <div class="accordion-body">
      <ol>
        <li>(Student's name) has shown consistent effort in their learning, despite facing some challenges.</li>
        <li>(Student's name) has demonstrated a positive attitude and willingness to learn, even when faced with difficulties.</li>
        <li>I have observed (Student's name)'s persistence in tackling challenging topics.</li>
        <li>(Student's name) actively engages in discussions and asks questions to seek clarification.</li>
        <li>(Student's name) shows resilience in overcoming obstacles and continues to make efforts to progress.</li>
        <li>I appreciate (Student's name)'s willingness to seek help and support when needed.</li>
        <li>(Student's name) has displayed a positive work ethic and consistently completes lesson tasks.</li>
        <li>I have noticed (Student's name)'s commitment to self-improvement and willingness to accept feedback.</li>
        <li>I appreciate (Student's name)'s ability to reflect on their own progress and identify areas for improvement.</li>
        <li>(Student's name) is making steady progress and shows potential for further growth.</li>
        <li>(Student's name) shows dedication by seeking additional resources and materials to supplement their learning.</li>
        <li>I have noticed (Student's name)'s perseverance in completing lesson tasks, even when faced with difficulties.</li>
        <li>(Student's name) actively engages in our discussions and demonstrates an eagerness to participate.</li>
        <li>(Student's name) actively seeks clarification and demonstrates a willingness to understand concepts thoroughly.</li>
        <li>I appreciate (Student's name)'s initiative in seeking extra practice and engaging in self-study.</li>
        <li>(Student's name) has shown growth in their ability to express their thoughts and ideas more clearly.</li>
        <li>I have noticed (Student's name)'s efforts in applying feedback to their work.</li>
        <li>(Student's name) shows potential for improvement and has demonstrated a desire to learn.</li>
        <li>I appreciate (Student's name)'s persistence in trying different approaches to solve problems.</li>
        <li>(Student's name) has shown progress in their ability to analyse information and draw conclusions.</li>
      </ol>
    </div>
  </div>
</div>

</div>		
		
    </div>

    <!-- Classroom URL Tab -->
    <div class="tab-pane fade" id="classroom-url" role="tabpanel" aria-labelledby="classroom-url-tab">
        <h5>Your Classroom URL</h5>
        <?php
        $classroom_url = get_user_meta(get_current_user_id(), 'classroom_url', true);
        if (!empty($classroom_url)) {
            echo '<a href="' . esc_url($classroom_url) . '" target="_blank">' . esc_html($classroom_url) . '</a></p>';
        } else {
            echo '<p>No classroom URL has been set. Please contact an administrator to set up your classroom URL.</p>';
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

    <!-- Requests Tab -->
    <div class="tab-pane fade" id="requests" role="tabpanel" aria-labelledby="requests-tab">
        <?php display_tutor_requests_content(); ?>
    </div>
    </div>            </div> <!-- This div closes the "tab-content" div -->
        <div>

<?php
} else {
    echo "Access denied. You don't have permission to view this page.";
}
?>

<?php get_footer(); ?>

<script>
document.getElementById('add-resource').addEventListener('click', function() {
    const container = document.getElementById('resource-uploads');
    const newField = document.createElement('div');
    newField.className = 'resource-upload-field mb-2 d-flex align-items-center';
    
    newField.innerHTML = `
        <input type="file" name="resources[]" class="form-control">
        <button type="button" class="btn btn-danger btn-sm ms-2 remove-resource">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(newField);
    
    // Add remove button functionality
    newField.querySelector('.remove-resource').addEventListener('click', function() {
        this.parentElement.remove();
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Handle reschedule request submission
    const submitButton = document.getElementById('submitReschedule');
    if (submitButton) {
        submitButton.addEventListener('click', function() {
            // Get form data
            const form = document.getElementById('rescheduleForm');
            const formData = new FormData(form);
            
            // Submit the form using fetch
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    // Show success message
                    const successMessage = document.getElementById('rescheduleSuccessMessage');
                    successMessage.style.display = 'block';
                    
                    // Clear form fields
                    document.getElementById('student_select').value = '';
                    document.getElementById('original_date').value = '';
                    document.getElementById('original_time').value = '';
                    document.getElementById('new_date').value = '';
                    document.getElementById('new_time').value = '';
                    
                    // Hide the form
                    form.style.display = 'none';
                    
                    // Set a timeout to close the modal after 3 seconds
                    setTimeout(function() {
                        // Close the modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('newRescheduleModal'));
                        modal.hide();
                        
                        // Reload the page to show the updated list of reschedule requests
                        window.location.reload();
                    }, 3000);
                } else {
                    alert('There was an error submitting your request. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('There was an error submitting your request. Please try again.');
            });
        });
    }
    
    // Handle toggle for reschedule history
    const toggleButton = document.getElementById('toggleRescheduleHistory');
    if (toggleButton) {
        toggleButton.addEventListener('click', function() {
            const historyTable = document.getElementById('rescheduleHistoryTable');
            const showText = this.querySelector('.show-text');
            const hideText = this.querySelector('.hide-text');
            const showIcon = this.querySelector('.show-icon');
            const hideIcon = this.querySelector('.hide-icon');
            
            if (historyTable.style.display === 'none') {
                // Show the table
                historyTable.style.display = 'block';
                showText.classList.add('d-none');
                hideText.classList.remove('d-none');
                showIcon.classList.add('d-none');
                hideIcon.classList.remove('d-none');
            } else {
                // Hide the table
                historyTable.style.display = 'none';
                showText.classList.remove('d-none');
                hideText.classList.add('d-none');
                showIcon.classList.remove('d-none');
                hideIcon.classList.add('d-none');
            }
        });
    }
    
    // Auto-refresh the reschedule history table every 60 seconds
    function refreshRescheduleHistory() {
        const historyTable = document.getElementById('rescheduleHistoryTable');
        if (historyTable && historyTable.style.display !== 'none') {
            fetch(window.location.href + '?refresh_reschedule=1')
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTable = doc.getElementById('rescheduleHistoryTable');
                    if (newTable) {
                        historyTable.innerHTML = newTable.innerHTML;
                    }
                })
                .catch(error => console.error('Error refreshing reschedule history:', error));
        }
    }
    
    // Set up auto-refresh interval
    setInterval(refreshRescheduleHistory, 60000); // Refresh every 60 seconds

    // Handle toggle for unconfirmed requests
    const toggleUnconfirmedButton = document.getElementById('toggleUnconfirmedRequests');
    if (toggleUnconfirmedButton) {
        toggleUnconfirmedButton.addEventListener('click', function() {
            const unconfirmedSection = document.getElementById('unconfirmedRequestsSection');
            const showText = this.querySelector('.show-text');
            const hideText = this.querySelector('.hide-text');
            const showIcon = this.querySelector('.show-icon');
            const hideIcon = this.querySelector('.hide-icon');
            
            if (unconfirmedSection.style.display === 'none') {
                // Show the section
                unconfirmedSection.style.display = 'block';
                showText.classList.add('d-none');
                hideText.classList.remove('d-none');
                showIcon.classList.add('d-none');
                hideIcon.classList.remove('d-none');
            } else {
                // Hide the section
                unconfirmedSection.style.display = 'none';
                showText.classList.remove('d-none');
                hideText.classList.add('d-none');
                showIcon.classList.remove('d-none');
                hideIcon.classList.add('d-none');
            }
        });
    }

    // Handle alternative times form submissions
    document.querySelectorAll('form[id^="alternativeForm"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const requestId = this.querySelector('input[name="request_id"]').value;
            const successMessage = document.getElementById('alternativeSuccess' + requestId);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    // Show success message
                    successMessage.style.display = 'block';
                    
                    // Hide the form
                    this.querySelectorAll('input, textarea, button').forEach(el => {
                        el.disabled = true;
                    });
                    
                    // Reload the page after 2 seconds to update the UI
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    alert('There was an error submitting your alternative times. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('There was an error submitting your alternative times. Please try again.');
            });
        });
    });

    // Auto-refresh the unconfirmed requests section every 60 seconds
    function refreshUnconfirmedRequests() {
        const unconfirmedSection = document.getElementById('unconfirmedRequestsSection');
        if (unconfirmedSection && unconfirmedSection.style.display !== 'none') {
            fetch(window.location.href + '?refresh_unconfirmed=1')
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newSection = doc.getElementById('unconfirmedRequestsSection');
                    if (newSection) {
                        unconfirmedSection.innerHTML = newSection.innerHTML;
                    }
                })
                .catch(error => console.error('Error refreshing unconfirmed requests:', error));
        }
    }

    // Set up auto-refresh interval for unconfirmed requests
    setInterval(refreshUnconfirmedRequests, 60000); // Refresh every 60 seconds
    
    // Toggle alternatives form when "Unavailable" button is clicked
    document.querySelectorAll('.toggle-alternatives').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetForm = document.getElementById(targetId);
            
            if (targetForm.style.display === 'none') {
                targetForm.style.display = 'block';
                this.classList.remove('btn-danger');
                this.classList.add('btn-secondary');
                this.textContent = 'Hide Alternative Times';
            } else {
                targetForm.style.display = 'none';
                this.classList.remove('btn-secondary');
                this.classList.add('btn-danger');
                this.textContent = 'Unavailable';
            }
        });
    });
    
    // Handle confirming preferred time
    document.querySelectorAll('.confirm-preferred-time').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const requestId = this.getAttribute('data-request-id');
            const preferredIndex = this.getAttribute('data-index');
            
            if (confirm('Are you sure you want to confirm this preferred time?')) {
                // Create form data
                const formData = new FormData();
                formData.append('confirm_preferred_time', '1');
                formData.append('request_id', requestId);
                formData.append('preferred_index', preferredIndex);
                
                // Submit via fetch
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.ok) {
                        // Reload the page to show the updated status
                        window.location.reload();
                    } else {
                        alert('There was an error confirming the preferred time. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('There was an error confirming the preferred time. Please try again.');
                });
            }
        });
    });
});
</script>

<?php
// Process alternative times submission
if (isset($_POST['provide_alternatives']) && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $student_id = intval($_POST['student_id']);
    
    // Get alternative times
    $alternatives = array();
    
    if (!empty($_POST['alt1_date']) && !empty($_POST['alt1_time'])) {
        $alternatives[] = array(
            'date' => sanitize_text_field($_POST['alt1_date']),
            'time' => sanitize_text_field($_POST['alt1_time'])
        );
    }
    
    if (!empty($_POST['alt2_date']) && !empty($_POST['alt2_time'])) {
        $alternatives[] = array(
            'date' => sanitize_text_field($_POST['alt2_date']),
            'time' => sanitize_text_field($_POST['alt2_time'])
        );
    }
    
    if (!empty($_POST['alt3_date']) && !empty($_POST['alt3_time'])) {
        $alternatives[] = array(
            'date' => sanitize_text_field($_POST['alt3_date']),
            'time' => sanitize_text_field($_POST['alt3_time'])
        );
    }
    
    $message = !empty($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
    
    // Create a new reschedule request with alternatives
    $new_request = array(
        'post_title'   => 'Alternative Reschedule Request - ' . wp_get_current_user()->display_name,
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'progress_report',
    );
    
    $new_request_id = wp_insert_post($new_request);
    
    if (!is_wp_error($new_request_id)) {
        // Save the request details
        update_post_meta($new_request_id, 'tutor_name', wp_get_current_user()->display_name);
        update_post_meta($new_request_id, 'student_id', $student_id);
        update_post_meta($new_request_id, 'request_type', 'reschedule_alternatives');
        update_post_meta($new_request_id, 'original_request_id', $request_id);
        update_post_meta($new_request_id, 'alternatives', $alternatives);
        update_post_meta($new_request_id, 'message', $message);
        update_post_meta($new_request_id, 'status', 'pending');
        
        // Mark the original request as having alternatives provided
        update_post_meta($request_id, 'alternatives_provided', '1');
        
        // Set a global message to display to the user
        global $submission_message;
        $submission_message = 'Alternative times have been successfully submitted.';
    } else {
        global $submission_message;
        $submission_message = 'Error: ' . $new_request_id->get_error_message();
    }
}
?>

<style>
    /* Add this to the existing style section or create a new one */
    .nav-link .badge {
        font-size: 0.65rem;
        transform: translate(-50%, -30%) !important;
    }
    
    /* Style for the unconfirmed requests section */
    #unconfirmedRequestsSection {
        background-color: #f8f9fa;
        border-radius: 0.25rem;
        padding: 1rem;
        margin-top: 0.5rem;
    }
    
    /* Make the badge more visible */
    .badge.bg-danger {
        background-color: #dc3545 !important;
    }
</style>

<!-- Add this to the Unconfirmed Requests section in tutor-dashboard.php -->
<?php
// Get requests where student is unavailable for all alternatives
$unavailable_all_args = array(
    'post_type'      => 'progress_report',
    'posts_per_page' => -1,
    'meta_query'     => array(
        'relation' => 'AND',
        array(
            'key'     => 'tutor_name',
            'value'   => wp_get_current_user()->display_name,
            'compare' => '=',
        ),
        array(
            'key'     => 'request_type',
            'value'   => 'reschedule_unavailable_all',
            'compare' => '=',
        ),
        array(
            'key'     => 'status',
            'value'   => 'pending',
            'compare' => '=',
        )
    ),
    'order'          => 'DESC',
    'orderby'        => 'date'
);

$unavailable_all_requests = get_posts($unavailable_all_args);

if (!empty($unavailable_all_requests)) {
    echo '<div class="alert alert-warning mt-3">';
    echo '<h6><i class="fas fa-exclamation-triangle me-2"></i>Students Unavailable for All Alternatives</h6>';
    echo '<p>The following students are unavailable for all alternative times you provided:</p>';
    echo '<ul class="list-group">';
    
    foreach ($unavailable_all_requests as $request) {
        $request_id = $request->ID;
        $student_id = get_post_meta($request_id, 'student_id', true);
        $student = get_userdata($student_id);
        $student_name = $student ? $student->display_name : 'Unknown Student';
        $alternatives_request_id = get_post_meta($request_id, 'alternatives_request_id', true);
        $request_date = get_the_date('F j, Y', $request_id);
        
        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        echo '<div>';
        echo '<strong>' . esc_html($student_name) . '</strong> - ' . $request_date;
        echo '<br><small class="text-muted">Student is unavailable for all alternative times provided</small>';
        echo '</div>';
        echo '<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newAlternativesModal' . $request_id . '">Provide New Alternatives</button>';
        echo '</li>';
        
        // Add modal for providing new alternatives
        echo '<div class="modal fade" id="newAlternativesModal' . $request_id . '" tabindex="-1" aria-labelledby="newAlternativesModalLabel' . $request_id . '" aria-hidden="true">';
        echo '<div class="modal-dialog">';
        echo '<div class="modal-content">';
        echo '<div class="modal-header">';
        echo '<h5 class="modal-title" id="newAlternativesModalLabel' . $request_id . '">Provide New Alternatives for ' . esc_html($student_name) . '</h5>';
        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
        echo '</div>';
        echo '<div class="modal-body">';
        echo '<form method="post" id="newAlternativesForm' . $request_id . '">';
        echo '<input type="hidden" name="provide_new_alternatives" value="1">';
        echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
        echo '<input type="hidden" name="student_id" value="' . $student_id . '">';
        
        echo '<p>Please provide new alternative times for this student:</p>';
        
        echo '<div class="mb-3">';
        echo '<label class="form-label">Alternative 1</label>';
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<input type="date" class="form-control" name="alt1_date" required>';
        echo '</div>';
        echo '<div class="col-md-6">';
        echo '<input type="time" class="form-control" name="alt1_time" required>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="mb-3">';
        echo '<label class="form-label">Alternative 2</label>';
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<input type="date" class="form-control" name="alt2_date">';
        echo '</div>';
        echo '<div class="col-md-6">';
        echo '<input type="time" class="form-control" name="alt2_time">';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="mb-3">';
        echo '<label class="form-label">Alternative 3</label>';
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<input type="date" class="form-control" name="alt3_date">';
        echo '</div>';
        echo '<div class="col-md-6">';
        echo '<input type="time" class="form-control" name="alt3_time">';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="mb-3">';
        echo '<label class="form-label">Message to Student</label>';
        echo '<textarea class="form-control" name="message" rows="3" placeholder="Optional message to the student"></textarea>';
        echo '</div>';
        
        echo '<div class="modal-footer">';
        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
        echo '<button type="submit" class="btn btn-primary">Submit New Alternatives</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>'; // End modal-body
        echo '</div>'; // End modal-content
        echo '</div>'; // End modal-dialog
        echo '</div>'; // End modal
    }
    
    echo '</ul>';
    echo '</div>';
}
?>

<?php
// Add this to the top of tutor-dashboard.php
// Process new alternatives submission
if (isset($_POST['provide_new_alternatives']) && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $student_id = intval($_POST['student_id']);
    
    // Get alternative times
    $alternatives = array();
    
    if (!empty($_POST['alt1_date']) && !empty($_POST['alt1_time'])) {
        $alternatives[] = array(
            'date' => sanitize_text_field($_POST['alt1_date']),
            'time' => sanitize_text_field($_POST['alt1_time'])
        );
    }
    
    if (!empty($_POST['alt2_date']) && !empty($_POST['alt2_time'])) {
        $alternatives[] = array(
            'date' => sanitize_text_field($_POST['alt2_date']),
            'time' => sanitize_text_field($_POST['alt2_time'])
        );
    }
    
    if (!empty($_POST['alt3_date']) && !empty($_POST['alt3_time'])) {
        $alternatives[] = array(
            'date' => sanitize_text_field($_POST['alt3_date']),
            'time' => sanitize_text_field($_POST['alt3_time'])
        );
    }
    
    $message = !empty($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
    
    // Create a new reschedule request with alternatives
    $new_request = array(
        'post_title'   => 'New Alternative Reschedule Request - ' . wp_get_current_user()->display_name,
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'progress_report',
    );
    
    $new_request_id = wp_insert_post($new_request);
    
    if (!is_wp_error($new_request_id)) {
        // Get the original request ID
        $original_request_id = get_post_meta($request_id, 'original_request_id', true);
        
        // Save the request details
        update_post_meta($new_request_id, 'tutor_name', wp_get_current_user()->display_name);
        update_post_meta($new_request_id, 'student_id', $student_id);
        update_post_meta($new_request_id, 'request_type', 'reschedule_alternatives');
        update_post_meta($new_request_id, 'original_request_id', $original_request_id);
        update_post_meta($new_request_id, 'alternatives', $alternatives);
        update_post_meta($new_request_id, 'message', $message);
        update_post_meta($new_request_id, 'status', 'pending');
        
        // Mark the original unavailable_all request as handled
        update_post_meta($request_id, 'status', 'handled');
        
        // Set a global message to display to the user
        global $submission_message;
        $submission_message = 'New alternative times have been successfully submitted.';
    } else {
        global $submission_message;
        $submission_message = 'Error: ' . $new_request_id->get_error_message();
    }
}
?>

<?php
// Add this function definition before line 106 where it's being called
function add_requests_tab_to_navigation() {
    $current_user = wp_get_current_user();
    $tutor_name = $current_user->display_name;
    
    // Count unread requests
    $unread_requests = get_posts(array(
        'post_type'      => 'progress_report',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'tutor_name',
                'value'   => $tutor_name,
                'compare' => '=',
            ),
            array(
                'key'     => 'request_type',
                'value'   => array('reschedule_unavailable_all', 'student_reschedule'),
                'compare' => 'IN',
            ),
            array(
                'key'     => 'status',
                'value'   => 'pending',
                'compare' => '=',
            ),
            array(
                'key'     => 'viewed_by_tutor',
                'compare' => 'NOT EXISTS',
            )
        ),
        'fields'         => 'ids'
    ));
    
    $unread_count = count($unread_requests);
    $notification_badge = $unread_count > 0 ? '<span class="badge rounded-pill bg-danger">' . $unread_count . '</span>' : '';
    
    // Return the tab HTML
    return '<li class="nav-item">
        <a class="nav-link position-relative" id="requests-tab" data-bs-toggle="tab" href="#requests">
            Requests ' . $notification_badge . '
        </a>
    </li>';
}

/**
 * Displays the Requests tab content for tutors
 */
function display_tutor_requests_content() {
    // Get current user's display name
    $current_user = wp_get_current_user();
    $tutor_display_name = $current_user->display_name;
    
    echo '<div class="container-fluid">';
    echo '<h3>Request Management</h3>';
    
    // Minimalist Tab Navigation
    echo '<ul class="nav nav-pills mb-4" id="requestTabs" role="tablist" style="padding-bottom: 10px; padding-left: 0px !important;">';
    echo '<li class="nav-item"><a class="nav-link active" id="outgoing-tab" data-bs-toggle="tab" href="#outgoing" role="tab" style="border-radius: 4px; margin-right: 10px;">Outgoing Requests</a></li>';
    echo '<li class="nav-item"><a class="nav-link" id="incoming-tab" data-bs-toggle="tab" href="#incoming" role="tab" style="border-radius: 4px;">Incoming Requests</a></li>';
    echo '</ul>';
    
    // Tab Content
    echo '<div class="tab-content" id="requestTabContent">';
    
    // Outgoing Requests Tab
    echo '<div class="tab-pane fade show active" id="outgoing" role="tabpanel">';
    echo '<div class="d-flex justify-content-between align-items-center mb-3">';
    echo '<h6>Requests You\'ve Sent</h6>';
    echo '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newRescheduleModal">Create New Request</button>';
    echo '</div>';
    
    // Outgoing Requests Table
    echo '<div class="table-responsive">';
    echo '<table class="table">';
    echo '<thead><tr><th>Student</th><th>Original Time</th><th>Proposed Time</th><th>Status</th></tr></thead>';
    echo '<tbody>';
    
    // Query for outgoing reschedule requests
    $args = array(
        'post_type'      => 'progress_report',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'tutor_name',
                'value'   => $tutor_display_name, // Use display name here
                'compare' => '=',
            ),
            array(
                'key'     => 'request_type',
                'value'   => 'reschedule',
                'compare' => '=',
            )
        ),
        'order'          => 'DESC',
        'orderby'        => 'date'
    );
    
    $outgoing_requests = get_posts($args);
    
    if (!empty($outgoing_requests)) {
        foreach ($outgoing_requests as $request) {
            $request_id = $request->ID;
            $student_id = get_post_meta($request_id, 'student_id', true);
            $student = get_userdata($student_id);
            
            // Get the student's first and last name instead of username
            $student_name = '';
            if ($student) {
                // Try to get first and last name
                if (!empty($student->first_name) && !empty($student->last_name)) {
                    $student_name = $student->first_name . ' ' . $student->last_name;
                } else {
                    // Fall back to display name
                    $student_name = $student->display_name;
                }
            } else {
                $student_name = 'Unknown Student';
            }
            
            $original_date = get_post_meta($request_id, 'original_date', true);
            $original_time = get_post_meta($request_id, 'original_time', true);
            $new_date = get_post_meta($request_id, 'new_date', true);
            $new_time = get_post_meta($request_id, 'new_time', true);
            $status = get_post_meta($request_id, 'status', true);
            
            // Format dates for display
            $original_datetime = date('M j, Y', strtotime($original_date)) . ' at ' . date('g:i A', strtotime($original_time));
            $new_datetime = date('M j, Y', strtotime($new_date)) . ' at ' . date('g:i A', strtotime($new_time));
            
            // Status badge
            $status_class = 'warning';
            $status_text = 'Pending';
            
            if ($status === 'confirmed') {
                $status_class = 'success';
                $status_text = 'Confirmed';
            } elseif ($status === 'unavailable') {
                $status_class = 'danger';
                $status_text = 'Unavailable';
            }
            
            echo '<tr>';
            echo '<td>' . esc_html($student_name) . '</td>';
            echo '<td>' . esc_html($original_datetime) . '</td>';
            echo '<td>' . esc_html($new_datetime) . '</td>';
            echo '<td><span class="badge bg-' . $status_class . '">' . $status_text . '</span></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4" class="text-center text-muted">No outgoing requests found.</td></tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>'; // End table-responsive
    echo '</div>'; // End outgoing tab
    
    // Incoming Requests Tab
    echo '<div class="tab-pane fade" id="incoming" role="tabpanel">';
    echo '<h6 class="mb-3">Requests From Students</h6>';
    
    // Incoming Requests Table
    echo '<div class="table-responsive">';
    echo '<table class="table">';
    echo '<thead><tr><th>Student</th><th>Original Time</th><th>Requested Time</th><th>Status</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    
    // Get assigned students
    $assigned_students = get_user_meta(get_current_user_id(), 'assigned_students', true);
    $student_ids = !empty($assigned_students) ? explode(',', $assigned_students) : array();
    
    if (!empty($student_ids)) {
        // Query for incoming reschedule requests from students
        $args = array(
            'post_type'      => 'progress_report',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => 'tutor_name',
                    'value'   => $tutor_display_name,
                    'compare' => '=',
                ),
                array(
                    'key'     => 'student_id',
                    'value'   => $student_ids,
                    'compare' => 'IN',
                ),
                array(
                    'key'     => 'request_type',
                    'value'   => 'student_reschedule',
                    'compare' => '=',
                )
            ),
            'order'          => 'DESC',
            'orderby'        => 'date'
        );
        
        $incoming_requests = get_posts($args);
        
        if (!empty($incoming_requests)) {
            foreach ($incoming_requests as $request) {
                // Mark as viewed by tutor
                update_post_meta($request->ID, 'viewed_by_tutor', '1');
                
                $request_id = $request->ID;
                $student_id = get_post_meta($request_id, 'student_id', true);
                $student = get_userdata($student_id);
                
                // Get the student's first and last name instead of username
                $student_name = '';
                if ($student) {
                    // Try to get first and last name
                    if (!empty($student->first_name) && !empty($student->last_name)) {
                        $student_name = $student->first_name . ' ' . $student->last_name;
                    } else {
                        // Fall back to display name
                        $student_name = $student->display_name;
                    }
                } else {
                    $student_name = 'Unknown Student';
                }
                
                $original_date = get_post_meta($request_id, 'original_date', true);
                $original_time = get_post_meta($request_id, 'original_time', true);
                $new_date = get_post_meta($request_id, 'new_date', true);
                $new_time = get_post_meta($request_id, 'new_time', true);
                $status = get_post_meta($request_id, 'status', true);
                $reason = get_post_meta($request_id, 'reason', true);
                
                // Format dates for display
                $original_datetime = date('M j, Y', strtotime($original_date)) . ' at ' . date('g:i A', strtotime($original_time));
                $new_datetime = date('M j, Y', strtotime($new_date)) . ' at ' . date('g:i A', strtotime($new_time));
                
                // Status badge
                $status_class = 'warning';
                $status_text = 'Pending';
                
                if ($status === 'confirmed') {
                    $status_class = 'success';
                    $status_text = 'Confirmed';
                } elseif ($status === 'unavailable') {
                    $status_class = 'danger';
                    $status_text = 'Unavailable';
                }
                
                echo '<tr>';
                echo '<td>' . esc_html($student_name) . '</td>';
                echo '<td>' . esc_html($original_datetime) . '</td>';
                echo '<td>' . esc_html($new_datetime) . '</td>';
                echo '<td><span class="badge bg-' . $status_class . '">' . $status_text . '</span></td>';
                
                // Action buttons
                echo '<td>';
                if ($status === 'pending') {
                    echo '<form method="post" class="d-inline me-2">';
                    echo '<input type="hidden" name="request_action" value="confirm">';
                    echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                    echo '<input type="hidden" name="tutor_request_nonce" value="' . wp_create_nonce('tutor_request_action') . '">';
                    echo '<button type="submit" class="btn btn-sm btn-success">Confirm</button>';
                    echo '</form> ';
                    
                    echo '<form method="post" class="d-inline">';
                    echo '<input type="hidden" name="request_action" value="decline">';
                    echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
                    echo '<input type="hidden" name="tutor_request_nonce" value="' . wp_create_nonce('tutor_request_action') . '">';
                    echo '<input type="text" name="decline_reason" placeholder="Reason" required>';
                    echo '<button type="submit" class="btn btn-sm btn-danger">Decline</button>';
                    echo '</form>';
                } elseif ($status === 'declined') {
                    echo 'Reason: ' . esc_html($reason);
                } else {
                    echo '<span class="text-muted">No actions available</span>';
                }
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="5" class="text-center text-muted">No incoming requests from students found.</td></tr>';
        }
    } else {
        echo '<tr><td colspan="5" class="text-center text-muted">No assigned students found.</td></tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>'; // End table-responsive
    echo '</div>'; // End incoming tab
    
    echo '</div>'; // End tab content
    
    // Modal for creating a new reschedule request
    ?>
    <div class="modal fade" id="newRescheduleModal" tabindex="-1" aria-labelledby="newRescheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newRescheduleModalLabel">Create Reschedule Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <input type="hidden" name="submit_reschedule_request" value="1">
                        <input type="hidden" name="tutor_name" value="<?php echo esc_attr($tutor_display_name); ?>">
                        
                        <div class="mb-3">
                            <label for="student_select" class="form-label">Select Student</label>
                            <select name="student_id" id="student_select" class="form-select" required>
                                <option value="">--Select student--</option>
                                <?php
                                $assigned_students = get_user_meta(get_current_user_id(), 'assigned_students', true);
                                $student_ids = !empty($assigned_students) ? explode(',', $assigned_students) : array();

                                foreach ($student_ids as $student_id) {
                                    $student = get_userdata($student_id);
                                    if ($student) {
                                        $year = get_field('year', 'user_' . $student_id);
                                        
                                        // Get the student's first and last name
                                        $student_name = '';
                                        if (!empty($student->first_name) && !empty($student->last_name)) {
                                            $student_name = $student->first_name . ' ' . $student->last_name;
                                        } else {
                                            $student_name = $student->display_name;
                                        }
                                        
                                        echo '<option value="' . esc_attr($student_id) . '">' . esc_html($student_name) . ' - Year ' . esc_html($year) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="original_date" class="form-label">Original Date</label>
                                <input type="date" class="form-control" id="original_date" name="original_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="original_time" class="form-label">Original Time</label>
                                <input type="time" class="form-control" id="original_time" name="original_time" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="new_date" class="form-label">Proposed Date</label>
                                <input type="date" class="form-control" id="new_date" name="new_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="new_time" class="form-label">Proposed Time</label>
                                <input type="time" class="form-control" id="new_time" name="new_time" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason (Optional)</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3"></textarea>
                        </div>
                        
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Submit Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    /* Minimalist tab styling */
    #requestTabs .nav-link {
        color: #495057;
        background-color: #f8f9fa;
        border: none;
        padding: 8px 16px;
        transition: all 0.2s ease;
    }
    
    #requestTabs .nav-link:hover {
        background-color: #e9ecef;
    }
    
    #requestTabs .nav-link.active {
        color: #fff;
        background-color: #0d6efd;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* Clean table styling */
    .table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .table thead th {
        border-top: none;
        border-bottom: 1px solid #dee2e6;
        font-weight: 500;
        color: #495057;
    }
    
    .table tbody tr {
        border-bottom: 1px solid #f8f9fa;
    }
    
    .table tbody tr:hover {
        background-color: #f8f9fa;
    }
    </style>
    <?php
    echo '</div>'; // End container
}

/**
 * Handle request actions (confirm/decline)
 */
function handle_tutor_request_actions() {
    // Check if this is a request action
    if (isset($_POST['request_action']) && isset($_POST['request_id'])) {
        $action = sanitize_text_field($_POST['request_action']);
        $request_id = intval($_POST['request_id']);
        $reason = isset($_POST['decline_reason']) ? sanitize_text_field($_POST['decline_reason']) : '';
        
        // Verify nonce for security
        if (!isset($_POST['tutor_request_nonce']) || !wp_verify_nonce($_POST['tutor_request_nonce'], 'tutor_request_action')) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'tutor_requests';
        
        if ($action === 'confirm') {
            $wpdb->update(
                $table_name,
                ['status' => 'confirmed'],
                ['id' => $request_id],
                ['%s'],
                ['%d']
            );
        } elseif ($action === 'decline') {
            $wpdb->update(
                $table_name,
                [
                    'status' => 'declined',
                    'decline_reason' => $reason
                ],
                ['id' => $request_id],
                ['%s', '%s'],
                ['%d']
            );
        }
        
        // Redirect back to the same page to prevent form resubmission
        wp_redirect(add_query_arg('tab', 'requests', remove_query_arg(['request_action', 'request_id'])));
        exit;
    }
}
add_action('init', 'handle_tutor_request_actions');

/**
 * Modify display_tutor_requests_tab function to include form handling
 */
function display_tutor_requests_ui() {
    // ... existing code ...
    
    // Add this inside the function where you display the table rows
    function render_action_buttons($request) {
        $nonce = wp_create_nonce('tutor_request_action');
        $output = '';
        
        if ($request->status === 'pending') {
            $output .= '<form method="post" style="display:inline-block;">';
            $output .= '<input type="hidden" name="request_action" value="confirm">';
            $output .= '<input type="hidden" name="request_id" value="' . esc_attr($request->id) . '">';
            $output .= '<input type="hidden" name="tutor_request_nonce" value="' . $nonce . '">';
            $output .= '<button type="submit" class="btn btn-success">Confirm</button>';
            $output .= '</form> ';
            
            $output .= '<form method="post" style="display:inline-block;" onsubmit="return validateDeclineReason(this);">';
            $output .= '<input type="hidden" name="request_action" value="decline">';
            $output .= '<input type="hidden" name="request_id" value="' . esc_attr($request->id) . '">';
            $output .= '<input type="hidden" name="tutor_request_nonce" value="' . $nonce . '">';
            $output .= '<input type="text" name="decline_reason" placeholder="Reason" required>';
            $output .= '<button type="submit" class="btn btn-danger">Decline</button>';
            $output .= '</form>';
        } elseif ($request->status === 'declined') {
            $output .= 'Reason: ' . esc_html($request->decline_reason);
        } else {
            $output .= 'No actions available';
        }
        
        return $output;
    }
    
    // Add this JavaScript to validate the decline reason
    ?>
    <script>
    function validateDeclineReason(form) {
        const reasonInput = form.querySelector('input[name="decline_reason"]');
        if (!reasonInput.value.trim()) {
            alert('Please provide a reason for declining the request.');
            return false;
        }
        return true;
    }
    </script>
    <?php
}

?>