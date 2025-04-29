<!-- ===========================
     YOUR STUDENTS TAB
     =========================== -->
    <h3>Your Students</h3>
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