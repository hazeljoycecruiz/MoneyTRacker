<?php

// Include database connection
include('database/db_connection.php');
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
  // Redirect to the login page if no user is logged in
  header("Location: index.php");
  exit; // Always exit after a header redirect to prevent further code execution
}

// Handle incoming requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $action = $_POST['action'] ?? '';
  $id = $_POST['id'] ?? null;

  //Add Expenses Action
  if ($action === 'add_expenses') {
    $user_id = $_SESSION['user_id'] ?? null; // Automatically fetch user_id from the session
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);

    //Validations of input fields
    if (empty($category) || !in_array($category, ['Education', 'Entertainment', 'Food', 'Health', 'Miscellaneous', 'Shopping', 'Transportation', 'Utilities'])) {
      echo json_encode(['status' => 'error', 'message' => 'Invalid category']);
      exit;
    }
    if (empty($type) || !preg_match('/^[a-zA-Z\s]+$/', $type)) {
      echo json_encode(['status' => 'error', 'message' => 'Invalid type']);
      exit;
    }
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
      echo json_encode(['status' => 'error', 'message' => 'Invalid amount']);
      exit;
    }
    if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
      echo json_encode(['status' => 'error', 'message' => 'Invalid date format']);
      exit;
    }

    //insert data into database
    $query = "INSERT INTO user_expenses (user_id, category, type, amount, date) VALUES ('$user_id', '$category', '$type', '$amount', '$date')";
    if (mysqli_query($conn, $query)) {
      echo json_encode(['status' => 'success', 'message' => 'Expenses added successfully!']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit;
  }

  // Update Expenses
  if ($action === 'update_expenses' && $id) {
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $amount = floatval($_POST['amount']); // Ensure amount is a float
    $date = mysqli_real_escape_string($conn, $_POST['date']);

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
      echo json_encode(['status' => 'error', 'message' => 'Invalid date format. Use YYYY-MM-DD']);
      exit;
    }

    // Prepare the update query
    $stmt = $conn->prepare("UPDATE user_expenses SET category = ?, type = ?, amount = ?, date = ? WHERE expense_id = ?");
    $stmt->bind_param("ssdsi", $category, $type, $amount, $date, $id);

    // Execute and check for success
    if ($stmt->execute()) {
      echo json_encode(['status' => 'success', 'message' => 'Expenses updated successfully.']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Failed to update expenses: ' . $stmt->error]);
    }
    exit;
  }

  if ($action === 'update_expenses' && $id) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("UPDATE user_expenses SET category = ?, type = ?, amount = ?, date = ? WHERE expense_id = ? AND user_id = ?");
    $stmt->bind_param("ssdsii", $category, $type, $amount, $date, $id, $user_id);
    if ($stmt->execute()) {
      echo json_encode(['status' => 'success', 'message' => 'Expenses updated successfully.']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Failed to update expenses.']);
    }
    exit;
  }


  /// Fetch Expenses History
  if ($action === 'fetch_history') {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT expense_id, category, type, amount, date FROM user_expenses WHERE user_id = '$user_id' ORDER BY date DESC";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
      $data = [];
      while ($row = $result->fetch_assoc()) {
        $data[] = $row;
      }
      echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
      echo json_encode(['status' => 'success', 'data' => []]);
    }
    exit;
  }


  // Remove Expenses
  if ($action === 'remove_expenses' && $id) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("DELETE FROM user_expenses WHERE expense_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    if ($stmt->execute()) {
      echo json_encode(['status' => 'success', 'message' => 'Expenses removed successfully.']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Failed to remove expenses.']);
    }
    exit;
  }
}

