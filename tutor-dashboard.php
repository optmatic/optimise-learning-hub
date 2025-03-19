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
        <!-- ===========================
             NAVIGATION TABS
             =========================== -->
        <div class="col-12">
        <ul class="nav nav-tabs" id="myTab" role="tablist" style="padding-left: 0px !important;">
                <li class="nav-item">
                    <a class="nav-link active" id="progress-report-tab" data-bs-toggle="tab" href="#progress-report">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="your-students-tab" data-bs-toggle="tab" href="#your-students">Your Students</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" id="classroom-url-tab" data-bs-toggle="tab" href="#classroom-url">Your Lessons
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
        <!-- ===========================
             MAIN CONTENT
             =========================== -->
        <div class="tab-content" id="myTabContent" style="padding-top: 20px;">

        <?php 
            include 'tutors/home-tab.php';
            include 'tutors/your-students.php';
            include 'tutors/your-lessons.php';
            include 'tutors/curriculum-links.php';
            include 'tutors/submit-lesson-overview.php';
            include 'tutors/sample-lesson-overview.php';
            include 'tutors/sample-progress-comments.php';
            include 'tutors/requests.php';
?>

            </div> <!-- This div closes the "tab-content" div -->
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
        
                
                // If it's a decline action, validate the reason
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
            echo '<td>';
            // Display preferred times if they exist
            $preferred_times = get_post_meta($request_id, 'preferred_times', true);
            if (!empty($preferred_times)) {
                foreach ($preferred_times as $index => $time) {
                    if (!empty($time['date']) && !empty($time['time'])) {
                        $formatted_time = date('M j, Y', strtotime($time['date'])) . ' at ' . 
                                        date('g:i A', strtotime($time['time']));
                        echo 'Option ' . ($index + 1) . ': ' . esc_html($formatted_time) . '<br>';
                    }
                }
            } else {
                echo 'No preferred times specified';
            }
            echo '</td>';
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
    
    echo '<div class="table-responsive">';
    echo '<table class="table">';
    echo '<thead><tr><th>Student</th><th>Original Time</th><th>Requested Time</th><th>Status</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    
    // Current query (needs to be updated)
    $incoming_requests = get_posts(array(
        'post_type'      => 'progress_report',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'request_type',
                'value'   => 'reschedule', // This is likely only looking for 'reschedule' type
                'compare' => '=',
            ),
            // Other conditions...
        ),
        'order'          => 'DESC',
        'orderby'        => 'date'
    ));

    // Replace with this updated query to include student-initiated reschedule requests
    $incoming_requests = get_posts(array(
        'post_type'      => 'progress_report',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'request_type',
                'value'   => array('reschedule', 'student_reschedule'), // Include both types
                'compare' => 'IN',
            ),
            array(
                'key'     => 'tutor_name',
                'value'   => wp_get_current_user()->user_login, // Match current tutor's username
                'compare' => '=',
            ),
            array(
                'relation' => 'OR',
                array(
                    'key'     => 'status',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => 'status',
                    'value'   => 'pending',
                    'compare' => '=',
                )
            )
        ),
        'order'          => 'DESC',
        'orderby'        => 'date'
    ));
    
    if (!empty($incoming_requests)) {
        foreach ($incoming_requests as $request) {
            $request_id = $request->ID;
            $student_id = get_post_meta($request_id, 'student_id', true);
            $student = get_userdata($student_id);
            $student_name = $student ? $student->display_name : 'Unknown Student';
            
            $original_date = get_post_meta($request_id, 'original_date', true);
            $original_time = get_post_meta($request_id, 'original_time', true);
            $status = get_post_meta($request_id, 'status', true);
            $reason = get_post_meta($request_id, 'reason', true);
            
            // Get preferred times array
            $preferred_times = get_post_meta($request_id, 'preferred_times', true);
            
            // Format original datetime
            $original_datetime = date('M j, Y \a\t g:i A', strtotime("$original_date $original_time"));
            
            // Status badge
            $status_class = 'warning';
            $status_text = 'Pending';
            if ($status === 'confirmed') {
                $status_class = 'success';
                $status_text = 'Confirmed';
            } elseif ($status === 'declined') {
                $status_class = 'danger';
                $status_text = 'Declined';
            }
            
            echo '<tr data-request-id="' . $request_id . '">';
            echo '<td>' . esc_html($student_name) . '</td>';
            echo '<td>' . esc_html($original_datetime) . '</td>';
            
            // Display preferred times
            echo '<td>';
            // Get preferred times array
            $preferred_times = get_post_meta($request_id, 'preferred_times', true);

            // Display preferred times
            if (!empty($preferred_times) && is_array($preferred_times)) {
                foreach ($preferred_times as $index => $time) {
                    if (!empty($time['date']) && !empty($time['time'])) {
                        $formatted_time = date('M j, Y \a\t g:i A', 
                            strtotime($time['date'] . ' ' . $time['time']));
                        echo 'Option ' . ($index + 1) . ': ' . esc_html($formatted_time) . '<br>';
                    }
                }
            } else {
                echo 'No preferred times specified';
            }
            echo '</td>';
            
            echo '<td><span class="badge bg-' . $status_class . '">' . $status_text . '</span></td>';
            echo '<td>';
            
            // Render action buttons
            echo render_action_buttons($request);
            
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5" class="text-center">No incoming requests found.</td></tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    
}

// Update the render_action_buttons function to work with the new structure
function render_action_buttons($request) {
    $request_id = $request->ID;
    $status = get_post_meta($request_id, 'status', true);
    $reason = get_post_meta($request_id, 'reason', true);
    
    $output = '';
    
    if ($status === 'pending') {
        $output .= '<button type="button" class="btn btn-sm btn-success confirm-action" data-request-id="' . $request_id . '">Confirm</button> ';
        $output .= '<div class="d-inline-block">';
        $output .= '<input type="text" class="form-control form-control-sm d-inline-block decline-reason" style="width: 150px;" placeholder="Reason">';
        $output .= '<button type="button" class="btn btn-sm btn-danger decline-action" data-request-id="' . $request_id . '">Decline</button>';
        $output .= '</div>';
    } elseif ($status === 'declined') {
        $output .= 'Reason: ' . esc_html($reason);
    } else {
        $output .= '<span class="text-muted">No actions available</span>';
    }
    
    return $output;
}

// Add JavaScript to handle AJAX actions
add_action('wp_footer', function() {
    if (!is_page('tutor-dashboard')) return;
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle confirm action
        document.querySelectorAll('.confirm-action').forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.getAttribute('data-request-id');
                const row = this.closest('tr');
                
                // Send AJAX request to confirm
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=handle_tutor_request_ajax&request_action=confirm&request_id=' + requestId + '&security=' + tutorRequestsData.nonce,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update status cell
                        row.querySelector('td:nth-child(4)').innerHTML = '<span class="badge bg-success">Confirmed</span>';
                        // Update actions cell
                        row.querySelector('td:nth-child(5)').innerHTML = '<span class="text-muted">No actions available</span>';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            });
        });
        
        // Handle decline action
        document.querySelectorAll('.decline-action').forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.getAttribute('data-request-id');
                const row = this.closest('tr');
                const reasonInput = row.querySelector('.decline-reason');
                const reason = reasonInput.value.trim();
                
                if (!reason) {
                    alert('Please provide a reason for declining.');
                    return;
                }
                
                // Send AJAX request to decline
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=handle_tutor_request_ajax&request_action=decline&request_id=' + requestId + '&reason=' + encodeURIComponent(reason) + '&security=' + tutorRequestsData.nonce,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update status cell
                        row.querySelector('td:nth-child(4)').innerHTML = '<span class="badge bg-danger">Declined</span>';
                        // Update actions cell
                        row.querySelector('td:nth-child(5)').innerHTML = 'Reason: ' + reason;
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            });
        });
    });
    </script>
    <?php
});

