// Tutor Dashboard Requests Tab AJAX and Modal Handling
jQuery(document).ready(function ($) {
  // Function to load content into a container
  function loadTutorRequestSection(action, nonce, containerId) {
    var container = $(containerId);
    if (!container.length) {
      console.error("Container not found:", containerId);
      return; // Exit if container doesn't exist
    }
    container.html(
      '<div class="text-center p-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...</div>'
    );

    var attemptTooltipInitialization = function (targetContainer) {
      if (
        typeof bootstrap !== "undefined" &&
        typeof bootstrap.Tooltip !== "undefined"
      ) {
        try {
          var tooltipTriggerList = [].slice.call(
            targetContainer[0].querySelectorAll('[data-bs-toggle="tooltip"]')
          );
          tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            // Ensure we don't create multiple instances
            if (!bootstrap.Tooltip.getInstance(tooltipTriggerEl)) {
              new bootstrap.Tooltip(tooltipTriggerEl);
            }
          });
          console.log("Tooltips initialized for", targetContainer.attr("id"));
          return true; // Success
        } catch (e) {
          console.warn(
            "Error during tooltip initialization attempt for",
            targetContainer.attr("id"),
            ":",
            e
          );
          return false; // Failure
        }
      } else {
        console.warn(
          "Bootstrap Tooltip component not ready for",
          targetContainer.attr("id")
        );
        return false; // Failure
      }
    };

    // Use localized data if available
    const ajaxData = {
      action: action,
      nonce: nonce,
    };
    if (typeof olHubTutorData !== "undefined" && olHubTutorData.tutor_id) {
      ajaxData.tutor_id = olHubTutorData.tutor_id;
    }

    $.ajax({
      url: olHubTutorData.ajaxurl || ajaxurl, // Prefer localized, fallback to global
      type: "POST",
      data: ajaxData,
      success: function (response) {
        if (
          response.success &&
          response.data &&
          typeof response.data.html !== "undefined"
        ) {
          container.html(response.data.html);

          // Attempt to initialize tooltips immediately
          if (!attemptTooltipInitialization(container)) {
            // If failed, try again after a short delay
            console.log("Retrying tooltip initialization for", containerId);
            setTimeout(function () {
              attemptTooltipInitialization(container);
            }, 300);
          }

          $(document).trigger("tutorRequestSectionLoaded", {
            containerId: containerId,
            response: response.data,
          });
        } else {
          console.error(
            "AJAX Error for",
            action,
            ":",
            response.data?.message || "Unknown error or missing HTML"
          );
          container.html(
            '<div class="alert alert-danger">Error loading content: ' +
              (response.data?.message || "Unknown error") +
              "</div>"
          );
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(
          "AJAX Network/Server Error for",
          action,
          ":",
          textStatus,
          errorThrown
        );
        var errorMsg = "Failed to load content.";
        if (jqXHR.status === 403) {
          errorMsg =
            "Permission denied. Please ensure you are logged in and have the correct role."; // More specific
        } else if (jqXHR.status === 500) {
          errorMsg = "Server error encountered. Please check server logs."; // Suggest checking logs
        } else if (jqXHR.responseText) {
          console.error("Server Response: ", jqXHR.responseText);
        }
        container.html(
          '<div class="alert alert-danger">' +
            errorMsg +
            " (Action: " +
            action +
            ")</div>"
        ); // Include action in error
      },
    });
  }

  // --- Initial Loads (Keep delay) ---
  setTimeout(function () {
    // Use localized nonces directly
    var loadOutgoingNonce = olHubTutorData?.nonces?.loadTutorOutgoing;
    var loadIncomingNonce = olHubTutorData?.nonces?.checkTutorIncoming; // Matches PHP check_ajax_referer
    var notificationNonce = olHubTutorData?.nonces?.loadNotifications; // Matches PHP check_ajax_referer
    var loadAlternativesNonce = olHubTutorData?.nonces?.loadStudentAlternatives; // Matches PHP check_ajax_referer

    // Load Notifications
    if (notificationNonce) {
      loadTutorRequestSection(
        "load_tutor_notifications",
        notificationNonce,
        "#tutor-notifications-container"
      );
    } else {
      console.error("Tutor notification nonce not found in olHubTutorData.");
      $("#tutor-notifications-container").html(
        '<div class="alert alert-warning">Could not load notifications (security token missing).</div>'
      );
    }

    // Load Outgoing Requests
    if (loadOutgoingNonce) {
      loadTutorRequestSection(
        "load_tutor_outgoing_requests",
        loadOutgoingNonce,
        "#tutor-outgoing-requests-container"
      );
    } else {
      console.error(
        "Tutor load outgoing requests nonce not found in olHubTutorData."
      );
      $("#tutor-outgoing-requests-container").html(
        '<div class="alert alert-warning">Could not load outgoing requests (security token missing).</div>'
      );
    }

    // Load Incoming Requests
    if (loadIncomingNonce) {
      loadTutorRequestSection(
        "load_tutor_incoming_requests",
        loadIncomingNonce,
        "#tutor-incoming-requests-container"
      );
    } else {
      console.error(
        "Tutor load incoming requests nonce not found in olHubTutorData."
      );
      $("#tutor-incoming-requests-container").html(
        '<div class="alert alert-warning">Could not load incoming requests (security token missing).</div>'
      );
    }

    // Load Student Alternatives
    if (loadAlternativesNonce) {
      loadTutorRequestSection(
        "load_tutor_student_alternatives",
        loadAlternativesNonce,
        "#tutor-student-alternatives-container"
      );
    } else {
      console.error(
        "Tutor load student alternatives nonce not found in olHubTutorData."
      );
      $("#tutor-student-alternatives-container").html(
        '<div class="alert alert-warning">Could not load student alternatives (security token missing).</div>'
      );
    }
  }, 150); // Slightly increased delay, adjust if needed

  // --- Event Handlers (Delegated for AJAX-loaded content) ---

  // Handle Tutor deleting their own outgoing request
  $(document).on(
    "click",
    "#tutor-outgoing-requests-container .delete-tutor-request-btn",
    function () {
      var button = $(this);
      var requestId = button.data("request-id");
      // Get specific nonce from the button first, fallback to localized
      var nonce =
        button.data("nonce") || olHubTutorData?.nonces?.delete_request;

      if (!requestId || !nonce) {
        alert("Error: Could not identify request or security token.");
        console.error("Delete error: Missing request ID or nonce.", {
          requestId,
          nonce,
        });
        return;
      }

      if (
        !confirm("Are you sure you want to cancel this reschedule request?")
      ) {
        return;
      }

      button
        .prop("disabled", true)
        .html(
          '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
        );

      $.ajax({
        url: olHubTutorData.ajaxurl || ajaxurl,
        type: "POST",
        data: {
          action: "tutor_delete_outgoing_request", // Specific AJAX action
          request_id: requestId,
          nonce: nonce, // Send the specific or generic nonce
        },
        success: function (response) {
          if (response.success) {
            // Visually remove the row
            button.closest("tr").fadeOut(300, function () {
              $(this).remove();
            });
            // Refresh counts/notifications if necessary
            var notificationNonce =
              olHubTutorData?.nonces?.check_notifications ||
              $("#check_tutor_notifications_nonce_field").val();
            if (notificationNonce)
              loadTutorRequestSection(
                "load_tutor_notifications",
                notificationNonce,
                "#tutor-notifications-container"
              );
          } else {
            alert(
              "Error cancelling request: " +
                (response.data?.message || "Please try again.")
            );
            button
              .prop("disabled", false)
              .html(
                '<i class="fa-solid fa-trash-can"></i> <span class="d-none d-md-inline">Cancel</span>'
              ); // Restore button icon/text
          }
        },
        error: function () {
          alert(
            "An error occurred while cancelling the request. Please try again."
          );
          button
            .prop("disabled", false)
            .html(
              '<i class="fa-solid fa-trash-can"></i> <span class="d-none d-md-inline">Cancel</span>'
            ); // Restore button icon/text
        },
      });
    }
  );

  // Handle Tutor clicking Accept/Decline/Unavailable on an INCOMING student request
  $(document).on(
    "click",
    "#tutor-incoming-requests-container .handle-student-request-btn",
    function () {
      var button = $(this);
      var requestId = button.data("request-id");
      var actionType = button.data("action"); // 'accept', 'decline', 'unavailable'
      var handleNonce =
        olHubTutorData?.nonces?.handle_student_request ||
        $("#tutor_handle_student_request_nonce_field").val();
      var modal = $("#tutorHandleStudentRequestModal");
      var modalBody = $("#tutorHandleStudentRequestModalBody");
      var modalTitle = $("#tutorHandleStudentRequestModalLabel");

      if (
        !requestId ||
        !actionType ||
        !handleNonce ||
        !modal.length ||
        !modalBody.length ||
        !modalTitle.length
      ) {
        alert(
          "Error: Cannot process request (missing data or modal elements)."
        );
        console.error(
          "Handle student request error: Missing elements or data",
          {
            requestId,
            actionType,
            handleNonce,
            modalExists: modal.length > 0,
          }
        );
        return;
      }

      var actionText = actionType.charAt(0).toUpperCase() + actionType.slice(1);
      if (actionType === "unavailable") actionText = "Propose Alternatives";
      modalTitle.text("Respond to Request: " + actionText);
      modalBody.html(
        '<div class="text-center p-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading details...</div>'
      );

      // Show modal using Bootstrap 5 method
      var modalInstance = bootstrap.Modal.getOrCreateInstance(modal[0]);
      modalInstance.show();

      // AJAX call to get the modal content / form
      $.ajax({
        url: olHubTutorData.ajaxurl || ajaxurl,
        type: "POST",
        data: {
          action: "get_tutor_handle_request_modal_content",
          request_id: requestId,
          action_type: actionType,
          nonce: handleNonce,
        },
        success: function (response) {
          if (response.success && response.data && response.data.html) {
            modalBody.html(response.data.html);
            // Initialize components within the modal if necessary (e.g., datepickers)
            // Safely initialize tooltips within the modal body
            if (
              typeof bootstrap !== "undefined" &&
              typeof bootstrap.Tooltip !== "undefined"
            ) {
              var tooltipTriggerListModal = [].slice.call(
                modalBody[0].querySelectorAll('[data-bs-toggle="tooltip"]')
              );
              tooltipTriggerListModal.map(function (tooltipTriggerEl) {
                return bootstrap.Tooltip.getOrCreateInstance(tooltipTriggerEl);
              });
            }
          } else {
            modalBody.html(
              '<div class="alert alert-danger">' +
                (response.data?.message || "Error loading details.") +
                "</div>"
            );
          }
        },
        error: function () {
          modalBody.html(
            '<div class="alert alert-danger">Failed to load request details. Please close and try again.</div>'
          );
        },
      });
    }
  );

  // Handle form submissions INSIDE the 'Handle Student Request' modal
  $(document).on(
    "submit",
    "#tutorHandleStudentRequestModal form.ajax-modal-form",
    function (e) {
      e.preventDefault();
      var form = $(this);
      var submitButton = form.find('button[type="submit"]');
      var responseDiv = form.find(".ajax-modal-response");
      var modal = form.closest(".modal");
      var ajaxAction = form.data("action"); // e.g., 'tutor_accept_student_request'

      if (!ajaxAction) {
        console.error(
          "Modal form submit error: Missing data-action attribute on form."
        );
        responseDiv
          .html('<div class="alert alert-danger">Configuration error.</div>')
          .show();
        return;
      }

      // Store original button text/html
      var originalButtonContent = submitButton.html();
      submitButton
        .prop("disabled", true)
        .html(
          '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...'
        );
      responseDiv.hide().html("");

      var formData = form.serialize() + "&action=" + ajaxAction; // Add the WP AJAX action

      $.ajax({
        url: olHubTutorData.ajaxurl || ajaxurl,
        type: "POST",
        data: formData,
        success: function (response) {
          if (response.success) {
            responseDiv
              .html(
                '<div class="alert alert-success">' +
                  (response.data?.message || "Action successful!") +
                  "</div>"
              )
              .show();
            // Refresh the main lists
            var loadNonce =
              olHubTutorData?.nonces?.load_requests ||
              $("#tutor_load_requests_nonce_field").val();
            if (loadNonce) {
              loadTutorRequestSection(
                "load_tutor_incoming_requests",
                loadNonce,
                "#tutor-incoming-requests-container"
              );
              loadTutorRequestSection(
                "load_tutor_outgoing_requests",
                loadNonce,
                "#tutor-outgoing-requests-container"
              ); // Refresh outgoing if action affects it
              loadTutorRequestSection(
                "load_tutor_student_alternatives",
                loadNonce,
                "#tutor-student-alternatives-container"
              );
            }
            var notificationNonce =
              olHubTutorData?.nonces?.check_notifications ||
              $("#check_tutor_notifications_nonce_field").val();
            if (notificationNonce)
              loadTutorRequestSection(
                "load_tutor_notifications",
                notificationNonce,
                "#tutor-notifications-container"
              );

            // Close modal after delay
            setTimeout(function () {
              var modalInstance = bootstrap.Modal.getInstance(modal[0]);
              if (modalInstance) modalInstance.hide();
            }, 2000);
          } else {
            responseDiv
              .html(
                '<div class="alert alert-danger">Error: ' +
                  (response.data?.message || "Could not complete action.") +
                  "</div>"
              )
              .show();
            submitButton.prop("disabled", false).html(originalButtonContent); // Restore button
          }
        },
        error: function () {
          responseDiv
            .html(
              '<div class="alert alert-danger">AJAX error processing action.</div>'
            )
            .show();
          submitButton.prop("disabled", false).html(originalButtonContent); // Restore button
        },
      });
    }
  );

  // Handle Tutor clicking a button related to STUDENT ALTERNATIVES (within accordion)
  $(document).on(
    "click",
    "#tutor-student-alternatives-container .respond-to-student-alternative-btn",
    function () {
      var button = $(this);
      var altRequestId = button.data("alt-request-id");
      var actionType = button.data("action"); // 'accept', 'decline_all', 'cancel_original'
      var form = button.closest("form"); // Find the wrapping form
      // Get nonce from hidden field in the form, fallback to localized
      var nonce =
        form.find('input[name="tutor_respond_alt_nonce"]').val() ||
        olHubTutorData?.nonces?.respond_alternatives;
      var responseDiv = form.find(".ajax-modal-response"); // Find response div within the same form
      var selectedIndex = null;
      var ajaxAction = "";
      var confirmMsg = "";

      if (!altRequestId || !actionType || !nonce) {
        alert("Error: Missing data for handling alternatives.");
        console.error("Respond alternative error: Missing data", {
          altRequestId,
          actionType,
          nonce,
        });
        return;
      }

      if (actionType === "accept") {
        var selectedRadio = form.find(
          "input.tutor-accept-alternative-radio:checked"
        );
        if (!selectedRadio.length) {
          alert("Please select an alternative time to accept.");
          return;
        }
        selectedIndex = selectedRadio.data("selected-index");
        ajaxAction = "tutor_accept_student_alternative";
        confirmMsg = "Are you sure you want to accept this alternative time?";
      } else if (actionType === "decline_all") {
        ajaxAction = "tutor_decline_student_alternatives";
        confirmMsg =
          "Are you sure you want to decline all alternatives and cancel the original request?";
      } else if (actionType === "cancel_original") {
        ajaxAction = "tutor_cancel_original_from_unavailable";
        confirmMsg =
          "Are you sure you want to acknowledge this and cancel the original request?";
      } else {
        console.error("Unknown alternative action type:", actionType);
        return;
      }

      if (!confirm(confirmMsg)) {
        return;
      }

      // Store original button text/html
      var originalButtonContent = button.html();
      button
        .prop("disabled", true)
        .html(
          '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
        );
      responseDiv.hide().html("");

      var data = {
        action: ajaxAction,
        alt_request_id: altRequestId,
        nonce: nonce,
        action_type: actionType, // May not be strictly needed by handler, but good for context
      };
      if (selectedIndex !== null) {
        data.selected_index = selectedIndex;
      }

      $.ajax({
        url: olHubTutorData.ajaxurl || ajaxurl,
        type: "POST",
        data: data,
        success: function (response) {
          if (response.success) {
            responseDiv
              .html(
                '<div class="alert alert-success">' +
                  (response.data?.message || "Action successful!") +
                  "</div>"
              )
              .show();
            // Refresh relevant sections
            var loadNonce =
              olHubTutorData?.nonces?.load_requests ||
              $("#tutor_load_requests_nonce_field").val();
            if (loadNonce) {
              // Delay slightly to allow changes to propagate before reload
              setTimeout(function () {
                loadTutorRequestSection(
                  "load_tutor_outgoing_requests",
                  loadNonce,
                  "#tutor-outgoing-requests-container"
                );
                loadTutorRequestSection(
                  "load_tutor_student_alternatives",
                  loadNonce,
                  "#tutor-student-alternatives-container"
                );
                var notificationNonce =
                  olHubTutorData?.nonces?.check_notifications ||
                  $("#check_tutor_notifications_nonce_field").val();
                if (notificationNonce)
                  loadTutorRequestSection(
                    "load_tutor_notifications",
                    notificationNonce,
                    "#tutor-notifications-container"
                  );
              }, 500);
            }
          } else {
            responseDiv
              .html(
                '<div class="alert alert-danger">Error: ' +
                  (response.data?.message || "Could not complete action.") +
                  "</div>"
              )
              .show();
            button.prop("disabled", false).html(originalButtonContent); // Restore button
          }
        },
        error: function () {
          responseDiv
            .html(
              '<div class="alert alert-danger">AJAX error processing action.</div>'
            )
            .show();
          button.prop("disabled", false).html(originalButtonContent); // Restore button
        },
      });
    }
  );

  // --- Tutor Initiate Reschedule Modal Form ---
  // Add logic for tutor reschedule form lesson selection
  $("#tutor_reschedule_lesson_select").on("change", function () {
    var selectedOption = $(this).find("option:selected");
    var value = selectedOption.val();
    if (value) {
      var valueParts = value.split("|"); // date|time|student_id
      if (valueParts.length === 3) {
        $("#tutor_reschedule_original_date").val(valueParts[0]);
        $("#tutor_reschedule_original_time").val(valueParts[1]);
        // Update the hidden student ID field *AND* the visible select (for clarity, though hidden is used for submit)
        var studentIdFromLesson = valueParts[2];
        $("#tutor_reschedule_selected_student_id").val(studentIdFromLesson);
        $("#tutor_reschedule_student_select").val(studentIdFromLesson);
      } else {
        console.warn("Lesson option value format unexpected:", value);
        $("#tutor_reschedule_original_date").val("");
        $("#tutor_reschedule_original_time").val("");
        $("#tutor_reschedule_selected_student_id").val("");
      }
    } else {
      $("#tutor_reschedule_original_date").val("");
      $("#tutor_reschedule_original_time").val("");
      $("#tutor_reschedule_selected_student_id").val("");
    }
  });
  // Ensure student select also updates hidden field if changed manually (edge case)
  $("#tutor_reschedule_student_select").on("change", function () {
    var selectedStudentId = $(this).val();
    // Only update the hidden field if the lesson select is NOT set (otherwise lesson dictates)
    if (!$("#tutor_reschedule_lesson_select").val()) {
      $("#tutor_reschedule_selected_student_id").val(selectedStudentId);
    }
  });

  // Handle Tutor Initiate Reschedule Form Submission via AJAX
  $("#tutorRescheduleForm").on("submit", function (e) {
    e.preventDefault();

    var form = $(this);
    var submitButton = $("#submitTutorReschedule");
    var successMsg = $("#tutorRescheduleSuccessMessage");
    var errorMsg = $("#tutorRescheduleErrorMessage");

    // --- Basic Frontend Validation ---
    let isValid = true;
    const requiredFields = form.find("[required]");
    requiredFields.each(function () {
      if (!$(this).val()) {
        isValid = false;
        $(this).addClass("is-invalid"); // Highlight invalid fields
      } else {
        $(this).removeClass("is-invalid");
      }
    });
    // Ensure hidden student ID is set (should be by lesson select)
    var studentIdSelected = $("#tutor_reschedule_selected_student_id").val();
    if (!studentIdSelected) {
      isValid = false;
      $("#tutor_reschedule_student_select").addClass("is-invalid"); // Highlight student select
      console.warn(
        "Tutor Reschedule: No student ID selected/derived from lesson."
      );
    } else {
      $("#tutor_reschedule_student_select").removeClass("is-invalid");
    }

    if (!isValid) {
      errorMsg
        .html(
          '<p><i class="fas fa-exclamation-triangle"></i> Please fill in all required fields (marked with *).</p>'
        )
        .show();
      successMsg.hide();
      return; // Stop submission
    }
    // --- End Validation ---

    var originalButtonContent = submitButton.html();
    submitButton
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...'
      );
    successMsg.hide();
    errorMsg.hide();
    form.find(".is-invalid").removeClass("is-invalid"); // Clear validation classes

    // Prepare form data, ensuring the correct student ID is used
    var formData = form.serializeArray();
    // Remove student_id from the select if it exists, use the hidden one
    formData = formData.filter((item) => item.name !== "student_id");
    formData.push({ name: "student_id", value: studentIdSelected });
    // Add the AJAX action
    formData.push({ name: "action", value: "tutor_initiate_reschedule_ajax" });

    $.ajax({
      url: olHubTutorData.ajaxurl || ajaxurl,
      type: "POST",
      data: $.param(formData), // Serialize the array correctly for POST
      success: function (response) {
        if (response.success) {
          successMsg
            .html(
              '<p><i class="fas fa-check-circle"></i> ' +
                (response.data?.message || "Request submitted successfully.") +
                "</p>"
            )
            .show();
          form[0].reset();
          $("#tutor_reschedule_original_date").val("");
          $("#tutor_reschedule_original_time").val("");
          $("#tutor_reschedule_selected_student_id").val("");

          setTimeout(function () {
            var modalInstance = bootstrap.Modal.getInstance(
              $("#tutorRescheduleModal")[0]
            );
            if (modalInstance) modalInstance.hide();
            successMsg.hide(); // Hide message on close
          }, 2500);
          // Refresh relevant lists
          var loadNonce =
            olHubTutorData?.nonces?.load_requests ||
            $("#tutor_load_requests_nonce_field").val();
          if (loadNonce)
            loadTutorRequestSection(
              "load_tutor_outgoing_requests",
              loadNonce,
              "#tutor-outgoing-requests-container"
            );
          var notificationNonce =
            olHubTutorData?.nonces?.check_notifications ||
            $("#check_tutor_notifications_nonce_field").val();
          if (notificationNonce)
            loadTutorRequestSection(
              "load_tutor_notifications",
              notificationNonce,
              "#tutor-notifications-container"
            );
        } else {
          errorMsg
            .html(
              '<p><i class="fas fa-exclamation-triangle"></i> ' +
                (response.data?.message || "Error submitting request.") +
                "</p>"
            )
            .show();
        }
      },
      error: function () {
        errorMsg
          .html(
            '<p><i class="fas fa-exclamation-triangle"></i> An unexpected network or server error occurred. Please try again.</p>'
          )
          .show();
      },
      complete: function () {
        // Only restore button if error occurred
        if (errorMsg.is(":visible")) {
          submitButton.prop("disabled", false).html(originalButtonContent);
        } else {
          // Keep it disabled on success until modal closes
          submitButton.html(originalButtonContent); // Restore content but keep disabled
        }
      },
    });
  });

  // Add smooth scroll for notification links
  $(document).on("click", "a.scroll-to", function (e) {
    e.preventDefault();
    var targetId = $(this).attr("href"); // Get the target ID like #some-element
    var targetElement = $(targetId);
    if (targetElement.length) {
      $("html, body").animate(
        {
          scrollTop: targetElement.offset().top - 100, // Adjust offset as needed (e.g., for fixed headers)
        },
        500
      );
    }
  });
}); // end document ready
