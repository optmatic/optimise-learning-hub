document.addEventListener("DOMContentLoaded", function () {
  // Handle reschedule request submission
  const submitButton = document.getElementById("submitReschedule");
  if (submitButton) {
    submitButton.addEventListener("click", function () {
      // Get form data
      const form = document.getElementById("rescheduleForm");
      const formData = new FormData(form);

      // Submit the form using fetch
      fetch(window.location.href, {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          if (response.ok) {
            // Show success message
            const successMessage = document.getElementById(
              "rescheduleSuccessMessage"
            );
            successMessage.style.display = "block";

            // Clear form fields
            document.getElementById("student_select").value = "";
            document.getElementById("original_date").value = "";
            document.getElementById("original_time").value = "";
            document.getElementById("new_date").value = "";
            document.getElementById("new_time").value = "";

            // Hide the form
            form.style.display = "none";

            // Set a timeout to close the modal after 3 seconds
            setTimeout(function () {
              // Close the modal
              const modal = bootstrap.Modal.getInstance(
                document.getElementById("newRescheduleModal")
              );
              modal.hide();

              // Reload the page to show the updated list of reschedule requests
              window.location.reload();
            }, 3000);
          } else {
            alert(
              "There was an error submitting your request. Please try again."
            );
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert(
            "There was an error submitting your request. Please try again."
          );
        });
    });
  }

  // Prevent form resubmission on page refresh
  if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
  }

  // Handle tab switching to mark confirmed reschedules as viewed
  const scheduleTab = document.getElementById("schedule-tab");
  if (scheduleTab) {
    scheduleTab.addEventListener("click", function () {
      // Use AJAX to mark all confirmed reschedules as viewed
      fetch(window.location.href, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: "mark_viewed=1",
      }).then((response) => {
        if (response.ok) {
          // Remove the notification badge
          const badge = scheduleTab.querySelector(".badge");
          if (badge) {
            badge.remove();
          }
        }
      });
    });
  }

  // Mark alternative times as viewed when Tutor Comms tab is opened
  const tutorCommsTab = document.getElementById("requests-tab");
  if (tutorCommsTab) {
    tutorCommsTab.addEventListener("click", function () {
      // Send AJAX request to mark alternatives as viewed
      fetch(studentDashboardData.markAlternativesViewedUrl, {
        method: "GET",
        credentials: "same-origin",
      })
        .then((response) => {
          // Remove the notification badge after viewing
          const badge = this.querySelector(".badge");
          if (badge) {
            badge.style.display = "none";
          }
        })
        .catch((error) => {
          console.error("Error marking alternatives as viewed:", error);
        });
    });
  }

  // Handle unavailable for all button
  document
    .querySelectorAll('button[name="unavailable_all"]')
    .forEach((button) => {
      button.addEventListener("click", function (e) {
        if (
          !confirm(
            "Are you sure you are unavailable for all these alternative times?"
          )
        ) {
          e.preventDefault();
        }
      });
    });

  // Handle student reschedule request submission
  const submitStudentRescheduleButton = document.getElementById(
    "submitStudentReschedule"
  );
  if (submitStudentRescheduleButton) {
    submitStudentRescheduleButton.addEventListener("click", function (e) {
      e.preventDefault();

      // Get form data
      const form = document.getElementById("rescheduleRequestForm");

      // Validate required fields
      const tutorSelect = document.getElementById("tutor_select");
      const lessonSelect = document.getElementById("lesson_select");
      const reason = document.getElementById("reason");
      const errorMessage = document.getElementById(
        "rescheduleRequestErrorMessage"
      );
      const preferredTimesError = document.getElementById(
        "preferred-times-error"
      );

      // Check if basic required fields are filled
      if (!tutorSelect.value || !lessonSelect.value || !reason.value) {
        errorMessage.style.display = "block";
        errorMessage.querySelector("p").textContent =
          "Please fill in all required fields (tutor, lesson, and reason).";
        return; // Stop form submission
      } else {
        errorMessage.style.display = "none";
      }

      // Check if at least one preferred time is provided
      let hasPreferredTime = false;
      for (let i = 1; i <= 3; i++) {
        const dateInput = document.getElementById(`preferred_date_${i}`);
        const timeInput = document.getElementById(`preferred_time_${i}`);

        if (dateInput && timeInput && dateInput.value && timeInput.value) {
          hasPreferredTime = true;
          break;
        }
      }

      if (!hasPreferredTime) {
        preferredTimesError.style.display = "block";
        return; // Stop form submission
      } else {
        preferredTimesError.style.display = "none";
      }

      const formData = new FormData(form);

      // Submit the form using fetch
      fetch(window.location.href, {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          if (response.ok) {
            // Show success message
            const successMessage = document.getElementById(
              "rescheduleRequestSuccessMessage"
            );
            successMessage.style.display = "block";

            // Hide the form
            form.style.display = "none";
            errorMessage.style.display = "none";

            // Set a timeout to close the modal after 3 seconds
            setTimeout(function () {
              // Close the modal
              const modal = bootstrap.Modal.getInstance(
                document.getElementById("newRescheduleRequestModal")
              );
              modal.hide();

              // Reload the page to show the updated list of reschedule requests
              window.location.reload();
            }, 3000);
          } else {
            alert(
              "There was an error submitting your request. Please try again."
            );
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert(
            "There was an error submitting your request. Please try again."
          );
        });
    });
  }

  // Add event listeners to preferred time inputs to hide error when filled
  const preferredDateInputs = document.querySelectorAll(".preferred-date");
  const preferredTimeInputs = document.querySelectorAll(".preferred-time");
  const preferredTimesError = document.getElementById("preferred-times-error");

  function checkPreferredTimes() {
    let hasPreferredTime = false;
    for (let i = 1; i <= 3; i++) {
      const dateInput = document.getElementById(`preferred_date_${i}`);
      const timeInput = document.getElementById(`preferred_time_${i}`);

      if (dateInput && timeInput && dateInput.value && timeInput.value) {
        hasPreferredTime = true;
        break;
      }
    }

    if (hasPreferredTime) {
      preferredTimesError.style.display = "none";
    }
  }

  preferredDateInputs.forEach((input) => {
    input.addEventListener("change", checkPreferredTimes);
  });

  preferredTimeInputs.forEach((input) => {
    input.addEventListener("change", checkPreferredTimes);
  });

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

        // Fetch preferred times for this request
        fetch(studentDashboardData.ajaxurl, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: "action=get_preferred_times&request_id=" + requestId,
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success && data.data.preferred_times) {
              const preferredTimes = data.data.preferred_times;

              // Fill in the preferred times fields
              for (let i = 0; i < preferredTimes.length && i < 3; i++) {
                document.getElementById(
                  "edit_preferred_date_" + (i + 1)
                ).value = preferredTimes[i].date;
                document.getElementById(
                  "edit_preferred_time_" + (i + 1)
                ).value = preferredTimes[i].time;
              }
            }
          });
      });
    });
  }

  // Handle delete request confirmation
  const deleteForms = document.querySelectorAll(".delete-request-form");
  if (deleteForms.length > 0) {
    deleteForms.forEach((form) => {
      form.addEventListener("submit", function (e) {
        if (
          !confirm("Are you sure you want to delete this reschedule request?")
        ) {
          e.preventDefault();
        }
      });
    });
  }

  // Handle update reschedule request submission
  const updateButton = document.getElementById("updateStudentReschedule");
  if (updateButton) {
    updateButton.addEventListener("click", function (e) {
      e.preventDefault();

      // Get form data
      const form = document.getElementById("editRescheduleRequestForm");
      const formData = new FormData(form);

      // Submit the form using fetch
      fetch(window.location.href, {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          if (response.ok) {
            // Show success message
            const successMessage = document.getElementById(
              "editRescheduleSuccessMessage"
            );
            successMessage.style.display = "block";

            // Set a timeout to close the modal after 2 seconds
            setTimeout(function () {
              // Close the modal
              const modal = bootstrap.Modal.getInstance(
                document.getElementById("editRescheduleRequestModal")
              );
              modal.hide();

              // Reload the page to show the updated list
              window.location.reload();
            }, 2000);
          } else {
            alert(
              "There was an error updating your request. Please try again."
            );
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("There was an error updating your request. Please try again.");
        });
    });
  }
});

// Add this script to handle form submissions and maintain the active tab
jQuery(document).ready(function ($) {
  // Add hidden input to edit and delete forms
  $(".edit-request-btn, .delete-request-form button")
    .closest("form")
    .append(
      '<input type="hidden" name="active_tab" value="requests" class="requests-tab-return">'
    );

  // If URL has active_tab parameter, activate that tab
  const urlParams = new URLSearchParams(window.location.search);
  const activeTab = urlParams.get("active_tab");
  if (activeTab === "requests") {
    $("#requests-tab").tab("show");
  }
});

// Update hidden fields when a lesson is selected
document.addEventListener("DOMContentLoaded", function () {
  const lessonSelect = document.getElementById("lesson_select");
  const originalDate = document.getElementById("original_date");
  const originalTime = document.getElementById("original_time");

  if (lessonSelect) {
    lessonSelect.addEventListener("change", function () {
      if (this.value) {
        const [date, time] = this.value.split("|");
        originalDate.value = date;
        originalTime.value = time;
      } else {
        originalDate.value = "";
        originalTime.value = "";
      }
    });
  }
});
