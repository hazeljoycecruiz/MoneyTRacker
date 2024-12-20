<?php

// Include database connection
include('database/db_connection.php');
session_start();

// Ensure user is logged in , SESSION MANAGEMENT
if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}

  // Handle different type of action or incoming requests - POST Request
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? null;

    // Common function for database operations
    function executeQuery($query, $params = [], $types = '')
    {
      global $conn;
      $stmt = $conn->prepare($query);
      if ($params) {
        $stmt->bind_param($types, ...$params);
      }
      return $stmt->execute();
    }

    // Add Expenses Action
    if ($action === 'add_expenses') {
      $user_id = $_SESSION['user_id'];
      $category = mysqli_real_escape_string($conn, $_POST['category']);
      $type = mysqli_real_escape_string($conn, $_POST['type']);
      $amount = mysqli_real_escape_string($conn, $_POST['amount']);
      $date = mysqli_real_escape_string($conn, $_POST['date']);

      // Validations
      $valid_categories = ['Education', 'Entertainment', 'Food', 'Health', 'Miscellaneous', 'Shopping', 'Transportation', 'Utilities'];
      if (empty($category) || !in_array($category, $valid_categories)) {
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
      if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {      // must follow format YYYY-MM-DD
        echo json_encode(['status' => 'error', 'message' => 'Invalid date format']);
        exit;
      }

      // Insert data into database
      $query = "INSERT INTO user_expenses (user_id, category, type, amount, date) VALUES (?, ?, ?, ?, ?)";
      if (executeQuery($query, [$user_id, $category, $type, $amount, $date], 'issds')) {
        echo json_encode(['status' => 'success', 'message' => 'Expenses added successfully!']);
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
      }
      exit;
    }

    // Fetch Expenses History
    if ($action === 'fetch_history') {
      $user_id = $_SESSION['user_id'];  
      // all expenses for the logged in user 
      $query = "SELECT expense_id, category, type, amount, date FROM user_expenses WHERE user_id = '$user_id' ORDER BY date DESC";  
      $result = $conn->query($query);
      //returns result in json response nga kdtung list of expenses
      $data = ($result->num_rows > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
      echo json_encode(['status' => 'success', 'data' => $data]);
      exit;
    }

    // Update Expenses
    if ($action === 'update_expenses' && $id) {
      $category = mysqli_real_escape_string($conn, $_POST['category']);
      $type = mysqli_real_escape_string($conn, $_POST['type']);
      $amount = floatval($_POST['amount']);
      $date = mysqli_real_escape_string($conn, $_POST['date']);

      // Validate date format
      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid date format. Use YYYY-MM-DD']);
        exit;
      }

      $query = "UPDATE user_expenses SET category = ?, type = ?, amount = ?, date = ? WHERE expense_id = ? AND user_id = ?";
      if (executeQuery($query, [$category, $type, $amount, $date, $id, $_SESSION['user_id']], 'ssdsii')) {
        echo json_encode(['status' => 'success', 'message' => 'Expenses updated successfully.']);
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update expenses.']);
      }
      exit;
    }

    // Remove Expenses
    if ($action === 'remove_expenses' && $id) {
      $query = "DELETE FROM user_expenses WHERE expense_id = ? AND user_id = ?";
      if (executeQuery($query, [$id, $_SESSION['user_id']], 'ii')) {
        echo json_encode(['status' => 'success', 'message' => 'Expenses removed successfully.']);
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to remove expenses.']);
      }
      exit;
    }
  }

// Handle GET requests for fetching expenses , retrieve all expenses for the user
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_expenses') {
  $user_id = $_SESSION['user_id'];
  $stmt = $conn->query("SELECT * FROM user_expenses WHERE user_id = '$user_id'");
  $rows = $stmt->fetch_all(MYSQLI_ASSOC);

  //loops the result and generate HTML table row for each expense
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

    // Expenses Tracking System JavaScript

    // DOM Content Loaded Event - Initialize all functionality
    document.addEventListener("DOMContentLoaded", function() {
      // Event Listener for Adding Expenses
      document.getElementById("expensesAddForm").addEventListener("submit", function(event) {
        event.preventDefault();

        const formData = new FormData(this);
        formData.append("action", "add_expenses");

        // Validate form inputs
        if (Array.from(formData.values()).includes("") || Array.from(formData.values()).includes(undefined)) {
          Swal.fire({
            icon: "warning",
            title: "Missing Fields",
            text: "Please fill out all fields before submitting.",
          });
          return;
        }

        // Submit the form data for adding an expense
        submitForm(formData, "Expenses Added", "add_expenses");
      });

      // Event Listener for Logout Button
      document.getElementById("logout").addEventListener("click", function(event) {
        event.preventDefault();

        Swal.fire({
          title: 'Are you sure?',
          text: "You will be logged out of your account.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Log out',
          cancelButtonText: 'No'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = "index.php"; // Redirect to the login page
          }
        });
      });

      // Fetch and Render Expenses History on Page Load
      fetchExpensesHistory();

      // Event Listener for Actions in Expenses History (Edit/Remove Buttons)
      document.querySelector(".scrollbar").addEventListener("click", function(event) {
        const expenseId = event.target.dataset.id;

        if (event.target.classList.contains("edit")) {
          editExpense(expenseId); // Handle editing an expense
        } else if (event.target.classList.contains("remove")) {
          removeExpense(expenseId); // Handle removing an expense
        }
      });
    });

