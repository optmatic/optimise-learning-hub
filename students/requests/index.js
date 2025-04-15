document.addEventListener("DOMContentLoaded", function () {
  // Initialize tooltips
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.forEach(function (tooltipTriggerEl) {
    new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Add fixed height and scrollbars to the request tables
  const requestTablesContainers = [
    document.querySelector(".card-body .table-responsive"), // Outgoing Reschedule Requests
    document.querySelectorAll(".card-body .table-responsive")[1], // Incoming Reschedule Requests
    document.querySelector("#alternativeTimesSection .card-body .accordion"), // Tutor Alternative Times
  ];

  // Apply styling to each container if it exists
  requestTablesContainers.forEach((container) => {
    if (container) {
      container.style.maxHeight = "300px";
      container.style.overflowY = "auto";
      container.style.overflowX = "hidden";
      container.style.padding = "5px";
      container.style.border = "1px solid #dee2e6";
      container.style.borderRadius = "5px";

      // Add shadow to scrollbar container for better visual separation
      container.style.boxShadow = "inset 0 0 5px rgba(0,0,0,0.1)";
    }
  });

  // Apply better scrollbar styling for WebKit browsers (Chrome, Safari, Edge)
  const style = document.createElement("style");
  style.textContent = `
            .table-responsive::-webkit-scrollbar, .accordion::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }
            .table-responsive::-webkit-scrollbar-track, .accordion::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 4px;
            }
            .table-responsive::-webkit-scrollbar-thumb, .accordion::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 4px;
            }
            .table-responsive::-webkit-scrollbar-thumb:hover, .accordion::-webkit-scrollbar-thumb:hover {
                background: #555;
            }
        `;
  document.head.appendChild(style);

  // Enhanced function to check incoming requests and update notification badges
  function checkIncomingRequests() {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", ajaxurl, true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onload = function () {
      if (this.status === 200) {
        try {
          const response = JSON.parse(this.responseText);
          if (response.success) {
            // Update the main Requests tab badge
            updateRequestsTabBadge(response.data.count);

            // If we're not currently on the requests tab, no need to update the content
            if (
              !document.getElementById("requests").classList.contains("active")
            ) {
              return;
            }

            // Update the notifications section with fresh content
            const notificationsSection = document.getElementById(
              "requestNotifications"
            );
            if (notificationsSection && response.data.notificationsHtml) {
              notificationsSection.innerHTML = response.data.notificationsHtml;
            }

            // Update the incoming requests section
            const incomingSection = document.querySelector(
              "#incomingRescheduleSection .card-body"
            );
            if (incomingSection && response.data.incomingHtml) {
              incomingSection.innerHTML = response.data.incomingHtml;
            }

            // Update the badge on the incoming section header
            const incomingBadge = document.querySelector(
              "#incomingRescheduleSection .card-header .badge"
            );
            if (response.data.pendingRescheduleCount > 0) {
              if (incomingBadge) {
                incomingBadge.textContent =
                  response.data.pendingRescheduleCount;
              } else {
                const headerDiv = document.querySelector(
                  "#incomingRescheduleSection .card-header .d-flex"
                );
                if (headerDiv) {
                  const badge = document.createElement("span");
                  badge.className = "badge bg-danger";
                  badge.textContent = response.data.pendingRescheduleCount;
                  headerDiv.appendChild(badge);
                }
              }
            } else if (incomingBadge) {
              incomingBadge.style.display = "none";
            }

            // Update the alternatives section badge if it exists
            const alternativesBadge = document.querySelector(
              "#alternativeTimesSection .card-header .badge"
            );
            if (alternativesBadge && response.data.pendingAlternativesCount) {
              alternativesBadge.textContent =
                response.data.pendingAlternativesCount;
              alternativesBadge.style.display =
                response.data.pendingAlternativesCount > 0
                  ? "inline-block"
                  : "none";
            }
          }
        } catch (e) {
          console.error("Error parsing JSON response:", e);
        }
      }
    };

    // Create a nonce for security
    const nonce = studentDashboardData.nonce; // Use localized nonce
    const studentId = studentDashboardData.student_id; // Use localized student ID

    xhr.send(
      "action=check_incoming_reschedule_requests&nonce=" +
        nonce +
        "&student_id=" +
        studentId
    );
  }

  // Function to update the badge on the Requests tab
  function updateRequestsTabBadge(count) {
    const requestsTab = document.getElementById("-tab");
    if (!requestsTab) return;

    // Find or create the badge element
    let badge = requestsTab.querySelector(".badge");

    if (count > 0) {
      if (!badge) {
        badge = document.createElement("span");
        badge.className = "badge rounded-pill bg-danger ms-1";
        requestsTab.appendChild(badge);
      }

      badge.textContent = count;
      badge.style.display = "inline-block";
    } else if (badge) {
      badge.style.display = "none";
    }
  }

  // Add click handler to mark notifications as read when tab is clicked
  const requestsTab = document.getElementById("requests-tab");
  if (requestsTab) {
    requestsTab.addEventListener("click", function () {
      const badge = this.querySelector(".badge");
      if (badge) {
        badge.style.display = "none";
      }

      // Mark notifications as read via AJAX
      const xhr = new XMLHttpRequest();
      xhr.open("POST", ajaxurl, true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

      const nonce = studentDashboardData.markReadNonce; // Use localized nonce
      const studentId = studentDashboardData.student_id; // Use localized student ID

      xhr.send(
        "action=mark_student_requests_read&nonce=" +
          nonce +
          "&student_id=" +
          studentId
      );
    });
  }

  // Check for notifications on page load
  checkIncomingRequests();

  // Check periodically (every 30 seconds)
  setInterval(checkIncomingRequests, 30000);

  // Handle edit request button clicks
  const editButtons = document.querySelectorAll(".edit-request-btn");
  if (editButtons.length > 0) {
    editButtons.forEach((button) => {
      button.addEventListener("click", function () {
        const requestId = this.getAttribute("data-request-id");
        const tutorName = this.getAttribute("data-tutor-name");
        const originalDate = this.getAttribute("data-original-date");
        const originalTime = this.getAttribute("data-original-time");
        const reason = this.getAttribute("data-reason");
        const preferredTimes = this.getAttribute("data-preferred-times");

        // Set values in the edit form
        document.getElementById("edit_request_id").value = requestId;
        document.getElementById("edit_tutor_name").value = tutorName;
        document.getElementById("edit_original_datetime").value =
          new Date(originalDate).toLocaleDateString() +
          " at " +
          new Date("1970-01-01T" + originalTime).toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit",
          });
        document.getElementById("edit_reason").value = reason;

        // Fill in preferred times if they exist
        if (preferredTimes) {
          console.log("Raw preferredTimes attribute:", preferredTimes);
          try {
            // Decode HTML entities before parsing
            const decodedPreferredTimes = preferredTimes.replace(
              /&quot;/g,
              '"'
            );
            console.log(
              "Decoded preferredTimes string:",
              decodedPreferredTimes
            );
            const times = JSON.parse(decodedPreferredTimes);
            console.log("Parsed times object:", times);
            // Clear any existing values first
            for (let i = 1; i <= 3; i++) {
              document.getElementById(`edit_preferred_date_${i}`).value = "";
              document.getElementById(`edit_preferred_time_${i}`).value = "";
            }
            // Fill in the new values
            times.forEach((time, index) => {
              console.log(`Processing time ${index + 1}:`, time);
              if (time.date && time.time) {
                const dateInput = document.getElementById(
                  `edit_preferred_date_${index + 1}`
                );
                const timeInput = document.getElementById(
                  `edit_preferred_time_${index + 1}`
                );
                console.log(`Found date input ${index + 1}:`, dateInput);
                console.log(`Found time input ${index + 1}:`, timeInput);
                if (dateInput && timeInput) {
                  dateInput.value = time.date;
                  timeInput.value = time.time;
                  console.log(`Set date ${index + 1} to:`, time.date);
                  console.log(`Set time ${index + 1} to:`, time.time);
                }
              }
            });
          } catch (e) {
            console.error("Error parsing preferred times:", e);
          }
        }
      });
    });
  }

  // Prevent Bootstrap from automatically closing the modal on form submission
  const editModal = document.getElementById("editRescheduleRequestModal");

  // Define the handler for the update button click
  function handleUpdateStudentRescheduleClick(event) {
    event.preventDefault(); // Prevent default form submission

    const editPreferredDates = document.querySelectorAll(
      "#edit-preferred-times-container .preferred-date"
    );
    const editPreferredTimes = document.querySelectorAll(
      "#edit-preferred-times-container .preferred-time"
    );
    let hasPreferredTime = false;

    // Check if at least one preferred date and time is provided
    for (let i = 0; i < editPreferredDates.length; i++) {
      if (editPreferredDates[i].value && editPreferredTimes[i].value) {
        hasPreferredTime = true;
        break;
      }
    }

    if (!hasPreferredTime) {
      alert("Please provide at least one preferred alternative time.");
      return; // Prevent form submission
    }

    // Get the form data
    const form = document.getElementById("editRescheduleRequestForm");
    const formData = new FormData(form);

    // Use AJAX to submit the form
    const xhr = new XMLHttpRequest();
    xhr.open("POST", window.location.href, true);

    // Define what happens on successful data submission
    xhr.onload = function () {
      if (xhr.status === 200) {
        // Check if the update was actually successful by looking for indicators in the response
        // NOTE: Checking responseText might be fragile. Consider a JSON response if possible.
        if (xhr.responseText.includes("update_student_reschedule_request=1")) {
          // Show success message
          document.getElementById(
            "editRescheduleSuccessMessage"
          ).style.display = "block";

          // Change buttons after successful submission
          const footerButtons = document.querySelector(
            "#editRescheduleRequestForm .modal-footer"
          );
          footerButtons.innerHTML = `
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="editAgainButton">Edit Again</button>
                        `;

          // Add event listener to the Edit Again button
          const editAgainButton = document.getElementById("editAgainButton");
          if (editAgainButton) {
            editAgainButton.addEventListener("click", function () {
              document.getElementById(
                "editRescheduleSuccessMessage"
              ).style.display = "none";

              footerButtons.innerHTML = `
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="updateStudentReschedule">Update Request</button>
                                `;

              // Reattach the event listener to the NEW Update Request button
              const newUpdateBtn = document.getElementById(
                "updateStudentReschedule"
              );
              if (newUpdateBtn) {
                // Remove potential old listener first (optional but good practice)
                // newUpdateBtn.removeEventListener('click', handleUpdateStudentRescheduleClick);
                newUpdateBtn.addEventListener(
                  "click",
                  handleUpdateStudentRescheduleClick
                );
              }
            });
          }
          // Consider adding code here to update the table row without full reload
          // For now, the user closes the modal manually.
        } else {
          // Handle cases where the server responded but the update wasn't confirmed
          console.error(
            "Update request submitted, but success indicator not found in response."
          );
          alert(
            "An issue occurred while updating the request. Please check the request list or try again."
          );
        }
      } else {
        // Handle HTTP errors (e.g., 404, 500)
        console.error("AJAX error during update:", xhr.status, xhr.statusText);
        alert(
          "An error occurred while communicating with the server. Please try again."
        );
      }
    };

    // Handle network errors
    xhr.onerror = function () {
      console.error("AJAX network error during update.");
      alert(
        "A network error occurred. Please check your connection and try again."
      );
    };

    // Send the form data
    xhr.send(formData);
  } // End of handleUpdateStudentRescheduleClick function

  if (editModal) {
    // Prevent click events on submit buttons within the modal from closing it automatically
    editModal.addEventListener("click", function (e) {
      // Target the specific update button if needed, or generally prevent for submit types
      if (
        e.target.type === "submit" ||
        e.target.id === "updateStudentReschedule"
      ) {
        e.preventDefault();
      }
    });

    // When the modal is shown, set up the initial state and attach the handler
    editModal.addEventListener("shown.bs.modal", function () {
      // Hide any previous success message
      document.getElementById("editRescheduleSuccessMessage").style.display =
        "none";

      // Ensure the form is in edit mode with the correct buttons
      const footerButtons = document.querySelector(
        "#editRescheduleRequestForm .modal-footer"
      );
      if (footerButtons) {
        // Check if the update button already exists to avoid duplicate setup if modal is reopened without closing
        let updateButton = document.getElementById("updateStudentReschedule");
        if (
          !updateButton ||
          footerButtons.innerHTML.includes("editAgainButton")
        ) {
          footerButtons.innerHTML = `
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="updateStudentReschedule">Update Request</button>
                        `;
          updateButton = document.getElementById("updateStudentReschedule"); // Get the new button reference
        }

        // Add the event listener to the button
        if (updateButton) {
          // Remove potential old listener first before adding
          // updateButton.removeEventListener('click', handleUpdateStudentRescheduleClick);
          updateButton.addEventListener(
            "click",
            handleUpdateStudentRescheduleClick
          );
        } else {
          console.error("Could not find the update button to attach listener.");
        }
      } else {
        console.error("Could not find modal footer to set up buttons.");
      }
    });
  } // End of if (editModal)

  // --- Submit New Reschedule Request Logic ---
  const submitNewButton = document.getElementById("submitStudentReschedule");
  if (submitNewButton) {
    submitNewButton.addEventListener("click", function (e) {
      e.preventDefault(); // Prevent the default button action

      // ... (rest of the submit new request logic remains the same) ...

      // Check if button is already disabled (preventing duplicate submissions)
      if (this.disabled) {
        return;
      }

      const preferredDates = document.querySelectorAll(
        "#preferred-times-container .preferred-date"
      ); // Scoped selector
      const preferredTimes = document.querySelectorAll(
        "#preferred-times-container .preferred-time"
      ); // Scoped selector
      let hasPreferredTime = false;

      // Check if at least one preferred date and time is provided
      for (let i = 0; i < preferredDates.length; i++) {
        if (preferredDates[i].value && preferredTimes[i].value) {
          hasPreferredTime = true;
          break;
        }
      }

      if (!hasPreferredTime) {
        document.getElementById("preferred-times-error").style.display =
          "block";
        return; // Prevent form submission
      }

      document.getElementById("preferred-times-error").style.display = "none";

      // Use AJAX to submit the form
      const form = document.getElementById("rescheduleRequestForm");
      const formData = new FormData(form);

      // Add a unique submission ID to prevent duplicate processing on the server
      const submissionId = Date.now().toString();
      formData.append("submission_id", submissionId);

      // Disable submit button to prevent multiple submissions
      const submitButton = this;
      submitButton.disabled = true;
      submitButton.innerHTML = "Submitting...";

      // Disable other form elements to prevent changes during submission
      const formElements = form.querySelectorAll("input, select, textarea");
      formElements.forEach((el) => (el.disabled = true));

      const xhr = new XMLHttpRequest();
      xhr.open("POST", window.location.href, true);

      xhr.onload = function () {
        if (xhr.status === 200) {
          // Assuming successful submission always redirects or reloads
          // Check response if specific confirmation is needed before reload
          document.getElementById(
            "rescheduleRequestSuccessMessage"
          ).style.display = "block";
          document.getElementById(
            "rescheduleRequestErrorMessage"
          ).style.display = "none";

          const footerButtons = document.querySelector(
            "#rescheduleRequestForm .modal-footer"
          );
          footerButtons.innerHTML = `
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        `;

          setTimeout(function () {
            location.reload();
          }, 2000); // Reload after 2 seconds
        } else {
          document.getElementById(
            "rescheduleRequestErrorMessage"
          ).style.display = "block";
          document
            .getElementById("rescheduleRequestErrorMessage")
            .querySelector("p").textContent =
            "There was an error submitting your request. Please try again.";
          document.getElementById(
            "rescheduleRequestSuccessMessage"
          ).style.display = "none";

          formElements.forEach((el) => (el.disabled = false));
          submitButton.disabled = false;
          submitButton.innerHTML = "Submit Request";
        }
      };

      xhr.onerror = function () {
        document.getElementById("rescheduleRequestErrorMessage").style.display =
          "block";
        document
          .getElementById("rescheduleRequestErrorMessage")
          .querySelector("p").textContent =
          "Network error. Please check your connection and try again.";
        document.getElementById(
          "rescheduleRequestSuccessMessage"
        ).style.display = "none";

        formElements.forEach((el) => (el.disabled = false));
        submitButton.disabled = false;
        submitButton.innerHTML = "Submit Request";
      };

      // Set a timeout in case the request takes too long
      const timeoutId = setTimeout(function () {
        xhr.abort();
        document.getElementById("rescheduleRequestErrorMessage").style.display =
          "block";
        document
          .getElementById("rescheduleRequestErrorMessage")
          .querySelector("p").textContent =
          "Request timed out. Please try again.";
        document.getElementById(
          "rescheduleRequestSuccessMessage"
        ).style.display = "none";

        formElements.forEach((el) => (el.disabled = false));
        submitButton.disabled = false;
        submitButton.innerHTML = "Submit Request";
      }, 30000); // 30 seconds timeout

      xhr.onloadend = function () {
        clearTimeout(timeoutId);
      };

      xhr.send(formData);
    });
  }

  // --- Process lesson select ---
  const lessonSelect = document.getElementById("lesson_select");
  if (lessonSelect) {
    lessonSelect.addEventListener("change", function () {
      const selectedValue = this.value;
      if (selectedValue) {
        const [date, time] = selectedValue.split("|");
        document.getElementById("original_date").value = date;
        document.getElementById("original_time").value = time;
      } else {
        document.getElementById("original_date").value = "";
        document.getElementById("original_time").value = "";
      }
    });
  }

  // --- Prevent new request modal closing ---
  const newRescheduleRequestModal = document.getElementById(
    "newRescheduleRequestModal"
  );
  if (newRescheduleRequestModal) {
    newRescheduleRequestModal.addEventListener("mousedown", function (e) {
      if (e.target.type === "submit" || e.target.type === "button") {
        // Only prevent default if it's the submit button, not close/cancel
        if (e.target.id === "submitStudentReschedule") {
          e.preventDefault();
        }
      }
    });

    const modalForm = document.getElementById("rescheduleRequestForm");
    if (modalForm) {
      modalForm.addEventListener("submit", function (e) {
        e.preventDefault();
        e.stopPropagation();
      });
    }

    newRescheduleRequestModal.addEventListener("hidden.bs.modal", function () {
      const form = document.getElementById("rescheduleRequestForm");
      if (form) form.reset(); // Reset the form

      // Reset messages
      const successMsg = document.getElementById(
        "rescheduleRequestSuccessMessage"
      );
      const errorMsg = document.getElementById("rescheduleRequestErrorMessage");
      const preferredErrorMsg = document.getElementById(
        "preferred-times-error"
      );
      if (successMsg) successMsg.style.display = "none";
      if (errorMsg) errorMsg.style.display = "none";
      if (preferredErrorMsg) preferredErrorMsg.style.display = "none";

      // Reset submit button state
      const submitBtn = document.getElementById("submitStudentReschedule");
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = "Submit Request";
        // Ensure footer is reset if AJAX changed it
        const footer = submitBtn.closest(".modal-footer");
        if (footer && !footer.querySelector("#submitStudentReschedule")) {
          footer.innerHTML = `
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="submitStudentReschedule">Submit Request</button>
                     `;
          // Re-attach listener to the new button if needed (though page reload usually handles this)
          document
            .getElementById("submitStudentReschedule")
            .addEventListener("click" /* original handler reference */);
        }
      }

      // Re-enable form elements if they were disabled
      const formElements = form
        ? form.querySelectorAll("input, select, textarea")
        : [];
      formElements.forEach((el) => (el.disabled = false));
    });
  }

  // --- Unavailable Modal Logic ---
  const unavailableModal = document.getElementById("unavailableModal");
  if (unavailableModal) {
    unavailableModal.addEventListener("show.bs.modal", function (event) {
      const button = event.relatedTarget;
      if (!button) return; // Exit if triggered programmatically without a relatedTarget

      const requestId = button.getAttribute("data-request-id");
      const tutorName = button.getAttribute("data-tutor-name");
      const originalDate = button.getAttribute("data-original-date");
      const originalTime = button.getAttribute("data-original-time");

      const modalRequestIdInput = document.getElementById(
        "unavailable_request_id"
      );
      const modalTutorNameSpan = document.getElementById(
        "unavailable_tutor_name"
      );
      const modalOriginalTimeSpan = document.getElementById(
        "unavailable_original_time"
      );

      if (modalRequestIdInput) modalRequestIdInput.value = requestId;
      if (modalTutorNameSpan) modalTutorNameSpan.textContent = tutorName;

      if (modalOriginalTimeSpan && originalDate && originalTime) {
        try {
          // Format the date and time robustly
          const dateObj = new Date(originalDate + "T" + originalTime); // Use ISO format for better parsing
          if (!isNaN(dateObj)) {
            // Check if date is valid
            const formattedDate = dateObj.toLocaleDateString("en-AU", {
              // Use locale-specific format
              weekday: "long",
              year: "numeric",
              month: "long",
              day: "numeric",
            });
            const formattedTime = dateObj.toLocaleTimeString("en-AU", {
              // Use locale-specific format
              hour: "numeric",
              minute: "numeric",
              hour12: true,
            });
            modalOriginalTimeSpan.textContent =
              formattedDate + " at " + formattedTime;
          } else {
            modalOriginalTimeSpan.textContent =
              originalDate + " at " + originalTime + " (Could not format)";
          }
        } catch (e) {
          console.error("Error formatting date/time for unavailable modal:", e);
          modalOriginalTimeSpan.textContent =
            originalDate + " at " + originalTime; // Fallback
        }
      } else if (modalOriginalTimeSpan) {
        modalOriginalTimeSpan.textContent = "N/A";
      }

      // Clear previous alternative times and error message
      const altTimesContainer = document.getElementById(
        "alternative-times-container"
      );
      if (altTimesContainer) {
        altTimesContainer
          .querySelectorAll('input[type="date"], input[type="time"]')
          .forEach((input) => (input.value = ""));
      }
      const unavailableError = document.getElementById(
        "unavailableErrorMessage"
      );
      if (unavailableError) unavailableError.style.display = "none";
    }); // End of show.bs.modal listener

    // Unavailable form validation
    const unavailableForm = document.getElementById("unavailableForm");
    if (unavailableForm) {
      unavailableForm.addEventListener("submit", function (event) {
        const altDates = unavailableForm.querySelectorAll(".alt-date");
        const altTimes = unavailableForm.querySelectorAll(".alt-time");
        let valid = false;

        for (let i = 0; i < altDates.length; i++) {
          if (altDates[i].value && altTimes[i].value) {
            valid = true;
            break;
          }
        }

        if (!valid) {
          event.preventDefault(); // Stop submission
          const unavailableError = document.getElementById(
            "unavailableErrorMessage"
          );
          if (unavailableError) {
            unavailableError.style.display = "block";
          } else {
            alert("Please provide at least one alternative time."); // Fallback alert
          }
        } else {
          const unavailableError = document.getElementById(
            "unavailableErrorMessage"
          );
          if (unavailableError) unavailableError.style.display = "none"; // Hide error if valid
        }
      }); // End of submit listener
    } // End of if (unavailableForm)
  } // End of if (unavailableModal)

  // Handle tutor selection to populate tutor ID
  const tutorSelect = document.getElementById("tutor_select");
  const tutorIdInput = document.getElementById("tutor_id_input");

  if (tutorSelect && tutorIdInput) {
    tutorSelect.addEventListener("change", function () {
      const selectedOption = this.options[this.selectedIndex];
      if (selectedOption) {
        tutorIdInput.value = selectedOption.getAttribute("data-tutor-id") || "";
      }
    });
  }

  // Handle reason modal using Bootstrap event
  const reasonModal = document.getElementById("reasonModal");
  if (reasonModal) {
    reasonModal.addEventListener("show.bs.modal", function (event) {
      // Get the element that triggered the modal
      const triggerElement = event.relatedTarget;
      // Extract the reason text from the data attribute
      const reason = triggerElement
        ? triggerElement.getAttribute("data-reason")
        : "Reason not found.";
      // Find the modal body element
      const modalBody = reasonModal.querySelector("#fullReasonText");
      // Update the modal body content
      if (modalBody) {
        modalBody.innerHTML = reason; // Use innerHTML to render potential formatting
      }
    });
  }

  // Consolidated Date/Time Validation Logic
  function setupDateTimeValidation() {
    const now = new Date();
    // Set time to midnight for accurate date comparison
    now.setHours(0, 0, 0, 0);
    const today = now.toISOString().split("T")[0];

    // Get current time components (for today's time validation)
    const nowForTime = new Date(); // Get current time
    const currentHour = nowForTime.getHours().toString().padStart(2, "0");
    const currentMinute = nowForTime.getMinutes().toString().padStart(2, "0");
    const currentTime = `${currentHour}:${currentMinute}`;

    // Function to setup validation for a pair of date/time inputs
    function setupPair(dateInput, timeInput) {
      if (!dateInput || !timeInput) return;

      // Set minimum date allowed to today
      dateInput.min = today;

      // Function to validate time based on selected date
      function validateTime() {
        const selectedDate = dateInput.value;
        const selectedTime = timeInput.value;

        if (!selectedDate || !selectedTime) return; // Don't validate if incomplete

        // Check if selected date is today
        if (selectedDate === today) {
          // If today, ensure time is not in the past
          if (selectedTime < currentTime) {
            alert("Please select a future time for today.");
            timeInput.value = ""; // Clear invalid time
          }
        }
        // Check if selected date is in the past (should be prevented by min attribute, but good failsafe)
        else if (selectedDate < today) {
          alert("Please select today or a future date.");
          dateInput.value = today; // Reset to today
          timeInput.value = ""; // Clear time as date changed
          validateTime(); // Re-validate time for today
        }
      }

      // Add event listeners
      dateInput.addEventListener("change", validateTime);
      timeInput.addEventListener("change", validateTime);

      // Initial check in case the form is pre-filled or edited
      if (dateInput.value) {
        validateTime();
      }
    }

    // Apply validation setup to New Request preferred times
    for (let i = 1; i <= 3; i++) {
      setupPair(
        document.getElementById(`preferred_date_${i}`),
        document.getElementById(`preferred_time_${i}`)
      );
    }

    // Apply validation setup to Edit Request preferred times
    for (let i = 1; i <= 3; i++) {
      setupPair(
        document.getElementById(`edit_preferred_date_${i}`),
        document.getElementById(`edit_preferred_time_${i}`)
      );
    }

    // Apply validation setup to Unavailable Modal alternative times
    for (let i = 1; i <= 3; i++) {
      setupPair(
        document.getElementById(`alt_date_${i}`),
        document.getElementById(`alt_time_${i}`)
      );
    }
  }

  // Initialize date/time validation on page load
  setupDateTimeValidation();

  // Enhanced form submission validation (applied to relevant forms)
  function validateFormBeforeSubmit(form) {
    let isValid = true;
    const dateInputs = form.querySelectorAll('input[type="date"]');
    const timeInputs = form.querySelectorAll('input[type="time"]');
    const now = new Date();

    dateInputs.forEach((dateInput, index) => {
      const timeInput = timeInputs[index];
      // Only validate pairs where both date and time have values
      if (dateInput.value && timeInput && timeInput.value) {
        const selectedDateTime = new Date(
          `${dateInput.value}T${timeInput.value}`
        );

        // Check if the selected date/time is in the past
        if (selectedDateTime < now) {
          alert(
            `The selected time (${dateInput.value} at ${timeInput.value}) is in the past. Please select a future time.`
          );
          isValid = false;
          // Optionally focus the invalid input or add a visual indicator
          timeInput.focus();
          timeInput.classList.add("is-invalid"); // Add Bootstrap invalid class
        } else {
          timeInput.classList.remove("is-invalid"); // Remove class if valid
        }
      } else {
        // If only one part is filled, ensure it's not marked invalid from previous attempt
        if (timeInput) timeInput.classList.remove("is-invalid");
      }
    });

    return isValid;
  }

  // Add form submission validation listeners
  const rescheduleForm = document.getElementById("rescheduleRequestForm");
  const editRescheduleForm = document.getElementById(
    "editRescheduleRequestForm"
  );
  const unavailableForm = document.getElementById("unavailableForm"); // Added validation for this form too

  if (rescheduleForm) {
    // Prevent default form submission, rely on AJAX handler which should include validation
    rescheduleForm.addEventListener("submit", function (e) {
      e.preventDefault(); // Prevent default only if AJAX is handling it
      // Validation logic is inside the AJAX submit handler ('submitStudentReschedule' button click)
      // Ensure validateFormBeforeSubmit is called there if needed.
    });
    // We might need to add validation inside the existing AJAX handler for #submitStudentReschedule
  }

  if (editRescheduleForm) {
    // Prevent default form submission, rely on AJAX handler which should include validation
    editRescheduleForm.addEventListener("submit", function (e) {
      e.preventDefault(); // Prevent default only if AJAX is handling it
      // Validation logic is inside the AJAX submit handler ('updateStudentReschedule' button click)
      // Ensure validateFormBeforeSubmit is called there.
    });
    // We might need to add validation inside the existing AJAX handler for #updateStudentReschedule
  }

  if (unavailableForm) {
    unavailableForm.addEventListener("submit", function (e) {
      // Standard form submission, so validate here
      if (!validateFormBeforeSubmit(this)) {
        e.preventDefault(); // Stop submission if invalid
      }

      // Also check the requirement for at least one alternative time (existing logic)
      const altDates = unavailableForm.querySelectorAll(".alt-date");
      const altTimes = unavailableForm.querySelectorAll(".alt-time");
      let hasAlternative = false;
      for (let i = 0; i < altDates.length; i++) {
        if (altDates[i].value && altTimes[i].value) {
          hasAlternative = true;
          break;
        }
      }
      if (!hasAlternative) {
        e.preventDefault(); // Stop submission
        const unavailableError = document.getElementById(
          "unavailableErrorMessage"
        );
        if (unavailableError) {
          unavailableError.textContent =
            "Please provide at least one valid alternative time."; // More specific message
          unavailableError.style.display = "block";
        } else {
          alert("Please provide at least one alternative time."); // Fallback
        }
      } else {
        const unavailableError = document.getElementById(
          "unavailableErrorMessage"
        );
        if (unavailableError) unavailableError.style.display = "none";
      }
    });
  }
}); // End of DOMContentLoaded listener
