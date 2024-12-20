<?php
// Include database connection
include_once 'database/db_connection.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
  // Redirect to the login page if no user is logged in
  header("Location: index.php");
  exit; // Always exit after a header redirect to prevent further code execution
}

$user_id = $_SESSION['user_id']; // Get logged-in user's ID


// Function to get daily data (income or expenses) for a specific category
function getDailyData($user_id, $type = 'expenses', $period = 'week')
{
  global $conn;

  // Determine which table to query based on the type
  $table = $type === 'income' ? 'user_incomes' : 'user_expenses';
  $column = 'amount'; // Same column for both types (income or expenses)
  $date_column = 'date'; // Assuming 'date' exists in both tables

  // Modify the query based on the selected period (week or month)
  if ($period == 'month') {
    $query = "SELECT DATE_FORMAT(date, '%M') AS month, SUM($column) AS total
              FROM $table
              WHERE user_id = '$user_id' 
              AND date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
              GROUP BY month
              ORDER BY FIELD(month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')";
  } else {
    $query = "SELECT DATE_FORMAT(date, '%W') AS day, SUM($column) AS total
              FROM $table
              WHERE user_id = '$user_id' 
              AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              GROUP BY day
              ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
  }

  $result = mysqli_query($conn, $query);
  $data = ($period == 'month')
    ? array_fill_keys(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'], 0)
    : array_fill_keys(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'], 0);

  while ($row = mysqli_fetch_assoc($result)) {
    $data[$row['day']] = $row['total'];  // Populate the data array with actual totals for each day/month
  }

  return $data;
}



// Fetch daily spending data from user_expenses (Spending category)
$spending_data = getDailyData($user_id, 'expenses');

// Fetch daily income data from user_incomes (Income category)
$income_data = getDailyData($user_id, 'income');

// Fetch total income for the period (Month by default)
$income = getTotalForPeriod($user_id, 'Income');

// Fetch total expenses for the period (Month by default)
$expenses = getTotalForPeriod($user_id, 'Expenses');


// Function to get monthly data (income or expenses)
function getMonthlyData($user_id, $type = 'expenses')
{
  global $conn;

  $table = $type === 'income' ? 'user_incomes' : 'user_expenses';
  $column = 'amount';
  $date_column = 'date';

  $query = "SELECT DATE_FORMAT($date_column, '%M') AS month, SUM($column) AS total
            FROM $table
            WHERE user_id = '$user_id'
            AND $date_column >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
            GROUP BY month
            ORDER BY FIELD(month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')";

  $result = mysqli_query($conn, $query);
  $data = array_fill_keys(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'], 0);

  while ($row = mysqli_fetch_assoc($result)) {
    $data[$row['month']] = $row['total'];
  }

  return $data;
}

// Fetch monthly data
$monthly_spending_data = getMonthlyData($user_id, 'expenses');
$monthly_income_data = getMonthlyData($user_id, 'income');

// Endpoint for AJAX requests
if (isset($_GET['period']) && $_GET['period'] === 'month') {
  echo json_encode([
    'income' => array_values($monthly_income_data),
    'spending' => array_values($monthly_spending_data)
  ]);
}

// Function to get total expenses for a specific period (week or month)
function getTotalForPeriod($user_id, $category, $period = 'month')
{
  global $conn;
  $date_filter = ($period == 'week') ? "DATE_SUB(CURDATE(), INTERVAL 1 WEEK)" : "DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";

  $query = "SELECT SUM(amount) AS total_expenses 
            FROM user_expenses 
            WHERE user_id = '$user_id' 
            AND category = '$category' 
            AND date >= $date_filter";
  $result = mysqli_query($conn, $query);

  return ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['total_expenses'] : 0;
}

// Fetch expenses for each category
$categories = ['Education', 'Entertainment', 'Food', 'Health', 'Utilities', 'Shopping', 'Transportation', 'Miscellaneous'];
$expenses = [];
foreach ($categories as $category) {
  $expenses[$category] = getTotalForPeriod($user_id, $category); // Default period is 'month'
}

// Fetch income and budget data (you may modify this query as needed)
$income = getTotalForPeriod($user_id, 'Income');
$budget = getTotalForPeriod($user_id, 'Budget');

// Query to get total expenses for each category for the logged-in user
$query = "SELECT category, SUM(amount) AS total_expenses FROM user_expenses WHERE user_id = '$user_id' GROUP BY category";
$result = mysqli_query($conn, $query);

// Store data in an associative array for later use
$expenses = [];
if ($result && mysqli_num_rows($result) > 0) {
  while ($row = mysqli_fetch_assoc($result)) {
    $expenses[$row['category']] = $row['total_expenses'];
  }
}

// Categories to display
$categories = ['Education', 'Entertainment', 'Food', 'Health', 'Utilities', 'Shopping', 'Transportation', 'Miscellaneous'];

// Define function to get category icon
function getCategoryIcon($category)
{
  $icons = [
    'Education' => 'fa-book',
    'Entertainment' => 'fa-film',
    'Food' => 'fa-utensils',
    'Health' => 'fa-medkit',
    'Utilities' => 'fa-lightbulb',
    'Shopping' => 'fa-cart-arrow-down',
    'Transportation' => 'fa-bus-alt',
    'Miscellaneous' => 'fa-ellipsis-h'
  ];

  return isset($icons[$category]) ? $icons[$category] : 'fa-question-circle'; // Default icon if category not found
}

// getData.php
if (isset($_GET['period'])) {
  $period = $_GET['period']; // 'week' or 'month'

  // Fetch income and expenses based on the selected period
  $income_data = getDailyData($user_id, 'income', $period);
  $spending_data = getDailyData($user_id, 'expenses', $period);

  // Return the data as a JSON response
  echo json_encode([
    'income' => array_values($income_data), // Convert associative array to indexed array
    'spending' => array_values($spending_data)
  ]);
}


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

  <link rel="stylesheet" href="css/summary.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script type="module" src="./js/summary/summary.js"></script>
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
        <li class="">
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

        <li class="active">
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
    <div class="content d-flex ps-5 align-item-center">
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
      <div class="dashboard-content px-3 pt-4">
        <h3 class="fw-bold">Summary</h3>
        <div class="container-fluid d-flex ps-2 topcontainer">
          <div class="col-6" style="margin-right: 20px;">
            <div class="barCon drowShadow">
              <div class="d-flex justify-content-between align-items-center" id="periodSelect">
                <!-- Chart and Select Dropdown -->
                <h5 class="fw-bold pt-4 pb-2 ps-4">Weekly Summary</h5>
              </div>
              <div class="container pt-2 ps-3">
                <div class="chartCon">
                  <canvas id="myChart"></canvas>
                </div>
              </div>
            </div>
          </div>

          <div class="col-5">
            <div class="barCon drowShadow">
              <div class="d-flex justify-content-between align-items-center" id="rightPeriodSelect">
                <!-- Chart and Select Dropdown -->
                <h5 class="fw-bold pt-4 pb-2 ps-4">Monthly Summary</h5>

              </div>
              <div class="container pt-2 ps-3">
                <div class="chartCon">
                  <canvas id="rightChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="container-fluid pt-3 bot-container">
          <!-- First Row: Education, Entertainment, Food, Health -->
          <div class="row">
            <?php
            $first_row_categories = ['Education', 'Entertainment', 'Food', 'Health'];
            foreach ($first_row_categories as $category): ?>
              <div class="col-3 px-2">
                <div class="rec drowShadow">
                  <div class="container pt-3 ps-3 d-flex align-items-center">
                    <i class="fas fa-<?= getCategoryIcon($category) ?> iconColor"></i>
                    <span class="fs-5 ps-1"><?= $category ?></span>
                  </div>
                  <span class="fs-5 ps-4 total-expenses-label">Total Expenses:</span>
                  <div class="container <?= strtolower($category) ?>Con d-flex align-items-center justify-content-center">
                    <div class="text-center">
                      <?php if (isset($expenses[$category])): ?>
                        <span class="fs-5">₱ <?= number_format($expenses[$category], 2) ?></span>
                      <?php else: ?>
                        <span class="fs-5">No expenses</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Second Row: Utilities, Shopping, Transportation, Miscellaneous -->
          <div class="row mt-3">
            <?php
            $second_row_categories = ['Utilities', 'Shopping', 'Transportation', 'Miscellaneous'];
            foreach ($second_row_categories as $category): ?>
              <div class="col-3 px-2">
                <div class="rec drowShadow">
                  <div class="container pt-3 ps-3 d-flex align-items-center">
                    <i class="fas fa-<?= getCategoryIcon($category) ?> iconColor"></i>
                    <span class="fs-5 ps-1"><?= $category ?></span>
                  </div>
                  <span class="fs-5 ps-4 total-expenses-label">Total Expenses:</span>
                  <div class="container <?= strtolower($category) ?>Con d-flex align-items-center justify-content-center">
                    <div class="text-center">
                      <?php if (isset($expenses[$category])): ?>
                        <span class="fs-5">₱ <?= number_format($expenses[$category], 2) ?></span>
                      <?php else: ?>
                        <span class="fs-5">No expenses</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>


    </div>
  </div>
  </div>

  <script>
    // Pass PHP data to JavaScript
    const categories = <?php echo json_encode(array_keys($expenses)); ?>;
    const spendingData = <?php echo json_encode($spending_data); ?>;
    const incomeData = <?php echo json_encode($income_data); ?>;
    const monthlyIncomeData = Object.values(<?php echo json_encode($monthly_income_data); ?>);
    const monthlySpendingData = Object.values(<?php echo json_encode($monthly_spending_data); ?>);

    const monthLabels = [
      'January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December'
    ];

    // Chart.js initialization for the "rightChart"
    const rightCtx = document.getElementById('rightChart').getContext('2d');
    const rightChart = new Chart(rightCtx, {
      type: 'bar',
      data: {
        labels: monthLabels, // Default to months
        datasets: [{
            label: 'Income (₱)',
            data: monthlyIncomeData,
            backgroundColor: 'rgba(0, 123, 255, 0.2)',
            borderColor: 'rgba(0, 123, 255, 1)',
            borderWidth: 1,
          },
          {
            label: 'Spending (₱)',
            data: monthlySpendingData,
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: (value) => '₱' + value.toLocaleString(), // Format as currency
            },
          },
        },
      },
    });

    // Fetch data based on the selected period (week or month)
    function fetchRightChartData(period) {
      const url = `getData.php?period=${period}`;
      fetch(url)
        .then((response) => response.json())
        .then((data) => updateRightChart(data, period))
        .catch((error) => console.error('Error fetching data:', error));
    }

    // Update the "rightChart" with new data and labels
    function updateRightChart(data, period) {
      const labels = period === 'month' ?
        monthLabels : ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

      rightChart.data.labels = labels;
      rightChart.data.datasets[0].data = data.income;
      rightChart.data.datasets[1].data = data.spending;
      rightChart.update();
    }

    // Add event listener for the period selector
    document.getElementById('rightPeriodSelect').addEventListener('change', function() {
      fetchRightChartData(this.value); // Fetch data based on the selected period
    });

    // Initialize the chart for daily income and spending
    const ctx = document.getElementById('myChart').getContext('2d');
    const myChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'], // Default: days of the week
        datasets: [{
            label: 'Income (₱)',
            data: Object.values(incomeData),
            backgroundColor: 'rgba(0, 123, 255, 0.2)',
            borderColor: 'rgba(0, 123, 255, 1)',
            borderWidth: 1,
          },
          {
            label: 'Spending (₱)',
            data: Object.values(spendingData),
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: (value) => '₱' + value.toLocaleString(), // Format as currency
            },
          },
        },
      },
    });

    // Add event listener for the "Logout" link with SweetAlert confirmation
    document.getElementById('logout').addEventListener('click', function(event) {
      event.preventDefault();
      Swal.fire({
        title: 'Are you sure?',
        text: "You will be logged out of your account.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, logout',
        cancelButtonText: 'No',
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'index.php'; // Redirect to logout page
        }
      });
    });
  </script>


  <script
    src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"
    integrity="sha512-JPcRR8yFa8mmCsfrw4TNte1ZvF1e3+1SdGMslZvmrzDYxS69J7J49vkFL8u6u8PlPJK+H3voElBtUCzaXj+6ig=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer"></script>

  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script
    src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
    integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
    crossorigin="anonymous"></script>
  <script
    src="https://cdn.jsdelivr.net/npm/popper.js@1.14.3/dist/umd/popper.min.js"
    integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49"
    crossorigin="anonymous"></script>
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"
    integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
    crossorigin="anonymous"></script>
  <script src="https://www.google.com/recaptcha/api.js"></script>

  <!-- Bootstrap Datepicker JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
  <script rc="/js/utils/script.js"></script>
  <script src="./js/utils/script.js"></script>
</body>

</html>