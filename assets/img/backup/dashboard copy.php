<?php
// Include database connection
require_once('database/db_connection.php');
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
  // Redirect to the login page if no user is logged in
  header("Location: index.php");
  exit; // Always exit after a header redirect to prevent further code execution
}

// Handle incoming requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $id = $_POST['id'] ?? null;

  // Add Income Action
  if ($action === 'add_income') {
    $user_id = $_SESSION['user_id'];
    $type = $_POST['type'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];

    // Validate the inputs (ensure they are safe to use)
    if (empty($type) || !in_array($type, ['income', 'allowance'])) {
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

    // Use prepared statements to avoid SQL injection
    $stmt = $conn->prepare("INSERT INTO user_incomes (user_id, type, amount, date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isds', $user_id, $type, $amount, $date);

    // Only execute once
    if ($stmt->execute()) {
      echo json_encode(['status' => 'success', 'message' => 'Income added successfully!']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
    }
    exit;
  }

  // Fetch Income History
  if ($action === 'fetch_history') {
    $user_id = $_SESSION['user_id']; // Current logged-in user ID
    $query = "SELECT income_id, type, amount, date FROM user_incomes WHERE user_id = ? ORDER BY date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id); // Bind the user ID
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
      $data[] = $row;
    }

    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
  }


  // Remove Income
  if ($action === 'remove_income' && $id) {
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("DELETE FROM user_incomes WHERE income_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);

    if ($stmt->execute()) {
      echo json_encode(['status' => 'success', 'message' => 'Income removed successfully.']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Failed to remove income.']);
    }
    exit;
  }


  // Update Income
  if ($action === 'update_income' && $id) {
    $user_id = $_SESSION['user_id'];
    $type = $_POST['type'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];

    // Validate the inputs again for security
    if (empty($type) || !in_array($type, ['income', 'allowance'])) {
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

    // Update the income in the database
    $stmt = $conn->prepare("UPDATE user_incomes SET type = ?, amount = ?, date = ? WHERE income_id = ? AND user_id = ?");
    $stmt->bind_param("sdsii", $type, $amount, $date, $id, $user_id);

    if ($stmt->execute()) {
      echo json_encode(['status' => 'success', 'message' => 'Income updated successfully.']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Failed to update income.']);
    }
    exit;
  }
}

// Handle GET requests for fetching incomes
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_incomes') {
  $stmt = $conn->query("SELECT * FROM user_incomes");
  $rows = $stmt->fetch_all(MYSQLI_ASSOC);
  foreach ($rows as $row) {
    echo "<tr id='row-{$row['income_id']}'>
                <td class='type'>{$row['type']}</td>
                <td class='amount'>{$row['amount']}</td>
                <td class='date'>{$row['date']}</td>
                <td>
                    <button class='edit' data-id='{$row['income_id']}'>Edit</button>
                    <button class='remove' data-id='{$row['income_id']}'>Remove</button>
                </td>
              </tr>";
  }
  exit;
}

// Fetch today's income, spending, and savings from the database
$sql_today_income = "SELECT SUM(amount) AS total_income 
                     FROM user_incomes 
                     WHERE user_id = '{$_SESSION['user_id']}' 
                     AND date = CURDATE() 
                     AND type IN ('income', 'allowance')";
$result_today_income = $conn->query($sql_today_income);
$today_income = $result_today_income->fetch_assoc()['total_income'] ?? 0;


$sql_today_spending = "SELECT SUM(amount) AS total_spending 
                       FROM user_expenses 
                       WHERE user_id = '{$_SESSION['user_id']}' 
                       AND date = CURDATE()";
$result_today_spending = $conn->query($sql_today_spending);
$today_spending = $result_today_spending->fetch_assoc()['total_spending'] ?? 0;


// Query for today's savings (assuming savings = income - spending)
$today_savings = $today_income - $today_spending;

// Query for weekly data (using `WEEK` function to get current week)
$sql_weekly_income = "SELECT SUM(amount) AS total_income 
                      FROM user_incomes 
                      WHERE user_id = '{$_SESSION['user_id']}' 
                      AND WEEK(date, 1) = WEEK(CURDATE(), 1) 
                      AND type IN ('income', 'allowance')";
$result_weekly_income = $conn->query($sql_weekly_income);
$weekly_income = $result_weekly_income->fetch_assoc()['total_income'] ?? 0;


$sql_weekly_spending = "SELECT SUM(amount) AS total_spending 
                        FROM user_expenses 
                        WHERE user_id = '{$_SESSION['user_id']}' 
                        AND WEEK(date, 1) = WEEK(CURDATE(), 1)";
$result_weekly_spending = $conn->query($sql_weekly_spending);
$weekly_spending = $result_weekly_spending->fetch_assoc()['total_spending'] ?? 0;


// Weekly savings calculation (assuming savings = income - spending)
$weekly_savings = $weekly_income - $weekly_spending;

// Close the database connection
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>
  <!-- <script type="module" src="./js/dashboard/dashboard.js"></script> -->
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
        <button
          class="btn-close d-md-none d-block close-btn px-1 py-0 text-dark"></button>
      </div>

      <ul class="list-unstyled">
        <li class="active">
          <a
            href="dashboard.php"
            class="text-decoration-none px-3 py-2 d-block">
            <i class="bi bi-house-door-fill"></i> Dashboard
          </a>
        </li>

        <li class="">
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
        id="incomeModal"
        tabindex="1"
        role="dialog">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Edit Income</h5>
              <button
                type="button"
                style="background-color: transparent; border: none"
                class="close"
                data-dismiss="modal"
                aria-label="Close">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>

            <div class="modal-body" id="editIncomeContainer">
              <form id="incomeEditForm">
                <!-- Modal Content -->
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="dashboard-content px-3">
        <h3 class="fw-bold">Dashboard</h3>
        <div class="container-fluid topcontainer justify-content-center">
          <div class="d-flex justify-content-center align-items-center">
            <div class="px-5">
              <div
                style="background: #b4f38d"
                class="daily-container d-flex justify-content-center align-items-center drowShadow">
                <div>
                  <i class="fa-solid fa-wallet pe-3 walletSize"></i>
                </div>
                <div>
                  <div class="ps-2">
                    <span style="font-size: 20px">Today's Income</span>
                  </div>
                  <div>
                    <span style="font-size: 24px; font-weight: bold; color: #4CAF50; margin-left: 20px;">
                      ₱ <?php echo number_format($today_income, 2); ?>
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="px-5">
              <div
                style="background: #fee19d"
                class="daily-container d-flex justify-content-center align-items-center drowShadow">
                <div>
                  <i class="fa-solid fa-wallet pe-3 walletSize"></i>
                </div>
                <div>
                  <div class="ps-2">
                    <span style="font-size: 20px">Today's Spending</span>
                  </div>
                  <div>
                    <span style="font-size: 24px; font-weight: bold; color: #FF6347; margin-left: 20px;">
                      ₱ <?php echo number_format($today_spending, 2); ?>
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="px-5">
              <div
                style="background: #b4f38d"
                class="daily-container d-flex justify-content-center align-items-center drowShadow">
                <div>
                  <i class="fa-solid fa-wallet pe-3 walletSize"></i>
                </div>
                <div>
                  <div class="ps-2">
                    <span style="font-size: 20px">Today's Savings</span>
                  </div>
                  <div>
                    <span style="font-size: 24px; font-weight: bold; color: #4CAF50; margin-left: 20px;">
                      ₱ <?php echo number_format($today_savings, 2); ?>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="d-flex pt-4 justify-content-center align-items-center">
            <div class="px-5">
              <div
                style="background: #70d380; color: #ffffff"
                class="daily-container d-flex justify-content-center align-items-center drowShadow">
                <div>
                  <i class="fa-solid fa-wallet pe-3 walletSize"></i>
                </div>
                <div>
                  <div class="ps-2">
                    <span style="font-size: 20px">Weekly Income</span>
                  </div>
                  <div>
                    <span style="font-size: 24px; font-weight: bold; color: #4CAF50; margin-left: 20px;">
                      ₱ <?php echo number_format($weekly_income, 2); ?>
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="px-5">
              <div
                style="background: #f1ba3a; color: #ffffff"
                class="daily-container d-flex justify-content-center align-items-center drowShadow">
                <div>
                  <i class="fa-solid fa-wallet pe-3 walletSize"></i>
                </div>
                <div>
                  <div class="ps-2">
                    <span style="font-size: 20px">Weekly Spending</span>
                  </div>
                  <div>
                    <span style="font-size: 24px; font-weight: bold; color: #FF6347; margin-left: 20px;">
                      ₱ <?php echo number_format($weekly_spending, 2); ?>
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="px-5">
              <div
                style="background: #70d380; color: #ffffff"
                class="daily-container d-flex justify-content-center align-items-center drowShadow">
                <div>
                  <i class="fa-solid fa-wallet pe-3 walletSize"></i>
                </div>
                <div>
                  <div class="ps-2">
                    <span style="font-size: 20px">Weekly Savings</span>
                  </div>
                  <div>
                    <span style="font-size: 24px; font-weight: bold; color: #4CAF50; margin-left: 20px;">
                      ₱ <?php echo number_format($weekly_savings, 2); ?>
                    </span>
                  </div>
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
                  <div style="height: 110px" class="form-group pt-4">
                    <label style="font-size: 25px" for="type">Type:</label>
                    <div class="ps-4">
                      <select name="type" id="type" class="form-control" style="width: 320px;">
                        <option value="income">Income</option>
                        <option value="allowance">Allowance</option>
                      </select>
                      <span class="validation-error-color" id="type_error"></span>
                    </div>
                  </div>
                  <div style="height: 90px" class="form-group">
                    <label style="font-size: 25px" for="amount">Amount:</label>
                    <div class="ps-4">
                      <input name="amount" type="text" id="amount" style="width: 320px; text-indent: 10px" class="form-control inpamount" />
                      <span class="validation-error-color" id="amount_error"></span>
                    </div>
                  </div>
                  <div style="height: 100px" class="form-group">
                    <label style="font-size: 25px" for="date">Date:</label>
                    <div class="ps-4">
                      <input name="date" type="date" id="date" style="width: 320px" class="form-control" />
                      <span class="validation-error-color" id="date_error"></span>
                    </div>
                  </div>
                  <div class="row justify-content-center">
                    <button type="button" style="background: #8cef84; border-radius: 8px; border: none; width: 200px;" id="add" class="add">Add</button>
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
    // Function to fetch and render income history
    function fetchIncomeHistory() {
      const historyContainer = document.querySelector(".scrollbar");
      historyContainer.innerHTML = `
    <div class="pt-5 d-flex justify-content-center align-items-center">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <b class="ms-2">Loading Data...</b>
    </div>
  `;

      fetch("dashboard.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: "action=fetch_history",
        })
        .then((response) => response.json())
        .then((data) => {
          if (data.status === "success") {
            const incomes = data.data;

            if (incomes.length === 0) {
              historyContainer.innerHTML = `
          <div class="pt-5 d-flex justify-content-center align-items-center">
            <b>No income history available.</b>
          </div>
        `;
              return;
            }

            const rows = incomes
              .map((income) => {
                return `
            <div class="row py-2 border-bottom align-items-center" id="row-${income.income_id}">
              <div class="col text-center">
                <p>${income.type}</p>
              </div>
              <div class="col grey text-center">
                <p>₱${parseFloat(income.amount).toFixed(2)}</p>
              </div>
              <div class="col text-center">
                <p>${income.date}</p>
              </div>
              <div class="col text-center">
                <button class="btn btn-sm btn-warning me-2 edit" data-id="${income.income_id}">Edit</button>
                <button class="btn btn-sm btn-danger remove" data-id="${income.income_id}">Remove</button>
              </div>
            </div>
          `;
              })
              .join("");

            historyContainer.innerHTML = rows;

          } else {
            historyContainer.innerHTML = `
        <div class="pt-5 d-flex justify-content-center align-items-center">
          <b>Error: ${data.message}</b>
        </div>
      `;
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message
            });
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          historyContainer.innerHTML = `
      <div class="pt-5 d-flex justify-content-center align-items-center">
        <b>An error occurred while fetching income history.</b>
      </div>
    `;
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An unexpected error occurred. Please try again later.'
          });
        });
    }

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

    // Add Income Handler
    document.addEventListener("DOMContentLoaded", function() {
      document.getElementById("add").addEventListener("click", function() {
        const type = document.getElementById("type").value;
        const amount = document.getElementById("amount").value;
        const date = document.getElementById("date").value;

        if (type === "" || amount === "" || date === "") {
          Swal.fire({
            icon: "warning",
            title: "Missing Fields",
            text: "Please fill out all fields before submitting.",
          });
          return;
        }

        const formData = new FormData();
        formData.append("action", "add_income");
        formData.append("type", type);
        formData.append("amount", amount);
        formData.append("date", date);

        fetch("dashboard.php", {
            method: "POST",
            body: formData,
          })
          .then((response) => response.json())
          .then((data) => {
            if (data.status === "success") {
              Swal.fire({
                icon: "success",
                title: "Income Added",
                text: data.message,
              });

              document.getElementById("type").value = "income";
              document.getElementById("amount").value = "";
              document.getElementById("date").value = "";
              fetchIncomeHistory();
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

    // Remove Income Handler
    document.querySelector(".scrollbar").addEventListener("click", function(event) {
      if (event.target.classList.contains("remove")) {
        const incomeId = event.target.dataset.id;

        // Show SweetAlert2 confirmation before deleting
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
            // If user confirms, proceed to delete
            const formData = new FormData();
            formData.append("action", "remove_income");
            formData.append("id", incomeId);

            fetch("dashboard.php", {
                method: "POST",
                body: formData,
              })
              .then((response) => response.json())
              .then((data) => {
                if (data.status === "success") {
                  Swal.fire({
                    icon: "success",
                    title: "Income Removed",
                    text: data.message,
                  });
                  fetchIncomeHistory(); // Refresh the income history after deletion
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
          }
        });
      }
    });

    // Edit Income Handler
    document.querySelector(".scrollbar").addEventListener("click", function(event) {
      if (event.target.classList.contains("edit")) {
        const incomeId = event.target.dataset.id;
        const incomeRow = document.querySelector(`#row-${incomeId}`);
        const type = incomeRow.querySelector("div:nth-child(1) p").textContent.trim();
        const amount = parseFloat(
          incomeRow.querySelector("div:nth-child(2) p").textContent.trim().replace("₱", "")
        );
        const date = incomeRow.querySelector("div:nth-child(3) p").textContent.trim();

        // Display Swal input fields for editing
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
          preConfirm: () => {
            return {
              type: document.getElementById("edit-type").value,
              amount: document.getElementById("edit-amount").value,
              date: document.getElementById("edit-date").value,
            };
          },
        }).then((result) => {
          if (result.isConfirmed) {
            // Send the edited data to the server
            const formData = new FormData();
            formData.append("action", "update_income");
            formData.append("id", incomeId); // Include the income ID for the update
            formData.append("type", result.value.type);
            formData.append("amount", result.value.amount);
            formData.append("date", result.value.date);

            fetch("dashboard.php", {
                method: "POST",
                body: formData,
              })
              .then((response) => response.json())
              .then((data) => {
                if (data.status === "success") {
                  Swal.fire({
                    icon: "success",
                    title: "Income Updated",
                    text: data.message,
                  });
                  fetchIncomeHistory(); // Refresh the income history after update
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
          }
        });
      }
    });

    document.addEventListener("DOMContentLoaded", fetchIncomeHistory);
  </script>


  <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->

  <!-- Bootstrap Datepicker JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="./js/utils/script.js"></script>
</body>

</html>