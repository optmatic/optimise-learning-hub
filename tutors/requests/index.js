document.addEventListener("DOMContentLoaded", function () {
  // Existing tooltip initialization code
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.forEach(function (tooltipTriggerEl) {
    const tooltip = new bootstrap.Tooltip(tooltipTriggerEl, {
      delay: { show: 0, hide: 0 },
    });

    tooltipTriggerEl.addEventListener("mouseenter", function () {
      tooltip.show();
    });

    tooltipTriggerEl.addEventListener("mouseleave", function () {
      tooltip.hide();
    });
  });

  // Helper function to format date and time
  function formatDateTime(date, time) {
    if (!date || !time) return "N/A";
    const dateObj = new Date(`${date}T${time}`);
    return dateObj.toLocaleString("en-US", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
      hour12: true,
    });
  }

  // Handle student selection to populate hidden field
  const studentSelect = document.getElementById("student_select");
  const studentIdInput = document.getElementById("student_id");
  if (studentSelect && studentIdInput) {
    studentSelect.addEventListener("change", function () {
      const selectedOption = this.options[this.selectedIndex];
      if (selectedOption) {
        studentIdInput.value = selectedOption.getAttribute("data-id") || "";
      }
    });
  }

  // Set minimum date for date pickers to today
  const today = new Date().toISOString().split("T")[0];
  const datePickers = document.querySelectorAll('input[type="date"]');
  datePickers.forEach((picker) => {
    picker.min = today;
  });

  // Handle form submission
  const submitTutorRescheduleBtn = document.getElementById(
    "submitTutorReschedule"
  );
  const rescheduleRequestForm = document.getElementById(
    "rescheduleRequestForm"
  );

  if (submitTutorRescheduleBtn && rescheduleRequestForm) {
    submitTutorRescheduleBtn.addEventListener("click", function (e) {
      e.preventDefault();

      // Check if button is already disabled (preventing duplicate submissions)
      if (this.disabled) {
        return;
      }

      // Reset error messages
      document.getElementById("rescheduleRequestErrorMessage").style.display =
        "none";
      document.getElementById("preferred-times-error").style.display = "none";

      // Get form elements
      const student = document.getElementById("student_select").value;
      const originalDate = document.getElementById("original_date").value;
      const originalTime = document.getElementById("original_time").value;
      const reason = document.getElementById("reason").value;

      // Check required fields
      if (!student || !originalDate || !originalTime || !reason) {
        document.getElementById("rescheduleRequestErrorMessage").style.display =
          "block";
        return;
      }

      // Validate preferred times (at least one required)
      const preferredDates = document.querySelectorAll(
        "#preferred-times-container .preferred-date"
      );
      const preferredTimes = document.querySelectorAll(
        "#preferred-times-container .preferred-time"
      );
      let hasPreferredTime = false;

      for (let i = 0; i < preferredDates.length; i++) {
        if (preferredDates[i].value && preferredTimes[i].value) {
          hasPreferredTime = true;
          break;
        }
      }

      if (!hasPreferredTime) {
        document.getElementById("preferred-times-error").style.display =
          "block";
        return;
      }

      // Disable form elements during submission
      const formElements = rescheduleRequestForm.querySelectorAll(
        "input, select, textarea, button"
      );
      formElements.forEach((el) => (el.disabled = true));
      submitTutorRescheduleBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';

      // Submit the form
      rescheduleRequestForm.submit();
    });
  }

  // Handle the Unavailable button click
  document
    .querySelectorAll('.btn-warning[data-bs-target="#unavailableModal"]')
    .forEach((btn) => {
      btn.addEventListener("click", function () {
        // Gather all data attributes
        const studentName = this.getAttribute("data-student-name");
        const originalDate = this.getAttribute("data-original-date");
        const originalTime = this.getAttribute("data-original-time");
        const reason = this.getAttribute("data-reason");
        const preferredTimesAttr = this.getAttribute("data-preferred-times");

        // Get modal elements
        const studentNameEl = document.getElementById(
          "unavailable_student_name"
        );
        const originalLessonTimeEl = document.getElementById(
          "unavailable_original_time"
        );
        const preferredTimesListEl = document.getElementById(
          "preferred_times_list"
        );
        const studentReasonEl = document.getElementById("unavailable_reason");

        // Set student name - ensure it's not empty
        if (studentNameEl) {
          studentNameEl.textContent = studentName || "N/A";
        }

        // Format and set original lesson time
        if (originalLessonTimeEl) {
          const formattedOriginalTime = formatDateTime(
            originalDate,
            originalTime
          );
          originalLessonTimeEl.textContent = formattedOriginalTime;
        }

        // Set student's reason
        if (studentReasonEl) {
          studentReasonEl.textContent = reason || "No reason provided";
        }

        // Populate preferred times list
        if (preferredTimesListEl) {
          preferredTimesListEl.innerHTML = ""; // Clear previous entries

          try {
            const preferredTimes = JSON.parse(preferredTimesAttr);

            if (preferredTimes && preferredTimes.length > 0) {
              preferredTimes.forEach((time, index) => {
                const li = document.createElement("li");
                li.className = "list-group-item";
                const formattedTime = formatDateTime(time.date, time.time);
                li.textContent = `Option ${index + 1}: ${formattedTime}`;
                preferredTimesListEl.appendChild(li);
              });
            } else {
              const li = document.createElement("li");
              li.className = "list-group-item text-muted";
              li.textContent = "No preferred times provided";
              preferredTimesListEl.appendChild(li);
            }
          } catch (error) {
            console.error("Error parsing preferred times:", error);
            const li = document.createElement("li");
            li.className = "list-group-item text-danger";
            li.textContent = "Error loading preferred times";
            preferredTimesListEl.appendChild(li);
          }
        }
      });
    });

  // Reason Modal Handling
  const reasonModal = document.getElementById("reasonModal");
  const fullReasonTextEl = document.getElementById("fullReasonText");

  // Add click event to all reason text spans
  document.querySelectorAll(".reason-text").forEach((reasonSpan) => {
    reasonSpan.addEventListener("click", function () {
      const fullReason = this.getAttribute("data-reason");

      if (fullReasonTextEl) {
        fullReasonTextEl.textContent = fullReason;
      }
    });
  });

  // Development Mode: Generate sample data
  const devModeCheckbox = document.getElementById("devModeCheckbox");
  if (devModeCheckbox) {
    devModeCheckbox.addEventListener("change", function () {
      if (this.checked) {
        console.log("Development mode enabled - filling form with sample data");

        // Generate random dates and times
        const today = new Date();
        const nextWeek = new Date(today);
        nextWeek.setDate(today.getDate() + 7);

        // Random student selection
        const studentSelect = document.getElementById("student_select");
        const studentNameInput = document.getElementById("student_name");
        if (studentSelect && studentSelect.options.length > 1) {
          console.log("Setting random student");
          const randomIndex =
            Math.floor(Math.random() * (studentSelect.options.length - 1)) + 1;
          studentSelect.selectedIndex = randomIndex;

          // Get the selected option's data-username attribute
          const selectedOption = studentSelect.options[randomIndex];
          if (selectedOption && studentNameInput) {
            studentNameInput.value =
              selectedOption.getAttribute("data-username") || "";
          }

          // Trigger both change and input events
          studentSelect.dispatchEvent(new Event("change", { bubbles: true }));
          studentSelect.dispatchEvent(new Event("input", { bubbles: true }));
        }

        // Random lesson date (next week)
        const lessonDate = document.getElementById("lesson_date");
        if (lessonDate) {
          console.log("Setting random lesson date");
          const randomDate = new Date(nextWeek);
          randomDate.setDate(
            nextWeek.getDate() + Math.floor(Math.random() * 7)
          );
          lessonDate.value = randomDate.toISOString().split("T")[0];
          lessonDate.dispatchEvent(new Event("change", { bubbles: true }));
          lessonDate.dispatchEvent(new Event("input", { bubbles: true }));
        }

        // Random lesson time
        const lessonTime = document.getElementById("lesson_time");
        if (lessonTime) {
          console.log("Setting random lesson time");
          const hours = Math.floor(Math.random() * 12) + 9; // 9 AM to 8 PM
          const minutes = Math.random() < 0.5 ? "00" : "30";
          lessonTime.value = `${hours.toString().padStart(2, "0")}:${minutes}`;
          lessonTime.dispatchEvent(new Event("change", { bubbles: true }));
          lessonTime.dispatchEvent(new Event("input", { bubbles: true }));
        }

        // Random reason
        const reason = document.getElementById("reason");
        if (reason) {
          console.log("Setting random reason");
          const reasons = [
            "I have a conflicting appointment that came up",
            "I need to attend an important family event",
            "I'm feeling under the weather and need to reschedule",
            "I have an unexpected work commitment",
            "I need to reschedule due to an urgent matter",
            "I have a scheduling conflict with another student",
            "There's an important professional development event I need to attend",
            "I need to accommodate an emergency situation",
          ];
          reason.value = reasons[Math.floor(Math.random() * reasons.length)];
          reason.dispatchEvent(new Event("change", { bubbles: true }));
          reason.dispatchEvent(new Event("input", { bubbles: true }));
        }

        // Random preferred times
        console.log("Setting random preferred times");
        const usedDates = new Set(); // To ensure unique dates

        for (let i = 1; i <= 3; i++) {
          const dateInput = document.getElementById(`preferred_date_${i}`);
          const timeInput = document.getElementById(`preferred_time_${i}`);

          if (dateInput && timeInput) {
            let randomDate;
            let dateStr;

            // Keep generating dates until we get a unique one
            do {
              randomDate = new Date(nextWeek);
              randomDate.setDate(
                nextWeek.getDate() + Math.floor(Math.random() * 14) + 1
              );
              dateStr = randomDate.toISOString().split("T")[0];
            } while (usedDates.has(dateStr));

            usedDates.add(dateStr);
            dateInput.value = dateStr;

            const hours = Math.floor(Math.random() * 12) + 9;
            const minutes = Math.random() < 0.5 ? "00" : "30";
            timeInput.value = `${hours.toString().padStart(2, "0")}:${minutes}`;

            // Trigger events
            dateInput.dispatchEvent(new Event("change", { bubbles: true }));
            dateInput.dispatchEvent(new Event("input", { bubbles: true }));
            timeInput.dispatchEvent(new Event("change", { bubbles: true }));
            timeInput.dispatchEvent(new Event("input", { bubbles: true }));
          }
        }

        // Validate the form after filling
        console.log("Validating filled form");
        const form = document.getElementById("rescheduleRequestForm");
        if (form) {
          // Trigger form validation
          form.checkValidity();
          form.dispatchEvent(new Event("change", { bubbles: true }));

          // Hide any error messages that might be showing
          const errorMessages = form.querySelectorAll(
            ".alert-danger, .text-danger"
          );
          errorMessages.forEach((msg) => (msg.style.display = "none"));
        }

        console.log("Sample data population complete");
      } else {
        console.log("Development mode disabled - clearing form");
        // Clear all form fields
        const form = document.getElementById("rescheduleRequestForm");
        if (form) {
          form.reset();

          // Clear any populated hidden fields
          const hiddenFields = form.querySelectorAll('input[type="hidden"]');
          hiddenFields.forEach((field) => {
            if (
              ![
                "submit_tutor_reschedule_request",
                "tutor_id",
                "tutor_name",
                "active_tab",
              ].includes(field.name)
            ) {
              field.value = "";
            }
          });

          // Trigger form validation
          form.checkValidity();
          form.dispatchEvent(new Event("change", { bubbles: true }));
        }
      }
    });

    // Add keyboard shortcut (Ctrl/Cmd + Shift + D) to toggle dev mode
    document.addEventListener("keydown", function (e) {
      if (
        (e.ctrlKey || e.metaKey) &&
        e.shiftKey &&
        e.key.toLowerCase() === "d"
      ) {
        e.preventDefault(); // Prevent default browser behavior
        devModeCheckbox.checked = !devModeCheckbox.checked;
        devModeCheckbox.dispatchEvent(new Event("change"));
      }
    });
  }

  // Fix for the invalid selector error
  function replaceDeclineButtons() {
    // Use separate selectors for danger buttons and decline buttons
    const dangerButtons = document.querySelectorAll("button.btn-danger");
    const declineButtons = Array.from(
      document.querySelectorAll("button")
    ).filter((button) => button.textContent.trim().toLowerCase() === "decline");

    // Combine the button collections
    const allButtons = [...dangerButtons, ...declineButtons];

    allButtons.forEach((button) => {
      // Your button replacement logic here
      // For example:
      button.classList.add("btn-warning");
      button.classList.remove("btn-danger");
      button.innerHTML = '<i class="fas fa-times"></i> Unavailable';
    });
  }

  // Call the fixed function
  replaceDeclineButtons();
});
