<?php
/**
 * Understrap Child Theme functions and definitions*
 * @package UnderstrapChild
 */

 // Enqueue student dashboard styles and scripts
function enqueue_student_dashboard_styles() {
    if (is_page('student-dashboard')) {
        wp_enqueue_style(
            'student-dashboard-styles', 
            get_stylesheet_directory_uri() . '/students/styles.css',
            array(),
            filemtime(get_stylesheet_directory() . '/students/styles.css')
        );
             
        // Enqueue JavaScript
        wp_enqueue_script(
            'student-dashboard-scripts',
            get_stylesheet_directory_uri() . '/students/index.js',
            array('jquery'),
            filemtime(get_stylesheet_directory() . '/students/index.js'),
            true
        );
        
        // Pass PHP variables to JavaScript
        wp_localize_script('student-dashboard-scripts', 'studentDashboardData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'student_id' => get_current_user_id(),
            'nonce' => wp_create_nonce('check_incoming_reschedule_requests_nonce'),
            'markReadNonce' => wp_create_nonce('mark_student_requests_read_nonce'), // Added this nonce
            'markAlternativesViewedUrl' => add_query_arg(array("mark_alternatives_viewed" => "1"), get_permalink()),
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_student_dashboard_styles');




function enqueue_tutor_dashboard_styles() {
    // if (is_page('tutor-dashboard')) { // <-- Keep condition commented out for now

        // RESTORE STYLE ENQUEUE
        wp_enqueue_style(
            'tutor-dashboard-styles',
            get_stylesheet_directory_uri() . '/tutors/styles.css',
            array(),
            filemtime(get_stylesheet_directory() . '/tutors/styles.css')
        );

        // // SIMPLIFIED SCRIPT ENQUEUE (Keep dependencies minimal for now)
        // wp_enqueue_script(
        //     'tutor-requests-scripts',
        //     get_stylesheet_directory_uri() . '/tutors/requests/index.js',
        //     array('jquery'), // Only depend on jQuery
        //     null, // No versioning
        //     true // Load in footer
        // );

        // RESTORE wp_localize_script
        wp_localize_script(
            'jquery',                    // <--- Change handle to 'jquery'
            'tutorDashboardData',        // Name of the JavaScript object
            array(                       // Data array
                'ajaxurl' => admin_url('admin-ajax.php'),
                'rescheduleNonce' => wp_create_nonce('tutor_reschedule_request_action'), // Nonce for the AJAX request
            )
        );

    // } // <-- Keep condition commented out for now
}
add_action('wp_enqueue_scripts', 'enqueue_tutor_dashboard_styles');
    

// Add classroom URL field to user profile // THIS IS THE TUTOR DASHBOARD //
function add_classroom_url_field($user) {
    if (in_array('tutor', (array)$user->roles)) {
        ?>
        <hr style="margin-top: 20px; margin-bottom: 20px;">
        <h3>Tutor Classroom URL</h3>
        <table class="form-table" id="classroom-fields">
            <tr>
                <th><label for="tutor_classroom_name">Classroom URL</label></th>    
                <td>
                    <input type="url" name="tutor_classroom_url" id="tutor_classroom_url" value="<?php echo esc_attr(get_user_meta($user->ID, 'tutor_classroom_url', true)); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }
}
add_action('show_user_profile', 'add_classroom_url_field');
add_action('edit_user_profile', 'add_classroom_url_field');



function add_schedule_field($user) {
    if (in_array('tutor', (array)$user->roles)) {
        ?>
        <h3>Schedule</h3>
        <table class="form-table">
            <tr>
                <th><label for="schedule">Schedule</label></th>
                <td>
                    <input type="url" name="schedule" id="schedule" value="<?php echo esc_attr(get_user_meta($user->ID, 'schedule', true)); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }
}
add_action('show_user_profile', 'add_schedule_field');
add_action('edit_user_profile', 'add_schedule_field');

// Save schedule field
function save_schedule_field($user_id) {
    if (current_user_can('edit_user', $user_id)) {
        if (isset($_POST['schedule'])) {
            update_user_meta($user_id, 'schedule', sanitize_text_field($_POST['schedule']));
        }
    }
}
add_action('personal_options_update', 'save_schedule_field');
add_action('edit_user_profile_update', 'save_schedule_field');



// Add lesson schedule field to user profile
function add_lesson_schedule_field($user) {
    if (in_array('student', (array)$user->roles)) {
        // Get existing schedule
        $existing_schedule = get_user_meta($user->ID, 'lesson_schedule_list', true);
        ?>
        <h3>Lesson Schedule</h3>
        <table class="form-table">
            <tr>
                <th><label for="lesson_subject">Select Subject</label></th>
                <td>
                    <select name="lesson_subject" id="lesson_subject">
                        <option value="mathematics">Mathematics</option>
                        <option value="english">English</option>
                        <option value="chemistry">Chemistry</option>
                        <option value="physics">Physics</option>
                    </select>
                    <p class="description">Select the subject for the lesson.</p>
                </td>
            </tr>
            <tr>
                <th><label for="lesson_date">Date</label></th>
                <td>
                    <input type="date" name="lesson_date" id="lesson_date" class="regular-text" min="<?php echo date('Y-m-d'); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="lesson_time">Time</label></th>
                <td>
                    <input type="time" name="lesson_time" id="lesson_time" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="is_recurring">Recurring Lesson</label></th>
                <td>
                    <input type="checkbox" name="is_recurring" id="is_recurring">
                    <div id="recurring_options" style="display: none; margin-top: 10px;">
                        <select name="recurring_weeks" id="recurring_weeks">
                            <option value="1">1 week</option>
                            <option value="2">2 weeks</option>
                            <option value="3">3 weeks</option>
                            <option value="4">4 weeks</option>
                            <option value="5">5 weeks</option>
                            <option value="6">6 weeks</option>
                            <option value="7">7 weeks</option>
                            <option value="8">8 weeks</option>
                            <option value="9">9 weeks</option>
                            <option value="10">10 weeks</option>
                        </select>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding:0px;">
                    <button type="button" id="add_lesson" class="button">Add Lesson</button>
                </td>
            </tr>
        </table>
        <div id="lesson_schedule_list" style="max-width: 400px; margin: 0; padding: 0;">
            <h4>Scheduled Lessons</h4>
            <ul id="scheduled_lessons" style="list-style: none; padding: 0;">
                <?php
                if (!empty($existing_schedule)) {
                    $lessons = explode("\n", $existing_schedule);
                    foreach ($lessons as $lesson) {
                        if (!empty(trim($lesson))) {
                            ?>
                            <li style="margin-bottom: 10px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; background-color: #f9f9f9; padding: 10px; width: 100%; box-sizing: border-box;">
                                    <span style="flex-grow: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo esc_html(trim($lesson)); ?></span>
                                    <button type="button" class="button delete-lesson" style="flex-shrink: 0;">Delete</button>
                                </div>
                            </li>
                            <?php
                        }
                    }
                }
                ?>
            </ul>
        </div>
        <input type="hidden" name="lesson_schedule_list" id="lesson_schedule_list_input" value="<?php echo esc_attr($existing_schedule); ?>">

        <script>
            jQuery(document).ready(function($) {
                // Toggle recurring options visibility
                $('#is_recurring').change(function() {
                    $('#recurring_options').toggle(this.checked);
                });

                // Function to update hidden input with proper line breaks
                function updateHiddenInput() {
                    var lessons = [];
                    $('#scheduled_lessons li').each(function() {
                        var lessonText = $(this).find('span').text().trim();
                        if (lessonText) {
                            lessons.push(lessonText);
                        }
                    });
                    $('#lesson_schedule_list_input').val(lessons.join('\n'));
                }

                // Update the add lesson code to use the new structure
                $('#add_lesson').click(function() {
                    var subject = $('#lesson_subject').val();
                    var date = $('#lesson_date').val();
                    var time = $('#lesson_time').val();
                    var isRecurring = $('#is_recurring').is(':checked');
                    var recurringWeeks = isRecurring ? parseInt($('#recurring_weeks').val()) : 0;

                    if (subject && date && time) {
                        var totalLessons = recurringWeeks + 1;
                        var baseDate = new Date(date);
                        
                        for (var i = 0; i < totalLessons; i++) {
                            var currentDate = new Date(baseDate);
                            currentDate.setDate(currentDate.getDate() + (i * 7));
                            
                            var formattedDate = currentDate.toLocaleDateString('en-GB', {
                                weekday: 'long',    // e.g., "Monday"
                                day: 'numeric',     // e.g., "1"
                                month: 'long',      // e.g., "January"
                                year: 'numeric'     // e.g., "2024"
                            });
                            
                            var lesson = subject.charAt(0).toUpperCase() + subject.slice(1) + ' on ' + formattedDate + ' at ' + time;
                            
                            var lessonItem = $(
                                '<li style="margin-bottom: 10px; padding: 10px; background: #f9f9f9; display: flex; justify-content: space-between; align-items: center;">' +
                                '<span>' + lesson + '</span>' +
                                '<button type="button" class="button delete-lesson">Delete</button>' +
                                '</li>'
                            );
                            $('#scheduled_lessons').append(lessonItem);
                        }
                        
                        updateHiddenInput();
                        
                        // Clear form fields
                        $('#lesson_subject').val('mathematics'); // do we need other subjects?
                        $('#lesson_subject').val('english');
                        $('#lesson_subject').val('chemistry');
                        $('#lesson_subject').val('physics');
                        $('#lesson_date').val('');
                        $('#lesson_time').val('');
                        $('#is_recurring').prop('checked', false);
                        $('#recurring_options').hide();
                    }
                });

                // Handle deletion of individual lessons
                $(document).on('click', '.delete-lesson', function() {
                    $(this).closest('li').remove();
                    updateHiddenInput();
                });
            });
        </script>
        <?php
    }
}
add_action('show_user_profile', 'add_lesson_schedule_field');
add_action('edit_user_profile', 'add_lesson_schedule_field');

// Save lesson schedule field
function save_lesson_schedule_field($user_id) {
    if (current_user_can('edit_user', $user_id)) {
        // Here you can save the lesson schedule data as needed
        // For example, you might want to save it as user meta
    }
}
add_action('personal_options_update', 'save_lesson_schedule_field');
add_action('edit_user_profile_update', 'save_lesson_schedule_field');

// Save lesson schedule
function save_lesson_schedule($user_id) {
    if (isset($_POST['lesson_schedule_list'])) {
        // Get the raw lesson schedule list
        $raw_schedule = wp_unslash($_POST['lesson_schedule_list']);
        
        // Split into array and clean up
        $lessons = explode("\n", $raw_schedule);
        $lessons = array_map('trim', $lessons);
        $lessons = array_filter($lessons);
        
        // Save with explicit line breaks and preserve formatting
        update_user_meta($user_id, 'lesson_schedule_list', wp_kses_post(implode("\n", $lessons)));
    }
}
add_action('personal_options_update', 'save_lesson_schedule');
add_action('edit_user_profile_update', 'save_lesson_schedule');



// ================ end of changes January 2025 ============== //

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Removes the parent themes stylesheet and scripts from inc/enqueue.php
 */
function understrap_remove_scripts() {
	wp_dequeue_style( 'understrap-styles' );
	wp_deregister_style( 'understrap-styles' );

	wp_dequeue_script( 'understrap-scripts' );
	wp_deregister_script( 'understrap-scripts' );
}
add_action( 'wp_enqueue_scripts', 'understrap_remove_scripts', 20 );

/**
 * Enqueue our stylesheet and javascript file
 */
function theme_enqueue_styles() {
	// Get the theme data.
	$the_theme     = wp_get_theme();
	$theme_version = $the_theme->get( 'Version' );

	$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	// Grab asset urls.
	$theme_styles  = "/css/child-theme{$suffix}.css";
	$theme_scripts = "/js/child-theme{$suffix}.js";

	$css_version = $theme_version . '.' . filemtime( get_stylesheet_directory() . $theme_styles );

	wp_enqueue_style( 'child-understrap-styles', get_stylesheet_directory_uri() . $theme_styles, array(), $css_version );
	wp_enqueue_script( 'jquery' );

	$js_version = $theme_version . '.' . filemtime( get_stylesheet_directory() . $theme_scripts );

	// Child theme script depends only on jQuery again
	wp_enqueue_script( 'child-understrap-scripts', get_stylesheet_directory_uri() . $theme_scripts, array('jquery'), $js_version, true );
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );

/**
 * Load the child theme's text domain
 */
function add_child_theme_textdomain() {
	load_child_theme_textdomain( 'understrap-child', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'add_child_theme_textdomain' );

/**
 * Overrides the theme_mod to default to Bootstrap 5
 *
 * This function uses the `theme_mod_{$name}` hook and
 * can be duplicated to override other theme settings.
 *
 * @return string
 */
function understrap_default_bootstrap_version() {
	return 'bootstrap5';
}
add_filter( 'theme_mod_understrap_bootstrap_version', 'understrap_default_bootstrap_version', 20 );

/**
 * Loads javascript for showing customizer warning dialog.
 */
function understrap_child_customize_controls_js() {
	wp_enqueue_script(
		'understrap_child_customizer',
		get_stylesheet_directory_uri() . '/js/customizer-controls.js',
		array( 'customize-preview' ),
		'20130508',
		true
	);
}
add_action( 'customize_controls_enqueue_scripts', 'understrap_child_customize_controls_js' );

/**
 * Remove page title from header on certain pages
 */
add_filter( 'the_title', 'remove_page_title_from_header' );
function remove_page_title_from_header( $title ) {
    if ( is_page() ) {
        $title = '';
    }
    return $title;
}

/** Custom Post Type [progress report] */

function create_progress_report_post_type() {
	register_post_type( 'progress_report',
		array(
			'labels'      => array(
				'name'          => __( 'Lesson Overviews' ),
				'singular_name' => __( 'Lesson Overview' ),
			),
			'public'      => true,
			'has_archive' => true,
			'supports'    => array( 'title', 'editor', 'custom-fields' ),
			'menu_icon'   => 'dashicons-clipboard', // Added the menu_icon attribute
		)
	);
}
add_action( 'init', 'create_progress_report_post_type' );

function create_custom_roles() {
    add_role(
        'student',
        'Student',
        array(
            'read' => true,
        )
    );

    add_role(
        'tutor',
        'Tutor',
        array(
            'read' => true,
        )
    );
}
add_action( 'after_switch_theme', 'create_custom_roles' );

function show_assigned_students_field( $user ) {
    if ( in_array( 'tutor', $user->roles ) ) {
        $assigned_students = get_user_meta( $user->ID, 'assigned_students', true );
        $assigned_student_ids = ! empty( $assigned_students ) ? array_map( 'intval', explode( ',', $assigned_students ) ) : array();

        $students = get_users( array(
            'role'         => 'student',
            'orderby'      => 'display_name',
            'order'        => 'ASC',
            'meta_query'   => array(
                array(
                    'key'     => 'wp_capabilities',
                    'value'   => 'student',
                    'compare' => 'LIKE'
                )
            )
        ) );

        $assigned_student_names = array();
        foreach ( $students as $student ) {
            if ( in_array( $student->ID, $assigned_student_ids ) ) {
                $assigned_student_names[] = $student->display_name;
            }
        }
        ?>
        <h3>Assigned Students</h3>
        <table class="form-table">
            <tr>
                <th><label for="assigned_students">Assigned Students</label></th>
                <td>
                    <?php if ( empty( $students ) ) { ?>
                        <p>No students found.</p>
                    <?php } else { ?>
                        <select name="assigned_students[]" id="assigned_students" multiple>
                            <?php foreach ( $students as $student ) { ?>
                                <option value="<?php echo $student->ID; ?>"<?php if ( in_array( $student->ID, $assigned_student_ids ) ) { echo ' selected'; } ?>><?php echo $student->display_name; ?></option>
                            <?php } ?>
                        </select>
                        <script>
                            jQuery('#assigned_students').change(function() {
                                var selected_names = [];
                                jQuery('#assigned_students option:selected').each(function() {
                                    selected_names.push(jQuery(this).text());
                                });
                                jQuery('#assigned_students_list').empty();
                                for (var i = 0; i < selected_names.length; i++) {
                                    jQuery('#assigned_students_list').append('<li>' + selected_names[i] + '</li>');
                                }
                            });
                        </script>
                    <?php } ?>
                    <br />
                    <span class="description">Select the students assigned to this tutor.</span>
                </td>
            </tr>
            <?php if ( ! empty( $assigned_student_names ) ) { ?>
                <tr>
                    <th><label for="assigned_students_list">Assigned Students List</label></th>
                    <td>
                        <ul id="assigned_students_list">
                            <?php foreach ( $assigned_student_names as $name ) { ?>
                                <li><?php echo $name; ?></li>
                            <?php } ?>
                        </ul>
                    </td>
                </tr>
            <?php } ?>
        </table>
        <?php
    }
}

// Save classroom URL
function save_classroom_url_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    // Save Tutor classroom details
    if (isset($_POST['tutor_classroom_name'])) {
        update_field('tutor_classroom_name', sanitize_text_field($_POST['tutor_classroom_name']), 'user_' . $user_id);
    }
    if (isset($_POST['tutor_classroom_url'])) {
        update_field('tutor_classroom_url', sanitize_url($_POST['tutor_classroom_url']), 'user_' . $user_id);
    }
}
add_action('personal_options_update', 'save_classroom_url_field');
add_action('edit_user_profile_update', 'save_classroom_url_field');

// ============================== //

add_action( 'show_user_profile', 'show_assigned_students_field' );
add_action( 'edit_user_profile', 'show_assigned_students_field' );

function save_assigned_students_field( $user_id ) {
    if ( current_user_can( 'edit_user', $user_id ) ) {
        $students = isset( $_POST['assigned_students'] ) && is_array( $_POST['assigned_students'] ) ? $_POST['assigned_students'] : array();
        update_user_meta( $user_id, 'assigned_students', implode( ',', $students ) );
        error_log( 'Assigned students updated for user ID ' . $user_id . ': ' . print_r( $students, true ) );
    }
}
add_action( 'personal_options_update', 'save_assigned_students_field' );
add_action( 'edit_user_profile_update', 'save_assigned_students_field' );

function process_tutor_dashboard_form() {
    // Handle regular lesson overview submissions (keep this existing code)
    if (isset($_POST['submit_progress_report'])) {
        // Include WordPress file handling functions
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $tutor_name = $_POST['tutor_name'];
        $student_id = $_POST['student_id'];
        $student_name = get_user_by('ID', $student_id)->display_name; // Student's display name based on ID
        $lesson_date = $_POST['lesson_date'];
        $lesson_focus = $_POST['lesson_focus'];
        $content_covered = $_POST['content_covered'];
        $student_progress = $_POST['student_progress'];
        $next_focus = $_POST['next_focus'];
        $resources = $_FILES['resources'];

        $new_progress_report = array(
            'post_title' => 'Submission by ' . $tutor_name . ' - ' . $lesson_date . ' - ' . '[' . $student_name . ']',
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'progress_report',
        );

        $post_id = wp_insert_post( $new_progress_report, true );

        if ( is_wp_error( $post_id ) ) {
            echo $post_id->get_error_message();
        } else if ( $post_id ) {
            update_post_meta( $post_id, 'tutor_name', $tutor_name );
            update_post_meta( $post_id, 'student_id', $student_id );
            update_post_meta( $post_id, 'lesson_date', $lesson_date );
            update_post_meta( $post_id, 'lesson_focus', $lesson_focus );
            update_post_meta( $post_id, 'content_covered', $content_covered );
            update_post_meta( $post_id, 'student_progress', $student_progress );
            update_post_meta( $post_id, 'next_focus', $next_focus );

            // Handle multiple file uploads
            $uploaded_files = array();
            
            if(!empty($_FILES['resources']['name'][0])) {
                $files = $_FILES['resources'];
                $total = count($_FILES['resources']['name']);
                
                // Restructure files array
                for($i = 0; $i < $total; $i++) {
                    $file = array(
                        'name'     => $files['name'][$i],
                        'type'     => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error'    => $files['error'][$i],
                        'size'     => $files['size'][$i]
                    );
                    
                    // Upload each file
                    $upload = wp_handle_upload($file, array('test_form' => false));
                    
                    if(!isset($upload['error'])) {
                        $uploaded_files[] = $upload['url'];
                    }
                }
            }
            
            // Store uploaded files URLs as post meta
            if(!empty($uploaded_files)) {
                update_post_meta($post_id, 'lesson_resources', $uploaded_files);
            }

            global $submission_message;
            $submission_message = 'Your Lesson Overview has been successfully submitted.';
        } else {
            echo 'Error creating your lesson overview.';
        }
    }
    
    // Add new code to handle reschedule requests
    if (isset($_POST['submit_reschedule_request'])) {
        $tutor_name = sanitize_text_field($_POST['tutor_name']);
        $student_id = intval($_POST['student_id']);
        $student = get_userdata($student_id);
        $student_name = $student ? $student->display_name : 'Unknown Student';
        
        // Get original and new date/time
        $original_date = sanitize_text_field($_POST['original_date']);
        $original_time = sanitize_text_field($_POST['original_time']);
        $new_date = sanitize_text_field($_POST['new_date']);
        $new_time = sanitize_text_field($_POST['new_time']);
        $reason = sanitize_textarea_field($_POST['reason']);
        
        // Create a new progress report post for the reschedule request
        $new_reschedule_request = array(
            'post_title'   => 'Reschedule Request: ' . $tutor_name . ' - ' . $student_name,
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'progress_report',
        );
        
        $post_id = wp_insert_post($new_reschedule_request, true);
        
        if (!is_wp_error($post_id)) {
            // Save the reschedule request details as post meta
            update_post_meta($post_id, 'tutor_name', $tutor_name);
            update_post_meta($post_id, 'student_id', $student_id);
            update_post_meta($post_id, 'request_type', 'reschedule');
            update_post_meta($post_id, 'original_date', $original_date);
            update_post_meta($post_id, 'original_time', $original_time);
            update_post_meta($post_id, 'new_date', $new_date);
            update_post_meta($post_id, 'new_time', $new_time);
            update_post_meta($post_id, 'reason', $reason);
            
            // Set a global message to display to the user
            global $submission_message;
            $submission_message = 'Your reschedule request has been successfully submitted.';
        } else {
            global $submission_message;
            $submission_message = 'Error: ' . $post_id->get_error_message();
        }
    }
}
add_action('init', 'process_tutor_dashboard_form');

function get_student_progress_reports( $user_id ) {
    $args = array(
        'post_type'      => 'progress_report',
        'orderby'        => 'meta_value',
        'meta_key'       => 'lesson_date',
        'order'          => 'DESC',
		'posts_per_page' => -1,  // Fetch all matching reports
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'student_id',
                'value'   => $user_id,
                'compare' => '=',
            ),
            array(
                'key'     => 'lesson_date',
                'value'   => date( 'Y-m-d' ),
                'compare' => '<=',
                'type'    => 'DATE'
            )
        )
    );

    $progress_reports = get_posts( $args );
    return $progress_reports;
}

function load_custom_wp_admin_style() {
    wp_enqueue_script( 'wp-tinymce' );
}
add_action( 'admin_enqueue_scripts', 'load_custom_wp_admin_style' );

// Custom login
function my_custom_login_logo_and_background() {
    echo '<style type="text/css">
        h1 a { 
            background-image:url(https://optimiselearning.com/wp-content/uploads/2020/12/optimiselearningDEC81.png) !important; 
            background-size: contain !important; 
            min-width: 300px !important; 
        }
        body.login { 
            background-color: #103351; 
        }
        body.login #nav a, 
        body.login #backtoblog a {
            color: #fcb31e !important;
        }
        .wp-core-ui .button-primary {
            background: #fcb31e;
            border-color: #fcb31e;
            text-shadow: none;
            box-shadow: none;
        }
        .wp-core-ui .button-primary.focus, 
        .wp-core-ui .button-primary.hover, 
        .wp-core-ui .button-primary:focus, 
        .wp-core-ui .button-primary:hover {
            background: #fcb31e;
            border-color: #fcb31e;
        }
        .login .message {
            border-left-color: #fcb31e;
        }
        .login form {
            border-color: #fcb31e;
        }
        input[type=text]:focus, input[type=password]:focus, input[type=email]:focus, input[type=url]:focus, input[type=number]:focus, input[type=tel]:focus, input[type=range]:focus, input[type=search]:focus, input[type=color]:focus, textarea:focus {
            border-color: #fcb31e !important;
            -webkit-box-shadow: 0 0 2px #fcb31e !important;
            box-shadow: 0 0 2px #fcb31e !important;
        }
        .login .button.wp-hide-pw .dashicons {
            color: #fcb31e;
        }
        .login input[type=checkbox]:checked:before {
            color: #fcb31e;
        }
    </style>';
}
add_action( 'login_head', 'my_custom_login_logo_and_background' );


/* Redirects based on user role */
/*
function my_custom_login_redirect( $redirect_to, $request, $user ) {
    // Is there a user to check?
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        // Check for admins
        if ( in_array( 'administrator', $user->roles ) ) {
            // Redirect them to the default place
            return $redirect_to;
        } else if ( in_array( 'student', $user->roles ) ) {
            return home_url( '/student-dashboard' );
        } else if ( in_array( 'tutor', $user->roles ) ) {
            return home_url( '/tutor-dashboard' );
        } else {
            return home_url();
        }
    } else {
        return $redirect_to;
    }
}
add_filter( 'login_redirect', 'my_custom_login_redirect', 10, 3 );

*/ 


function restrict_access_by_role() {
    global $current_user;
    $current_user_roles = $current_user->roles;
    $requested_url = $_SERVER['REQUEST_URI'];

    // Check if the user is trying to logout
    if ( strpos( $requested_url, 'access/?action=logout' ) !== false ) {
        return; // Don't do anything if the user is logging out
    }

    if ( in_array( 'student', $current_user_roles ) && strpos( $requested_url, '/student-dashboard' ) === false ) {
        wp_redirect( home_url( '/student-dashboard' ) );
        exit;
    } else if ( in_array( 'tutor', $current_user_roles ) && strpos( $requested_url, '/tutor-dashboard' ) === false ) {
        wp_redirect( home_url( '/tutor-dashboard' ) );
        exit;
    }
}
add_action( 'init', 'restrict_access_by_role' );




function my_custom_login_url( $url, $path, $scheme, $blog_id ) {
    if ( $path == 'wp-login.php' ) {
        $url = str_replace( 'wp-login.php', 'access', $url );
    }
    return $url;
}

function my_custom_login_redirect( $redirect_to, $request, $user ) {
    // Is there a user to check?
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        // Check for admins
        if ( in_array( 'administrator', $user->roles ) || in_array( 'editor', $user->roles )) {
            // Redirect them to the default place
            return $redirect_to;
        } else {
            return home_url();
        }
    } else {
        return $redirect_to;
    }
}
add_filter( 'login_redirect', 'my_custom_login_redirect', 10, 3 );

// Hide admin bar for non-admins
function hide_admin_bar_from_non_admins(){
    if (!current_user_can('administrator')) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'hide_admin_bar_from_non_admins');

// Restrict access to wp-admin for non-admins
function restrict_admin_with_redirect() {
    if (!current_user_can('administrator') && !current_user_can('editor') && (is_admin() || is_blog_admin())) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('admin_init', 'restrict_admin_with_redirect');

// Custom WordPress New User Registration Email
function custom_wp_new_user_notification_email($wp_new_user_notification_email, $user, $blogname) {
    // Manually generate a new user key
    $key = get_password_reset_key($user);

    if(is_wp_error($key)){
        // There was an error, handle it here
        return;
    }

    // Generate a password reset link
    $reset_link = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login');

    // Your custom message
    $message = "Hi ".$user->display_name.",

    <p>I am emailing to introduce you to our <strong>Optimise Learning Hub</strong>. Please see the key features of the hub below:</p>

    <ul>
    <li>The Optimise Learning Hub provides your parents with a convenient platform to access your Individual Learning Plan, which highlights your learning goals.</li>

    <li>Your parents will be able to log in to the Optimise Hub to read comments written by your tutor in regard to your focus, what you are learning and the academic progress you are making.</li>

    <li>You can log in to your online classroom directly from the Optimise Hub.</li>
	
	<li>In addition, your parents will be able to view your lesson schedule on the Optimise Hub.</li>
    </ul>

    <p>To access the Optimise Learning Hub, please follow these steps:</p>

    <ol>
    <li>Go to <a href='https://hub.optimiselearning.com'>hub.optimiselearning.com</a>.</li>
    <li>Use the following login details:<br>
    <strong>Username:</strong> ".$user->user_login."</li>
    <li><a href='$reset_link'>Click here</a> to set your password</li>
    </ol>

    <p><em>Please note that your login details are for your use only, and we request that you keep them confidential for security purposes.</em></p>
    <p>We hope you enjoy using the Optimise Learning Hub. If you or your parents have any feedback or suggestions in regard to the Hub, please do not hesitate to <a href=\"mailto:info@optimiselearning.com\">contact us</a>.</p>
    <p>Warm regards,<br>
	Tracey Hand | Co Founder<br>
	Optimise Learning</p>

    ";

    $wp_new_user_notification_email['message'] = $message;
    $wp_new_user_notification_email['subject'] = 'Accessing the Optimise Learning Hub';
    $wp_new_user_notification_email['headers'] = 'From: Tracey from Optimise Learning <info@optimiselearning.com>' . "\r\n" .
                                                 'Content-Type: text/html; charset=UTF-8';

    return $wp_new_user_notification_email;
}

add_filter('wp_new_user_notification_email', 'custom_wp_new_user_notification_email', 10, 3);


/* Meta Boxes for Custom Post Type */

// Add meta box to progress report post type
function add_progress_report_meta_box() {
    add_meta_box(
        'progress_report_meta_box', // Unique ID of the meta box
        'Lesson Overview Details',  // Title of the meta box
        'progress_report_meta_box_html',  // Callback function that outputs the HTML
        'progress_report',  // Post type to which to add the meta box
        'normal',  // Context in which to show the box ('normal', 'advanced', or 'side')
        'default'  // Priority within the context where the boxes should show ('default', 'core', 'high', or 'low')
    );
}
add_action('add_meta_boxes', 'add_progress_report_meta_box');

// Output the HTML for the meta box
function progress_report_meta_box_html($post) {
    $tutor_name = get_post_meta($post->ID, 'tutor_name', true);
    $lesson_date = get_post_meta($post->ID, 'lesson_date', true);
    $lesson_focus = get_post_meta($post->ID, 'lesson_focus', true);
    $content_covered = get_post_meta($post->ID, 'content_covered', true);
    $student_progress = get_post_meta($post->ID, 'student_progress', true);
    $next_focus = get_post_meta($post->ID, 'next_focus', true);
    $resources = get_post_meta($post->ID, 'resources', true);

    // Output your form fields here, using the above variables to set the current values
    // Note: You'll likely need to use esc_attr() to ensure the values are safe to output in HTML attributes

    // As an example, here's how you might output the tutor_name field:

    echo '<label for="tutor_name">Tutor Name: </label>';
    echo '<input type="text" id="tutor_name" name="tutor_name" value="' . esc_attr($tutor_name) . '">';
    
    echo '<br> <br>'; 

    echo '<label for="lesson_date">Lesson Date: </label>';
    echo '<input type="text" id="lesson_date" name="lesson_date" value="' . esc_attr($lesson_date) . '">';

    echo '<br> <br>'; 

    echo '<label for="lesson_focus" >Lesson Focus: </label>';
    echo '<br>';
    echo '<textarea id="lessson_focus" name="lesson_focus"  cols="50" rows="6">' . esc_textarea($lesson_focus) . '</textarea>';

    echo '<br> <br>'; 

    echo '<label for="content_covered">Content Covered During the Lesson: </label>';
    echo '<br>';
    echo '<textarea id="content_covered" name="content_covered"  cols="50" rows="6">' . esc_textarea($content_covered) . '</textarea>';
    
    echo '<br> <br>'; 

    echo '<label for="student_progress">Student Progress: </label>';
    echo '<br>';
    echo '<textarea id="student_progress" name="student_progress"  cols="50" rows="6">' . esc_textarea($student_progress) . '</textarea>';

    echo '<br> <br>'; 

    echo '<label for="next_focus">Focus for Next Lesson: </label>';
    echo '<br>';
    echo '<textarea id="next_focus" name="next_focus" cols="50" rows="6">' . esc_textarea($next_focus) . '</textarea>';

    echo '<br> <br>'; 

    echo '<label for="resources">Attached Resources: </label>';
    echo '<br>';
    echo '<input type="text" id="resources" name="resources" value="' . esc_attr($resources) . '">';
}


// Save the data from the meta box
function save_progress_report_meta_box_data($post_id) {
    if (array_key_exists('tutor_name', $_POST)) {
        update_post_meta($post_id, 'tutor_name', sanitize_text_field($_POST['tutor_name']));
    }
    if (array_key_exists('lesson_date', $_POST)) {
        update_post_meta($post_id, 'lesson_date', sanitize_text_field($_POST['lesson_date']));
    }
    if (array_key_exists('lesson_focus', $_POST)) {
        update_post_meta($post_id, 'lesson_focus', sanitize_text_field($_POST['lesson_focus']));
    }
	if (array_key_exists('content_covered', $_POST)) {
        update_post_meta($post_id, 'content_covered', sanitize_text_field($_POST['content_covered']));
    }
    if (array_key_exists('student_progress', $_POST)) {
        update_post_meta($post_id, 'student_progress', sanitize_text_field($_POST['student_progress']));
    }
    if (array_key_exists('next_focus', $_POST)) {
        update_post_meta($post_id, 'next_focus', sanitize_text_field($_POST['next_focus']));
    }

}
add_action('save_post', 'save_progress_report_meta_box_data');


// Notify admin of learning overview submission 
function email_admin_on_new_progress_report($new_status, $old_status, $post) {
    if('publish' === $new_status && 'publish' !== $old_status && $post->post_type === 'progress_report') {
        wp_schedule_single_event(time() + 1, 'send_email_notification', [$post->ID]);
    }
}
add_action('transition_post_status', 'email_admin_on_new_progress_report', 10, 3);

// Notify admin of learning overview submission 

function send_email_notification($post_id) {
    $tutor_name = get_post_meta($post_id, 'tutor_name', true);

    $subject = 'ACTION: New Tutor Lesson Overview Submission by ' . $tutor_name;
    $message = 'A new tutor lesson overview has been submitted by ' . $tutor_name . '. Please navigate to <a href="https://hub.optimiselearning.com/wp-admin/edit.php?post_type=progress_report">Lesson Overviews</a> on the Optimise Hub admin to review it.';
    $admin_email = get_option('admin_email'); 

    // Set mail content type to HTML
    $set_content_type = function( $content_type ) {
        return 'text/html';
    };
    add_filter( 'wp_mail_content_type', $set_content_type);
	
    wp_mail($admin_email, $subject, $message);
	
    // Reset content type to default (text/plain)
    remove_filter( 'wp_mail_content_type', $set_content_type );
}

add_action('send_email_notification', 'send_email_notification');

// Filter WP_Mail Function to Add Multiple Admin Emails


add_filter( 'wp_mail', 'my_custom_to_admin_emails' );

/**

* @param array $args A compacted array of wp_mail() arguments, including the "to" email,
*                    subject, message, headers, and attachments values.
*
* @return array
*/
function my_custom_to_admin_emails( $args ) {

    // This assumes that admin emails are sent with only the admin email
    // used in the to argument.
    if( is_array( $args['to'] ) ) return $args;

    $admin_email = get_option( 'admin_email' );

    // Check if admin email is in string, as plugins/themes could have changed format (ie. Administrator <admin@domain.com> )
    if( strpos( $args['to'], $admin_email ) !== FALSE ){

        // Create array in case there are multiple emails defined in CSV format
        $emails = explode( ',', $args['to'] );
        
        /* Add any additional emails to the array */
 

      // remove notifications // $emails[] = 'tom@optmatic.com';
	  // remove notifications // $emails[] = 'peter@optimiselearning.com'; 
	 //  remove notifications // $emails[] = 'tracey@optimiselearning.com';
		
		$emails[] = 'roxanne@optimiselearning.com';

        $args['to'] = $emails;
    }

    return $args;
}

// // AJAX handler for tutor requests
// add_action('wp_ajax_handle_tutor_request', 'handle_tutor_request_ajax');
// function handle_tutor_request_ajax() {
//     check_ajax_referer('tutor_request_ajax_nonce', 'security');
    
//     if (!isset($_POST['request_action']) || !isset($_POST['request_id'])) {
//         wp_send_json_error('Missing required fields');
//     }
    
//     $request_id = intval($_POST['request_id']);
//     $action = sanitize_text_field($_POST['request_action']);
    
//     if ($action === 'confirm') {
//         update_post_meta($request_id, 'status', 'confirmed');
//         wp_send_json_success();
//     } elseif ($action === 'decline') {
//         if (empty($_POST['decline_reason'])) {
//             wp_send_json_error('Decline reason is required');
//         }
//         $reason = sanitize_text_field($_POST['decline_reason']);
//         update_post_meta($request_id, 'status', 'declined');
//         update_post_meta($request_id, 'reason', $reason);
//         wp_send_json_success();
//     } else {
//         wp_send_json_error('Invalid action');
//     }
// }

// // wp_localize_script('jquery', 'tutorRequestsData', array(
// //     'nonce' => wp_create_nonce('tutor_request_action')
// // ));

// Include the custom request handler
// require_once get_stylesheet_directory() . '/tutor-request-handler.php';

// Add custom scripts for handling tutor requests
/* COMMENTING OUT - This inline script conflicts with enqueued tutor-requests-scripts.js
add_action('wp_footer', 'add_custom_tutor_request_scripts');

function add_custom_tutor_request_scripts() {
    if (!is_page('tutor-dashboard')) return;
    
    // Localize the script with new data
    wp_localize_script('jquery', 'customTutorData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('custom_tutor_request_action')
    ));
    
    ?>
    <script>
    console.log('Custom tutor request scripts loaded');
    
    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing custom handlers');
        
        // Replace all decline buttons with our custom implementation
        replaceDeclineButtons();
        
        // Add mutation observer to handle dynamically added elements
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    replaceDeclineButtons();
                }
            });
        });
        
        observer.observe(document.body, { childList: true, subtree: true });
    });
    
    function replaceDeclineButtons() {
        // Find all decline buttons
        const declineButtons = document.querySelectorAll('button.btn-danger, button:contains("Decline")');
        console.log('Found decline buttons:', declineButtons.length);
        
        declineButtons.forEach(function(button, index) {
            // Skip if already processed
            if (button.hasAttribute('data-custom-processed')) {
                return;
            }
            
            console.log('Processing button:', button);
            
            // Mark as processed
            button.setAttribute('data-custom-processed', 'true');
            
            // Find the request ID
            let requestId = button.getAttribute('data-request-id');
            if (!requestId) {
                // Try to find it from a parent form
                const parentForm = button.closest('form');
                if (parentForm) {
                    const requestIdInput = parentForm.querySelector('input[name="request_id"]');
                    if (requestIdInput) {
                        requestId = requestIdInput.value;
                    }
                }
            }
            
            if (!requestId) {
                // Generate a fallback ID based on position
                requestId = 'unknown-' + index;
            }
            
            console.log('Request ID for button:', requestId);
            
            // Find or create reason input
            let reasonInput = null;
            const parentContainer = button.parentNode;
            
            // Look for existing input
            reasonInput = parentContainer.querySelector('input[type="text"]');
            
            if (!reasonInput) {
                // Create a new input if none exists
                reasonInput = document.createElement('input');
                reasonInput.type = 'text';
                reasonInput.className = 'form-control form-control-sm d-inline-block custom-reason-input';
                reasonInput.style.width = '150px';
                reasonInput.placeholder = 'Reason';
                reasonInput.id = 'custom-reason-' + requestId;
                
                // Insert before the button
                parentContainer.insertBefore(reasonInput, button);
            }
            
            // Create error message container
            const errorContainer = document.createElement('div');
            errorContainer.className = 'text-danger small mt-1 custom-error-message';
            errorContainer.style.display = 'none';
            errorContainer.textContent = 'Please provide a reason for declining';
            errorContainer.id = 'custom-error-' + requestId;
            
            // Add after the button
            if (button.nextSibling) {
                parentContainer.insertBefore(errorContainer, button.nextSibling);
            } else {
                parentContainer.appendChild(errorContainer);
            }
            
            // Replace the button's click handler
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Custom decline button clicked');
                
                // Get the reason
                const reason = reasonInput.value.trim();
                console.log('Reason:', reason);
                
                // Validate reason
                if (!reason) {
                    console.log('No reason provided, showing error');
                    errorContainer.style.display = 'block';
                    reasonInput.focus();
                    return;
                }
                
                // Hide error if shown
                errorContainer.style.display = 'none';
                
                // Show loading state
                button.disabled = true;
                button.textContent = 'Processing...';
                
                // Send AJAX request
                jQuery.ajax({
                    url: customTutorData.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'custom_decline_request',
                        request_id: requestId,
                        reason: reason,
                        security: customTutorData.security
                    },
                    success: function(response) {
                        console.log('AJAX response:', response);
                        
                        if (response.success) {
                            console.log('Request declined successfully');
                            
                            // Update UI
                            const row = button.closest('tr');
                            if (row) {
                                // Update status cell
                                const statusCell = row.querySelector('td:nth-child(4)');
                                if (statusCell) {
                                    statusCell.innerHTML = '<span class="badge bg-danger">Declined</span>';
                                }
                                
                                // Update actions cell
                                const actionsCell = row.querySelector('td:nth-child(5)');
                                if (actionsCell) {
                                    actionsCell.innerHTML = 'Reason: ' + reason;
                                }
                            } else {
                                // Fallback: reload the page
                                window.location.reload();
                            }
                        } else {
                            console.error('Error:', response.data.message);
                            alert('Error: ' + response.data.message);
                            
                            // Reset button
                            button.disabled = false;
                            button.textContent = 'Decline';
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', status, error);
                        alert('An error occurred. Please try again.');
                        
                        // Reset button
                        button.disabled = false;
                        button.textContent = 'Decline';
                    }
                });
            });
            
            // Add input handler to hide error when typing
            reasonInput.addEventListener('input', function() {
                if (this.value.trim()) {
                    errorContainer.style.display = 'none';
                }
            });
        });
    }
    </script>
    
    <style>
    .custom-error-message {
        font-size: 12px;
        margin-top: 5px;
    }
    </style>
    <?php
}
*/

// Add custom JavaScript to override the browser alert/prompt functions
add_action('wp_head', function() {
    if (!is_page('tutor-dashboard')) return;
    ?>
    <script>
    // Override the browser's alert, prompt, and confirm functions
    (function() {
        // Store original functions
        const originalAlert = window.alert;
        const originalPrompt = window.prompt;
        const originalConfirm = window.confirm;
        
        // Override alert function
        window.alert = function(message) {
            console.log('Alert intercepted:', message);
            // If it's the decline reason prompt, don't show it
            if (message && message.includes('reason for declining')) {
                console.log('Decline reason alert suppressed');
                return;
            }
            // Otherwise, use the original alert
            return originalAlert.apply(this, arguments);
        };
        
        // Override prompt function
        window.prompt = function(message, defaultValue) {
            console.log('Prompt intercepted:', message);
            // If it's the decline reason prompt, return a default reason
            if (message && message.includes('reason for declining')) {
                console.log('Decline reason prompt intercepted, returning default reason');
                return "No specific reason provided";
            }
            // Otherwise, use the original prompt
            return originalPrompt.apply(this, arguments);
        };
        
        // Override confirm function
        window.confirm = function(message) {
            console.log('Confirm intercepted:', message);
            // Always return true for confirms
            return true;
        };
        
        console.log('Browser dialog functions overridden');
    })();
    </script>
    <?php
});

// Add a function to migrate old classroom_url to the new fields
function migrate_old_classroom_url() {
    // Only run for logged-in tutors
    if (!is_user_logged_in() || !current_user_can('tutor')) {
        return;
    }
    
    $user_id = get_current_user_id();
    $old_url = get_user_meta($user_id, 'classroom_url', true);
    
    // If there's an old URL and no new URLs set, migrate it
    if (!empty($old_url)) {
        $math_classroom = get_field('mathematics_classroom', 'user_' . $user_id);
        
        if (empty($math_classroom)) {
            update_field('mathematics_classroom', $old_url, 'user_' . $user_id);
            // Optionally, you can delete the old field after migration
            // delete_user_meta($user_id, 'classroom_url');
        }
    }
}
add_action('wp', 'migrate_old_classroom_url');

/**
 * AJAX handler to check for incoming requests for students
 */
function check_student_requests_ajax() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'check_student_requests_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    // Get student ID
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    if (!$student_id) {
        wp_send_json_error('Invalid student ID');
        return;
    }

    // Count incoming reschedule requests (tutor-initiated)
    $tutor_requests_count = count(get_posts(array(
        'post_type'      => 'progress_report',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'student_id',
                'value'   => $student_id,
                'compare' => '=',
            ),
            array(
                'key'     => 'request_type',
                'value'   => 'tutor_reschedule',
                'compare' => '=',
            ),
            array(
                'key'     => 'status',
                'value'   => 'pending',
                'compare' => '=',
            ),
            array(
                'key'     => 'viewed_by_student',
                'compare' => 'NOT EXISTS',
            )
        ),
        'fields'         => 'ids'
    )));

    // Count status changes on outgoing requests
    $status_changes_count = count(get_posts(array(
        'post_type'      => 'progress_report',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'student_id',
                'value'   => $student_id,
                'compare' => '=',
            ),
            array(
                'key'     => 'request_type',
                'value'   => array('tutor_unavailable', 'reschedule_confirmed'),
                'compare' => 'IN',
            ),
            array(
                'key'     => 'viewed_by_student',
                'compare' => 'NOT EXISTS',
            )
        ),
        'fields'         => 'ids'
    )));

    // Total count
    $total_count = $tutor_requests_count + $status_changes_count;
    
    wp_send_json_success(['count' => $total_count]);
    exit;
}
add_action('wp_ajax_check_student_requests', 'check_student_requests_ajax');

