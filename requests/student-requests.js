// Student Dashboard Requests Tab AJAX and Modal Handling
document.addEventListener("DOMContentLoaded", function () {
  let studentAjaxData = null; // To store localized data when ready
  let initializationAttempts = 0;
  const MAX_INIT_ATTEMPTS = 10;
  let ajaxIntervalId = null; // To store the interval ID

  // --- Modal & Form Handling ---

  // Populate Edit Modal (Student Request)
  const editStudentModal = document.getElementById(
    "editStudentRescheduleRequestModal"
  );
  if (editStudentModal) {
    editStudentModal.addEventListener("show.bs.modal", function (event) {
      const button = event.relatedTarget;
      const requestId = button.dataset.requestId;
      const tutorName = button.dataset.tutorName;
      const originalDate = button.dataset.originalDate;
      const originalTime = button.dataset.originalTime;
      const reason = button.dataset.reason;
      let preferredTimes = [];
      try {
        preferredTimes = JSON.parse(button.dataset.preferredTimes || "[]");
      } catch (e) {
        console.error("Error parsing preferred times JSON: ", e);
      }

      const modal = this;
      modal.querySelector("#edit_request_id").value = requestId;
      modal.querySelector("#edit_tutor_name_display").value =
        get_tutor_display_name(tutorName); // Use helper if needed, or pass display name
      modal.querySelector("#edit_original_datetime_display").value =
        format_datetime(originalDate, originalTime);
      modal.querySelector("#edit_reason").value = reason;

      // Clear previous preferred times
      for (let i = 1; i <= 3; i++) {
        modal.querySelector(`#edit_preferred_date_${i}`).value = "";
        modal.querySelector(`#edit_preferred_time_${i}`).value = "";
      }
      // Populate preferred times
      if (Array.isArray(preferredTimes)) {
        preferredTimes.forEach((time, index) => {
          if (index < 3) {
            modal.querySelector(`#edit_preferred_date_${index + 1}`).value =
              time.date || "";
            modal.querySelector(`#edit_preferred_time_${index + 1}`).value =
              time.time || "";
          }
        });
      }
      // Hide success/error messages
      modal.querySelector("#editRescheduleSuccessMessage").style.display =
        "none";
      modal.querySelector("#editRescheduleErrorMessage").style.display = "none";
    });
  }

  // Handle Lesson Select Change in New Request Modal
  const newLessonSelect = document.getElementById("new_lesson_select");
  if (newLessonSelect) {
    newLessonSelect.addEventListener("change", function () {
      const selectedValue = this.value;
      if (selectedValue) {
        const [date, time] = selectedValue.split("|");
        document.getElementById("new_original_date").value = date;
        document.getElementById("new_original_time").value = time;
      } else {
        document.getElementById("new_original_date").value = "";
        document.getElementById("new_original_time").value = "";
      }
    });
  }

  // Reset New Request Modal on close
  const newStudentModal = document.getElementById(
    "newStudentRescheduleRequestModal"
  );
  if (newStudentModal) {
    newStudentModal.addEventListener("hidden.bs.modal", function () {
      const form = document.getElementById("newStudentRescheduleRequestForm");
      if (form) form.reset();
      document.getElementById("new_original_date").value = ""; // Clear hidden fields too
      document.getElementById("new_original_time").value = "";
      document.getElementById(
        "newRescheduleRequestSuccessMessage"
      ).style.display = "none";
      document.getElementById(
        "newRescheduleRequestErrorMessage"
      ).style.display = "none";
      document.getElementById("new_preferred-times-error").style.display =
        "none";
      const submitBtn = document.getElementById("submitNewStudentReschedule");
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = "Submit Request";
      }
    });
  }

  // Populate Reason Modal
  const reasonModal = document.getElementById("reasonModal");
  if (reasonModal) {
    reasonModal.addEventListener("show.bs.modal", function (event) {
      const button = event.relatedTarget; // Span that triggered the modal
      const reason = button.dataset.reason || "No reason provided.";
      const modalBodyP = reasonModal.querySelector("#fullReasonText");
      modalBodyP.textContent = reason;
    });
  }

  // --- Form Submission (using standard POST, handled by post-handlers.php) ---
  // Add validation before submitting the forms

  // New Student Request Form Validation
  const newStudentForm = document.getElementById(
    "newStudentRescheduleRequestForm"
  );
  if (newStudentForm) {
    const submitNewBtn = document.getElementById("submitNewStudentReschedule");
    submitNewBtn?.addEventListener("click", function (e) {
      // Basic check for required fields
      let isValid = true;
      const requiredFields = newStudentForm.querySelectorAll("[required]");
      requiredFields.forEach((field) => {
        if (!field.value) isValid = false;
      });

      // Check at least one preferred time
      let hasPreferred = false;
      for (let i = 1; i <= 3; i++) {
        if (
          newStudentForm.querySelector(`#new_preferred_date_${i}`).value &&
          newStudentForm.querySelector(`#new_preferred_time_${i}`).value
        ) {
          hasPreferred = true;
          break;
        }
      }
      if (!hasPreferred) isValid = false;

      if (!isValid) {
        e.preventDefault(); // Stop submission
        document.getElementById(
          "newRescheduleRequestErrorMessage"
        ).style.display = "block";
        document.getElementById("new_preferred-times-error").style.display =
          !hasPreferred ? "block" : "none";
      } else {
        document.getElementById(
          "newRescheduleRequestErrorMessage"
        ).style.display = "none";
        document.getElementById("new_preferred-times-error").style.display =
          "none";
        submitNewBtn.disabled = true; // Prevent double submit
        submitNewBtn.textContent = "Submitting...";
        newStudentForm.submit(); // Allow submission
      }
    });
  }

  // Edit Student Request Form Validation
  const editStudentForm = document.getElementById(
    "editStudentRescheduleRequestForm"
  );
  if (editStudentForm) {
    const submitEditBtn = document.getElementById("updateStudentReschedule");
    submitEditBtn?.addEventListener("click", function (e) {
      let isValid = true;
      if (!editStudentForm.querySelector("#edit_reason").value) isValid = false;

      let hasPreferred = false;
      for (let i = 1; i <= 3; i++) {
        if (
          editStudentForm.querySelector(`#edit_preferred_date_${i}`).value &&
          editStudentForm.querySelector(`#edit_preferred_time_${i}`).value
        ) {
          hasPreferred = true;
          break;
        }
      }
      if (!hasPreferred) isValid = false;

      if (!isValid) {
        e.preventDefault();
        document.getElementById("editRescheduleErrorMessage").style.display =
          "block";
        document.getElementById("edit_preferred-times-error").style.display =
          !hasPreferred ? "block" : "none";
      } else {
        document.getElementById("editRescheduleErrorMessage").style.display =
          "none";
        document.getElementById("edit_preferred-times-error").style.display =
          "none";
        submitEditBtn.disabled = true;
        submitEditBtn.textContent = "Updating...";
        editStudentForm.submit();
      }
    });
  }

  // --- AJAX Actions (Delete, Mark Unavailable, etc.) ---

  // AJAX Delete Student Request
  document.body.addEventListener("click", function (event) {
    if (event.target.matches(".delete-student-request-btn")) {
      event.preventDefault();
      const button = event.target;
      const requestId = button.dataset.requestId;
      const nonce = button.dataset.nonce; // Get nonce from button data
      const row = button.closest("tr");

      // Check if AJAX data is ready
      if (!studentAjaxData || !studentAjaxData.ajaxurl) {
        console.error("AJAX Delete Error: Student AJAX data not initialized.");
        showGlobalAlert(
          "danger",
          "Cannot perform action: Page setup incomplete. Please refresh."
        );
        return;
      }

      if (confirm("Are you sure you want to delete this request?")) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const formData = new FormData();
        formData.append("action", "delete_student_request");
        formData.append("request_id", requestId);
        // Nonce needs to be passed correctly for check_ajax_referer in PHP
        // The localized nonce array likely holds this if generated dynamically
        // Let's assume a specific nonce pattern or add it to localization
        // For now, using the button's data-nonce which might be 'delete_student_request_{id}'
        // Note: check_ajax_referer expects the nonce value itself, not the key.
        // The PHP handler `delete_student_request_ajax` currently uses `check_ajax_referer('delete_student_request_nonce', 'nonce');`
        // This expects a nonce created with the action 'delete_student_request_nonce', not delete_student_request_{id}
        // We need to adjust either the PHP nonce creation/check or how JS gets/sends it.
        // Let's assume the localized data HAS the correct nonce for this action
        const deleteNonce = studentAjaxData.nonces?.deleteStudentRequest; // Check if this exists
        if (!deleteNonce) {
          console.error(
            "AJAX Delete Error: Delete nonce not found in localized data."
          );
          showGlobalAlert(
            "danger",
            "Security token error. Cannot delete request."
          );
          button.disabled = false;
          button.innerHTML =
            '<i class="fas fa-trash"></i> <span class="d-none d-md-inline">Delete</span>';
          return;
        }
        formData.append("nonce", deleteNonce); // Send the correct nonce value

        fetch(studentAjaxData.ajaxurl, { method: "POST", body: formData })
          .then((response) => {
            if (!response.ok) {
              // Handle HTTP errors (like 403 Forbidden from nonce failure)
              throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
          })
          .then((data) => {
            if (data.success) {
              row.style.opacity = "0";
              setTimeout(() => row.remove(), 300);
              showGlobalAlert(
                "success",
                data.data?.message || "Request deleted."
              );
            } else {
              showGlobalAlert(
                "danger",
                data.data?.message || "Failed to delete request."
              );
              button.disabled = false;
              button.innerHTML =
                '<i class="fas fa-trash"></i> <span class="d-none d-md-inline">Delete</span>';
            }
          })
          .catch((error) => {
            showGlobalAlert(
              "danger",
              "An error occurred while deleting the request. Check console for details."
            );
            console.error("Error deleting request:", error);
            button.disabled = false;
            button.innerHTML =
              '<i class="fas fa-trash"></i> <span class="d-none d-md-inline">Delete</span>';
          });
      }
    }

    // Handle "Unavailable for All Options" button click
    if (event.target.matches(".unavailable-all-btn")) {
      event.preventDefault();
      const button = event.target;
      const requestId = button.dataset.requestId;
      const nonce = button.dataset.nonce;

      if (
        confirm(
          "Are you sure you are unavailable for all the proposed alternative times?"
        )
      ) {
        // This action is handled by a standard POST request via post-handlers.php
        // Create a temporary form and submit it
        const tempForm = document.createElement("form");
        tempForm.method = "post";
        tempForm.style.display = "none";

        const nonceInput = document.createElement("input");
        nonceInput.type = "hidden";
        nonceInput.name = "_wpnonce"; // Use correct nonce name for check_admin_referer
        nonceInput.value = nonce;
        tempForm.appendChild(nonceInput);

        const actionInput = document.createElement("input");
        actionInput.type = "hidden";
        actionInput.name = "unavailable_all";
        actionInput.value = "1";
        tempForm.appendChild(actionInput);

        const requestIdInput = document.createElement("input");
        requestIdInput.type = "hidden";
        requestIdInput.name = "request_id";
        requestIdInput.value = requestId;
        tempForm.appendChild(requestIdInput);

        document.body.appendChild(tempForm);
        tempForm.submit();
      }
    }

    // Add similar handlers for other AJAX actions if needed (e.g., archive)
    // Ensure they use the correct action names and nonces defined in ajax-handlers.php
  }); // End body event listener

  // --- AJAX Polling/Updating --- Function Definitions ---

  function checkStudentIncomingRequests() {
    // Ensure data is initialized
    if (
      !studentAjaxData ||
      !studentAjaxData.ajaxurl ||
      !studentAjaxData.nonces ||
      !studentAjaxData.nonces.checkStudentIncoming // Use the correct nonce handle
    ) {
      console.warn(
        "checkStudentIncomingRequests: AJAX data or nonce not ready. Skipping check."
      );
      // Stop interval if it keeps failing after init should be done?
      // Consider adding logic here if needed.
      return;
    }

    const nonce = studentAjaxData.nonces.checkStudentIncoming; // Get nonce from stored data

    const formData = new FormData();
    formData.append("action", "check_student_incoming_requests");
    formData.append("nonce", nonce); // Send the nonce value

    fetch(studentAjaxData.ajaxurl, { method: "POST", body: formData })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        // --- Debug: Log the entire received data object ---
        console.log(
          "Received AJAX data for checkStudentIncomingRequests:",
          data
        );
        // --------------------------------------------------

        if (data.success && data.data) {
          // Update Notifications Box
          const notificationsDiv = document.getElementById(
            "studentRequestNotifications"
          );
          if (notificationsDiv) {
            // Check if specific notification HTML is provided, otherwise use counts
            if (typeof data.data.notificationsHtml !== "undefined") {
              notificationsDiv.innerHTML =
                data.data.notificationsHtml ||
                '<div class="alert alert-secondary">No current notifications.</div>';
            } else {
              // Fallback or alternative display using counts if HTML isn't sent
              console.warn("Notification HTML not provided in AJAX response.");
              // Example: Update a simple count or message
            }
          }

          // Update Incoming Tutor Requests Table
          const incomingTutorTableBody = document.querySelector(
            ".incoming-tutor-requests-table-body"
          );
          if (incomingTutorTableBody) {
            // Check if specific table HTML is provided
            if (typeof data.data.incomingTutorRequestsHtml !== "undefined") {
              incomingTutorTableBody.innerHTML =
                data.data.incomingTutorRequestsHtml ||
                '<tr><td colspan="6">No incoming requests found.</td></tr>';
            } else {
              console.warn("Incoming requests HTML not provided.");
            }
          }

          // Update Count Badges (using specific counts from data)
          const incomingTutorBadge = document.querySelector(
            ".incoming-tutor-request-count"
          );
          if (incomingTutorBadge) {
            const count = data.data.pendingTutorRequestCount || 0;
            incomingTutorBadge.textContent = count;
            incomingTutorBadge.style.display =
              count > 0 ? "inline-block" : "none";
          }

          const alternativesBadge = document.querySelector(
            ".tutor-alternatives-count"
          );
          if (alternativesBadge) {
            const count = data.data.pendingAlternativesCount || 0; // Use correct property name if different
            alternativesBadge.textContent = count;
            alternativesBadge.style.display =
              count > 0 ? "inline-block" : "none";
          }

          // Update main Requests Tab Badge (using total count)
          updateMainRequestsTabBadge(data.data.count || 0);

          // Refresh tooltips if new content was added
          initializeTooltips();
        } else {
          console.error(
            "Error checking student requests:",
            data.data?.message || "Invalid response structure"
          );
          // Potentially stop interval if errors persist?
          // clearInterval(ajaxIntervalId);
        }
      })
      .catch((error) => {
        console.error("Fetch error checking student requests:", error);
        // Potentially stop interval if network errors persist?
        // clearInterval(ajaxIntervalId);
      });
  }

  function updateMainRequestsTabBadge(count) {
    const requestsTabButton = document.getElementById("requests-tab-button");
    if (!requestsTabButton) return;
    let badge = requestsTabButton.querySelector(".notification-badge");

    if (count > 0) {
      if (!badge) {
        badge = document.createElement("span");
        badge.className =
          "badge rounded-pill bg-danger notification-badge ms-1"; // Added ms-1
        requestsTabButton.appendChild(badge);
      }
      badge.textContent = count;
      badge.style.display = "inline-block";
    } else if (badge) {
      badge.style.display = "none";
    }
  }

  // Function to show dismissible alerts at the top of the content area
  function showGlobalAlert(type, message) {
    const container = document.querySelector(".student-requests-content"); // Target the main content div
    if (!container) return;

    const alertDiv = document.createElement("div");
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = "alert";
    alertDiv.innerHTML = `
             ${message}
             <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         `;

    // Prepend the alert to the container
    container.insertBefore(alertDiv, container.firstChild);

    // Optional: Auto-dismiss after some time
    // setTimeout(() => {
    //     const alertInstance = bootstrap.Alert.getOrCreateInstance(alertDiv);
    //     if (alertInstance) alertInstance.close();
    // }, 5000);
  }

  // Initialize Tooltips
  function initializeTooltips() {
    const tooltipTriggerList = Array.from(
      document.querySelectorAll("[data-bs-toggle='tooltip']")
    );
    tooltipTriggerList.map((tooltipTriggerEl) => {
      // Check if a tooltip instance already exists
      if (!bootstrap.Tooltip.getInstance(tooltipTriggerEl)) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
      }
      return null;
    });
  }

  // Helper functions (keep as is)
  function format_datetime(dateStr, timeStr, format = "default") {
    if (!dateStr || !timeStr) return "N/A";
    try {
      const dt = new Date(`${dateStr} ${timeStr}`);
      if (isNaN(dt)) return "Invalid Date";
      if (format === "default") {
        return dt.toLocaleString("en-US", {
          dateStyle: "medium",
          timeStyle: "short",
        });
      }
      // Add other formats if needed
      return dt.toISOString(); // Fallback
    } catch (e) {
      return "Invalid Date";
    }
  }

  function get_tutor_display_name(login) {
    // In JS, we don't have direct access to WP user meta.
    // Pass the display name via data attributes instead, or make another AJAX call if absolutely necessary.
    return login; // Simple fallback
  }

  // --- Initialization Function ---
  function initializeStudentScripts() {
    initializationAttempts++;
    console.log(`Student Script Init Attempt: ${initializationAttempts}`);

    if (typeof window.olHubStudentData !== "undefined") {
      console.log("olHubStudentData found. Initializing...");
      studentAjaxData = window.olHubStudentData;

      // Perform initial check
      checkStudentIncomingRequests();

      // Start periodic checks (only if interval isn't already running)
      if (!ajaxIntervalId) {
        ajaxIntervalId = setInterval(checkStudentIncomingRequests, 30000); // Poll every 30 seconds
      }

      // Initialize tooltips after first data load might be better?
      initializeTooltips();

      // Re-check when the requests tab becomes active
      const requestsTabButton = document.getElementById("requests-tab-button");
      if (requestsTabButton) {
        // Ensure listener is only added once
        requestsTabButton.removeEventListener(
          "shown.bs.tab",
          checkStudentIncomingRequests
        );
        requestsTabButton.addEventListener(
          "shown.bs.tab",
          checkStudentIncomingRequests
        );
      }
    } else if (initializationAttempts < MAX_INIT_ATTEMPTS) {
      console.log("olHubStudentData not found. Retrying initialization...");
      setTimeout(initializeStudentScripts, 200); // Wait and retry
    } else {
      console.error(
        `Failed to initialize student scripts after ${MAX_INIT_ATTEMPTS} attempts. Localized data (olHubStudentData) not available.`
      );
      showGlobalAlert(
        "danger",
        "Error initializing page components. AJAX features may not work. Please refresh or contact support."
      );
    }
  }

  // Start the initialization process
  initializeStudentScripts();
});
