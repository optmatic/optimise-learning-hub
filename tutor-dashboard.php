<?php
/*
Template Name: Tutor Dashboard
*/
?>
<?php
get_header(); ?>

<?php
if (current_user_can('tutor')) {
?>

<?php
// Get the current user to use as the tutor
$current_user = wp_get_current_user();
$tutor = $current_user;
$tutor_id = get_current_user_id();

// Handle AJAX request to mark reschedules as viewed
if (isset($_POST['mark_viewed']) && $_POST['mark_viewed'] === '1') {
    $confirmed_reschedules = get_posts(array(
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
                'value'   => 'reschedule',
                'compare' => '=',
            ),
            array(
                'key'     => 'status',
                'value'   => 'confirmed',
                'compare' => '=',
            )
        ),
        'fields'         => 'ids'
    ));
    
    foreach ($confirmed_reschedules as $reschedule_id) {
        update_post_meta($reschedule_id, 'viewed_by_tutor', '1');
    }
    
    // If this is an AJAX request, return success
    if (wp_doing_ajax()) {
        wp_send_json_success();
        exit;
    }
}

add_action('wp_ajax_get_preferred_times', 'get_preferred_times_ajax');
function get_preferred_times_ajax() {
    if (!isset($_POST['request_id'])) {
        wp_send_json_error();
    }
    
    $request_id = intval($_POST['request_id']);
    $student_id = get_post_meta($request_id, 'student_id', true);
    
    // Verify this request belongs to the current user
    if ($student_id != get_current_user_id()) {
        wp_send_json_error();
    }
    
    $preferred_times = get_post_meta($request_id, 'preferred_times', true);
    wp_send_json_success(array('preferred_times' => $preferred_times));
}


/**
 * AJAX handler to check for incoming reschedule requests
 */
function check_incoming_reschedule_requests_ajax() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'check_incoming_reschedule_requests_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    // Get tutor ID
    $tutor_id = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : 0;
    if (!$tutor_id) {
        wp_send_json_error('Invalid tutor ID');
        return;
    }

       // Query for incoming reschedule requests
       $student_requests_args = array(
        'post_type'      => 'progress_report',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array('key' => 'tutor_id', 'value' => $tutor_id, 'compare' => '='),
            array('key' => 'request_type', 'value' => 'reschedule', 'compare' => '='),
            array('key' => 'status', 'value' => 'pending', 'compare' => '=')
        ),
        'order'          => 'DESC',
        'orderby'        => 'date'
    );
    
    $student_requests = get_posts($student_requests_args);
    $count = count($student_requests);

    // Generate HTML for the requests section
    $html = '';
    if ($count > 0) {
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-striped">';
        $html .= '<thead><tr><th>Date Requested</th><th>Original Lesson</th><th>Proposed New Time</th><th>Student</th><th>Action</th></tr></thead>';
        $html .= '<tbody>';


        foreach ($student_requests as $request) {
            $request_id = $request->ID;
            $student_name = esc_html(get_post_meta($request_id, 'student_name', true));
            $original_date = esc_html(get_post_meta($request_id, 'original_date', true));
            $original_time = esc_html(get_post_meta($request_id, 'original_time', true));
            $new_date = esc_html(get_post_meta($request_id, 'new_date', true));
            $new_time = esc_html(get_post_meta($request_id, 'new_time', true));
            $request_date = esc_html(get_the_date('M j, Y', $request_id));
            
            $html .= '<tr>';
            $html .= '<td>' . $request_date . '</td>';
            $html .= '<td>' . $original_date . ' at ' . $original_time . '</td>';
            $html .= '<td>' . $new_date . ' at ' . $new_time . '</td>';
            $html .= '<td>' . $student_name . '</td>';
            $html .= '<td>';
            $html .= '<form method="post" class="d-inline">';
            $html .= '<input type="hidden" name="confirm_reschedule" value="1">';
            $html .= '<input type="hidden" name="request_id" value="' . $request_id . '">';
            $html .= '<button type="submit" class="btn btn-sm btn-success me-1">Accept</button>';
            $html .= '</form>';
            $html .= '<form method="post" class="d-inline">';
            $html .= '<input type="hidden" name="decline_reschedule" value="1">';
            $html .= '<input type="hidden" name="request_id" value="' . $request_id . '">';
            $html .= '<button type="submit" class="btn btn-sm btn-danger">Decline</button>';
            $html .= '</form>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        $html .= '</div>';
    } else {
        $html = '<p>No incoming reschedule requests from students at this time.</p>';
    }
    
    wp_send_json_success(['count' => $count, 'html' => $html]);
    exit;
}
add_action('wp_ajax_check_incoming_reschedule_requests', 'check_incoming_reschedule_requests_ajax');

