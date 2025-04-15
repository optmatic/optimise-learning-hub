<!-- =========================== REQUESTS TAB =========================== -->
<div class="tab-pane fade" id="requests" role="tabpanel" aria-labelledby="requests">
    <h4>Reschedule Requests</h4>

    <?php include 'request-reschedule.php'; ?>
    <?php include 'outgoing-requests.php'; ?>
    <?php include 'incoming-requests.php'; ?>
    <?php include 'student-alternative-times.php'; ?>
    <?php include 'notifications.php'; ?>

</div>


<!-- Add hidden fields with nonces for AJAX security -->
<input type="hidden" id="check_tutor_requests_nonce" value="<?php echo wp_create_nonce('check_tutor_requests_nonce'); ?>">
<input type="hidden" id="mark_tutor_requests_read_nonce" value="<?php echo wp_create_nonce('mark_tutor_requests_read_nonce'); ?>">