// Add AJAX handler for tutor request actions
add_action('wp_ajax_handle_tutor_request_ajax', 'handle_tutor_request_ajax');
function handle_tutor_request_ajax() {
    // Check nonce for security
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'tutor_request_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    $request_action = isset($_POST['request_action']) ? sanitize_text_field($_POST['request_action']) : '';
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    
    if (!$request_id) {
        wp_send_json_error(['message' => 'Invalid request ID']);
        return;
    }
    
    // Handle confirm action
    if ($request_action === 'confirm') {
        update_post_meta($request_id, 'status', 'confirmed');
        wp_send_json_success(['message' => 'Request confirmed successfully']);
        return;
    }
    
    // Handle decline action
    if ($request_action === 'decline') {
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
        
        if (empty($reason)) {
            wp_send_json_error(['message' => 'Please provide a reason for declining']);
            return;
        }
        
        update_post_meta($request_id, 'status', 'declined');
        update_post_meta($request_id, 'reason', $reason);
        
        wp_send_json_success(['message' => 'Request declined successfully']);
        return;
    }
    
    wp_send_json_error(['message' => 'Invalid action']);
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle confirm action
    document.querySelectorAll('.confirm-action').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.getAttribute('data-request-id');
            const row = this.closest('tr');
            
            // Send AJAX request to confirm
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=handle_tutor_request_ajax&request_action=confirm&request_id=' + requestId + '&security=' + tutorRequestsData.nonce,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update status cell
                    row.querySelector('td:nth-child(4)').innerHTML = '<span class="badge bg-success">Confirmed</span>';
                    // Update actions cell
                    row.querySelector('td:nth-child(5)').innerHTML = '<span class="text-muted">No actions available</span>';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    });

    // Handle decline action
    document.querySelectorAll('.decline-action').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.getAttribute('data-request-id');
            const row = this.closest('tr');
            const reasonInput = row.querySelector('.decline-reason');
            const reason = reasonInput.value.trim();
            
            if (!reason) {
                alert('Please provide a reason for declining.');
                reasonInput.focus();
                return;
            }
            
            // Send AJAX request to decline
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=handle_tutor_request_ajax&request_action=decline&request_id=' + requestId + '&reason=' + encodeURIComponent(reason) + '&security=' + tutorRequestsData.nonce,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update status cell
                    row.querySelector('td:nth-child(4)').innerHTML = '<span class="badge bg-danger">Declined</span>';
                    // Update actions cell
                    row.querySelector('td:nth-child(5)').innerHTML = 'Reason: ' + reason;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    });
});
</script>