/**
 * Function to Submit Form Data
 * Handles all server communication for Add, Edit, and Remove actions.
 */
function submitForm(formData, successTitle, actionType) {
  fetch("expenses.php", {
      method: "POST",
      body: formData
    })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        Swal.fire({
          icon: "success",
          title: successTitle,
          text: data.message,
        });
        document.getElementById("expensesAddForm").reset(); // Reset form after success
        fetchExpensesHistory(); // Refresh expenses history
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: data.message
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
}

/**
 * Function to Fetch and Render Expenses History
 * Retrieves data from the server and dynamically updates the UI.
 */
function fetchExpensesHistory() {
  const historyContainer = document.querySelector(".scrollbar");

  // Show loading indicator while fetching data
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
      body: "action=fetch_history"
    })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        renderExpensesHistory(data.data); // Populate UI with fetched data
      } else {
        historyContainer.innerHTML = `<b>Error: ${data.message}</b>`;
      }
    })
    .catch(() => {
      historyContainer.innerHTML = `<b>An error occurred while fetching expenses history.</b>`;
    });
}

/**
 * Function to Render Expenses History
 * Dynamically creates HTML for each expense record.
 */
function renderExpensesHistory(expenses) {
  const historyContainer = document.querySelector(".scrollbar");

  if (!expenses.length) {
    historyContainer.innerHTML = `<div class="pt-5 d-flex justify-content-center align-items-center"><b>No expenses history available.</b></div>`;
    return;
  }

  const rows = expenses.map((expense) => `
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
}

/**
 * Function to Edit an Expense
 * Shows a Swal modal with editable fields and updates the expense.
 */
function editExpense(expenseId) {
  const expensesRow = document.querySelector(`#row-${expenseId}`);
  const category = expensesRow.querySelector(".col:nth-child(1) p")?.textContent.trim() || "N/A";
  const type = expensesRow.querySelector(".col:nth-child(2) p")?.textContent.trim() || "";
  const amount = parseFloat(expensesRow.querySelector(".col:nth-child(3) p")?.textContent.replace("₱", "") || 0);
  const date = expensesRow.querySelector(".col:nth-child(4) p")?.textContent.trim() || "";

  Swal.fire({
    title: "Edit Expenses",
    html: `
      <select id="edit-category" class="swal2-input">
        ${["Education", "Entertainment", "Food", "Health", "Miscellaneous", "Shopping", "Transportation", "Utilities"]
          .map(option => `<option value="${option}" ${category === option ? "selected" : ""}>${option}</option>`).join('')}
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

      if (!updatedCategory || !updatedType || isNaN(updatedAmount) || updatedAmount <= 0 || !updatedDate) {
        Swal.fire({
          icon: 'warning',
          title: 'Missing Fields',
          text: 'Please fill out all fields before submitting.',
        });
        return false;
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

      submitForm(formData, "Expenses Updated", "update_expenses");
    }
  });
}

/**
 * Function to Remove an Expense
 * Deletes an expense after user confirmation.
 */
function removeExpense(expenseId) {
  Swal.fire({
    title: "Are you sure?",
    text: "This action will permanently delete the expense.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Delete",
    cancelButtonText: "Cancel"
  }).then((result) => {
    if (result.isConfirmed) {
      const formData = new FormData();
      formData.append("action", "remove_expenses");
      formData.append("id", expenseId);

      submitForm(formData, "Expense Deleted", "remove_expenses");
    }
  });
}

  </script>

  <!-- Bootstrap Datepicker JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="./js/utils/script.js"></script>
</body>

</html>