// Add this function to test if the AJAX endpoint is working
function test_reschedule_requests() {
    // Create a test reschedule request
    $student_id = get_current_user_id();
    $student_name = 'Test Student';
    $original_date = date('Y-m-d');
    $original_time = '14:00:00';
    $new_date = date('Y-m-d', strtotime('+1 day'));
    $new_time = '15:00:00';
    
    // Get the current user as tutor
    $tutor_id = get_current_user_id();
     
    // Create a new reschedule request post
    $new_request = array(
        'post_title'   => 'Test Reschedule Request',
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'progress_report',
    );

    $new_request_id = wp_insert_post($new_request);
       
    if (!is_wp_error($new_request_id)) {
        // Save the request details
        update_post_meta($new_request_id, 'tutor_id', $tutor_id);
        update_post_meta($new_request_id, 'student_name', $student_name);
        update_post_meta($new_request_id, 'request_type', 'reschedule');
        update_post_meta($new_request_id, 'original_date', $original_date);
        update_post_meta($new_request_id, 'original_time', $original_time);
        update_post_meta($new_request_id, 'new_date', $new_date);
        update_post_meta($new_request_id, 'new_time', $new_time);
        update_post_meta($new_request_id, 'status', 'pending');
        
        return "Test reschedule request created with ID: $new_request_id";
    } else {
        return "Error creating test reschedule request: " . $new_request_id->get_error_message();
    }
}

?>



<div class="container mt-4">
    <div class="row">
<!-- ===========================
     NAVIGATION TABS
     =========================== -->
        <div class="col-12">
        <?php
        // Count unread requests - keeping this section for the data, but removing the badge display
        $current_user = wp_get_current_user();
        $tutor_name = $current_user->display_name;
        
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
        // Removing this badge display
        // if ($unread_count > 0) {
        //     echo '<span class="badge rounded-pill bg-danger">' . $unread_count . '</span>';
        // }
        ?>
            
        <ul class="nav nav-tabs" id="myTab" role="tablist" style="padding-left: 0px !important;">
                <li class="nav-item">
                    <a class="nav-link active" id="progress-report-tab" data-bs-toggle="tab" href="#progress-report">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="your-students-tab" data-bs-toggle="tab" href="#your-students">Your Students</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" id="classroom-url-tab" data-bs-toggle="tab" href="#classroom-url">Your Lessons
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
                <li class="nav-item">
                    <a class="nav-link position-relative" id="requests-tab" data-bs-toggle="tab" href="#requests">
                        Requests 
                        <?php
                        // Count pending student reschedule requests
                        $student_requests_args = array(
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
                                    'value'   => array('student_reschedule', 'reschedule_unavailable_all'),
                                    'compare' => 'IN',
                                ),
                                array(
                                    'key'     => 'status',
                                    'value'   => 'pending',
                                    'compare' => '=',
                                )
                            ),
                            'fields'         => 'ids'
                        );
                        $requests_notification_count = count(get_posts($student_requests_args));
                        
                        if ($requests_notification_count > 0): 
                        ?>
                            <span class="badge rounded-pill bg-danger notification-badge"><?php echo $requests_notification_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
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

            </div>
        <div>

<?php
} else {
    echo "Access denied. You don't have permission to view this page.";
}
?>