/**
 * AJAX handler to mark student requests as read
 */
function mark_student_requests_read_ajax() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mark_student_requests_read_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    // Get student ID
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    if (!$student_id) {
        wp_send_json_error('Invalid student ID');
        return;
    }

    // Mark tutor-initiated requests as viewed
    $tutor_requests = get_posts(array(
        'post_type'      => 'progress_report',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'student_id',
                'value'   => $student_id,
                'compare' => '=',
            ),
            array(
                'key'     => 'request_type',
                'value'   => 'tutor_reschedule',
                'compare' => '=',
            ),
            array(
                'key'     => 'status',
                'value'   => 'pending',
                'compare' => '=',
            ),
            array(
                'key'     => 'viewed_by_student',
                'compare' => 'NOT EXISTS',
            )
        ),
        'fields'         => 'ids'
    ));

    foreach ($tutor_requests as $request_id) {
        update_post_meta($request_id, 'viewed_by_student', '1');
    }

    // Mark status changes as viewed
    $status_changes = get_posts(array(
        'post_type'      => 'progress_report',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'student_id',
                'value'   => $student_id,
                'compare' => '=',
            ),
            array(
                'key'     => 'request_type',
                'value'   => array('tutor_unavailable', 'reschedule_confirmed'),
                'compare' => 'IN',
            ),
            array(
                'key'     => 'viewed_by_student',
                'compare' => 'NOT EXISTS',
            )
        ),
        'fields'         => 'ids'
    ));

    foreach ($status_changes as $request_id) {
        update_post_meta($request_id, 'viewed_by_student', '1');
    }
    
    wp_send_json_success(['marked' => count($tutor_requests) + count($status_changes)]);
    exit;
}
add_action('wp_ajax_mark_student_requests_read', 'mark_student_requests_read_ajax');

