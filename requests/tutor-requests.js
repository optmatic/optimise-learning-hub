// Tutor Dashboard Requests Tab AJAX and Modal Handling
jQuery(document).ready(function ($) {
  // Function Definitions (moved back inside document.ready)

  // Tooltip initialization logic (with internal retries)
  var attemptTooltipInitialization = function (
    targetContainer,
    attemptsLeft = 3
  ) {
    // Check if Bootstrap (as understrap) and Tooltip component are loaded
    if (
      typeof understrap === "undefined" || // Check for understrap
      typeof understrap.Tooltip === "undefined" // Check for understrap.Tooltip
    ) {
      console.warn(
        "Understrap Tooltip component not ready for", // Updated message
        targetContainer.attr("id"),
        attemptsLeft > 0
          ? `(${attemptsLeft} attempts left)`
          : "(No attempts left)"
      );
      // If attempts remain, schedule a retry
      if (attemptsLeft > 0) {
        setTimeout(function () {
          attemptTooltipInitialization(targetContainer, attemptsLeft - 1);
        }, 300); // Retry delay
      }
      return false; // Indicate failure or exhaustion of retries
    }

    // Now check if there are actually tooltips to initialize IN THIS CONTAINER
    var tooltipTriggerList = targetContainer[0].querySelectorAll(
      '[data-bs-toggle="tooltip"]'
    );

    if (tooltipTriggerList.length === 0) {
      // console.log("No tooltips found in", targetContainer.attr("id"));
      return true;
    }

    // console.log("Found", tooltipTriggerList.length, "tooltips in", targetContainer.attr("id"));
    var initializedCount = 0;
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
      // Use understrap.Tooltip
      if (!understrap.Tooltip.getInstance(tooltipTriggerEl)) {
        new understrap.Tooltip(tooltipTriggerEl);
        initializedCount++;
      }
    });
    // console.log("Initialized", initializedCount, "new tooltips in", targetContainer.attr("id"));
    return true; // Indicate success
  };

  // Function to initialize ALL tooltips within the requests tab pane
  function initializeAllTooltips(attemptsLeft = 5) {
    // Add attempts counter
    console.log(
      `Attempting to initialize all tooltips... (${attemptsLeft} attempts left)`
    );

    // REMOVED Detailed check logging

    // Check if understrap and Tooltip component are loaded
    if (
      typeof understrap === "undefined" || // Check for understrap
      typeof understrap.Tooltip === "undefined" // Check for understrap.Tooltip
    ) {
      console.warn("Understrap Tooltip component not ready for global init."); // Updated message
      if (attemptsLeft > 0) {
        console.log("Retrying tooltip init in 500ms...");
        setTimeout(() => initializeAllTooltips(attemptsLeft - 1), 500); // Retry
      } else {
        console.error(
          "Understrap Tooltip component failed to initialize after multiple attempts."
        ); // Updated message
      }
      return; // Stop this attempt
    }

    const container = document.getElementById("requests-tab-pane");
    if (!container) {
      console.warn("Tooltip init: requests-tab-pane not found.");
      return;
    }

    const tooltipTriggerList = container.querySelectorAll(
      '[data-bs-toggle="tooltip"]'
    );
    console.log(
      `Found ${tooltipTriggerList.length} tooltip triggers in #requests-tab-pane.`
    );
    const tooltipList = [...tooltipTriggerList].map((tooltipTriggerEl) => {
      // Check if instance already exists before creating
      // Use understrap.Tooltip
      if (!understrap.Tooltip.getInstance(tooltipTriggerEl)) {
        return new understrap.Tooltip(tooltipTriggerEl);
      }
      return understrap.Tooltip.getInstance(tooltipTriggerEl); // Return existing instance
    });
    console.log("Tooltip initialization complete.");
  }

  // Function to load content into a container
  function loadTutorRequestSection(
    action,
    nonce,
    containerId,
    isInitialLoad = false
  ) {
    var container = $(containerId);
    if (!container.length) {
      console.error("Container not found:", containerId);
      return; // Exit if container doesn't exist
    }
    container.html(
      '<div class="text-center p-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...</div>'
    );

    // Use localized data if available
    const ajaxData = {
      action: action,
      nonce: nonce,
    };
    // Correctly check for userId from localization
    if (typeof olHubTutorData !== "undefined" && olHubTutorData.userId) {
      // Correctly assign userId to tutor_id for the POST request
      ajaxData.tutor_id = olHubTutorData.userId;
    }

    $.ajax({
      url: olHubTutorData.ajax_url,
      type: "POST",
      data: ajaxData,
      success: function (response) {
        if (response.success && response.data && response.data.html) {
          container.html(response.data.html);

          // If this was one of the initial loads, increment counter and check if all done
          if (isInitialLoad) {
            initialLoadCounter++;
            if (initialLoadCounter >= 4) {
              // Check if all 4 initial loads are complete
              console.log(
                "All initial sections loaded, initializing tooltips..."
              );
              initializeAllTooltips();
            }
          }

          $(document).trigger("tutorRequestSectionLoaded", {
            containerId: containerId,
            action: action,
            response: response,
          });
        } else {
          console.error(
            "AJAX Success but no HTML:",
            action,
            response?.data?.message || "No message"
          );
          let errorMessage =
            response?.data?.message ||
            "Server returned success but no HTML content.";
          if (response?.data?.message === "Permission denied.") {
            errorMessage =
              'Permission denied. Please ensure you are logged in as the correct tutor. <a href="/access/">Login</a>';
          }
          container.html(
            '<div class="alert alert-warning">' + errorMessage + "</div>"
          );
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(
          "AJAX Network/Server Error for",
          action,
          ":",
          textStatus,
          errorThrown,
          jqXHR.responseText
        );
        container.html(
          '<div class="alert alert-danger">Server error encountered (' +
            textStatus +
            "). Please check server logs.</div>"
        );
      },
    });
  }

  var initialLoadCounter = 0; // Counter for initial section loads

  // ==================================================
  // Initialization Logic
  // ==================================================

  // Check if localized data is available before proceeding
  if (typeof olHubTutorData === "undefined") {
    console.error("olHubTutorData is not defined. Script cannot initialize.");
    // Display an error message on the page?
    return; // Stop execution if data is missing
  }

  // --- Initial Loads (with delay) ---
  setTimeout(function () {
    // Use localized nonces directly, matching the keys from functions.php
    var loadOutgoingNonce = olHubTutorData?.nonces?.loadTutorOutgoingNonce;
    var loadIncomingNonce = olHubTutorData?.nonces?.checkTutorIncomingNonce;
    var notificationNonce =
      olHubTutorData?.nonces?.checkTutorNotificationsNonce;
    var loadAlternativesNonce =
      olHubTutorData?.nonces?.loadTutorStudentAlternativesNonce;

    // Load Notifications
    if (notificationNonce) {
      loadTutorRequestSection(
        "load_tutor_notifications", // Corrected AJAX action name
        notificationNonce,
        "#tutor-notifications-container",
        true // Mark as initial load
      );
    } else {
      console.error("Tutor notification nonce not found in olHubTutorData.");
      $("#tutor-notifications-container").html(
        '<div class="alert alert-warning">Could not load notifications (nonce missing).</div>'
      );
      initialLoadCounter++; // Increment even on error to avoid blocking tooltip init
    }

    // Load Outgoing Requests
    if (loadOutgoingNonce) {
      loadTutorRequestSection(
        "load_tutor_outgoing_requests", // Corrected AJAX action name
        loadOutgoingNonce,
        "#tutor-outgoing-requests-container",
        true // Mark as initial load
      );
    } else {
      console.error(
        "Tutor load outgoing requests nonce not found in olHubTutorData."
      );
      $("#tutor-outgoing-requests-container").html(
        '<div class="alert alert-warning">Could not load outgoing requests (nonce missing).</div>'
      );
      initialLoadCounter++;
    }

    // Load Incoming Requests (Students needing response)
    if (loadIncomingNonce) {
      loadTutorRequestSection(
        "load_tutor_incoming_requests", // Corrected AJAX action name
        loadIncomingNonce,
        "#tutor-incoming-requests-container",
        true // Mark as initial load
      );
    } else {
      console.error(
        "Tutor load incoming requests nonce not found in olHubTutorData."
      );
      $("#tutor-incoming-requests-container").html(
        '<div class="alert alert-warning">Could not load incoming requests (nonce missing).</div>'
      );
      initialLoadCounter++;
    }

    // Load Student Alternatives
    if (loadAlternativesNonce) {
      loadTutorRequestSection(
        "load_tutor_student_alternatives", // Corrected AJAX action name
        loadAlternativesNonce,
        "#tutor-student-alternatives-container",
        true // Mark as initial load
      );
    } else {
      console.error(
        "Tutor load student alternatives nonce not found in olHubTutorData."
      );
      $("#tutor-student-alternatives-container").html(
        '<div class="alert alert-warning">Could not load student alternatives (nonce missing).</div>'
      );
      initialLoadCounter++;
    }

    // Consider if intervals are still desired
    // setInterval(() => loadTutorRequestSection('load_tutor_notifications', '#tutor-notifications-container'), 30000); // e.g., Check notifications every 30s
  }, 500); // Delay for the initial AJAX calls themselves

  // --- Event Handlers (Delegated for AJAX-loaded content) ---

  // Handle clicking "Mark as Viewed" (example for notifications)
  $(document).on("click", ".mark-tutor-item-viewed", function (e) {
    e.preventDefault();
    var $button = $(this);
    var itemId = $button.data("item-id"); // Assuming data-item-id attribute exists
    var itemType = $button.data("item-type"); // e.g., 'notification', 'alternative'
    var nonce = olHubTutorData?.nonces?.markItemViewedNonce;

    if (!itemId || !itemType || !nonce) {
      console.error("Missing data for mark as viewed action.");
      return;
    }

    $.ajax({
      url: olHubTutorData.ajax_url,
      type: "POST",
      data: {
        action: "mark_tutor_item_viewed",
        item_id: itemId,
        item_type: itemType,
        nonce: nonce,
        tutor_id: olHubTutorData.userId,
      },
      success: function (response) {
        if (response.success) {
          console.log("Item marked as viewed:", itemId, itemType);
          // Option 1: Remove the item visually
          // $button.closest('.notification-item, .alternative-item').fadeOut(); // Adjust selector

          // Option 2: Update UI element (e.g., remove 'unread' class, disable button)
          $button
            .removeClass("btn-primary")
            .addClass("btn-secondary disabled")
            .text("Viewed");
          // Update notification count badge if applicable
          // loadTutorRequestSection("load_tutor_notifications", olHubTutorData?.nonces?.checkTutorNotificationsNonce, "#tutor-notifications-container");
        } else {
          console.error(
            "Failed to mark item as viewed:",
            response.data?.message
          );
          alert(
            "Error marking item as viewed: " +
              (response.data?.message || "Unknown error")
          );
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(
          "AJAX error marking item as viewed:",
          textStatus,
          errorThrown
        );
        alert("Could not mark item as viewed due to a server error.");
      },
    });
  });

  // Handle clicking "Delete My Request"
  $(document).on("click", ".delete-tutor-request-btn", function (e) {
    e.preventDefault();
    var $button = $(this);
    var requestId = $button.data("request-id");
    var nonce = olHubTutorData?.nonces?.deleteTutorRequestNonce;

    if (!requestId || !nonce) {
      console.error("Missing data for delete request action.");
      return;
    }

    if (confirm("Are you sure you want to delete this request permanently?")) {
      $.ajax({
        url: olHubTutorData.ajax_url,
        type: "POST",
        data: {
          action: "delete_tutor_request", // Matches PHP hook wp_ajax_delete_tutor_request
          request_id: requestId,
          nonce: nonce, // Sending the specific nonce
          tutor_id: olHubTutorData.userId, // Optional: Pass tutor ID for extra server-side check
        },
        success: function (response) {
          if (response.success) {
            console.log("Request deleted:", requestId);
            // Remove the request's row/card from the UI
            $button
              .closest(".tutor-request-item") // Or 'tr' if in a table
              .fadeOut(function () {
                $(this).remove();
                // Optional: Check if the container is empty and show a message
                if (
                  $("#tutor-outgoing-requests-container").children().length ===
                  0
                ) {
                  $("#tutor-outgoing-requests-container").html(
                    '<p class="text-muted">No outgoing requests.</p>'
                  );
                }
              });
          } else {
            console.error("Failed to delete request:", response.data?.message);
            alert(
              "Error deleting request: " +
                (response.data?.message || "Unknown error")
            );
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error(
            "AJAX error deleting request:",
            textStatus,
            errorThrown
          );
          alert("Could not delete request due to a server error.");
        },
      });
    }
  });

  // --- Tutor Responding to Student Requests (Incoming) ---

  // Open modal for Accept/Decline/Propose
  $(document).on("click", ".respond-student-request-btn", function () {
    const requestId = $(this).data("request-id");
    const studentName = $(this).data("student-name");
    const originalDateTime = $(this).data("original-datetime");
    const newDateTime = $(this).data("new-datetime");
    const reason = $(this).data("reason");

    // Populate modal fields
    $("#responseModalLabel").text(`Respond to ${studentName}'s Request`);
    $("#modal-request-id").val(requestId);
    $("#modal-original-datetime").text(originalDateTime);
    $("#modal-new-datetime").text(newDateTime);
    $("#modal-student-reason").text(reason || "No reason provided.");

    // Reset proposal fields
    $("#propose-new-datetime-group").hide();
    $("#propose-new-datetime").val("");
    $("#modal-tutor-comments").val("");
    $("#decline-reason-group").hide();
    $("#modal-decline-reason").val("");
    $("#response-action-accept").prop("checked", true); // Default to accept

    // Show the modal
    const responseModal = new understrap.Modal(
      document.getElementById("respondStudentRequestModal")
    );
    responseModal.show();
  });

  // Toggle visibility of modal fields based on action chosen
  $('input[name="response-action"]').on("change", function () {
    const action = $(this).val();
    $("#propose-new-datetime-group").toggle(action === "propose");
    $("#decline-reason-group").toggle(action === "decline");
  });

  // Handle modal form submission
  $("#submitResponseForm").on("submit", function (e) {
    e.preventDefault();
    const formData = $(this).serializeArray(); // Get form data as array
    const nonce = olHubTutorData?.nonces?.handleStudentRequestNonce;

    if (!nonce) {
      console.error("Missing nonce for student request response.");
      alert("Security token missing. Cannot process response.");
      return;
    }

    // Add action and nonce to the data
    formData.push({ name: "action", value: "handle_student_request_response" });
    formData.push({ name: "nonce", value: nonce });
    formData.push({ name: "tutor_id", value: olHubTutorData.userId });

    // Disable button, show spinner
    const $submitButton = $(this).find('button[type="submit"]');
    const originalButtonText = $submitButton.html();
    $submitButton
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...'
      );

    $.ajax({
      url: olHubTutorData.ajax_url,
      type: "POST",
      data: $.param(formData), // Convert array to URL-encoded string
      success: function (response) {
        if (response.success) {
          // Close the modal
          const responseModal = understrap.Modal.getInstance(
            document.getElementById("respondStudentRequestModal")
          );
          responseModal.hide();

          // Refresh the incoming requests section to show updated status
          console.log("Refreshing incoming requests after response.");
          loadTutorRequestSection(
            "load_tutor_incoming_requests",
            olHubTutorData?.nonces?.checkTutorIncomingNonce,
            "#tutor-incoming-requests-container"
          );
          // Optionally, also refresh notifications if status change triggers one
          // loadTutorRequestSection("load_tutor_notifications", olHubTutorData?.nonces?.checkTutorNotificationsNonce, "#tutor-notifications-container");
        } else {
          alert(
            "Error processing response: " +
              (response.data?.message || "Unknown error")
          );
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(
          "AJAX error responding to student request:",
          textStatus,
          errorThrown
        );
        alert("Could not process response due to a server error.");
      },
      complete: function () {
        // Re-enable button, restore text
        $submitButton.prop("disabled", false).html(originalButtonText);
      },
    });
  });

  // --- Tutor Responding to Student Alternative Times ---

  // Open modal for responding to alternatives
  $(document).on("click", ".respond-alternatives-btn", function () {
    const requestId = $(this).data("request-id");
    const studentName = $(this).data("student-name");
    const originalDateTime = $(this).data("original-datetime");
    const alternative1 = $(this).data("alt1");
    const alternative2 = $(this).data("alt2");
    const alternative3 = $(this).data("alt3");

    $("#alternativesResponseModalLabel").text(
      `Respond to ${studentName}'s Alternatives`
    );
    $("#alt-modal-request-id").val(requestId);
    $("#alt-modal-original-datetime").text(originalDateTime);

    // Populate radio buttons/labels dynamically
    const $optionsContainer = $("#alternative-options-container");
    $optionsContainer.empty(); // Clear previous options

    const alternatives = [alternative1, alternative2, alternative3].filter(
      Boolean
    ); // Filter out empty alternatives

    if (alternatives.length > 0) {
      alternatives.forEach((alt, index) => {
        const radioId = `alt-choice-${index + 1}`;
        $optionsContainer.append(`
                  <div class="form-check">
                      <input class="form-check-input" type="radio" name="alternative_choice" id="${radioId}" value="${
          index + 1
        }" ${index === 0 ? "checked" : ""}>
                      <label class="form-check-label" for="${radioId}">
                          ${alt}
                      </label>
                  </div>
              `);
      });
      // Add the 'None suitable' option
      $optionsContainer.append(`
              <div class="form-check">
                  <input class="form-check-input" type="radio" name="alternative_choice" id="alt-choice-none" value="none">
                  <label class="form-check-label" for="alt-choice-none">
                      None of these times are suitable
                  </label>
              </div>
          `);
      $("#alt-modal-tutor-comments-group").show();
    } else {
      $optionsContainer.html(
        '<p class="text-danger">No alternative times were provided by the student.</p>'
      );
      // Hide the submit button and comments if no alternatives? Or allow commenting?
      $("#alt-modal-tutor-comments-group").hide(); // Hide comments if no alternatives
    }

    $("#alt-modal-tutor-comments").val("");

    // Show the modal
    const alternativesModal = new understrap.Modal(
      document.getElementById("respondAlternativesModal")
    );
    alternativesModal.show();
  });

  // Handle alternative response form submission
  $("#submitAlternativesResponseForm").on("submit", function (e) {
    e.preventDefault();
    const formData = $(this).serializeArray();
    const nonce = olHubTutorData?.nonces?.respondToAlternativesNonce;

    if (!nonce) {
      console.error("Missing nonce for alternatives response.");
      alert("Security token missing. Cannot process response.");
      return;
    }

    // Add action and nonce
    formData.push({
      name: "action",
      value: "handle_tutor_alternatives_response",
    });
    formData.push({ name: "nonce", value: nonce });
    formData.push({ name: "tutor_id", value: olHubTutorData.userId });

    // Disable button, show spinner
    const $submitButton = $(this).find('button[type="submit"]');
    const originalButtonText = $submitButton.html();
    $submitButton
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...'
      );

    $.ajax({
      url: olHubTutorData.ajax_url,
      type: "POST",
      data: $.param(formData),
      success: function (response) {
        if (response.success) {
          // Close the modal
          const alternativesModal = understrap.Modal.getInstance(
            document.getElementById("respondAlternativesModal")
          );
          alternativesModal.hide();

          // Refresh the alternatives section
          console.log("Refreshing student alternatives after response.");
          loadTutorRequestSection(
            "load_tutor_student_alternatives",
            olHubTutorData?.nonces?.loadTutorStudentAlternativesNonce,
            "#tutor-student-alternatives-container"
          );
          // Optionally refresh notifications
          // loadTutorRequestSection("load_tutor_notifications", olHubTutorData?.nonces?.checkTutorNotificationsNonce, "#tutor-notifications-container");
        } else {
          alert(
            "Error processing response: " +
              (response.data?.message || "Unknown error")
          );
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(
          "AJAX error responding to alternatives:",
          textStatus,
          errorThrown
        );
        alert("Could not process response due to a server error.");
      },
      complete: function () {
        // Re-enable button, restore text
        $submitButton.prop("disabled", false).html(originalButtonText);
      },
    });
  });

  // --- End Event Handlers ---
}); // End jQuery(document).ready
