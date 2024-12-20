<?php
// Include the database connection
include 'database/db_connection.php';

// Start a session to store user details
session_start();

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Get the posted data from the form
  $email = $_POST['email'];
  $password = $_POST['password'];

  // Input validation
  if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Email and Password are required!']);
    exit;
  }

  // Prepare and execute the query to check if the email exists in the database
  $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  // Check if an account with the provided email exists
  if ($result->num_rows === 1) {
    // Fetch the user data
    $user = $result->fetch_assoc();

    // Verify the password
    if (password_verify($password, $user['password'])) {
      // Store user details in the session for authentication
      $_SESSION['user_id'] = $user['user_id']; // Ensure user_id matches the column name in your table
      $_SESSION['user_email'] = $user['email'];

      // Return a success response to the frontend
      echo json_encode(['status' => 'success', 'message' => 'Login successful!']);
    } else {
      // Incorrect password
      echo json_encode(['status' => 'error', 'message' => 'Incorrect password!']);
    }
  } else {
    // No account found with the given email
    echo json_encode(['status' => 'error', 'message' => 'No account found with this email!']);
  }

  // Close the prepared statement and database connection
  $stmt->close();
  $conn->close();
  exit;
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css?family=Inter&display=swap" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.min.css" />
  <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

  <!-- Bootstrap Datepicker CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" />

  <link rel="icon" href="./assets/img/piclogo-removebg.png" type="image/x-icon" />
  <link rel="stylesheet" href="css/login.css">

  <title>MoneyTracker</title>
</head>

<body>
  <div class="container-fluid">
    <div class="row row1">
      <!-- Left Column (Form Section) -->
      <div class="col-md-6 col1 d-flex" data-aos="zoom-in">
        <div class="consign">

          <!-- Error and Success Messages -->
          <div class="alert alert-success" id="successMessage" hidden></div>
          <div class="alert alert-danger" id="errorMessage" hidden></div>

          <h3 class="fw-bold">Welcome!</h3>
          <p class="pb-3">Please enter your details:</p>

          <!-- Login Form -->
          <form id="loginForm">
            <div class="form-container pb-5">
              <!-- Email Input -->
              <label class="form-label">Email:</label>
              <input id="email" type="text" placeholder="Enter your email" class="form-control narrow-input" name="email">
              <span class="validation-error-color" id="email_Error"></span>

              <br>

              <!-- Password Input -->
              <label class="form-label">Password:</label>
              <input id="password" type="password" placeholder="*********" class="form-control narrow-input" name="password">
              <span class="validation-error-color" id="password_Error"></span>
            </div>

            <!-- Submit Button (Sign In) -->
            <button type="submit" style="background: #ADEF84; border: 1px solid #90C271; width: 300px" class="btn btn-signin">
              Sign in
            </button>

            <!-- Sign Up Link -->
            <p class="text-center pt-2">Don't have an account? <a href="register.php">Sign up</a></p>
          </form>

        </div>
      </div>

      <!-- Right Column (Image Section) -->
      <div class="col-md-6 col2 d-flex">
        <img src="./assets/img/piclogo.png" class="img-fluid imgcon" alt="Logo" />
      </div>
    </div>
  </div>

  <!-- External JS for Login Handling -->
  <script type="module" src="./js/auth/login.js"></script>

  <!-- Bootstrap Datepicker JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="./js/utils/script.js"></script>
</body>

</html>