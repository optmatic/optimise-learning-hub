document.addEventListener("DOMContentLoaded", function () {
  console.log("Tutor requests specific JS loaded.");

  // Wrap Bootstrap-dependent initializations in a short timeout
  setTimeout(() => {
    console.log("Initializing Bootstrap components...");
    // Initialize tooltips without delay
    const tooltipTriggerList = [].slice.call(
      document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
      // Ensure bootstrap is loaded before trying to use it
      if (typeof bootstrap !== "undefined") {
        const tooltip = new bootstrap.Tooltip(tooltipTriggerEl, {
          delay: { show: 0, hide: 0 }, // Ensure no delay
        });

        // Manually handle mouse events for instant display
        tooltipTriggerEl.addEventListener("mouseenter", function () {
          tooltip.show();
        });

        tooltipTriggerEl.addEventListener("mouseleave", function () {
          tooltip.hide();
        });
      } else {
        console.warn("Bootstrap not available for tooltips.");
      }
    });

    // Handle tab state using URL parameters (preferred over session storage)
    const params = new URLSearchParams(window.location.search);
    const activeTab = params.get("active_tab");

    if (activeTab) {
      const tabElement = document.querySelector(
        `.nav-link[href="#${activeTab}"]`
      ); // Target nav-link
      if (tabElement && typeof bootstrap !== "undefined") {
        try {
          const tab = new bootstrap.Tab(tabElement);
          tab.show();
          console.log(`Successfully activated tab: #${activeTab}`);
        } catch (e) {
          console.error(`Error activating tab #${activeTab}:`, e);
        }
      } else if (!tabElement) {
        console.warn(`Tab element not found for #${activeTab}`);
      } else {
        console.warn("Bootstrap not available for tab activation.");
      }
    }

    // Keep track of active tab across page loads by updating URL
    document.querySelectorAll('a[data-bs-toggle="tab"]').forEach((tabLink) => {
      tabLink.addEventListener("shown.bs.tab", function (e) {
        const id = e.target.getAttribute("href").substring(1);
        const newUrl = new URL(window.location);
        newUrl.searchParams.set("active_tab", id);
        history.replaceState(null, "", newUrl);
      });
    });
  }, 100); // Delay slightly (100ms)

  // Handle student selection - update hidden input with username
  const studentSelect = document.getElementById("student_select");
  const studentNameInput = document.getElementById("student_name");

  if (studentSelect && studentNameInput) {
    studentSelect.addEventListener("change", function () {
      const selectedOption = this.options[this.selectedIndex];
      if (selectedOption.value) {
        const username = selectedOption.getAttribute("data-username");
        studentNameInput.value = username;
      } else {
        studentNameInput.value = "";
      }
    });
  }

  // Submit form handling for reschedule request
  const submitTutorRescheduleBtn = document.getElementById(
    "submitTutorReschedule"
  );
  if (submitTutorRescheduleBtn) {
    submitTutorRescheduleBtn.addEventListener("click", function () {
      const form = document.getElementById("rescheduleRequestForm");

      // Check if student is selected
      if (!form.student_id.value) {
        document.getElementById("rescheduleRequestErrorMessage").style.display =
          "block";
        document
          .getElementById("rescheduleRequestErrorMessage")
          .querySelector("p").textContent = "Please select a student.";
        return;
      }

      // Check if date and time are filled
      if (!form.original_date.value || !form.original_time.value) {
        document.getElementById("rescheduleRequestErrorMessage").style.display =
          "block";
        document
          .getElementById("rescheduleRequestErrorMessage")
          .querySelector("p").textContent =
          "Please provide the lesson date and time to reschedule.";
        return;
      }

      // Check for reason
      if (!form.reason.value) {
        document.getElementById("rescheduleRequestErrorMessage").style.display =
          "block";
        document
          .getElementById("rescheduleRequestErrorMessage")
          .querySelector("p").textContent =
          "Please provide a reason for the reschedule request.";
        return;
      }

      // Check for at least one preferred time
      let hasPreferredTime = false;
      for (let i = 1; i <= 3; i++) {
        if (
          form["preferred_date_" + i].value &&
          form["preferred_time_" + i].value
        ) {
          hasPreferredTime = true;
          break;
        }
      }

      if (!hasPreferredTime) {
        document.getElementById("preferred-times-error").style.display =
          "block";
        return;
      } else {
        document.getElementById("preferred-times-error").style.display = "none";
      }

      // Hide error messages
      document.getElementById("rescheduleRequestErrorMessage").style.display =
        "none";

      // Add a hidden input for active tab
      const activeTabInput = document.createElement("input");
      activeTabInput.type = "hidden";
      activeTabInput.name = "active_tab";
      activeTabInput.value = "requests";
      form.appendChild(activeTabInput);

      // Store the current tab in session storage
      // sessionStorage.setItem('activeTab', 'requests'); // Let URL handle tab state

      // Submit the form
      form.submit();
    });
  }

  // Populate Edit Modal
  document.querySelectorAll(".edit-request-btn").forEach((button) => {
    button.addEventListener("click", function () {
      const modal = document.getElementById("editRescheduleRequestModal");
      const requestId = this.getAttribute("data-request-id");
      const studentName = this.getAttribute("data-student-name"); // Use the pre-formatted name
      const originalDateTimeFormatted = this.getAttribute(
        "data-original-datetime-formatted"
      ); // Use pre-formatted date/time
      const reason = this.getAttribute("data-reason");
      const preferredTimesJson = this.getAttribute("data-preferred-times");

      // Basic details
      modal.querySelector("#edit_request_id").value = requestId;
      modal.querySelector("#edit_student_name").value = studentName;
      modal.querySelector("#edit_original_datetime").value =
        originalDateTimeFormatted;
      modal.querySelector("#edit_reason").value = reason;

      // Clear previous preferred times
      for (let i = 1; i <= 3; i++) {
        modal.querySelector(`#edit_preferred_date_${i}`).value = "";
        modal.querySelector(`#edit_preferred_time_${i}`).value = "";
      }

      // Populate preferred times
      if (preferredTimesJson) {
        try {
          const preferredTimes = JSON.parse(preferredTimesJson);
          if (Array.isArray(preferredTimes)) {
            preferredTimes.forEach((time, index) => {
              if (index < 3) {
                // Max 3 slots
                const i = index + 1;
                if (time.date) {
                  modal.querySelector(`#edit_preferred_date_${i}`).value =
                    time.date;
                }
                if (time.time) {
                  modal.querySelector(`#edit_preferred_time_${i}`).value =
                    time.time;
                }
              }
            });
          }
        } catch (e) {
          console.error("Error parsing preferred times JSON:", e);
        }
      }
    });
  });

  // Handle Unavailable Modal Population
  document
    .querySelectorAll('button[data-bs-target="#unavailableModal"]')
    .forEach((button) => {
      button.addEventListener("click", function () {
        const modal = document.getElementById("unavailableModal");
        const requestId = this.getAttribute("data-request-id");
        const studentId = this.getAttribute("data-student-id");
        const studentName = this.getAttribute("data-student-name");
        const originalDate = this.getAttribute("data-original-date"); // Keep raw date
        const originalTime = this.getAttribute("data-original-time"); // Keep raw time

        // Format for display
        let formattedTime = "N/A";
        if (originalDate && originalTime) {
          try {
            // Attempt to format - adjust format string as needed
            const dateObj = new Date(`${originalDate}T${originalTime}`);
            formattedTime =
              dateObj.toLocaleDateString("en-US", {
                month: "short",
                day: "numeric",
                year: "numeric",
              }) +
              " at " +
              dateObj.toLocaleTimeString("en-US", {
                hour: "numeric",
                minute: "2-digit",
                hour12: true,
              });
          } catch (e) {
            console.error(
              "Error formatting date/time for unavailable modal:",
              e
            );
            formattedTime = `${originalDate} at ${originalTime}`; // Fallback
          }
        } else if (originalDate) {
          formattedTime = originalDate;
        }

        // Populate modal fields
        modal.querySelector("#unavailable_request_id").value = requestId;
        modal.querySelector("#unavailable_student_id").value = studentId;
        modal.querySelector("#unavailable_student_name").textContent =
          studentName;
        // Use the newly formatted time for display
        modal.querySelector("#unavailable_original_time").textContent =
          formattedTime;
      });
    });

  // Handle Reason Modal Population
  document
    .querySelectorAll('.reason-text[data-bs-target="#reasonModal"]')
    .forEach((span) => {
      span.addEventListener("click", function () {
        const reason = this.getAttribute("data-reason");
        document.getElementById("fullReasonText").textContent = reason
          ? reason
          : "No reason provided.";
      });
    });
});
