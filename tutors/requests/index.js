let tutorRequestsInitialized = false; // Flag to prevent multiple initializations

document.addEventListener("DOMContentLoaded", function () {
  if (tutorRequestsInitialized) {
    console.warn(
      "Tutor Requests JS: DOMContentLoaded listener fired again. Skipping initialization."
    );
    return;
  }
  tutorRequestsInitialized = true;
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

  // REMOVE OLD DIRECT LISTENER FOR SUBMIT BUTTON
  // if (submitTutorRescheduleBtn && rescheduleRequestForm) {
  //   console.log(
  //     "Tutor Requests JS: Found submit button and form. Attaching click listener..."
  //   );
  //   submitTutorRescheduleBtn.addEventListener("click", function (e) {
  //       // OLD SUBMIT HANDLER LOGIC
  //   });
  // } else { ... }

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

  // REMOVE OLD DIRECT LISTENER FOR AUTOFILL BUTTON
  // if (devModeButton) {
  //   console.log(
  //     "Tutor Requests JS: Found autofill button. Attaching click listener..."
  //   );
  //   devModeButton.addEventListener("click", function () {
  //       // OLD AUTOFILL HANDLER LOGIC
  //   });
  // } else { ... }

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

  // Get the modal element for DELEGATION
  const rescheduleModal = document.getElementById("newRescheduleRequestModal");

  if (rescheduleModal) {
    console.log(
      "Tutor Requests JS: Found modal (#newRescheduleRequestModal). Attaching delegated listener."
    );

    rescheduleModal.addEventListener("click", function (event) {
      // --- Handle Submit Button Click ---
      if (event.target.closest("#submitTutorReschedule")) {
        const submitBtn = event.target.closest("#submitTutorReschedule");
        const form = document.getElementById("rescheduleRequestForm");
        console.log(
          "Tutor Requests JS: Delegated listener caught click on/inside #submitTutorReschedule."
        );
        event.preventDefault();

        if (submitBtn.disabled) return;

        // ... (Reset error messages) ...
        const errorMsgDiv = document.getElementById(
          "rescheduleRequestErrorMessage"
        );
        const preferredErrorDiv = document.getElementById(
          "preferred-times-error"
        );
        if (errorMsgDiv) errorMsgDiv.style.display = "none";
        if (preferredErrorDiv) preferredErrorDiv.style.display = "none";

        // ... (Validation logic - checks studentId, dates, reason, preferredTimes) ...
        const studentId = document.getElementById("student_id")?.value;
        const originalDate = document.getElementById("original_date")?.value;
        const originalTime = document.getElementById("original_time")?.value;
        const reason = document.getElementById("reason")?.value;
        if (
          !studentId ||
          !originalDate ||
          !originalTime ||
          !reason ||
          !reason.trim()
        ) {
          console.log("Validation FAILED: Required fields empty.");
          if (errorMsgDiv) errorMsgDiv.style.display = "block";
          return;
        }
        const preferredDates = form.querySelectorAll(
          "#preferred-times-container .preferred-date"
        );
        const preferredTimes = form.querySelectorAll(
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
          console.log("Validation FAILED: No preferred time.");
          if (preferredErrorDiv) preferredErrorDiv.style.display = "block";
          return;
        }
        console.log("Validation PASSED. Preparing fetch...");

        // --- > START: Submit using Fetch <---
        const formElements = form.querySelectorAll(
          "input, select, textarea, button"
        );
        formElements.forEach((el) => (el.disabled = true));
        submitBtn.innerHTML =
          '<span class="spinner-border spinner-border-sm"></span> Submitting...';

        const formData = new FormData(form);
        // Ensure action and nonce are included if not already in form
        if (!formData.has("action")) {
          formData.append("action", "submit_tutor_reschedule");
        }
        // *** ADD NONCE HERE ***
        // Nonce key must match what the PHP handler expects: 'tutor_reschedule_nonce'
        if (tutorDashboardData && tutorDashboardData.rescheduleNonce) {
          formData.append(
            "tutor_reschedule_nonce",
            tutorDashboardData.rescheduleNonce
          );
          console.log(
            "Tutor Requests JS: Appended nonce:",
            tutorDashboardData.rescheduleNonce
          ); // Log nonce being added
        } else {
          console.error(
            "Tutor Requests JS: ERROR - Nonce data not found in tutorDashboardData."
          );
          // Display an error to the user and stop submission
          formElements.forEach((el) => (el.disabled = false));
          submitBtn.innerHTML = "Submit Request";
          // Display error message
          if (errorMsgDiv) {
            errorMsgDiv.querySelector("p").textContent =
              "Error: Security token missing. Please refresh the page and try again.";
            errorMsgDiv.style.display = "block";
          }
          return; // Stop submission
        }

        console.log(
          "Tutor Requests JS: Sending fetch request (delegated) to:",
          tutorDashboardData.ajaxurl
        );

        fetch(tutorDashboardData.ajaxurl, {
          // Make sure tutorDashboardData.ajaxurl is available
          method: "POST",
          body: formData,
        })
          .then((response) => {
            // Try parsing JSON regardless of ok status, backend might send error details
            return response.json().then((data) => ({
              ok: response.ok,
              status: response.status,
              data,
            }));
          })
          .then(({ ok, status, data }) => {
            console.log("Fetch response received:", { ok, status, data });
            if (ok && data.success) {
              console.log("AJAX success:", data);
              const successMsgDiv = document.getElementById(
                "tutorRescheduleSuccessMessage"
              );
              if (successMsgDiv) successMsgDiv.style.display = "block";
              if (errorMsgDiv) errorMsgDiv.style.display = "none";

              form.querySelector(".modal-body").style.display = "none";
              const footer = submitBtn.closest(".modal-footer");
              if (footer)
                footer.innerHTML =
                  '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';

              setTimeout(() => {
                location.reload();
              }, 1500);
            } else {
              // Throw error with message from backend if available
              throw new Error(
                data?.data?.message || `Submission failed (Status: ${status})`
              );
            }
          })
          .catch((error) => {
            console.error("Error submitting form via fetch:", error);
            if (errorMsgDiv) {
              errorMsgDiv.querySelector("p").textContent =
                "Error: " + error.message;
              errorMsgDiv.style.display = "block";
            }
            // Re-enable form
            formElements.forEach((el) => (el.disabled = false));
            submitBtn.innerHTML = "Submit Request";
          });
        // --- > END: Submit using Fetch <---
      } // --- End Submit Button Logic ---

      // --- Handle Autofill Button Click ---
      else if (event.target.closest("#devModeCheckbox")) {
        console.log("Tutor Requests JS: Entering Autofill logic block.");
        const modalForAutofill = document.getElementById(
          "newRescheduleRequestModal"
        );
        console.log(
          "Tutor Requests JS: Found modal inside autofill handler:",
          modalForAutofill ? "Yes" : "No"
        );

        if (!modalForAutofill) {
          console.error(
            "Could not find the reschedule request modal for autofill."
          );
          return;
        }

        console.log("Autofill button clicked - filling form with sample data");

        try {
          // Add try...catch around autofill logic
          // --- Select Student ---
          const studentSelect =
            modalForAutofill.querySelector("#student_select");
          const studentIdInput = modalForAutofill.querySelector("#student_id");
          console.log("Autofill - studentSelect found:", !!studentSelect);
          if (
            studentSelect &&
            studentIdInput &&
            studentSelect.options.length > 1
          ) {
            const randomIndex =
              Math.floor(Math.random() * (studentSelect.options.length - 1)) +
              1;
            studentSelect.selectedIndex = randomIndex;
            studentSelect.dispatchEvent(new Event("change", { bubbles: true }));
            console.log("Autofill - Selected student index:", randomIndex);
          } else {
            console.warn(
              "Autofill - Could not select student or no students available."
            );
          }

          // --- Lesson to Reschedule ---
          const originalDateInput =
            modalForAutofill.querySelector("#original_date");
          const originalTimeInput =
            modalForAutofill.querySelector("#original_time");
          console.log(
            "Autofill - originalDateInput found:",
            !!originalDateInput
          );
          console.log(
            "Autofill - originalTimeInput found:",
            !!originalTimeInput
          );
          // ... (Set date/time values - keep existing logic) ...
          if (originalDateInput) {
            const today = new Date();
            const nextWeek = new Date(today);
            nextWeek.setDate(today.getDate() + 7);
            const randomDate = new Date(nextWeek);
            randomDate.setDate(
              nextWeek.getDate() + Math.floor(Math.random() * 7)
            );
            originalDateInput.value = randomDate.toISOString().split("T")[0];
            console.log(
              "Autofill - Set original date:",
              originalDateInput.value
            );
          }
          if (originalTimeInput) {
            const hours = Math.floor(Math.random() * 10) + 9;
            const minutes = Math.random() < 0.5 ? "00" : "30";
            originalTimeInput.value = `${hours
              .toString()
              .padStart(2, "0")}:${minutes}`;
            console.log(
              "Autofill - Set original time:",
              originalTimeInput.value
            );
          }

          // --- Reason ---
          const reasonInput = modalForAutofill.querySelector("#reason");
          console.log("Autofill - reasonInput found:", !!reasonInput);
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
            reasonInput.value =
              reasons[Math.floor(Math.random() * reasons.length)];
            console.log("Autofill - Set reason:", reasonInput.value);
          }

          // --- Preferred Alternative Times ---
          console.log("Autofill - Setting preferred times...");
          const usedDates = new Set();
          for (let i = 1; i <= 3; i++) {
            const preferredDateInput = modalForAutofill.querySelector(
              `#preferred_date_${i}`
            );
            const preferredTimeInput = modalForAutofill.querySelector(
              `#preferred_time_${i}`
            );
            console.log(
              `Autofill - preferredDateInput ${i} found:`,
              !!preferredDateInput
            );
            console.log(
              `Autofill - preferredTimeInput ${i} found:`,
              !!preferredTimeInput
            );
            if (preferredDateInput && preferredTimeInput) {
              // ... (Set preferred times logic - keep existing) ...
              let randomDate, dateStr;
              let attempts = 0;
              do {
                const today = new Date(); // Re-calculate 'today' if needed
                const nextWeek = new Date(today); // Re-calculate 'nextWeek' if needed
                nextWeek.setDate(today.getDate() + 7);
                randomDate = new Date(nextWeek);
                randomDate.setDate(
                  nextWeek.getDate() + Math.floor(Math.random() * 14) + 1
                );
                dateStr = randomDate.toISOString().split("T")[0];
                attempts++;
              } while (usedDates.has(dateStr) && attempts < 10);
              if (attempts < 10) {
                usedDates.add(dateStr);
                preferredDateInput.value = dateStr;
                const hours = Math.floor(Math.random() * 10) + 9;
                const minutes = Math.random() < 0.5 ? "00" : "30";
                preferredTimeInput.value = `${hours
                  .toString()
                  .padStart(2, "0")}:${minutes}`;
                console.log(
                  `Autofill - Set preferred time ${i}:`,
                  preferredDateInput.value,
                  preferredTimeInput.value
                );
              } else {
                console.warn(
                  `Autofill - Could not find unique date for preferred time ${i}`
                );
                preferredDateInput.value = "";
                preferredTimeInput.value = "";
              }
            }
          }

          // --- Clear validation messages ---
          const errorMsg = modalForAutofill.querySelector(
            "#rescheduleRequestErrorMessage"
          );
          const preferredErrorMsg = modalForAutofill.querySelector(
            "#preferred-times-error"
          );
          if (errorMsg) errorMsg.style.display = "none";
          if (preferredErrorMsg) preferredErrorMsg.style.display = "none";
          console.log("Autofill - Form autofill complete.");
        } catch (error) {
          console.error(
            "Tutor Requests JS: Error occurred during autofill:",
            error
          );
        }

        console.log("Tutor Requests JS: Exiting Autofill logic block.");
      } // --- End Autofill Button Logic ---
    }); // End modal click listener
  } else {
    console.error(
      "Tutor Requests JS: ERROR - Could not find modal element #newRescheduleRequestModal to attach delegated listener."
    );
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
});
