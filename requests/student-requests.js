// Student Dashboard Requests Tab AJAX and Modal Handling
document.addEventListener("DOMContentLoaded", function () {
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

      if (confirm("Are you sure you want to delete this request?")) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const formData = new FormData();
        formData.append("action", "delete_student_request");
        formData.append("request_id", requestId);
        formData.append("_ajax_nonce", nonce); // Use the correct nonce key

        // IMPORTANT: Use the localized ajaxurl variable
        if (
          typeof olHubStudentData === "undefined" ||
          !olHubStudentData.ajaxurl
        ) {
          console.error(
            "AJAX URL not defined. Script not localized correctly."
          );
          showGlobalAlert(
            "danger",
            "Configuration error. Please contact support."
          );
          button.disabled = false;
          button.innerHTML =
            '<i class="fas fa-trash"></i> <span class="d-none d-md-inline">Delete</span>';
          return;
        }

        fetch(olHubStudentData.ajaxurl, { method: "POST", body: formData })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              row.style.opacity = "0";
              setTimeout(() => row.remove(), 300);
              showGlobalAlert("success", data.message || "Request deleted.");
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
              "An error occurred while deleting the request."
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

  // --- AJAX Polling/Updating ---
  let initialLoadComplete = false;
  function checkStudentIncomingRequests() {
    // IMPORTANT: Use the localized nonce
    if (
      typeof olHubStudentData === "undefined" ||
      !olHubStudentData.checkNonce
    ) {
      console.error("Check nonce not defined. Script not localized correctly.");
      // Optionally display an error to the user, but only once
      if (!initialLoadComplete) {
        showGlobalAlert(
          "danger",
          "Configuration error preventing updates. Please contact support."
        );
        initialLoadComplete = true;
      }
      return;
    }
    const nonce = olHubStudentData.checkNonce;

    const formData = new FormData();
    formData.append("action", "check_student_incoming_requests");
    formData.append("nonce", nonce);

    // IMPORTANT: Use the localized ajaxurl variable
    if (typeof olHubStudentData === "undefined" || !olHubStudentData.ajaxurl) {
      console.error("AJAX URL not defined. Script not localized correctly.");
      // Optionally display an error to the user, but only once
      if (!initialLoadComplete) {
        showGlobalAlert(
          "danger",
          "Configuration error preventing updates. Please contact support."
        );
        initialLoadComplete = true;
      }
      return;
    }

    fetch(olHubStudentData.ajaxurl, { method: "POST", body: formData })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Update Notifications Box
          const notificationsDiv = document.getElementById(
            "studentRequestNotifications"
          );
          if (notificationsDiv) {
            notificationsDiv.innerHTML =
              data.data.notificationsHtml ||
              '<div class="alert alert-secondary">No current notifications.</div>';
          }

          // Update Incoming Tutor Requests Table
          const incomingTutorTableBody = document.querySelector(
            ".incoming-tutor-requests-table-body"
          );
          if (incomingTutorTableBody) {
            incomingTutorTableBody.innerHTML =
              data.data.incomingTutorRequestsHtml ||
              '<tr><td colspan="6">No incoming requests found.</td></tr>';
          }

          // Update Incoming Tutor Request Count Badge
          const incomingTutorBadge = document.querySelector(
            ".incoming-tutor-request-count"
          );
          if (incomingTutorBadge) {
            const count = data.data.pendingTutorRequestCount || 0;
            incomingTutorBadge.textContent = count;
            incomingTutorBadge.style.display =
              count > 0 ? "inline-block" : "none";
          }

          // Update Tutor Alternatives Count Badge
          const alternativesBadge = document.querySelector(
            ".tutor-alternatives-count"
          );
          if (alternativesBadge) {
            const count = data.data.pendingAlternativesCount || 0;
            alternativesBadge.textContent = count;
            alternativesBadge.style.display =
              count > 0 ? "inline-block" : "none";
          }

          // Update main Requests Tab Badge
          updateMainRequestsTabBadge(data.data.count || 0);

          // Refresh tooltips if new content was added
          initializeTooltips();

          initialLoadComplete = true;
        } else {
          console.error("Error checking student requests:", data.data?.message);
          if (!initialLoadComplete) {
            // Show error on initial load failure
            const notificationsDiv = document.getElementById(
              "studentRequestNotifications"
            );
            if (notificationsDiv)
              notificationsDiv.innerHTML =
                '<div class="alert alert-danger">Error loading notifications.</div>';
            const incomingTutorTableBody = document.querySelector(
              ".incoming-tutor-requests-table-body"
            );
            if (incomingTutorTableBody)
              incomingTutorTableBody.innerHTML =
                '<tr><td colspan="6">Error loading requests.</td></tr>';
            initialLoadComplete = true; // Prevent repeated errors shown
          }
        }
      })
      .catch((error) => {
        console.error("Fetch error checking student requests:", error);
        if (!initialLoadComplete) {
          const notificationsDiv = document.getElementById(
            "studentRequestNotifications"
          );
          if (notificationsDiv)
            notificationsDiv.innerHTML =
              '<div class="alert alert-danger">Network error loading notifications.</div>';
          const incomingTutorTableBody = document.querySelector(
            ".incoming-tutor-requests-table-body"
          );
          if (incomingTutorTableBody)
            incomingTutorTableBody.innerHTML =
              '<tr><td colspan="6">Network error loading requests.</td></tr>';
          initialLoadComplete = true;
        }
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

  // Initial Load & Interval
  checkStudentIncomingRequests();
  // Initial call to initialize tooltips
  // Use a slight delay to potentially allow Bootstrap more time to initialize fully
  initializeTooltips();
  setInterval(checkStudentIncomingRequests, 30000); // Poll every 30 seconds

  // Re-check when the requests tab becomes active (if using Bootstrap tabs)
  const requestsTabButton = document.getElementById("requests-tab-button");
  if (requestsTabButton) {
    requestsTabButton.addEventListener("shown.bs.tab", function (event) {
      checkStudentIncomingRequests();
      // Mark alternatives as viewed? Depends on UX preference
      // markTutorAlternativesAsViewed();
    });
  }

  // Helper function (example - implement based on your needs)
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
});
