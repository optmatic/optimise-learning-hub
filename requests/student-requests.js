// Student Dashboard Requests Tab AJAX and Modal Handling
document.addEventListener("DOMContentLoaded", function () {
  let studentAjaxData = null; // To store localized data when ready
  let initializationAttempts = 0;
  const MAX_INIT_ATTEMPTS = 10;
  let ajaxIntervalId = null; // To store the interval ID
  let initIntervalId = null; // Store interval ID for clearing

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

  // --- Core AJAX Functions ---

  // Function to check for requests and update badge AND content
  function checkStudentIncomingRequests() {
    // Check if studentAjaxData and necessary properties (including nested nonce) are defined
    if (
      !studentAjaxData ||
      !studentAjaxData.ajaxurl ||
      !studentAjaxData.student_id ||
      !studentAjaxData.nonces || // Check if nonces object exists
      !studentAjaxData.nonces.checkNonce // Check for the specific nonce inside the object
    ) {
      console.warn(
        "checkStudentIncomingRequests: AJAX data or nonce not ready. Skipping check."
      );
      return; // Exit if data isn't ready
    }

    // Show loading state in table? (Optional, could add spinner here if desired)
    // const container = document.getElementById("student-incoming-requests-tbody");
    // if (container) container.innerHTML = '<tr><td colspan="6" class="text-center"><span class="spinner-border spinner-border-sm"></span> Loading...</td></tr>';

    fetch(studentAjaxData.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "check_student_incoming_requests", // This action now returns HTML too
        student_id: studentAjaxData.student_id,
        nonce: studentAjaxData.nonces.checkNonce, // Use the correct nested nonce
      }),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(
            `Network response was not ok: ${response.statusText}`
          );
        }
        return response.json();
      })
      .then((data) => {
        if (data.success && data.data) {
          // Check for success and data object
          const count = data.data?.count || 0;
          updateMainRequestsTabBadge(count); // Update badge using helper

          // Update Notifications Area
          const notificationsDiv = document.getElementById(
            "studentRequestNotifications"
          ); // Assuming this ID exists
          if (
            notificationsDiv &&
            typeof data.data.notificationsHtml !== "undefined"
          ) {
            notificationsDiv.innerHTML = data.data.notificationsHtml;
          } else if (notificationsDiv) {
            console.warn(
              "Notification container found, but no notificationsHtml in response."
            );
            // Optionally clear or show default message
            notificationsDiv.innerHTML = ""; // Clear it perhaps
          }

          // Update Incoming Requests Table Body
          const incomingTbody = document.getElementById(
            "incoming-tutor-requests-table-body"
          ); // Corrected ID
          console.log("[AJAX Success] Found tbody element:", incomingTbody); // Log if element found
          if (
            incomingTbody &&
            typeof data.data.incomingTutorRequestsHtml !== "undefined"
          ) {
            console.log(
              "[AJAX Success] Received incomingTutorRequestsHtml:",
              data.data.incomingTutorRequestsHtml.substring(0, 100) + "..."
            ); // Log first 100 chars
            incomingTbody.innerHTML = data.data.incomingTutorRequestsHtml;
            console.log("[AJAX Success] Updated incomingTbody innerHTML."); // Log after update
          } else if (incomingTbody) {
            console.warn(
              "Incoming requests tbody found, but no incomingTutorRequestsHtml in response."
            );
            incomingTbody.innerHTML =
              '<tr><td colspan="6" class="text-center text-muted">Could not load requests content.</td></tr>'; // Show error in table
          }

          // Initialize tooltips for the newly added content
          initializeTooltips();
        } else {
          console.error(
            "Error checking/loading incoming requests:", // Updated error message context
            data.data?.message || "Unknown error or invalid response structure"
          );
          // Handle error in UI - maybe show error in table/notifications?
          const incomingTbodyError = document.getElementById(
            "incoming-tutor-requests-table-body"
          ); // Corrected ID
          if (incomingTbodyError)
            incomingTbodyError.innerHTML =
              '<tr><td colspan="6" class="text-center text-danger">Error loading requests.</td></tr>';
          const notificationsDivError = document.getElementById(
            "studentRequestNotifications"
          );
          if (notificationsDivError)
            notificationsDivError.innerHTML =
              '<div class="alert alert-danger">Error loading notifications.</div>';

          // Stop interval on application error? Only if it seems unrecoverable.
          // if (ajaxIntervalId) clearInterval(ajaxIntervalId);
          // ajaxIntervalId = null;
        }
      })
      .catch((error) => {
        console.error("Fetch error checking/loading incoming requests:", error);
        // Stop interval on network error to prevent flooding logs
        if (ajaxIntervalId) clearInterval(ajaxIntervalId);
        ajaxIntervalId = null;
        console.warn("Stopped checking for new requests due to fetch error.");
        updateMainRequestsTabBadge("!"); // Update badge to show error state
        // Show error in UI
        const incomingTbodyNetworkError = document.getElementById(
          "incoming-tutor-requests-table-body"
        ); // Corrected ID
        if (incomingTbodyNetworkError)
          incomingTbodyNetworkError.innerHTML =
            '<tr><td colspan="6" class="text-center text-danger">Network error loading requests.</td></tr>';
        const notificationsDivNetworkError = document.getElementById(
          "studentRequestNotifications"
        );
        if (notificationsDivNetworkError)
          notificationsDivNetworkError.innerHTML =
            '<div class="alert alert-danger">Network error loading notifications.</div>';
      });
  }

  // --- Helper Functions ---

  // Update the main 'Requests' tab badge
  function updateMainRequestsTabBadge(count) {
    const badgeElement = document.getElementById("student-requests-tab-badge");
    if (badgeElement) {
      if (count > 0) {
        badgeElement.textContent = count;
        badgeElement.style.display = "inline-block";
      } else if (count === "!") {
        // Handle error state
        badgeElement.textContent = "!";
        badgeElement.style.display = "inline-block";
        badgeElement.classList.add("bg-danger"); // Style as error
      } else {
        badgeElement.style.display = "none";
        badgeElement.classList.remove("bg-danger");
      }
    } else {
      // console.warn("Badge element #student-requests-tab-badge not found.");
    }
  }

  // Show global alert message
  function showGlobalAlert(type, message) {
    const container = document.getElementById("student-global-alert-container");
    if (!container) {
      console.error(
        "Alert container #student-global-alert-container not found."
      );
      return;
    }
    // Simple alert for now, replace with Bootstrap alert component if needed
    container.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
          ${message}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
  }

  // Initialize Bootstrap tooltips
  function initializeTooltips() {
    // Use understrap global if available
    const bootstrap = window.understrap || window.bootstrap;
    if (bootstrap && bootstrap.Tooltip) {
      const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
      );
      tooltipTriggerList.map(function (tooltipTriggerEl) {
        // Only initialize if one doesn't exist
        if (!bootstrap.Tooltip.getInstance(tooltipTriggerEl)) {
          return new bootstrap.Tooltip(tooltipTriggerEl);
        }
        return null;
      });
    } else {
      console.warn(
        "Could not initialize tooltips: Bootstrap/Understrap Tooltip component not found."
      );
    }
  }

  // Format date/time string
  function format_datetime(dateStr, timeStr, format = "default") {
    if (!dateStr || !timeStr) return "N/A";
    try {
      // Basic parsing assumes YYYY-MM-DD and HH:MM(:SS)
      const [year, month, day] = dateStr.split("-");
      const [hour, minute] = timeStr.split(":");
      const date = new Date(year, month - 1, day, hour, minute);
      if (isNaN(date)) return "Invalid Date";

      // Example formatting (adjust as needed)
      const options = {
        weekday: "short",
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
      };
      return date.toLocaleString("en-US", options);
    } catch (e) {
      console.error("Error formatting date/time:", e);
      return `${dateStr} ${timeStr}`;
    }
  }

  // Get tutor display name (placeholder)
  function get_tutor_display_name(login) {
    // In a real scenario, you might have this data localized or fetch it
    return login
      ? login.replace(/\./g, " ").replace(/\b\w/g, (l) => l.toUpperCase())
      : "Tutor";
  }

  // --- Initialization ---

  function initializeStudentScripts() {
    console.log("olHubStudentData found. Initializing...");

    if (!studentAjaxData) {
      console.error("Initialization Error: studentAjaxData is not assigned.");
      return;
    }

    // REMOVED initial call to loadStudentIncomingRequests();

    // Initial check for badge AND load content
    checkStudentIncomingRequests();

    // Set interval to periodically check for new requests (for badge update AND content refresh)
    if (!ajaxIntervalId) {
      ajaxIntervalId = setInterval(checkStudentIncomingRequests, 30000);
      console.log("Set interval for checking student requests and content."); // Updated log message
    }

    // Tooltips are now initialized within the success handler of checkStudentIncomingRequests
    // initializeTooltips(); // REMOVED from here

    // Add other event listeners specific to the student dashboard here

    console.log("Student dashboard scripts initialized.");
  }

  // Function to try initialization
  function tryInitialize() {
    initializationAttempts++;
    console.log(`Student Script Init Attempt: ${initializationAttempts}`);
    if (typeof olHubStudentData !== "undefined") {
      // Assign localized data *immediately* when found
      studentAjaxData = olHubStudentData;
      console.log("olHubStudentData found.", studentAjaxData); // Log the found data
      clearInterval(initIntervalId); // Stop polling
      initializeStudentScripts(); // Run the main initialization logic
    } else if (initializationAttempts >= MAX_INIT_ATTEMPTS) {
      clearInterval(initIntervalId); // Stop polling after max attempts
      console.error(
        "Failed to initialize student scripts: olHubStudentData not found after multiple attempts."
      );
      // Optionally display an error message to the user
      showGlobalAlert(
        "danger",
        "Error loading dashboard components. Please refresh the page."
      );
    }
  }

  // Start polling for olHubStudentData
  initIntervalId = setInterval(tryInitialize, 200);
});
