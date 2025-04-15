<!-- =========================== REQUESTS TAB =========================== -->
<div class="tab-pane fade" id="requests" role="tabpanel" aria-labelledby="requests">
    <h4>Reschedule Requests</h4>

    <?php include 'notifications.php'; ?>
    <?php include 'request-reschedule.php'; ?>
    <?php include 'outgoing-requests.php'; ?>
    <?php include 'incoming-requests.php'; ?>
    <?php include 'tutor-alternative-times.php'; ?>

</div>

<!-- Add hidden fields with nonces for AJAX security -->
<input type="hidden" id="check_student_requests_nonce" value="<?php echo wp_create_nonce('check_student_requests_nonce'); ?>">
<input type="hidden" id="mark_student_requests_read_nonce" value="<?php echo wp_create_nonce('mark_student_requests_read_nonce'); ?>">