// Handle GET requests for fetching expenses (if applicable)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_expenses') {
  $user_id = $_SESSION['user_id'];
  $stmt = $conn->query("SELECT * FROM user_expenses WHERE user_id = '$user_id'");
  $rows = $stmt->fetch_all(MYSQLI_ASSOC);
  foreach ($rows as $row) {
    echo "<tr id='row-{$row['expense_id']}'>
                  <td class='category'>{$row['category']}</td>
                  <td class='type'>{$row['type']}</td>
                  <td class='amount'>{$row['amount']}</td>
                  <td class='date'>{$row['date']}</td>
                  <td>
                      <button class='edit' data-id='{$row['expense_id']}'>Edit</button>
                      <button class='remove' data-id='{$row['expense_id']}'>Remove</button>
                  </td>
                </tr>";
  }
  exit;
}


// Close the database connection
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
    rel="stylesheet" />
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
  <link
    href="https://fonts.googleapis.com/css?family=Inter&display=swap"
    rel="stylesheet" />
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.min.css" />
  <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

  <!-- Include SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

  <!-- Include SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Bootstrap Datepicker CSS -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" />

  <link
    rel="icon"
    href="./assets/img/piclogo-removebg.png"
    type="image/x-icon" />

  <link rel="stylesheet" href="css/sidebar.css" />

  <link rel="stylesheet" href="css/expenses.css" />

  <title>MoneyTracker</title>
</head>

