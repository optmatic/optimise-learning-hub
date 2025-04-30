jQuery(document).ready(function ($) {
  console.log("Tutor requests specific JS loaded.");

  // Function to initialize Bootstrap Tooltips safely
  function initializeTooltips() {
    if (typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
      console.log("Initializing tooltips...");
      const tooltipTriggerList = Array.from(
        document.querySelectorAll("[data-bs-toggle='tooltip']")
      );
      tooltipTriggerList.map((tooltipTriggerEl) => {
        // Check if a tooltip instance already exists to prevent duplicates
        if (!bootstrap.Tooltip.getInstance(tooltipTriggerEl)) {
          return new bootstrap.Tooltip(tooltipTriggerEl);
        } else {
          console.log("Tooltip already initialized for:", tooltipTriggerEl);
          return bootstrap.Tooltip.getInstance(tooltipTriggerEl); // Return existing instance
        }
      });
    } else {
      console.warn(
        "Bootstrap Tooltip component not available when trying to initialize."
      );
    }
  }

  // --- AJAX Handling for Incoming Requests ---
  function loadIncomingRequests() {
    // ... (rest of the AJAX function remains the same)
    $.ajax({
      url: tutorDashboardData.ajaxurl, // Make sure tutorDashboardData is localized
      type: "POST",
      data: {
        action: "check_tutor_incoming_requests",
        nonce: tutorDashboardData.checkNonce, // Ensure this nonce is localized
      },
      success: function (response) {
        if (response.success) {
          $("#tutor-incoming-requests-container").html(response.data.html);
          // Re-initialize tooltips after loading new content
          initializeTooltips();
        } else {
          $("#tutor-incoming-requests-container").html(
            '<div class="alert alert-warning">Could not load incoming requests.</div>'
          );
          console.error(
            "Error loading incoming requests:",
            response.data.message
          );
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        $("#tutor-incoming-requests-container").html(
          '<div class="alert alert-danger">AJAX error loading incoming requests.</div>'
        );
        console.error("AJAX Error:", textStatus, errorThrown);
      },
    });
  }

  // --- AJAX Handling for Notifications ---
  function checkNotifications() {
    // ... (rest of the AJAX function remains the same)
    $.ajax({
      url: tutorDashboardData.ajaxurl,
      type: "POST",
      data: {
        action: "check_tutor_notifications",
        nonce: tutorDashboardData.checkNotificationNonce, // Ensure this nonce is localized
      },
      success: function (response) {
        if (response.success) {
          $("#tutor-notifications-container").html(response.data.html);
          // Re-initialize tooltips after loading new content
          initializeTooltips();
        } else {
          $("#tutor-notifications-container").html(
            '<div class="alert alert-warning">Could not load notifications.</div>'
          );
          console.error("Error loading notifications:", response.data.message);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        $("#tutor-notifications-container").html(
          '<div class="alert alert-danger">AJAX error loading notifications.</div>'
        );
        console.error("Notification AJAX Error:", textStatus, errorThrown);
      },
    });
  }

  // --- Initial Load & Interval ---
  loadIncomingRequests(); // Initial load
  checkNotifications(); // Initial load
  initializeTooltips(); // Initialize tooltips on static elements

  setInterval(loadIncomingRequests, 60000); // Refresh incoming requests every 60 seconds
  setInterval(checkNotifications, 30000); // Refresh notifications every 30 seconds

  // --- Event Delegation for Dynamic Content ---

  // Delegate tooltip initialization for dynamically added content if necessary
  // This might not be strictly needed if initializeTooltips() is called after AJAX success
  // $(document).on('mouseenter', "[data-bs-toggle='tooltip']", function() {
  //    initializeTooltips(); // Or more targeted initialization
  // });

  // Delegate click handlers for dynamically loaded buttons
  $(document).on(
    "click",
    ".approve-request-btn, .decline-request-btn, .edit-tutor-request-btn, .delete-tutor-request-btn",
    function () {
      const $button = $(this);
      const requestId = $button.data("request-id");
      let action = "";
      let requiresConfirmation = false;
      let nonce = "";
      let additionalData = {};

      if ($button.hasClass("approve-request-btn")) {
        action = "approve_student_request";
        nonce = tutorDashboardData.approveNonce; // Ensure localized
        requiresConfirmation = true;
      } else if ($button.hasClass("decline-request-btn")) {
        action = "decline_student_request";
        nonce = tutorDashboardData.declineNonce; // Ensure localized
        requiresConfirmation = true; // Or handle reason input
        // Potentially trigger a modal for decline reason here
      } else if ($button.hasClass("edit-tutor-request-btn")) {
        action = "edit_tutor_request"; // Placeholder - implement edit logic
        console.log("Edit request clicked:", requestId);
        // Open the edit modal, pre-fill data via another AJAX call if needed
        // Example: $('#editTutorRescheduleModal').modal('show');
        return; // Stop further processing for now
      } else if ($button.hasClass("delete-tutor-request-btn")) {
        action = "cancel_tutor_request"; // Changed action name for clarity
        nonce = tutorDashboardData.cancelNonce; // Ensure localized
        requiresConfirmation = true;
        additionalData.confirmationMessage =
          "Are you sure you want to cancel this outgoing request? This cannot be undone.";
      }

      if (!action || !nonce) {
        console.error(
          "Could not determine action or nonce for button.",
          $button
        );
        return;
      }

      if (requiresConfirmation) {
        const message =
          additionalData.confirmationMessage ||
          `Are you sure you want to ${action.split("_")[0]} this request?`;
        if (!confirm(message)) {
          return; // User cancelled
        }
      }

      // Add loading state to button
      $button
        .prop("disabled", true)
        .html(
          '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
        );

      $.ajax({
        url: tutorDashboardData.ajaxurl,
        type: "POST",
        data: {
          action: action,
          nonce: nonce.replace("_placeholder", "_" + requestId), // Replace placeholder in nonce
          request_id: requestId,
          // Add decline reason etc. if needed
        },
        success: function (response) {
          if (response.success) {
            console.log(action + " successful for request:", requestId);
            // Refresh relevant sections
            loadIncomingRequests();
            checkNotifications();
            // Optionally display a success message
            // You might need to refresh the outgoing requests table too if cancelling
            if (action === "cancel_tutor_request") {
              $button.closest("tr").fadeOut(function () {
                $(this).remove();
              });
            }
          } else {
            console.error(
              "Error performing action " + action + ":",
              response.data.message
            );
            alert("Error: " + response.data.message); // Show error to user
            $button
              .prop("disabled", false)
              .html($button.data("original-html") || "Action"); // Restore button - consider saving original html
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error(
            "AJAX Error performing action " + action + ":",
            textStatus,
            errorThrown
          );
          alert("An AJAX error occurred. Please try again.");
          $button
            .prop("disabled", false)
            .html($button.data("original-html") || "Action"); // Restore button
        },
      });
    }
  );

  // Add logic for modal interactions (e.g., submitting forms) here...
});