/**
 * Update notification flags when a tutor creates a reschedule request for a student
 */
function set_notification_on_tutor_reschedule($request_id) {
    // Only proceed if this is a tutor-initiated reschedule
    $request_type = get_post_meta($request_id, 'request_type', true);
    if ($request_type === 'tutor_reschedule') {
        // The request doesn't need any marking - the absence of viewed_by_student
        // will be used to determine if it's a new notification
    }
}
add_action('wp_insert_post', 'set_notification_on_tutor_reschedule', 10, 1);

/**
 * Update notification flags when a tutor responds to a student request
 */
function set_notification_on_request_status_change($meta_id, $object_id, $meta_key, $meta_value) {
    // Check if this is a status update
    if ($meta_key === 'status') {
        // Get the request type
        $request_type = get_post_meta($object_id, 'request_type', true);
        
        // Only proceed for student-initiated requests
        if ($request_type === 'student_reschedule') {
            // The student_id is already in the meta, so no need to add it
            // Just ensure viewed_by_student is not set
            delete_post_meta($object_id, 'viewed_by_student');
        }
    }
}
add_action('updated_post_meta', 'set_notification_on_request_status_change', 10, 4);

/**
 * Helper function to get upcoming lessons for a student
 */
function get_upcoming_lessons_for_student($student_id) {
    // This is a placeholder. In a real implementation, this would query
    // your lesson scheduling system to get actual upcoming lessons.
    // For now, we'll return some dummy data.
    
    $upcoming_lessons = array();
    
    // Get lessons from your custom database or post type
    // This is just a placeholder example
    $today = date('Y-m-d');
    $lessons = get_posts(array(
        'post_type'      => 'lesson',
        'posts_per_page' => 10,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'student_id',
                'value'   => $student_id,
                'compare' => '=',
            ),
            array(
                'key'     => 'lesson_date',
                'value'   => $today,
                'compare' => '>=',
            )
        ),
        'order'          => 'ASC',
        'orderby'        => 'date'
    ));
    
    foreach ($lessons as $lesson) {
        $upcoming_lessons[] = array(
            'date' => get_post_meta($lesson->ID, 'lesson_date', true),
            'time' => get_post_meta($lesson->ID, 'lesson_time', true),
            'tutor_name' => get_post_meta($lesson->ID, 'tutor_name', true),
            'date_formatted' => get_the_date('M j, Y', $lesson)
        );
    }
    
    return $upcoming_lessons;
}

