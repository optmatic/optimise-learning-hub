document.addEventListener("DOMContentLoaded", function () {
  // Resource upload functionality
  const addResourceButton = document.getElementById("add-resource");
  if (addResourceButton) {
    addResourceButton.addEventListener("click", function () {
      const container = document.getElementById("resource-uploads");
      const newField = document.createElement("div");
      newField.className =
        "resource-upload-field mb-2 d-flex align-items-center";

      newField.innerHTML = `
                <input type="file" name="resources[]" class="form-control">
                <button type="button" class="btn btn-danger btn-sm ms-2 remove-resource">
                    <i class="fas fa-times"></i>
                </button>
            `;

      container.appendChild(newField);

      // Add remove button functionality
      newField
        .querySelector(".remove-resource")
        .addEventListener("click", function () {
          this.parentElement.remove();
        });
    });
  }

  // ===============================
  // Reschedule Request Functionality
  // ===============================

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

  // ===============================
  // Reschedule History Toggle
  // ===============================
  const toggleButton = document.getElementById("toggleRescheduleHistory");
  if (toggleButton) {
    toggleButton.addEventListener("click", function () {
      const historyTable = document.getElementById("rescheduleHistoryTable");
      const showText = this.querySelector(".show-text");
      const hideText = this.querySelector(".hide-text");
      const showIcon = this.querySelector(".show-icon");
      const hideIcon = this.querySelector(".hide-icon");

      if (historyTable.style.display === "none") {
        // Show the table
        historyTable.style.display = "block";
        showText.classList.add("d-none");
        hideText.classList.remove("d-none");
        showIcon.classList.add("d-none");
        hideIcon.classList.remove("d-none");
      } else {
        // Hide the table
        historyTable.style.display = "none";
        showText.classList.remove("d-none");
        hideText.classList.add("d-none");
        showIcon.classList.remove("d-none");
        hideIcon.classList.add("d-none");
      }
    });
  }

  // Auto-refresh the reschedule history table every 60 seconds
  function refreshRescheduleHistory() {
    const historyTable = document.getElementById("rescheduleHistoryTable");
    if (historyTable && historyTable.style.display !== "none") {
      fetch(window.location.href + "?refresh_reschedule=1")
        .then((response) => response.text())
        .then((html) => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, "text/html");
          const newTable = doc.getElementById("rescheduleHistoryTable");
          if (newTable) {
            historyTable.innerHTML = newTable.innerHTML;
          }
        })
        .catch((error) =>
          console.error("Error refreshing reschedule history:", error)
        );
    }
  }

  // Set up auto-refresh interval
  setInterval(refreshRescheduleHistory, 60000); // Refresh every 60 seconds

  // ===============================
  // Unconfirmed Requests Toggle
  // ===============================
  const toggleUnconfirmedButton = document.getElementById(
    "toggleUnconfirmedRequests"
  );
  if (toggleUnconfirmedButton) {
    toggleUnconfirmedButton.addEventListener("click", function () {
      const unconfirmedSection = document.getElementById(
        "unconfirmedRequestsSection"
      );
      const showText = this.querySelector(".show-text");
      const hideText = this.querySelector(".hide-text");
      const showIcon = this.querySelector(".show-icon");
      const hideIcon = this.querySelector(".hide-icon");

      if (unconfirmedSection.style.display === "none") {
        // Show the section
        unconfirmedSection.style.display = "block";
        showText.classList.add("d-none");
        hideText.classList.remove("d-none");
        showIcon.classList.add("d-none");
        hideIcon.classList.remove("d-none");
      } else {
        // Hide the section
        unconfirmedSection.style.display = "none";
        showText.classList.remove("d-none");
        hideText.classList.add("d-none");
        showIcon.classList.remove("d-none");
        hideIcon.classList.add("d-none");
      }
    });
  }

  // ===============================
  // Alternative Times Form Submission
  // ===============================
  document.querySelectorAll('form[id^="alternativeForm"]').forEach((form) => {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const requestId = this.querySelector('input[name="request_id"]').value;
      const successMessage = document.getElementById(
        "alternativeSuccess" + requestId
      );

      fetch(window.location.href, {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          if (response.ok) {
            // Show success message
            successMessage.style.display = "block";

            // Hide the form
            this.querySelectorAll("input, textarea, button").forEach((el) => {
              el.disabled = true;
            });

            // Reload the page after 2 seconds to update the UI
            setTimeout(() => {
              window.location.reload();
            }, 2000);
          } else {
            alert(
              "There was an error submitting your alternative times. Please try again."
            );
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert(
            "There was an error submitting your alternative times. Please try again."
          );
        });
    });
  });

  // Auto-refresh the unconfirmed requests section every 60 seconds
  function refreshUnconfirmedRequests() {
    const unconfirmedSection = document.getElementById(
      "unconfirmedRequestsSection"
    );
    if (unconfirmedSection && unconfirmedSection.style.display !== "none") {
      fetch(window.location.href + "?refresh_unconfirmed=1")
        .then((response) => response.text())
        .then((html) => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, "text/html");
          const newSection = doc.getElementById("unconfirmedRequestsSection");
          if (newSection) {
            unconfirmedSection.innerHTML = newSection.innerHTML;
          }
        })
        .catch((error) =>
          console.error("Error refreshing unconfirmed requests:", error)
        );
    }
  }

  // Set up auto-refresh interval for unconfirmed requests
  setInterval(refreshUnconfirmedRequests, 60000); // Refresh every 60 seconds

  // ===============================
  // Toggle Alternatives Form
  // ===============================
  document.querySelectorAll(".toggle-alternatives").forEach((button) => {
    button.addEventListener("click", function () {
      const targetId = this.getAttribute("data-target");
      const targetForm = document.getElementById(targetId);

      if (targetForm.style.display === "none") {
        targetForm.style.display = "block";
        this.classList.remove("btn-danger");
        this.classList.add("btn-secondary");
        this.textContent = "Hide Alternative Times";
      } else {
        targetForm.style.display = "none";
        this.classList.remove("btn-secondary");
        this.classList.add("btn-danger");
        this.textContent = "Unavailable";
      }
    });
  });

  // ===============================
  // Confirm Preferred Time
  // ===============================
  document.querySelectorAll(".confirm-preferred-time").forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();

      const requestId = this.getAttribute("data-request-id");
      const preferredIndex = this.getAttribute("data-index");

      if (confirm("Are you sure you want to confirm this preferred time?")) {
        // Create form data
        const formData = new FormData();
        formData.append("confirm_preferred_time", "1");
        formData.append("request_id", requestId);
        formData.append("preferred_index", preferredIndex);

        // Submit via fetch
        fetch(window.location.href, {
          method: "POST",
          body: formData,
        })
          .then((response) => {
            if (response.ok) {
              // Reload the page to show the updated status
              window.location.reload();
            } else {
              alert(
                "There was an error confirming the preferred time. Please try again."
              );
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            alert(
              "There was an error confirming the preferred time. Please try again."
            );
          });
      }
    });
  });

  // ===============================
  // Request Action Handling (Confirm/Decline)
  // ===============================
  // Handle confirm action buttons
  document.querySelectorAll(".confirm-action").forEach((button) => {
    button.addEventListener("click", function () {
      const requestId = this.getAttribute("data-request-id");
      const row = this.closest("tr");

      // Send AJAX request to confirm
      fetch(ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body:
          "action=handle_tutor_request_ajax&request_action=confirm&request_id=" +
          requestId +
          "&security=" +
          tutorRequestsData.nonce,
        credentials: "same-origin",
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Update status cell
            row.querySelector("td:nth-child(4)").innerHTML =
              '<span class="badge bg-success">Confirmed</span>';
            // Update actions cell
            row.querySelector("td:nth-child(5)").innerHTML =
              '<span class="text-muted">No actions available</span>';
          } else {
            alert("Error: " + data.message);
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("An error occurred. Please try again.");
        });
    });
  });

  // Handle decline action buttons
  document.querySelectorAll(".decline-action").forEach((button) => {
    button.addEventListener("click", function () {
      const requestId = this.getAttribute("data-request-id");
      const row = this.closest("tr");
      const reasonInput = row.querySelector(".decline-reason");
      const reason = reasonInput.value.trim();

      if (!reason) {
        alert("Please provide a reason for declining.");
        reasonInput.focus();
        return;
      }

      // Send AJAX request to decline
      fetch(ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body:
          "action=handle_tutor_request_ajax&request_action=decline&request_id=" +
          requestId +
          "&reason=" +
          encodeURIComponent(reason) +
          "&security=" +
          tutorRequestsData.nonce,
        credentials: "same-origin",
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Update status cell
            row.querySelector("td:nth-child(4)").innerHTML =
              '<span class="badge bg-danger">Declined</span>';
            // Update actions cell
            row.querySelector("td:nth-child(5)").innerHTML =
              "Reason: " + reason;
          } else {
            alert("Error: " + data.message);
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("An error occurred. Please try again.");
        });
    });
  });

  // ===============================
  // jQuery Functionality (Legacy)
  // ===============================
  // Check if jQuery is available before using it
  if (typeof jQuery !== "undefined") {
    jQuery(function ($) {
      // Handle confirm button clicks (jQuery version)
      $(".confirm-request-btn").on("click", function () {
        const requestId = $(this).data("request-id");
        const row = $(this).closest("tr");

        if (confirm("Are you sure you want to confirm this request?")) {
          $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
              action: "handle_tutor_request",
              request_id: requestId,
              status: "confirmed",
              nonce: tutor_dashboard_vars.nonce,
            },
            success: function (response) {
              const data = JSON.parse(response);
              if (data.success) {
                // Update status cell
                row
                  .find("td:nth-child(4)")
                  .html('<span class="badge bg-success">Confirmed</span>');
                // Update actions cell
                row.find("td:nth-child(5)").html("Confirmed");
              } else {
                alert("Error: " + data.message);
              }
            },
            error: function (xhr, status, error) {
              console.error("Error:", error);
              alert("An error occurred. Please try again.");
            },
          });
        }
      });

      // Decline request modal functionality
      $(".decline-request-btn").on("click", function () {
        const requestId = $(this).data("request-id");
        const row = $(this).closest("tr");

        showDeclineModal(requestId, row);
      });

      // Process the decline action when confirmed
      $(document).on("click", "#confirm-decline-btn", function () {
        const requestId = $(this).data("request-id");
        const reason = $("#decline-reason").val();
        const row = $(this).data("row");

        if (!reason.trim()) {
          alert("Please provide a reason for declining.");
          return;
        }

        processDeclineRequest(requestId, reason, row);
      });

      /**
       * Display the modal for declining a request
       */
      function showDeclineModal(requestId, row) {
        // Create modal if it doesn't exist
        if ($("#decline-modal").length === 0) {
          $("body").append(`
                        <div id="decline-modal" class="modal fade" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Decline Request</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Please provide a reason for declining this request:</p>
                                        <textarea id="decline-reason" class="form-control" rows="3"></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-danger" id="confirm-decline-btn">Decline Request</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
        }

        // Set up the modal with current request data
        $("#confirm-decline-btn")
          .data("request-id", requestId)
          .data("row", row);
        $("#decline-reason").val("");

        // Show the modal
        const declineModal = new bootstrap.Modal(
          document.getElementById("decline-modal")
        );
        declineModal.show();
      }

      /**
       * Process the decline request via AJAX
       */
      function processDeclineRequest(requestId, reason, row) {
        $.ajax({
          url: ajaxurl,
          type: "POST",
          data: {
            action: "handle_tutor_request",
            request_id: requestId,
            request_action: "decline",
            reason: reason,
            nonce: tutor_dashboard_data.nonce,
          },
          success: function (response) {
            const data = JSON.parse(response);
            if (data.success) {
              // Update status cell
              row
                .find("td:nth-child(4)")
                .html('<span class="badge bg-danger">Declined</span>');
              // Update actions cell
              row.find("td:nth-child(5)").html("Reason: " + reason);

              // Close the modal
              $("#decline-modal").modal("hide");
            } else {
              alert("Error: " + data.message);
            }
          },
          error: function (xhr, status, error) {
            console.error("Error:", error);
            alert("An error occurred. Please try again.");
          },
        });
      }
    });
  }
});