<body>
  <div class="main-container d-flex">
    <div class="sidebar" id="side_nav">
      <div class="header-box px-2 pt-3 pb-4 d-flex justify-content-between">
        <button class="btn d-md-none d-block close-btn px-1 py-0 text-white">
          <i class="bi bi-person"></i>
        </button>
      </div>

      <ul class="list-unstyled">
        <li class="">
          <a
            href="dashboard.php"
            class="text-decoration-none px-3 py-2 d-block">
            <i class="bi bi-house-door-fill"></i> Dashboard
          </a>
        </li>

        <li class="active">
          <a
            href="expenses.php"
            class="text-decoration-none px-3 py-2 d-block">
            <i class="bi bi-cash-coin"></i> Expenses
          </a>
        </li>

        <li class="">
          <a
            href="summary.php"
            class="text-decoration-none px-3 py-2 d-block d-flex justify-content-between">
            <span> <i class="bi bi-bar-chart-line-fill"></i> Summary </span>
          </a>
        </li>

      </ul>

      <!-- Sidebar with Logout -->
      <ul class="list-unstyled pt-5">
        <li>
          <a
            href="#"
            id="logout"
            class="text-decoration-none px-3 py-2 d-block">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
        </li>
      </ul>
    </div>

    <div class="content">
      <nav class="navbar navbar-expand-md">
        <div class="container-fluid navBar">
          <div class="d-flex justify-content-between d-md-none">
            <button class="btn open-btn me-2">
              <i class="fa fa-bars"></i>
            </button>
          </div>
          <button
            class="navbar-toggler border-0"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent"
            aria-expanded="false"
            aria-label="Toggle navigation">
            <i class="fal fa-bars"></i>
          </button>
        </div>
      </nav>
      <div
        id="errorMessages"
        class="alert alert-danger"
        style="display: none">
        <ul id="errorList"></ul>
      </div>

      <div class="modal fade pt-5" id="sucessModal" tabindex="-1">
        <div class="modal-dialog">
          <div
            class="container successCon text-center d-flex justify-content-center align-item-center">
            <div
              class="alert alert-success"
              id="successMessage"
              role="alert"></div>
          </div>
        </div>
      </div>

      <div
        class="pt-5 modal fade"
        id="editExpensesModal"
        tabindex="1"
        role="dialog">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Edit Expenses</h5>
              <button
                type="button"
                style="background-color: transparent; border: none"
                class="close"
                data-dismiss="modal"
                aria-label="Close">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>

            <div class="modal-body" id="expensesEditForm">
              <!-- Modal Content -->
            </div>
          </div>
        </div>
      </div>

      <div class="ps-5 pt-1">
        <div class="dashboard-content">
          <h2 class="ps-2 text-budget">Expenses</h2>
          <div class="container-fluid outside">
            <div class="box">
              <div class="align">
                <div class="row pt-2">
                  <div class="col text-center">
                    <label for="category" class="spanSize">Category</label>
                  </div>
                  <div class="col text-center pe-5">
                    <label for="type" class="spanSize ps-5">Type</label>
                  </div>
                  <div class="col text-center">
                    <label for="amount" class="spanSize ps-5">Amount</label>
                  </div>
                  <div class="col text-center pe-5">
                    <label for="date" class="spanSize ps-5">Date</label>
                  </div>
                  <hr class="HrTopBox" />
                </div>
                <form autocomplete="off" id="expensesAddForm" method="POST" action="expenses.php">
                  <input type="hidden" name="action" value="add_expenses">
                  <div class="row rowSize">
                    <div class="col justify-content-center">
                      <select
                        id="category"
                        class="form-select inputCategory"
                        name="category" required>
                        <option value="" disabled selected>
                          -- Select an Option --
                        </option>
                        <option value="Education">Education</option>
                        <option value="Entertainment">Entertainment</option>
                        <option value="Food">Food</option>
                        <option value="Health">Health</option>
                        <option value="Miscellaneous">Miscellaneous</option>
                        <option value="Shopping">Shopping</option>
                        <option value="Transportation">Transportation</option>
                        <option value="Utilities">Utilities</option>
                      </select>
                      <span
                        class="validation-error-color"
                        id="category_error"></span>
                    </div>
                    <div class="col">
                      <input type="text" name="type" id="type" placeholder="Type" class="form-control " required />
                      <span
                        class="validation-error-color"
                        id="type_error"></span>
                    </div>
                    <div class="col">
                      <input type="text" name="amount" id="amount" placeholder="Amount" style="text-indent: 10px" class="form-control inpamount" required />
                      <span
                        class="validation-error-color"
                        id="amount_error"></span>
                    </div>
                    <div class="col">
                      <input
                        id="picker"
                        type="date"
                        name="date"
                        class="form-control" required />
                      <span
                        class="validation-error-color"
                        id="date_error"></span>
                    </div>

                  </div>

                  <div class="row pt-2 pb-1 justify-content-center">
                    <button
                      type="submit"
                      style="
                          background: #8cef84;
                          border-radius: 8px;
                          border: none;
                          width: 150px;
                        "
                      id="add"
                      class="add">
                      Add
                    </button>

                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <div class="container-fluid outside pt-3">
          <div class="boxbelow">
            <div class="align">
              <div class="box2 pt-3">
                <h2>History</h2>
                <div class="row row1 pt-4">
                  <div class="col colum1 text-center">
                    <h4>Category</h4>
                  </div>
                  <div class="col colum2 text-center">
                    <h4 class="grey">Type</h4>
                  </div>
                  <div class="col colum3 text-center">
                    <h4 class="">Amount</h4>
                  </div>
                  <div class="col colum4 text-center">
                    <h4 class="grey">Date</h4>
                  </div>
                  <div class="col text-center">
                    <h4 class="">Actions</h4>
                  </div>
                </div>

                <div class="container scrollbar">
                  <!-- container of loaded budgets of user -->

                  <div
                    class="pt-5 d-flex justify-content-center align-items-center">
                    <div class="spinner-border" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                    <b class="ms-2">Loading Data...</b>
                  </div>
                </div>
              </div>
              <div class="row rowbotborder"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      document.getElementById("expensesAddForm").addEventListener("submit", function(event) {
        event.preventDefault(); // Prevent the form from reloading the page

        const category = document.getElementById("category").value;
        const type = document.getElementById("type").value;
        const amount = document.getElementById("amount").value;
        const date = document.getElementById("picker").value;

        if (category === "" || type === "" || amount === "" || date === "") {
          Swal.fire({
            icon: "warning",
            title: "Missing Fields",
            text: "Please fill out all fields before submitting.",
          });
          return;
        }

        const formData = new FormData();
        formData.append("action", "add_expenses");
        formData.append("category", category);
        formData.append("type", type);
        formData.append("amount", amount);
        formData.append("date", date);

        fetch("expenses.php", {
            method: "POST",
            body: formData,
          })
          .then((response) => response.json())
          .then((data) => {
            if (data.status === "success") {
              Swal.fire({
                icon: "success",
                title: "Expenses Added",
                text: data.message,
              });

              // Clear form fields
              document.getElementById("expensesAddForm").reset();
              fetchExpensesHistory(); // Refresh the expenses list
            } else {
              Swal.fire({
                icon: "error",
                title: "Error",
                text: data.message,
              });
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            Swal.fire({
              icon: "error",
              title: "Error",
              text: "An unexpected error occurred. Please try again later.",
            });
          });
      });
    });


    // Add event listener for the "Logout" link
    document.getElementById("logout").addEventListener("click", function(event) {
      event.preventDefault(); // Prevent default behavior of the link

      // SweetAlert2 confirmation popup
      Swal.fire({
        title: 'Are you sure?',
        text: "You will be logged out of your account.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, logout',
        cancelButtonText: 'No'
      }).then((result) => {
        if (result.isConfirmed) {
          // Redirect to index.php on confirmation
          window.location.href = "index.php"; // Change "index.php" to your desired logout redirection page
        }
      });
    });

    // Function to fetch and render expenses history
    function fetchExpensesHistory() {
      const historyContainer = document.querySelector(".scrollbar");
      historyContainer.innerHTML = `
    <div class="pt-5 d-flex justify-content-center align-items-center">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <b class="ms-2">Loading Data...</b>
    </div>
  `;

      fetch("expenses.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: "action=fetch_history",
        })
        .then((response) => response.json())
        .then((data) => {
          if (data.status === "success") {
            const expenses = data.data;

            if (!expenses.length) {
              historyContainer.innerHTML = `
            <div class="pt-5 d-flex justify-content-center align-items-center">
              <b>No expenses history available.</b>
            </div>
          `;
              return;
            }

            // Generate expense rows dynamically
            const rows = expenses
              .map((expense) => `
            <div class="row py-2 border-bottom align-items-center" id="row-${expense.expense_id}">
              <div class="col text-center"><p>${expense.category}</p></div>
              <div class="col text-center"><p>${expense.type || "N/A"}</p></div>
              <div class="col grey text-center"><p>₱${parseFloat(expense.amount).toFixed(2)}</p></div>
              <div class="col text-center"><p>${expense.date}</p></div>
              <div class="col text-center">
                <button class="btn btn-sm btn-warning me-2 edit" data-id="${expense.expense_id}">Edit</button>
                <button class="btn btn-sm btn-danger remove" data-id="${expense.expense_id}">Remove</button>
              </div>
            </div>
          `).join("");

            historyContainer.innerHTML = rows;

          } else {
            historyContainer.innerHTML = `<b>Error: ${data.message}</b>`;
          }
        })
        .catch((error) => {
          console.error("Error fetching expenses:", error);
          historyContainer.innerHTML = `<b>An error occurred while fetching expenses history.</b>`;
        });
    }

    // Edit Expenses Handler
    document.querySelector(".scrollbar").addEventListener("click", function(event) {
      if (event.target.classList.contains("edit")) {
        const expenseId = event.target.dataset.id;
        const expensesRow = document.querySelector(`#row-${expenseId}`);

        // Correctly fetch row data with failsafe defaults
        const category = expensesRow.querySelector(".col:nth-child(1) p")?.textContent.trim() || "N/A";
        const type = expensesRow.querySelector(".col:nth-child(2) p")?.textContent.trim() || "";
        const amount = parseFloat(
          expensesRow.querySelector(".col:nth-child(3) p")?.textContent.replace("₱", "") || 0
        );
        const date = expensesRow.querySelector(".col:nth-child(4) p")?.textContent.trim() || "";

        Swal.fire({
          title: "Edit Expenses",
          html: `
        <select id="edit-category" class="swal2-input">
          <option value="Education" ${category === "Education" ? "selected" : ""}>Education</option>
          <option value="Entertainment" ${category === "Entertainment" ? "selected" : ""}>Entertainment</option>
          <option value="Food" ${category === "Food" ? "selected" : ""}>Food</option>
          <option value="Health" ${category === "Health" ? "selected" : ""}>Health</option>
          <option value="Miscellaneous" ${category === "Miscellaneous" ? "selected" : ""}>Miscellaneous</option>
          <option value="Shopping" ${category === "Shopping" ? "selected" : ""}>Shopping</option>
          <option value="Transportation" ${category === "Transportation" ? "selected" : ""}>Transportation</option>
          <option value="Utilities" ${category === "Utilities" ? "selected" : ""}>Utilities</option>
        </select>
        <input id="edit-type" type="text" class="swal2-input" placeholder="Type" value="${type}">
        <input id="edit-amount" type="number" class="swal2-input" placeholder="Amount" value="${amount}">
        <input id="edit-date" type="date" class="swal2-input" value="${date}">
      `,
          confirmButtonText: "Save Changes",
          showCancelButton: true,
          preConfirm: () => {
            const updatedCategory = document.getElementById("edit-category").value;
            const updatedType = document.getElementById("edit-type").value.trim();
            const updatedAmount = parseFloat(document.getElementById("edit-amount").value);
            const updatedDate = document.getElementById("edit-date").value;

            // Validation using SweetAlert2's showValidationMessage
            if (!updatedCategory || !updatedType || isNaN(updatedAmount) || updatedAmount <= 0 || !updatedDate) {
              Swal.fire({
                icon: 'warning',
                title: 'Missing Fields',
                text: 'Please fill out all fields before submitting.',
                confirmButtonText: 'OK',
                customClass: {
                  popup: 'missing-fields-alert' // Optional: style the popup for custom CSS (e.g., icon, text, etc.)
                }
              });
              return false; // Prevent submission
            }

            return {
              category: updatedCategory,
              type: updatedType,
              amount: updatedAmount,
              date: updatedDate
            };
          },
        }).then((result) => {
          if (result.isConfirmed) {
            const formData = new FormData();
            formData.append("action", "update_expenses");
            formData.append("id", expenseId);
            formData.append("category", result.value.category);
            formData.append("type", result.value.type);
            formData.append("amount", result.value.amount);
            formData.append("date", result.value.date);

            fetch("expenses.php", {
                method: "POST",
                body: formData
              })
              .then((response) => response.json())
              .then((data) => {
                if (data.status === "success") {
                  Swal.fire("Expenses Updated", data.message, "success");
                  fetchExpensesHistory();
                } else {
                  Swal.fire("Error", data.message, "error");
                }
              })
              .catch((error) => {
                console.error("Error updating expenses:", error);
                Swal.fire("Error", "An unexpected error occurred. Please try again.", "error");
              });
          }
        });
      }

      // Remove Expenses Handler (this part remains the same as before)
      if (event.target.classList.contains("remove")) {
        const expenseId = event.target.dataset.id;
        Swal.fire({
          title: "Are you sure?",
          text: "This action will permanently delete the expense.",
          icon: "warning",
          showCancelButton: true,
          confirmButtonText: "Yes, delete it!",
          cancelButtonText: "Cancel"
        }).then((result) => {
          if (result.isConfirmed) {
            // Proceed with removal
            const formData = new FormData();
            formData.append("action", "remove_expenses");
            formData.append("id", expenseId);

            fetch("expenses.php", {
                method: "POST",
                body: formData
              })
              .then((response) => response.json())
              .then((data) => {
                if (data.status === "success") {
                  Swal.fire("Deleted!", data.message, "success");
                  fetchExpensesHistory(); // Refresh the list after removal
                } else {
                  Swal.fire("Error", data.message, "error");
                }
              })
              .catch((error) => {
                console.error("Error removing expense:", error);
                Swal.fire("Error", "An unexpected error occurred. Please try again.", "error");
              });
          }
        });
      }
    });

    // Fetch history when DOM content loads
    document.addEventListener("DOMContentLoaded", fetchExpensesHistory);
  </script>



  <script type="module" src="./js/expenses/expenses.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Bootstrap Datepicker JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="./js/utils/script.js"></script>
</body>

</html>