<script>
jQuery(document).ready(function($) {
    // Handle confirm button clicks
    $('.confirm-request-btn').on('click', function() {
        const requestId = $(this).data('request-id');
        const row = $(this).closest('tr');
        
        if (confirm('Are you sure you want to confirm this request?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'handle_tutor_request',
                    request_id: requestId,
                    status: 'confirmed',
                    nonce: tutor_dashboard_vars.nonce
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Update status cell
                        row.find('td:nth-child(4)').html('<span class="badge bg-success">Confirmed</span>');
                        // Update actions cell
                        row.find('td:nth-child(5)').html('Confirmed');
                    } else {
                        alert('Error: ' + data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                }
            });
        }
    });

    // Handle decline button clicks
    $('.decline-request-btn').on('click', function() {
        const requestId = $(this).data('request-id');
        const row = $(this).closest('tr');
        
        const reason = prompt('Please provide a reason for declining:');
        if (reason === null) return; // User canceled
        
        if (reason.trim() === '') {
            alert('Please provide a reason for declining.');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'handle_tutor_request',
                request_id: requestId,
                status: 'declined',
                reason: reason,
                nonce: tutor_dashboard_vars.nonce
            },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    // Update status cell
                    row.find('td:nth-child(4)').html('<span class="badge bg-danger">Declined</span>');
                    // Update actions cell
                    row.find('td:nth-child(5)').html('Reason: ' + reason);
                } else {
                    alert('Error: ' + data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            }
        });
    });
});
</script>

<script>
jQuery(document).ready(function($) {
    // Handle decline request action
    $('.decline-request-btn').on('click', function() {
        const requestId = $(this).data('request-id');
        const row = $(this).closest('tr');
        
        showDeclineModal(requestId, row);
    });
    
    // Process the decline action when confirmed
    $(document).on('click', '#confirm-decline-btn', function() {
        const requestId = $(this).data('request-id');
        const reason = $('#decline-reason').val();
        const row = $(this).data('row');
        
        if (!reason.trim()) {
            alert('Please provide a reason for declining.');
            return;
        }
        
        processDeclineRequest(requestId, reason, row);
    });
    
    /**
     * Display the modal for declining a request
     */
    function showDeclineModal(requestId, row) {
        // Create modal if it doesn't exist
        if ($('#decline-modal').length === 0) {
            $('body').append(`
                <div id="decline-modal" class="modal fade" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Decline Request</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Please provide a reason for declining this request:</p>
                                <textarea id="decline-reason" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" id="confirm-decline-btn">Decline Request</button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        }
        
        // Set up the modal with current request data
        $('#confirm-decline-btn').data('request-id', requestId).data('row', row);
        $('#decline-reason').val('');
        
        // Show the modal
        const declineModal = new bootstrap.Modal(document.getElementById('decline-modal'));
        declineModal.show();
    }
    
    /**
     * Process the decline request via AJAX
     */
    function processDeclineRequest(requestId, reason, row) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'handle_tutor_request',
                request_id: requestId,
                request_action: 'decline',
                reason: reason,
                nonce: tutor_dashboard_data.nonce
            },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    // Update status cell
                    row.find('td:nth-child(4)').html('<span class="badge bg-danger">Declined</span>');
                    // Update actions cell
                    row.find('td:nth-child(5)').html('Reason: ' + reason);
                    
                    // Close the modal
                    $('#decline-modal').modal('hide');
                } else {
                    alert('Error: ' + data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            }
        });
    }
});
</script>
