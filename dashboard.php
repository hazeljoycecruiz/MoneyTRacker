<?php
// Include the database connection file
require_once('database/db_connection.php');
// Start a session to manage user login state
session_start();

// Check if the user is logged in, if not, redirect to the login page
if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}

// Handle the form submission requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Retrieve the action from the form submission, default to empty
  $action = $_POST['action'] ?? '';
  $id = $_POST['id'] ?? null; // Income ID for specific actions (e.g., delete, update)

  // Add income action
  if ($action === 'add_income') {
    // Get user inputs from the form
    $user_id = $_SESSION['user_id'];
    $type = $_POST['type'];  // Type of income (income/allowance)
    $amount = $_POST['amount'];  // Income amount
    $date = $_POST['date'];  // Date of the income

    // Validate the inputs to ensure data is correct
    if (
      empty($type) || !in_array($type, ['income', 'allowance']) ||
      empty($amount) || !is_numeric($amount) || $amount <= 0 ||
      empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
    ) {
      // Return an error response if validation fails
      echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
      exit;
    }

    // Insert the income data into the database
    $stmt = $conn->prepare("INSERT INTO user_incomes (user_id, type, amount, date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isds', $user_id, $type, $amount, $date);

    // Check if the insert operation was successful
    if ($stmt->execute()) {
      echo json_encode(['status' => 'success', 'message' => 'Income added successfully!']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
    }
    exit;
  }

  // Fetch income history for the user
  if ($action === 'fetch_history') {
    // Get user ID from session
    $user_id = $_SESSION['user_id'];
    // Query the database for the user's income history
    $stmt = $conn->prepare("SELECT income_id, type, amount, date FROM user_incomes WHERE user_id = ? ORDER BY date DESC");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch all data and return it as a JSON response
    $data = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
  }

  // Remove income action
  if ($action === 'remove_income' && $id) {
    // Ensure the income ID is provided for removal
    $user_id = $_SESSION['user_id'];
    // Prepare and execute the deletion query
    $stmt = $conn->prepare("DELETE FROM user_incomes WHERE income_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);

    // Check if the removal was successful
    if ($stmt->execute()) {
      echo json_encode(['status' => 'success', 'message' => 'Income removed successfully.']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Failed to remove income.']);
    }
    exit;
  }

  // Update income action
  if ($action === 'update_income' && $id) {
    // Get user inputs for updating the income
    $user_id = $_SESSION['user_id'];
    $type = $_POST['type'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];

    // Validate the inputs for updating the income
    if (
      empty($type) || !in_array($type, ['income', 'allowance']) ||
      empty($amount) || !is_numeric($amount) || $amount <= 0 ||
      empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
    ) {
      echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
      exit;
    }

    // Prepare the SQL statement for updating the income record
    $stmt = $conn->prepare("UPDATE user_incomes SET type = ?, amount = ?, date = ? WHERE income_id = ? AND user_id = ?");
    $stmt->bind_param("sdsii", $type, $amount, $date, $id, $user_id);

    // Execute the update and return a success or error message
    if ($stmt->execute()) {
      echo json_encode(['status' => 'success', 'message' => 'Income updated successfully.']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Failed to update income.']);
    }
    exit;
  }
}

// Fetch data for today's income, spending, and savings
$user_id = $_SESSION['user_id'];

// Calculate today's total income
$sql_today_income = "SELECT SUM(amount) AS total_income FROM user_incomes WHERE user_id = ? AND date = CURDATE() AND type IN ('income', 'allowance')";
$stmt = $conn->prepare($sql_today_income);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$today_income = $stmt->get_result()->fetch_assoc()['total_income'] ?? 0;

// Calculate today's total spending
$sql_today_spending = "SELECT SUM(amount) AS total_spending FROM user_expenses WHERE user_id = ? AND date = CURDATE()";
$stmt = $conn->prepare($sql_today_spending);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$today_spending = $stmt->get_result()->fetch_assoc()['total_spending'] ?? 0;

// Calculate today's savings (income - spending)
$today_savings = $today_income - $today_spending;

// Calculate weekly income
$sql_weekly_income = "SELECT SUM(amount) AS total_income FROM user_incomes WHERE user_id = ? AND WEEK(date, 1) = WEEK(CURDATE(), 1) AND type IN ('income', 'allowance')";
$stmt = $conn->prepare($sql_weekly_income);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$weekly_income = $stmt->get_result()->fetch_assoc()['total_income'] ?? 0;

// Calculate weekly spending
$sql_weekly_spending = "SELECT SUM(amount) AS total_spending FROM user_expenses WHERE user_id = ? AND WEEK(date, 1) = WEEK(CURDATE(), 1)";
$stmt = $conn->prepare($sql_weekly_spending);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$weekly_spending = $stmt->get_result()->fetch_assoc()['total_spending'] ?? 0;

// Calculate weekly savings (income - spending)
$weekly_savings = $weekly_income - $weekly_spending;

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

  <link rel="stylesheet" href="css/dashboard.css" />

  <title>MoneyTracker</title>
</head>

<body>
  <div class="main-container d-flex">
    <div class="sidebar" id="side_nav">
      <div class="header-box px-2 pt-3 pb-4 d-flex justify-content-end">
        <button class="btn-close d-md-none d-block close-btn px-1 py-0 text-dark"></button>
      </div>

      <ul class="list-unstyled">
        <li class="active"><a href="dashboard.php" class="text-decoration-none px-3 py-2 d-block"><i class="bi bi-house-door-fill"></i> Dashboard</a></li>
        <li><a href="expenses.php" class="text-decoration-none px-3 py-2 d-block"><i class="bi bi-cash-coin"></i> Expenses</a></li>
        <li><a href="summary.php" class="text-decoration-none px-3 py-2 d-block d-flex justify-content-between"><span><i class="bi bi-bar-chart-line-fill"></i> Summary</span></a></li>
      </ul>

      <ul class="list-unstyled pt-5">
        <li><a href="#" id="logout" class="text-decoration-none px-3 py-2 d-block"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
      </ul>
    </div>

    <div class="content">
      <nav class="navbar navbar-expand-md">
        <div class="container-fluid navBar">
          <div class="d-flex justify-content-between d-md-none">
            <button class="btn open-btn me-2"><i class="fa fa-bars"></i></button>
          </div>
        </div>
      </nav>

      <div class="dashboard-content px-3">
        <h3 class="fw-bold">Dashboard</h3>
        <div class="container-fluid topcontainer justify-content-center">
          <div class="d-flex justify-content-center align-items-center">
            <div class="px-5">
              <div class="daily-container d-flex justify-content-center align-items-center drowShadow" style="background: #b4f38d">
                <i class="fa-solid fa-wallet pe-3 walletSize"></i>
                <div>
                  <span style="font-size: 20px">Today's Income</span>
                  <div><span style="font-size: 24px; font-weight: bold; color: #4CAF50;">₱ <?php echo number_format($today_income, 2); ?></span></div>
                </div>
              </div>
            </div>

            <div class="px-5">
              <div class="daily-container d-flex justify-content-center align-items-center drowShadow" style="background: #fee19d">
                <i class="fa-solid fa-wallet pe-3 walletSize"></i>
                <div>
                  <span style="font-size: 20px">Today's Spending</span>
                  <div><span style="font-size: 24px; font-weight: bold; color: #FF6347;">₱ <?php echo number_format($today_spending, 2); ?></span></div>
                </div>
              </div>
            </div>

            <div class="px-5">
              <div class="daily-container d-flex justify-content-center align-items-center drowShadow" style="background: #b4f38d">
                <i class="fa-solid fa-wallet pe-3 walletSize"></i>
                <div>
                  <span style="font-size: 20px">Today's Savings</span>
                  <div><span style="font-size: 24px; font-weight: bold; color: #4CAF50;">₱ <?php echo number_format($today_savings, 2); ?></span></div>
                </div>
              </div>
            </div>
          </div>

          <div class="d-flex pt-4 justify-content-center align-items-center">
            <div class="px-5">
              <div class="daily-container d-flex justify-content-center align-items-center drowShadow" style="background: #70d380; color: #ffffff">
                <i class="fa-solid fa-wallet pe-3 walletSize"></i>
                <div>
                  <span style="font-size: 20px">Weekly Income</span>
                  <div><span style="font-size: 24px; font-weight: bold; color: #4CAF50;">₱ <?php echo number_format($weekly_income, 2); ?></span></div>
                </div>
              </div>
            </div>

            <div class="px-5">
              <div class="daily-container d-flex justify-content-center align-items-center drowShadow" style="background: #f1ba3a; color: #ffffff">
                <i class="fa-solid fa-wallet pe-3 walletSize"></i>
                <div>
                  <span style="font-size: 20px">Weekly Spending</span>
                  <div><span style="font-size: 24px; font-weight: bold; color: #FF6347;">₱ <?php echo number_format($weekly_spending, 2); ?></span></div>
                </div>
              </div>
            </div>

            <div class="px-5">
              <div class="daily-container d-flex justify-content-center align-items-center drowShadow" style="background: #70d380; color: #ffffff">
                <i class="fa-solid fa-wallet pe-3 walletSize"></i>
                <div>
                  <span style="font-size: 20px">Weekly Savings</span>
                  <div><span style="font-size: 24px; font-weight: bold; color: #4CAF50;">₱ <?php echo number_format($weekly_savings, 2); ?></span></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <h3 class="fw-bold">Income</h3>
        <div class="container-fluid d-flex botcontainer">
          <div class="ps-3">
            <div class="container-income incomeContainer align-items-center drowShadow">
              <form autocomplete="off" class="incomeForm" id="incomeForm">
                <div class="container">
                  <div class="form-group pt-4" style="height: 110px">
                    <label for="type" style="font-size: 25px">Type:</label>
                    <div class="ps-4">
                      <select name="type" id="type" class="form-control" style="width: 320px;">
                        <option value="income">Income</option>
                        <option value="allowance">Allowance</option>
                      </select>
                      <span class="validation-error-color" id="type_error"></span>
                    </div>
                  </div>
                  <div class="form-group" style="height: 90px">
                    <label for="amount" style="font-size: 25px">Amount:</label>
                    <div class="ps-4">
                      <input name="amount" type="text" id="amount" class="form-control inpamount" style="width: 320px; text-indent: 10px" />
                      <span class="validation-error-color" id="amount_error"></span>
                    </div>
                  </div>
                  <div class="form-group" style="height: 100px">
                    <label for="date" style="font-size: 25px">Date:</label>
                    <div class="ps-4">
                      <input name="date" type="date" id="date" class="form-control" style="width: 320px" />
                      <span class="validation-error-color" id="date_error"></span>
                    </div>
                  </div>
                  <div class="row justify-content-center">
                    <button type="button" id="add" class="add" style="background: #8cef84; border-radius: 8px; border: none; width: 200px;">Add</button>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <div class="container ps-4">
            <div class="container income-history drowShadow">
              <div class="container-lg income-align">
                <div class="pt-4 pb-2">
                  <h3 class="fw-bold">History</h3>
                </div>
                <div class="container pt-3 pb-2">
                  <div class="row">
                    <div class="col text-center">
                      <h4>Type</h4>
                    </div>
                    <div class="col grey text-center">
                      <h4>Amount</h4>
                    </div>
                    <div class="col text-center">
                      <h4>Date</h4>
                    </div>
                    <div class="col text-center">
                      <h4>Actions</h4>
                    </div>
                  </div>
                </div>

                <!-- Scrollable container where history will be rendered -->
                <div class="container scrollbar">
                  <!-- Loading spinner initially shown -->
                  <div class="pt-5 d-flex justify-content-center align-items-center">
                    <div class="spinner-border" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                    <b class="ms-2">Loading Data...</b>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Function to handle displaying alerts using SweetAlert2
    function showAlert(icon, title, text) {
      Swal.fire({
        icon, // Alert icon (e.g., 'success', 'error', 'warning')
        title, // Title of the alert
        text // Description/message for the alert
      });
    }

    // Function to fetch and render the user's income history
    function fetchIncomeHistory() {
      const historyContainer = document.querySelector(".scrollbar");

      // Display a loading spinner while data is being fetched
      historyContainer.innerHTML = `
        <div class="pt-5 d-flex justify-content-center align-items-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <b class="ms-2">Loading Data...</b>
        </div>
        `;

      // Send a POST request to fetch the income history
      fetch("dashboard.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: "action=fetch_history", // Specify the action to fetch history
        })
        .then((response) => response.json()) // Parse JSON response
        .then((data) => {
          if (data.status === "success") {
            const incomes = data.data;

            // Display income history or a message if no data is available
            historyContainer.innerHTML = incomes.length ?
              incomes.map(income => `
                    <div class="row py-2 border-bottom align-items-center" id="row-${income.income_id}">
                        <div class="col text-center"><p>${income.type}</p></div>
                        <div class="col grey text-center"><p>₱${parseFloat(income.amount).toFixed(2)}</p></div>
                        <div class="col text-center"><p>${income.date}</p></div>
                        <div class="col text-center">
                            <button class="btn btn-sm btn-warning me-2 edit" data-id="${income.income_id}">Edit</button>
                            <button class="btn btn-sm btn-danger remove" data-id="${income.income_id}">Remove</button>
                        </div>
                    </div>
                    `).join("") :
              `<div class="pt-5 d-flex justify-content-center align-items-center"><b>No income history available.</b></div>`;
          } else {
            showAlert('error', 'Error', data.message); // Show error if fetch fails
          }
        })
        .catch(() => {
          showAlert('error', 'Error', 'An unexpected error occurred. Please try again later.');
        });
    }

    // Handler for adding income entries
    document.getElementById("add")?.addEventListener("click", function() {
      const type = document.getElementById("type").value; // Get income type
      const amount = document.getElementById("amount").value; // Get amount
      const date = document.getElementById("date").value; // Get date

      // Validate input fields
      if (!type || !amount || !date) {
        showAlert('warning', 'Missing Fields', 'Please fill out all fields before submitting.');
        return;
      }

      const formData = new FormData();
      formData.append("action", "add_income"); // Specify action
      formData.append("type", type);
      formData.append("amount", amount);
      formData.append("date", date);

      // Send POST request to add income
      fetch("dashboard.php", {
          method: "POST",
          body: formData
        })
        .then((response) => response.json())
        .then((data) => {
          if (data.status === "success") {
            showAlert('success', 'Income Added', data.message); // Show success message
            document.getElementById("type").value = "income";
            document.getElementById("amount").value = "";
            document.getElementById("date").value = "";
            fetchIncomeHistory(); // Refresh income history
          } else {
            showAlert('error', 'Error', data.message); // Show error message
          }
        })
        .catch(() => showAlert('error', 'Error', 'An unexpected error occurred. Please try again later.'));
    });

    // Event listener for income list actions (Remove/Edit)
    document.querySelector(".scrollbar").addEventListener("click", function(event) {
      // Handle removing income entries
      if (event.target.classList.contains("remove")) {
        const incomeId = event.target.dataset.id;

        // Confirm delete action
        Swal.fire({
          title: "Are you sure?",
          text: "This action will permanently delete this income entry.",
          icon: "warning",
          showCancelButton: true,
          confirmButtonText: "Yes, delete it!",
          cancelButtonText: "Cancel",
          reverseButtons: true
        }).then((result) => {
          if (result.isConfirmed) {
            const formData = new FormData();
            formData.append("action", "remove_income"); // Specify action
            formData.append("id", incomeId);

            // Send POST request to delete income
            fetch("dashboard.php", {
                method: "POST",
                body: formData
              })
              .then((response) => response.json())
              .then((data) => {
                if (data.status === "success") {
                  showAlert('success', 'Income Removed', data.message); // Show success message
                  fetchIncomeHistory(); // Refresh income history
                } else {
                  showAlert('error', 'Error', data.message); // Show error message
                }
              })
              .catch(() => showAlert('error', 'Error', 'An unexpected error occurred. Please try again later.'));
          }
        });
      }

      // Handle editing income entries
      if (event.target.classList.contains("edit")) {
        const incomeId = event.target.dataset.id;
        const incomeRow = document.querySelector(`#row-${incomeId}`);
        const type = incomeRow.querySelector("div:nth-child(1) p").textContent.trim();
        const amount = parseFloat(incomeRow.querySelector("div:nth-child(2) p").textContent.trim().replace("₱", ""));
        const date = incomeRow.querySelector("div:nth-child(3) p").textContent.trim();

        // Show SweetAlert2 input fields for editing
        Swal.fire({
          title: "Edit Income",
          html: `
                <select id="edit-type" class="swal2-input">
                    <option value="income" ${type === 'income' ? 'selected' : ''}>Income</option>
                    <option value="allowance" ${type === 'allowance' ? 'selected' : ''}>Allowance</option>
                </select>
                <input id="edit-amount" type="number" class="swal2-input" value="${amount}">
                <input id="edit-date" type="date" class="swal2-input" value="${date}">
                `,
          confirmButtonText: "Save Changes",
          showCancelButton: true,
          preConfirm: () => ({
            type: document.getElementById("edit-type").value,
            amount: document.getElementById("edit-amount").value,
            date: document.getElementById("edit-date").value
          }),
        }).then((result) => {
          if (result.isConfirmed) {
            const formData = new FormData();
            formData.append("action", "update_income"); // Specify action
            formData.append("id", incomeId);
            formData.append("type", result.value.type);
            formData.append("amount", result.value.amount);
            formData.append("date", result.value.date);

            // Send POST request to update income
            fetch("dashboard.php", {
                method: "POST",
                body: formData
              })
              .then((response) => response.json())
              .then((data) => {
                if (data.status === "success") {
                  showAlert('success', 'Income Updated', data.message); // Show success message
                  fetchIncomeHistory(); // Refresh income history
                } else {
                  showAlert('error', 'Error', data.message); // Show error message
                }
              })
              .catch(() => showAlert('error', 'Error', 'An unexpected error occurred. Please try again later.'));
          }
        });
      }
    });

    // Add an event listener to the "Logout" link
    document.getElementById("logout").addEventListener("click", function(event) {
      event.preventDefault(); // Prevent default link behavior

      // Confirm logout action with the user
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
          window.location.href = "index.php"; // Redirect to the login page
        }
      });
    });

    // Fetch income history when the page is fully loaded
    document.addEventListener("DOMContentLoaded", fetchIncomeHistory);
  </script>


  <!-- Bootstrap Datepicker JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="./js/utils/script.js"></script>
</body>

</html>