<?php get_footer(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check for previously stored active tab in localStorage
    const storedTab = localStorage.getItem('activeTutorTab');
    
    if (storedTab) {
        // Activate the stored tab
        const tabToActivate = document.querySelector(`a[href="${storedTab}"]`);
        if (tabToActivate) {
            const bsTab = new bootstrap.Tab(tabToActivate);
            bsTab.show();
        }
    }
    
    // Add event listeners to all tab links
    const tabLinks = document.querySelectorAll('a[data-bs-toggle="tab"]');
    tabLinks.forEach(function(tabLink) {
        tabLink.addEventListener('shown.bs.tab', function(event) {
            // Store the active tab in localStorage
            localStorage.setItem('activeTutorTab', event.target.getAttribute('href'));
        });
    });
    
    // Handle form submissions to preserve active tab
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            // Add a hidden field with the current active tab
            const activeTab = document.querySelector('.nav-link.active');
            if (activeTab) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'active_tab';
                hiddenInput.value = activeTab.getAttribute('href').substring(1); // Remove the # from the href
                this.appendChild(hiddenInput);
            }
        });
    });
    
    // Function to handle the Delete buttons with AJAX
    const deleteButtons = document.querySelectorAll('.delete-request-btn');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            
            if (!confirm('Are you sure you want to delete this request?')) {
                return;
            }
            
            const requestId = this.getAttribute('data-request-id');
            const row = this.closest('tr');
            
            // Set up AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (this.status >= 200 && this.status < 400) {
                    const response = JSON.parse(this.response);
                    
                    if (response.success) {
                        // Remove the table row
                        row.remove();
                        
                        // Show success message
                        const successAlert = document.createElement('div');
                        successAlert.className = 'alert alert-success';
                        successAlert.textContent = 'Request has been deleted successfully.';
                        
                        // Insert at the top of the reschedule section
                        const rescheduleSection = document.getElementById('rescheduleRequestsSection');
                        rescheduleSection.insertBefore(successAlert, rescheduleSection.firstChild);
                        
                        // Auto-hide after 3 seconds
                        setTimeout(function() {
                            successAlert.remove();
                        }, 3000);
                    } else {
                        alert('Error: ' + (response.data ? response.data.message : 'Failed to delete request'));
                    }
                } else {
                    alert('Error: Server returned an error');
                }
            };
            
            xhr.onerror = function() {
                alert('Error: Request failed');
            };
            
            xhr.send('action=delete_tutor_request&delete_tutor_request=1&request_id=' + requestId);
        });
    });
});
</script>

<script>
    // Initialize tab tracking
    document.addEventListener('DOMContentLoaded', function() {
        // Add a direct class to reschedule sections for easier JS targeting
        document.querySelectorAll('h2').forEach(function(heading) {
            if (heading.textContent.includes('Reschedule Requests')) {
                let section = heading;
                let container = document.createElement('div');
                container.className = 'reschedule-container';
                
                // Move everything from this heading until the next h2 into the container
                heading.parentNode.insertBefore(container, heading);
                container.appendChild(heading);
                
                let nextSibling = container.nextSibling;
                while (nextSibling && 
                       !(nextSibling.tagName === 'H2')) {
                    const temp = nextSibling.nextSibling;
                    container.appendChild(nextSibling);
                    nextSibling = temp;
                }
            }
        });
        
        // Handle tab switching
        const tabLinks = document.querySelectorAll('a[data-bs-toggle="tab"]');
        tabLinks.forEach(function(tabLink) {
            tabLink.addEventListener('click', function(event) {
                // Store which tab was clicked
                localStorage.setItem('activeTutorTab', this.getAttribute('href'));
                
                // Toggle reschedule content visibility
                const isRequestsTab = this.getAttribute('href') === '#requests';
                document.querySelectorAll('.reschedule-container').forEach(function(container) {
                    container.style.display = isRequestsTab ? 'block' : 'none';
                });
            });
        });
        
        // Check if we should restore a previously selected tab
        const storedTab = localStorage.getItem('activeTutorTab');
        if (storedTab) {
            const tabToSelect = document.querySelector(`a[href="${storedTab}"]`);
            if (tabToSelect) {
                // Trigger a click on the stored tab
                tabToSelect.click();
            }
        }
        
        // Hide reschedule sections initially if not on requests tab
        const activeTab = document.querySelector('.nav-link.active');
        if (!activeTab || activeTab.getAttribute('href') !== '#requests') {
            document.querySelectorAll('.reschedule-container').forEach(function(container) {
                container.style.display = 'none';
            });
        }
    });
</script>