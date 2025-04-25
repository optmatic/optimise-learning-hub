document.addEventListener("DOMContentLoaded", function () {
  console.log("Tutor Requests JS: DOMContentLoaded fired.");

  // --- Defer Tab Activation slightly --- START ---
  // setTimeout(() => {
  //   console.log("Tutor Requests JS: Running deferred tab activation.");
  //   try {
  //     const storedTab = localStorage.getItem('activeTutorTab');
  //     console.log("Tutor Requests JS: Stored active tab:", storedTab);
  //     if (storedTab) {
  //         const tabToSelect = document.querySelector(`a[data-bs-toggle="tab"][href="${storedTab}"]`);
  //         console.log("Tutor Requests JS: Found tab element to select:", tabToSelect);
  //         if (tabToSelect) {
  //             // Use bootstrap's Tab instance to show the tab
  //             // >>>>>>>> THIS LINE CAUSES THE ERROR <<<<<<<<<<
  //             // const tab = new bootstrap.Tab(tabToSelect);
  //             // console.log("Tutor Requests JS: Creating bootstrap Tab instance.");
  //             // tab.show();
  //             // console.log("Tutor Requests JS: Tab shown.");
  //              console.warn("Tutor Requests JS: Tab activation using new bootstrap.Tab() is temporarily disabled.");
  //         } else {
  //             console.log("Tutor Requests JS: Could not find tab element for stored href:", storedTab);
  //         }
  //     } else {
  //       console.log("Tutor Requests JS: No active tab found in localStorage.");
  //     }
  //     // Add listener to store active tab on change
  //     const tabLinks = document.querySelectorAll('a[data-bs-toggle="tab"]');
  //     console.log(`Tutor Requests JS: Found ${tabLinks.length} tab links to attach storage listener.`);
  //     tabLinks.forEach(function(tabLink) {
  //         tabLink.addEventListener('shown.bs.tab', function(event) {
  //             const activeTabHref = this.getAttribute('href');
  //             console.log("Tutor Requests JS: Tab changed, storing href:", activeTabHref);
  //             localStorage.setItem('activeTutorTab', activeTabHref);
  //         });
  //     });
  //   } catch (e) {
  //     console.error("Tutor Requests JS: Error in tab activation/storage logic:", e);
  //   }
  // }, 100); // Delay execution by 100ms
  // --- Defer Tab Activation slightly --- END ---

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
  console.log(
    "Tutor Requests JS: Attempting to find submit button and form..."
  ); // <-- Log A
  const submitTutorRescheduleBtn = document.getElementById(
    "submitTutorReschedule"
  );
  const rescheduleRequestForm = document.getElementById(
    "rescheduleRequestForm"
  );

  // *** Add logging here ***
  console.log(
    "Tutor Requests JS: submitTutorRescheduleBtn found:",
    submitTutorRescheduleBtn // Log the element itself
  );
  console.log(
    "Tutor Requests JS: rescheduleRequestForm found:",
    rescheduleRequestForm // Log the element itself
  );

  if (submitTutorRescheduleBtn && rescheduleRequestForm) {
    console.log(
      "Tutor Requests JS: Found submit button and form. Attaching click listener..."
    ); // <-- Log B
    submitTutorRescheduleBtn.addEventListener("click", function (e) {
      console.log("Tutor Requests JS: Submit button CLICKED."); // <-- Log C
      e.preventDefault();
      console.log("Tutor Requests JS: Submit button clicked!");

      // Check if button is already disabled (preventing duplicate submissions)
      if (this.disabled) {
        return;
      }

      // Reset error messages
      document.getElementById("rescheduleRequestErrorMessage").style.display =
        "none";
      document.getElementById("preferred-times-error").style.display = "none";

      // Get form elements
      const studentId = document.getElementById("student_id").value;
      const originalDate = document.getElementById("original_date").value;
      const originalTime = document.getElementById("original_time").value;
      const reason = document.getElementById("reason").value;

      // *** Add logging here ***
      console.log("--- Reschedule Form Validation ---");
      console.log("Student ID value:", studentId);
      console.log("Original Date value:", originalDate);
      console.log("Original Time value:", originalTime);
      console.log("Reason value:", reason);
      console.log("Checking required fields...");

      // Check required fields
      if (!studentId || !originalDate || !originalTime || !reason) {
        console.log(
          "Validation FAILED: One or more required fields are empty."
        ); // Log failure
        document.getElementById("rescheduleRequestErrorMessage").style.display =
          "block";
        return;
      }
      console.log("Validation PASSED: Basic required fields are present."); // Log success

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

      // --- Submit the form using Fetch API ---
      const formData = new FormData(rescheduleRequestForm);

      // Log FormData contents for debugging
      // console.log("--- FormData --- ");
      // for (let [key, value] of formData.entries()) {
      //   console.log(`${key}: ${value}`);
      // }
      // console.log("AJAX URL:", tutorDashboardData.ajaxurl);

      console.log(
        "Tutor Requests JS: Sending fetch request to:",
        tutorDashboardData.ajaxurl
      );

      fetch(tutorDashboardData.ajaxurl, {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          if (!response.ok) {
            // Try to get error message from server if possible
            return response.json().then((err) => {
              throw new Error(
                err.data?.message || "Network response was not ok."
              );
            });
          }
          return response.json();
        })
        .then((data) => {
          if (data.success) {
            console.log("AJAX success:", data);
            // Show success message inside the modal
            document.getElementById(
              "rescheduleRequestSuccessMessage"
            ).style.display = "block";
            document.getElementById(
              "rescheduleRequestErrorMessage"
            ).style.display = "none";
            // Hide form fields and buttons in modal footer except close button
            rescheduleRequestForm.querySelector(".modal-body").style.display =
              "none";
            const footer = rescheduleRequestForm.querySelector(".modal-footer");
            footer.innerHTML =
              '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';

            // Optionally close modal and reload page after a delay
            setTimeout(() => {
              const modalInstance = bootstrap.Modal.getInstance(
                document.getElementById("newRescheduleRequestModal")
              );
              if (modalInstance) {
                modalInstance.hide();
              }
              location.reload(); // Reload to show the updated outgoing requests table
            }, 2500); // 2.5 second delay
          } else {
            // Throw error to be caught by .catch()
            throw new Error(
              data.data?.message || "Submission failed. Please try again."
            );
          }
        })
        .catch((error) => {
          console.error("Error submitting form:", error);
          // Show error message
          const errorDiv = document.getElementById(
            "rescheduleRequestErrorMessage"
          );
          errorDiv.querySelector("p").textContent = "Error: " + error.message;
          errorDiv.style.display = "block";
          // Re-enable form
          formElements.forEach((el) => (el.disabled = false));
          submitTutorRescheduleBtn.innerHTML = "Submit Request";
        });

      // rescheduleRequestForm.submit(); // REMOVED standard form submission
    });
  } else {
    console.error(
      "Tutor Requests JS: ERROR - Could not find submit button or form to attach listener."
    );
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

  // Development Mode: Autofill form data
  console.log("Tutor Requests JS: Attempting to find autofill button..."); // <-- Log D
  const devModeButton = document.getElementById("devModeCheckbox");
  console.log("Tutor Requests JS: devModeButton found:", devModeButton); // Log the element itself

  if (devModeButton) {
    console.log(
      "Tutor Requests JS: Found autofill button. Attaching click listener..."
    ); // <-- Log E
    devModeButton.addEventListener("click", function () {
      console.log("Tutor Requests JS: Autofill button CLICKED."); // <-- Log F
      console.log("Autofill button clicked - filling form with sample data");

      // Target elements within the modal
      const modal = document.getElementById("newRescheduleRequestModal");
      if (!modal) {
        console.error("Could not find the reschedule request modal.");
        return;
      }

      // --- Select Student ---
      const studentSelect = modal.querySelector("#student_select");
      const studentIdInput = modal.querySelector("#student_id");
      if (studentSelect && studentIdInput && studentSelect.options.length > 1) {
        // Select a random student (excluding the placeholder "--Select student--")
        const randomIndex =
          Math.floor(Math.random() * (studentSelect.options.length - 1)) + 1;
        studentSelect.selectedIndex = randomIndex;
        // Trigger change event to update hidden ID field
        studentSelect.dispatchEvent(new Event("change", { bubbles: true }));
        console.log(
          "Selected student:",
          studentSelect.options[randomIndex].text
        );
      } else {
        console.warn("Could not select student or no students available.");
      }

      // --- Lesson to Reschedule ---
      const today = new Date();
      const nextWeek = new Date(today);
      nextWeek.setDate(today.getDate() + 7);
      const originalDateInput = modal.querySelector("#original_date");
      const originalTimeInput = modal.querySelector("#original_time");

      if (originalDateInput) {
        const randomDate = new Date(nextWeek);
        randomDate.setDate(nextWeek.getDate() + Math.floor(Math.random() * 7)); // Date within the next week
        originalDateInput.value = randomDate.toISOString().split("T")[0];
        console.log("Set original date:", originalDateInput.value);
      }
      if (originalTimeInput) {
        const hours = Math.floor(Math.random() * 10) + 9; // 9 AM to 6 PM
        const minutes = Math.random() < 0.5 ? "00" : "30";
        originalTimeInput.value = `${hours
          .toString()
          .padStart(2, "0")}:${minutes}`;
        console.log("Set original time:", originalTimeInput.value);
      }

      // --- Reason ---
      const reasonInput = modal.querySelector("#reason");
      if (reasonInput) {
        const reasons = [
          "Conflicting appointment",
          "Family event",
          "Feeling unwell",
          "Unexpected work commitment",
          "Urgent matter",
          "Scheduling conflict",
          "Professional development",
          "Emergency situation",
        ];
        reasonInput.value = reasons[Math.floor(Math.random() * reasons.length)];
        console.log("Set reason:", reasonInput.value);
      }

      // --- Preferred Alternative Times ---
      const usedDates = new Set(); // To ensure unique dates
      for (let i = 1; i <= 3; i++) {
        const preferredDateInput = modal.querySelector(`#preferred_date_${i}`);
        const preferredTimeInput = modal.querySelector(`#preferred_time_${i}`);
        if (preferredDateInput && preferredTimeInput) {
          let randomDate, dateStr;
          let attempts = 0;
          do {
            randomDate = new Date(nextWeek);
            // Pick a date 1-14 days after next week starts
            randomDate.setDate(
              nextWeek.getDate() + Math.floor(Math.random() * 14) + 1
            );
            dateStr = randomDate.toISOString().split("T")[0];
            attempts++;
          } while (usedDates.has(dateStr) && attempts < 10); // Prevent infinite loop

          if (attempts < 10) {
            usedDates.add(dateStr);
            preferredDateInput.value = dateStr;

            const hours = Math.floor(Math.random() * 10) + 9; // 9 AM to 6 PM
            const minutes = Math.random() < 0.5 ? "00" : "30";
            preferredTimeInput.value = `${hours
              .toString()
              .padStart(2, "0")}:${minutes}`;
            console.log(
              `Set preferred time ${i}:`,
              preferredDateInput.value,
              preferredTimeInput.value
            );
          } else {
            console.warn(`Could not find unique date for preferred time ${i}`);
            preferredDateInput.value = "";
            preferredTimeInput.value = "";
          }
        }
      }

      // Clear validation messages just in case
      const errorMsg = modal.querySelector("#rescheduleRequestErrorMessage");
      const preferredErrorMsg = modal.querySelector("#preferred-times-error");
      if (errorMsg) errorMsg.style.display = "none";
      if (preferredErrorMsg) preferredErrorMsg.style.display = "none";

      console.log("Form autofill complete.");
    });
  } else {
    console.error("Tutor Requests JS: ERROR - Could not find autofill button.");
  }

  // Fix for the invalid selector error
  function replaceDeclineButtons() {
    // Find all buttons first
    const allButtons = document.querySelectorAll("button");

    // Filter buttons: those with btn-danger class OR those whose text content is "Decline"
    const targetButtons = Array.from(allButtons).filter(
      (button) =>
        button.classList.contains("btn-danger") ||
        button.textContent.trim().toLowerCase() === "decline"
    );

    targetButtons.forEach((button) => {
      // Your button replacement logic here
      // Example: Make them warning buttons with an 'Unavailable' icon/text
      button.classList.remove("btn-danger"); // Remove danger class if present
      button.classList.add("btn-warning"); // Add warning class
      // Set icon and text (ensure Font Awesome is loaded or adjust as needed)
      button.innerHTML = '<i class="fas fa-times me-1"></i> Unavailable';
      // Add necessary Bootstrap attributes if this button should open the 'unavailableModal'
      button.setAttribute("data-bs-toggle", "modal");
      button.setAttribute("data-bs-target", "#unavailableModal");
    });
  }

  // Call the fixed function
  replaceDeclineButtons();

  // --- New Reschedule Request Modal Logic (Tutor) ---
  const tutorRescheduleModal = document.getElementById(
    "newRescheduleRequestModal"
  );
  const tutorRescheduleForm = document.getElementById("rescheduleRequestForm"); // Assuming this ID is correct for the tutor form
  const submitTutorButton = document.getElementById("submitTutorReschedule");
  const tutorSuccessMessage = document.getElementById(
    "tutorRescheduleSuccessMessage"
  );
  const tutorErrorMessage = document.getElementById(
    "tutorRescheduleErrorMessage"
  );
  const tutorPreferredTimesError = document.getElementById(
    "preferred-times-error"
  ); // Assuming same ID for preferred times error

  if (submitTutorButton && tutorRescheduleForm) {
    submitTutorButton.addEventListener("click", function (e) {
      e.preventDefault(); // Prevent default button action

      if (this.disabled) return; // Prevent double clicks

      // --- Get Elements (inside handler) ---
      const tutorSuccessMessage = document.getElementById(
        "tutorRescheduleSuccessMessage"
      );
      console.log("Found tutorSuccessMessage:", !!tutorSuccessMessage);
      const tutorErrorMessage = document.getElementById(
        "tutorRescheduleErrorMessage"
      );
      console.log("Found tutorErrorMessage:", !!tutorErrorMessage);
      const tutorPreferredTimesError = document.getElementById(
        "preferred-times-error"
      );
      console.log(
        "Found tutorPreferredTimesError:",
        !!tutorPreferredTimesError
      );

      // --- Validation ---
      let isValid = true;
      const studentSelect = document.getElementById("student_select");
      const originalDate = document.getElementById("original_date");
      const originalTime = document.getElementById("original_time");
      const reason = document.getElementById("reason");
      const preferredDates =
        tutorRescheduleForm.querySelectorAll(".preferred-date");
      const preferredTimes =
        tutorRescheduleForm.querySelectorAll(".preferred-time");

      // Hide previous messages only if elements exist
      if (tutorErrorMessage) tutorErrorMessage.style.display = "none";
      if (tutorSuccessMessage) tutorSuccessMessage.style.display = "none";
      if (tutorPreferredTimesError)
        tutorPreferredTimesError.style.display = "none";

      // Basic required field checks
      if (!studentSelect || !studentSelect.value) {
        isValid = false;
        console.error("Student not selected");
      }
      if (!originalDate || !originalDate.value) {
        isValid = false;
        console.error("Original date missing");
      }
      if (!originalTime || !originalTime.value) {
        isValid = false;
        console.error("Original time missing");
      }
      if (!reason || !reason.value.trim()) {
        isValid = false;
        console.error("Reason missing");
      }

      // Check if at least one preferred time is provided
      let hasPreferredTime = false;
      for (let i = 0; i < preferredDates.length; i++) {
        if (preferredDates[i].value && preferredTimes[i].value) {
          hasPreferredTime = true;
          break;
        }
      }
      if (!hasPreferredTime) {
        isValid = false;
        if (tutorPreferredTimesError)
          tutorPreferredTimesError.style.display = "block";
        console.error("No preferred time provided");
      } else {
        if (tutorPreferredTimesError)
          tutorPreferredTimesError.style.display = "none";
      }

      if (!isValid) {
        // Show general error message only if element exists
        if (tutorErrorMessage) {
          tutorErrorMessage.querySelector("p").textContent =
            "Please fill in all required fields and provide at least one preferred time.";
          tutorErrorMessage.style.display = "block";
        } else {
          alert(
            "Please fill in all required fields and provide at least one preferred time."
          );
        }
        return; // Stop if validation fails
      }

      // --- AJAX Submission ---
      const formData = new FormData(tutorRescheduleForm);

      // Hide previous messages right before sending
      // ... existing code ...
      // Ensure the correct AJAX action is set (check your PHP handler)
      formData.append("submit_tutor_reschedule_post", "1");
      // Add nonce if required by backend (ensure it's available in JS)
      // formData.append('nonce', tutorDashboardData.nonce);

      const submitButton = this;
      submitButton.disabled = true;
      submitButton.innerHTML = "Submitting...";

      const formElements = tutorRescheduleForm.querySelectorAll(
        "input, select, textarea, button"
      );
      formElements.forEach((el) => (el.disabled = true));

      const xhr = new XMLHttpRequest();
      // Use ajaxurl if defined (standard WordPress AJAX), otherwise fallback to current URL
      xhr.open("POST", window.location.href, true);
      // No need to set Content-Type header when using FormData

      xhr.onload = function () {
        // Simplified handler for AJAX + Reload approach
        if (xhr.status >= 200 && xhr.status < 300) {
          // Assume 2xx status means success (or redirect)
          console.log(
            "AJAX POST successful (status: " +
              xhr.status +
              "). Assuming server processed request."
          );
          // Show success message briefly before reload
          if (tutorSuccessMessage) {
            tutorSuccessMessage.querySelector("p").textContent =
              "Request submitted successfully."; // Generic message
            tutorSuccessMessage.style.display = "block";
          }
          if (tutorErrorMessage) tutorErrorMessage.style.display = "none";

          // Optionally clear form
          tutorRescheduleForm.reset(); // Reset form on success

          // Change footer buttons
          const footer = submitButton.closest(".modal-footer");
          if (footer) {
            footer.innerHTML = `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>`;
          }

          // Reload the page like the student implementation
          setTimeout(() => {
            location.reload();
          }, 1500); // Short delay to allow user to see message
        } else {
          // Handle HTTP errors (e.g., 404, 500)
          console.error("AJAX POST failed:", xhr.status, xhr.statusText);
          const responseMessage = `Submission error (Status: ${xhr.status}). Please try again.`;

          if (tutorErrorMessage) {
            tutorErrorMessage.querySelector("p").textContent = responseMessage;
            tutorErrorMessage.style.display = "block";
          }
          if (tutorSuccessMessage) tutorSuccessMessage.style.display = "none";

          // Re-enable form
          formElements.forEach((el) => (el.disabled = false));
          submitButton.innerHTML = "Submit Request";
        }
      };

      xhr.onerror = function () {
        console.error("AJAX network error.");
        const responseMessage =
          "Network error. Please check connection and try again.";
        // Re-enable form on network error
        formElements.forEach((el) => (el.disabled = false));
        submitButton.innerHTML = "Submit Request";
        if (tutorErrorMessage) {
          tutorErrorMessage.querySelector("p").textContent = responseMessage;
          tutorErrorMessage.style.display = "block";
        }
        if (tutorSuccessMessage) tutorSuccessMessage.style.display = "none";
      };

      xhr.send(formData);
    });
  }

  // Reset tutor modal on close
  if (tutorRescheduleModal) {
    tutorRescheduleModal.addEventListener("hidden.bs.modal", function () {
      if (tutorRescheduleForm) tutorRescheduleForm.reset();

      if (tutorSuccessMessage) tutorSuccessMessage.style.display = "none";
      if (tutorErrorMessage) tutorErrorMessage.style.display = "none";
      if (tutorPreferredTimesError)
        tutorPreferredTimesError.style.display = "none";

      const submitBtn = document.getElementById("submitTutorReschedule");
      const footer = submitBtn ? submitBtn.closest(".modal-footer") : null;

      // Restore original footer if it was changed on success
      if (footer && !footer.contains(submitBtn)) {
        footer.innerHTML = `
          <button type="button" id="devModeCheckbox" class="btn btn-outline-secondary me-auto" title="Autofill form with sample data (Dev)">Autofill</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" id="submitTutorReschedule">Submit Request</button>
        `;
        // Re-attach listener to the NEW button
        const newSubmitBtn = document.getElementById("submitTutorReschedule");
        if (newSubmitBtn) {
          // Find the original handler reference if needed, or redefine it.
          // This example assumes the listener is added once on DOMContentLoaded and persists.
          // If dynamically added/removed, re-adding here is crucial.
        } else {
          console.error("Failed to re-attach listener after modal reset.");
        }
      } else if (submitBtn) {
        // Ensure button is enabled and text is reset if closed during error/submission
        submitBtn.disabled = false;
        submitBtn.innerHTML = "Submit Request";
      }

      // Re-enable form elements if they were disabled
      const formElements = tutorRescheduleForm
        ? tutorRescheduleForm.querySelectorAll(
            "input, select, textarea, button"
          )
        : [];
      formElements.forEach((el) => (el.disabled = false));
    });
  }

  // Add student ID population logic
  const tutorStudentSelect = document.getElementById("student_select");
  const tutorStudentIdInput = document.getElementById("student_id"); // Make sure this hidden input exists
  if (tutorStudentSelect && tutorStudentIdInput) {
    tutorStudentSelect.addEventListener("change", function () {
      const selectedOption = this.options[this.selectedIndex];
      tutorStudentIdInput.value = selectedOption
        ? selectedOption.getAttribute("data-id") || ""
        : "";
    });
    // Trigger change on load in case a student is pre-selected
    tutorStudentSelect.dispatchEvent(new Event("change"));
  }

  // --- End New Reschedule Request Modal Logic ---
});