// Add to functions.php to ensure reschedule requests don't show on the Home tab
add_action('wp_ajax_delete_tutor_request', 'handle_delete_tutor_request');

function handle_delete_tutor_request() {
    if (isset($_POST['delete_tutor_request']) && $_POST['delete_tutor_request'] === '1') {
        $request_id = intval($_POST['request_id']);
        
        // Verify the request belongs to the current tutor
        $tutor_id = get_post_meta($request_id, 'tutor_id', true);
        if ($tutor_id == get_current_user_id()) {
            $result = wp_delete_post($request_id, true);
            if ($result) {
                wp_send_json_success(array('message' => 'Request deleted successfully'));
            } else {
                wp_send_json_error(array('message' => 'Failed to delete the request'));
            }
        } else {
            wp_send_json_error(array('message' => 'You do not have permission to delete this request'));
        }
    }
    wp_die();
}

// Add this function to ensure reschedule requests only show on the Requests tab
function should_display_reschedule_requests() {
    $current_tab = isset($_GET['active_tab']) ? $_GET['active_tab'] : '';
    return $current_tab === 'requests';
}

/**
 * Function to completely hide reschedule requests on the home tab
 */
function hide_reschedule_on_home_tab() {
    // Only add this script on the tutor dashboard page
    if (!is_page('tutor-dashboard') && !is_page('dashboard')) {
        return;
    }
    
    ?>
    <style>
        /* Hide reschedule requests on the home tab */
        body.tab-home .reschedule-container,
        #home h2:contains("Reschedule Requests"),
        #home .reschedule-container,
        #home + .reschedule-container {
            display: none !important;
        }
        
        /* Only show reschedule content when the Requests tab is active */
        .reschedule-container {
            display: none;
        }
        
        body.tab-requests .reschedule-container {
            display: block;
        }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Simple function to check if we're on the home tab
        function isHomeTabActive() {
            const activeTab = document.querySelector('.nav-link.active');
            return !activeTab || activeTab.getAttribute('href') === '#home' || activeTab.id === 'home-tab';
        }
        
        // Function to hide reschedule sections on home tab
        function hideRescheduleOnHomeTab() {
            if (isHomeTabActive()) {
                // Find the reschedule heading and hide everything until the next section
                const rescheduleHeading = Array.from(document.querySelectorAll('h2')).find(h => 
                    h.textContent.trim() === 'Reschedule Requests'
                );
                
                if (rescheduleHeading) {
                    // Hide the heading
                    rescheduleHeading.style.display = 'none';
                    
                    // Hide all siblings until the next h2
                    let currentElement = rescheduleHeading.nextElementSibling;
                    while (currentElement && currentElement.tagName !== 'H2') {
                        currentElement.style.display = 'none';
                        currentElement = currentElement.nextElementSibling;
                    }
                }
            }
        }
        
        // Initial call on page load
        hideRescheduleOnHomeTab();
        
        // Set up tab change listeners
        const tabLinks = document.querySelectorAll('a[data-bs-toggle="tab"]');
        tabLinks.forEach(function(tab) {
            tab.addEventListener('shown.bs.tab', function(event) {
                const isHomeTab = event.target.getAttribute('href') === '#home';
                
                // Get the reschedule heading
                const rescheduleHeading = Array.from(document.querySelectorAll('h2')).find(h => 
                    h.textContent.trim() === 'Reschedule Requests'
                );
                
                if (rescheduleHeading) {
                    // Show/hide based on which tab we're on
                    const displayStyle = isHomeTab ? 'none' : 'block';
                    rescheduleHeading.style.display = displayStyle;
                    
                    // Show/hide all elements until the next h2
                    let currentElement = rescheduleHeading.nextElementSibling;
                    while (currentElement && currentElement.tagName !== 'H2') {
                        currentElement.style.display = displayStyle;
                        currentElement = currentElement.nextElementSibling;
                    }
                }
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'hide_reschedule_on_home_tab');

/**
 * Add classes to reschedule sections for easier targeting
 */
function mark_reschedule_sections($content) {
    // Add container class to reschedule sections
    $content = preg_replace(
        '/<h2[^>]*>Reschedule Requests<\/h2>/',
        '<h2 class="reschedule-heading">Reschedule Requests</h2><div class="reschedule-container">',
        $content
    );
    
    // Find closing tags and add closing div
    $content = str_replace(
        '<h2 class="reschedule-heading">Reschedule Requests</h2><div class="reschedule-container">',
        '</div><!-- End previous section --><h2 class="reschedule-heading">Reschedule Requests</h2><div class="reschedule-container">',
        $content
    );
    
    // Add closing div at the end if needed
    if (strpos($content, 'reschedule-container') !== false && 
        substr_count($content, 'reschedule-container') > substr_count($content, 'End previous section')) {
        $content .= '</div><!-- End reschedule container -->';
    }
    
    return $content;
}
add_filter('the_content', 'mark_reschedule_sections', 999);

/**
 * Add a simple JavaScript fix to move reschedule requests to the requests tab only
 */
function fix_reschedule_requests_tab() {
    // Only add this script on the tutor dashboard page
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Simple function to check if we're on the home tab
        function isHomeTabActive() {
            const activeTab = document.querySelector('.nav-link.active');
            return !activeTab || activeTab.getAttribute('href') === '#home' || activeTab.id === 'home-tab';
        }
        
        // Function to hide reschedule sections on home tab
        function hideRescheduleOnHomeTab() {
            if (isHomeTabActive()) {
                // Find the reschedule heading and hide everything until the next section
                const rescheduleHeading = Array.from(document.querySelectorAll('h2')).find(h => 
                    h.textContent.trim() === 'Reschedule Requests'
                );
                
                if (rescheduleHeading) {
                    // Hide the heading
                    rescheduleHeading.style.display = 'none';
                    
                    // Hide all siblings until the next h2
                    let currentElement = rescheduleHeading.nextElementSibling;
                    while (currentElement && currentElement.tagName !== 'H2') {
                        currentElement.style.display = 'none';
                        currentElement = currentElement.nextElementSibling;
                    }
                }
            }
        }
        
        // Initial call on page load
        hideRescheduleOnHomeTab();
        
        // Set up tab change listeners
        const tabLinks = document.querySelectorAll('a[data-bs-toggle="tab"]');
        tabLinks.forEach(function(tab) {
            tab.addEventListener('shown.bs.tab', function(event) {
                const isHomeTab = event.target.getAttribute('href') === '#home';
                
                // Get the reschedule heading
                const rescheduleHeading = Array.from(document.querySelectorAll('h2')).find(h => 
                    h.textContent.trim() === 'Reschedule Requests'
                );
                
                if (rescheduleHeading) {
                    // Show/hide based on which tab we're on
                    const displayStyle = isHomeTab ? 'none' : 'block';
                    rescheduleHeading.style.display = displayStyle;
                    
                    // Show/hide all elements until the next h2
                    let currentElement = rescheduleHeading.nextElementSibling;
                    while (currentElement && currentElement.tagName !== 'H2') {
                        currentElement.style.display = displayStyle;
                        currentElement = currentElement.nextElementSibling;
                    }
                }
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'fix_reschedule_requests_tab');

/**
 * Function to determine if reschedule content should be displayed
 * 
 * @return bool True if we should show reschedule content
 */
function should_show_reschedule_content() {
    // Check if we're on the requests tab
    $active_tab = isset($_GET['active_tab']) ? $_GET['active_tab'] : '';
    $stored_tab = isset($_COOKIE['activeTutorTab']) ? str_replace('#', '', $_COOKIE['activeTutorTab']) : '';
    
    // Either the URL parameter or the stored tab cookie indicates we're on the requests tab
    return $active_tab === 'requests' || $stored_tab === 'requests';
}

/**
 * Filter that wraps reschedule content in a conditional
 */
function wrap_reschedule_content($content) {
    if (strpos($content, '<h2>Reschedule Requests</h2>') !== false || 
        strpos($content, '<h4>Reschedule Requests</h4>') !== false) {
        
        // Replace with conditional
        $content = preg_replace(
            '/<(h[24])>Reschedule Requests<\/\1>/',
            '<?php if (function_exists("should_show_reschedule_content") && should_show_reschedule_content()): ?>' .
            '<$1>Reschedule Requests</$1>',
            $content
        );
        
        // Add closing conditional
        $content .= '<?php endif; ?>';
    }
    
    return $content;
}
add_filter('the_content', 'wrap_reschedule_content', 999);

// AJAX handler for getting student lessons
add_action('wp_ajax_get_student_lessons', 'get_student_lessons_ajax');
function get_student_lessons_ajax() {
    if (!isset($_POST['student_id'])) {
        wp_send_json_error('No student ID provided');
        return;
    }

    $student_id = intval($_POST['student_id']);
    $now = new DateTime('now', new DateTimeZone('Australia/Brisbane'));
    $lesson_schedule = get_user_meta($student_id, 'lesson_schedule_list', true);
    $options_html = '<option value="">--Select a scheduled lesson--</option>';

    if (!empty($lesson_schedule)) {
        $lessons = explode("\n", $lesson_schedule);
        $upcoming_lessons = [];
        
        foreach ($lessons as $lesson) {
            if (!empty(trim($lesson)) && preg_match('/on ([A-Za-z]+) (\d+) ([A-Za-z]+) (\d{4}) at (\d{2}:\d{2})/', $lesson, $matches)) {
                $date_string = $matches[1] . ' ' . $matches[2] . ' ' . $matches[3] . ' ' . $matches[4] . ' ' . $matches[5];
                $lesson_date = DateTime::createFromFormat('l d F Y H:i', $date_string, new DateTimeZone('Australia/Brisbane'));
                
                if ($lesson_date > $now) {
                    $subject = 'Lesson';
                    if (stripos($lesson, 'mathematics') !== false) $subject = 'Mathematics';
                    elseif (stripos($lesson, 'english') !== false) $subject = 'English';
                    elseif (stripos($lesson, 'chemistry') !== false) $subject = 'Chemistry';
                    elseif (stripos($lesson, 'physics') !== false) $subject = 'Physics';
                    
                    $upcoming_lessons[] = [
                        'date' => $lesson_date,
                        'formatted' => $lesson_date->format('l, jS \of F Y \a\t g:i A'),
                        'subject' => $subject,
                        'date_value' => $lesson_date->format('Y-m-d'),
                        'time_value' => $lesson_date->format('H:i:s')
                    ];
                }
            }
        }
        
        usort($upcoming_lessons, function($a, $b) {
            return $a['date']->getTimestamp() - $b['date']->getTimestamp();
        });
        
        foreach ($upcoming_lessons as $lesson) {
            $options_html .= '<option value="' . $lesson['date_value'] . '|' . $lesson['time_value'] . '">' 
                . $lesson['subject'] . ' - ' . $lesson['formatted'] . '</option>';
        }
    }

    wp_send_json_success($options_html);
}

/**
 * Renders HTML input fields for preferred alternative dates and times.
 *
 * @param string $id_prefix Optional prefix for element IDs (e.g., 'edit_').
 * @param int    $count     Number of date/time pairs to render.
 * @param bool   $firstRequired Whether the first pair should have the 'required' attribute.
 */
function render_preferred_time_inputs(string $id_prefix = '', int $count = 3, bool $firstRequired = true) {
    for ($i = 1; $i <= $count; $i++): ?>
        <div class="preferred-time-row mb-2">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label small">Preferred Date <?php echo $i; ?>:</label>
                    <input type="date" class="form-control preferred-date" 
                           name="preferred_date_<?php echo $i; ?>" 
                           id="<?php echo !empty($id_prefix) ? esc_attr($id_prefix) : ''; ?>preferred_date_<?php echo $i; ?>" 
                           <?php echo ($i == 1 && $firstRequired) ? 'required' : ''; ?>>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Preferred Time <?php echo $i; ?>:</label>
                    <input type="time" class="form-control preferred-time" 
                           name="preferred_time_<?php echo $i; ?>" 
                           id="<?php echo !empty($id_prefix) ? esc_attr($id_prefix) : ''; ?>preferred_time_<?php echo $i; ?>" 
                           <?php echo ($i == 1 && $firstRequired) ? 'required' : ''; ?>>
                </div>
            </div>
        </div>
    <?php endfor;
}

add_filter('the_content', 'wrap_reschedule_content', 999);
// AJAX handler for submitting tutor reschedule request
add_action('wp_ajax_submit_tutor_reschedule', 'handle_tutor_reschedule_ajax');
function handle_tutor_reschedule_ajax() {
    error_log('[AJAX handle_tutor_reschedule] Handler started.');

    // Verify Nonce
    if (!isset($_POST['tutor_reschedule_nonce']) || !wp_verify_nonce($_POST['tutor_reschedule_nonce'], 'tutor_reschedule_request_action')) {
        error_log('[AJAX handle_tutor_reschedule] Nonce verification FAILED.'); 
        wp_send_json_error(['message' => 'Nonce verification failed.'], 403);
        return; 
    }
    error_log('[AJAX handle_tutor_reschedule] Nonce verification PASSED.');

    // Check user permissions (ensure user is a tutor)
    if (!current_user_can('tutor')) {
         error_log('[AJAX handle_tutor_reschedule] Permission check FAILED.');
         wp_send_json_error(['message' => 'Permission denied.'], 403);
        return; 
    }
    error_log('[AJAX handle_tutor_reschedule] Permission check PASSED.');
    
    // Get and sanitize form data
    $tutor_id = get_current_user_id();
    $tutor_name = wp_get_current_user()->user_login;
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    $student_name = ''; // Will be looked up
    $original_date = isset($_POST['original_date']) ? sanitize_text_field($_POST['original_date']) : '';
    $original_time = isset($_POST['original_time']) ? sanitize_text_field($_POST['original_time']) : '';
    $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

    error_log('[AJAX handle_tutor_reschedule] Received Data: ' . print_r($_POST, true)); 

    // Basic Validation
    if (empty($student_id) || empty($original_date) || empty($original_time) || empty($reason)) {
        error_log('[AJAX handle_tutor_reschedule] Basic validation FAILED.');
        wp_send_json_error(['message' => 'Missing required fields (Student, Lesson Date/Time, Reason).'], 400);
        return; 
    }
    error_log('[AJAX handle_tutor_reschedule] Basic validation PASSED.');

    // Add student name lookup 
    $student_user = get_userdata($student_id);
    if (!$student_user) {
        error_log('[AJAX handle_tutor_reschedule] Failed to get student user data for ID: ' . $student_id); 
        wp_send_json_error(['message' => 'Invalid student selected.'], 400);
        return;
    }
    $student_name = $student_user->user_login; 
    error_log('[AJAX handle_tutor_reschedule] Found student name: ' . $student_name); 

    // Collect preferred times
    $preferred_times = [];
    for ($i = 1; $i <= 3; $i++) {
        $date = isset($_POST['preferred_date_' . $i]) ? sanitize_text_field($_POST['preferred_date_' . $i]) : '';
        $time = isset($_POST['preferred_time_' . $i]) ? sanitize_text_field($_POST['preferred_time_' . $i]) : '';
        
        if (!empty($date) && !empty($time)) {
            $preferred_times[] = ['date' => $date, 'time' => $time];
        }
    }
    error_log('[AJAX handle_tutor_reschedule] Preferred Times: ' . print_r($preferred_times, true)); 
    
    // Require at least one preferred time
     if (empty($preferred_times)) {
        error_log('[AJAX handle_tutor_reschedule] Preferred times validation FAILED.'); 
        wp_send_json_error(['message' => 'Please provide at least one preferred alternative time.'], 400);
        return; 
    }
    error_log('[AJAX handle_tutor_reschedule] Preferred times validation PASSED.');

    // Create the request post
    $request = [
        'post_title'   => 'Tutor Reschedule Request: ' . $tutor_name . ' for ' . $student_name,
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'progress_report', 
    ];
    
    error_log('[AJAX handle_tutor_reschedule] Attempting wp_insert_post with data: ' . print_r($request, true)); 
    $request_id = wp_insert_post($request);
    
    if (is_wp_error($request_id) || $request_id === 0) { 
         $error_message = is_wp_error($request_id) ? $request_id->get_error_message() : 'wp_insert_post returned 0';
         error_log('[AJAX handle_tutor_reschedule] wp_insert_post FAILED: ' . $error_message); 
         wp_send_json_error(['message' => 'Error creating request post: ' . $error_message], 500);
         return; 
    }
    error_log('[AJAX handle_tutor_reschedule] wp_insert_post SUCCEEDED. New post ID: ' . $request_id);

    // Save meta data
    update_post_meta($request_id, 'request_type', 'tutor_reschedule');
    update_post_meta($request_id, 'tutor_id', $tutor_id);
    update_post_meta($request_id, 'tutor_name', $tutor_name);
    update_post_meta($request_id, 'student_id', $student_id);
    update_post_meta($request_id, 'student_name', $student_name); 
    update_post_meta($request_id, 'original_date', $original_date);
    update_post_meta($request_id, 'original_time', $original_time);
    update_post_meta($request_id, 'reason', $reason);
    update_post_meta($request_id, 'preferred_times', $preferred_times); 
    update_post_meta($request_id, 'status', 'pending'); 
    error_log('[AJAX handle_tutor_reschedule] Meta data update calls finished for post ID: ' . $request_id); 

    error_log('[AJAX handle_tutor_reschedule] Attempting wp_send_json_success.'); 
    wp_send_json_success(['message' => 'Reschedule request submitted successfully.', 'request_id' => $request_id]);
}

// AJAX handler for getting student lessons

// require_once get_stylesheet_directory() . '/inc/acf-fields.php';

// Function to process tutor reschedule request via POST
function process_tutor_reschedule_request_post() {
    global $tutor_reschedule_feedback; // Use a global variable for feedback

    // Check if our specific form was submitted
    if (isset($_POST['submit_tutor_reschedule_post'])) {
        error_log('[Tutor Reschedule POST] Form submitted.'); // LOGGING

        // Verify Nonce
        if (!isset($_POST['tutor_reschedule_post_nonce']) || !wp_verify_nonce($_POST['tutor_reschedule_post_nonce'], 'tutor_reschedule_request_post_action')) {
            error_log('[Tutor Reschedule POST] Nonce verification failed.'); // LOGGING
            $tutor_reschedule_feedback = ['type' => 'danger', 'message' => 'Security check failed. Please try again.'];
            return;
        }
        error_log('[Tutor Reschedule POST] Nonce verified.'); // LOGGING

        // Check user permissions (ensure user is a tutor)
        if (!current_user_can('tutor')) {
            error_log('[Tutor Reschedule POST] Permission denied for user ID: ' . get_current_user_id()); // LOGGING
            $tutor_reschedule_feedback = ['type' => 'danger', 'message' => 'Permission denied.'];
            return;
        }
        error_log('[Tutor Reschedule POST] Permissions check passed.'); // LOGGING
        
        // Get and sanitize form data (same as AJAX handler)
        $tutor_id = get_current_user_id();
        $tutor_name = wp_get_current_user()->user_login;
        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
        $student_name = isset($_POST['student_name']) ? sanitize_text_field($_POST['student_name']) : '';
        $original_date = isset($_POST['original_date']) ? sanitize_text_field($_POST['original_date']) : '';
        $original_time = isset($_POST['original_time']) ? sanitize_text_field($_POST['original_time']) : '';
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

        error_log('[Tutor Reschedule POST] Received Data: ' . print_r($_POST, true)); // LOGGING POST DATA

        // Basic Validation
        if (empty($student_id) || empty($original_date) || empty($original_time) || empty($reason)) {
            error_log('[Tutor Reschedule POST] Validation failed - missing required fields.'); // LOGGING
            $tutor_reschedule_feedback = ['type' => 'danger', 'message' => 'Missing required fields (Student, Lesson Date/Time, Reason).'];
            return;
        }
        error_log('[Tutor Reschedule POST] Basic validation passed.'); // LOGGING

        // Collect preferred times
        $preferred_times = [];
        for ($i = 1; $i <= 3; $i++) {
            $date = isset($_POST['preferred_date_' . $i]) ? sanitize_text_field($_POST['preferred_date_' . $i]) : '';
            $time = isset($_POST['preferred_time_' . $i]) ? sanitize_text_field($_POST['preferred_time_' . $i]) : '';
            
            if (!empty($date) && !empty($time)) {
                $preferred_times[] = ['date' => $date, 'time' => $time];
            }
        }
        error_log('[Tutor Reschedule POST] Preferred Times: ' . print_r($preferred_times, true)); // LOGGING
        
        // Require at least one preferred time
         if (empty($preferred_times)) {
            error_log('[Tutor Reschedule POST] Validation failed - no preferred times provided.'); // LOGGING
            $tutor_reschedule_feedback = ['type' => 'danger', 'message' => 'Please provide at least one preferred alternative time.'];
            return;
        }
        error_log('[Tutor Reschedule POST] Preferred times validation passed.'); // LOGGING

        // Create the request post
        $request = [
            'post_title'   => 'Tutor Reschedule Request: ' . $tutor_name . ' for ' . $student_name,
            'post_content' => '', // Content is not used, meta fields store details
            'post_status'  => 'publish',
            'post_type'    => 'progress_report',
        ];
        
        error_log('[Tutor Reschedule POST] Attempting wp_insert_post with data: ' . print_r($request, true)); // LOGGING
        $request_id = wp_insert_post($request);
        
        if (is_wp_error($request_id)) {
             error_log('[Tutor Reschedule POST] wp_insert_post failed: ' . $request_id->get_error_message()); // LOGGING
             $tutor_reschedule_feedback = ['type' => 'danger', 'message' => 'Error creating request: ' . $request_id->get_error_message()];
             return;
        }
        error_log('[Tutor Reschedule POST] wp_insert_post successful. New post ID: ' . $request_id); // LOGGING

        // Save meta data
        update_post_meta($request_id, 'request_type', 'tutor_reschedule');
        update_post_meta($request_id, 'tutor_id', $tutor_id);
        update_post_meta($request_id, 'tutor_name', $tutor_name);
        update_post_meta($request_id, 'student_id', $student_id);
        update_post_meta($request_id, 'student_name', $student_name); 
        update_post_meta($request_id, 'original_date', $original_date);
        update_post_meta($request_id, 'original_time', $original_time);
        update_post_meta($request_id, 'reason', $reason);
        update_post_meta($request_id, 'preferred_times', $preferred_times);
        update_post_meta($request_id, 'status', 'pending'); 
        error_log('[Tutor Reschedule POST] Meta data saved for post ID: ' . $request_id); // LOGGING

        // Set success message
        // $tutor_reschedule_feedback = ['type' => 'success', 'message' => 'Reschedule request submitted successfully.']; // Keep this commented, feedback handled by redirect param
        error_log('[Tutor Reschedule POST] Success feedback set.'); // LOGGING
        
        // Redirect back to the requests tab with a success flag
        $redirect_url = add_query_arg(['active_tab' => 'requests', 'reschedule_success' => '1'], get_permalink()); // Assuming this runs on the tutor dashboard page
        if ($redirect_url) {
            error_log('[Tutor Reschedule POST] Redirecting to: ' . $redirect_url); // LOGGING
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
}
add_action('init', 'process_tutor_reschedule_request_post'); // Hook into init to catch POST request early
