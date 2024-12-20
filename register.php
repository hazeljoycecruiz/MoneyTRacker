<?php
// Include the database connection file to establish a connection to the database
require_once 'database/db_connection.php';

// Initialize variables to store error messages and success message
$nameError = $emailError = $passwordError = $passwordConfirmationError = "";
$successMessage = "";

// Check if the form was submitted via POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Retrieve form data and sanitize inputs to avoid XSS attacks
  $name = htmlspecialchars(trim($_POST['name']));
  $email = htmlspecialchars(trim($_POST['email']));
  $password = trim($_POST['password']);
  $password_confirmation = trim($_POST['password_confirmation']);

  // Set a flag to track if the form is valid
  $isValid = true;

  // Validate name: It should not be empty
  if (empty($name)) {
    $nameError = "Full name is required.";
    $isValid = false;
  }

  // Validate email: It should not be empty and should be a valid email format
  if (empty($email)) {
    $emailError = "Email is required.";
    $isValid = false;
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $emailError = "Invalid email format.";
    $isValid = false;
  }

  // Validate password: It should not be empty and should be at least 6 characters long
  if (empty($password)) {
    $passwordError = "Password is required.";
    $isValid = false;
  } elseif (strlen($password) < 6) {
    $passwordError = "Password must be at least 6 characters long.";
    $isValid = false;
  }

  // Check if password and password confirmation match
  if ($password !== $password_confirmation) {
    $passwordConfirmationError = "Passwords do not match.";
    $isValid = false;
  }

  // Check if the email already exists in the database (to avoid duplicates)
  if ($isValid) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);  // Bind email parameter to the query
    $stmt->execute();  // Execute the query
    $stmt->bind_result($emailCount);  // Store the count of matching emails
    $stmt->fetch();  // Fetch the result
    $stmt->close();  // Close the statement

    // If the email is already registered, show an error message
    if ($emailCount > 0) {
      $emailError = "Email is already registered.";
    } else {
      // Hash the password before storing it in the database for security
      $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

      // Prepare the SQL statement to insert the new user into the database
      $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
      $stmt->bind_param("sss", $name, $email, $hashedPassword);  // Bind parameters to the query

      // Execute the query and check for success
      if ($stmt->execute()) {
        $successMessage = "Registered successfully. You can now log in.";
      } else {
        $emailError = "An error occurred, please try again later.";  // Show error if insert fails
      }

      // Close the statement
      $stmt->close();
    }
  }

  // Close the database connection
  $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css?family=Inter&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="css/register.css" />
  <title>MoneyTracker</title>
</head>

<body>
  <div class="container-fluid">
    <div class="row">
      <div class="col col1 d-flex" data-aos="zoom-in">
        <div class="consign">
          <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
              <?php echo $successMessage; ?>
            </div>
            <script>
              setTimeout(function() {
                window.location.href = 'index.php'; // Redirect to login page after 1.5 seconds
              }, 2000);
            </script>
          <?php endif; ?>

          <h3 class="fw-bold">Create new account</h3>
          <p class="pb-3">Please enter your details:</p>
          <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
            <div class="form-container pb-5">
              <label class="form-label" for="name">Full Name:</label>
              <input
                type="text"
                class="form-control"
                id="name"
                name="name"
                placeholder="Enter your full name"
                value="<?php echo isset($name) ? $name : ''; ?>" />
              <span class="text-danger"><?php echo $nameError; ?></span>
              <br />

              <label class="form-label" for="email">Email:</label>
              <input
                type="email"
                class="form-control"
                id="email"
                name="email"
                placeholder="Enter your active email"
                value="<?php echo isset($email) ? $email : ''; ?>" />
              <span class="text-danger"><?php echo $emailError; ?></span>
              <br />

              <label class="form-label" for="password">Password:</label>
              <input
                type="password"
                class="form-control narrow-input"
                id="password"
                name="password"
                placeholder="Enter your password" />
              <span class="text-danger"><?php echo $passwordError; ?></span>
              <br />

              <label class="form-label" for="password_confirmation">Confirm Password:</label>
              <input
                type="password"
                class="form-control"
                id="password_confirmation"
                name="password_confirmation"
                placeholder="Confirm your password" />
              <span class="text-danger"><?php echo $passwordConfirmationError; ?></span>
            </div>

            <button
              type="submit"
              class="btn"
              style="background: #adef84; border: 1px solid #90c271; width: 300px;">
              Sign up
            </button>
          </form>
          <p class="text-center pt-2">Already have an account? <a href="index.php">Sign in</a></p>
        </div>
      </div>

      <div class="col-md-6 col2 d-flex">
        <img src="./assets/img/piclogo.png" class="img-fluid imgcon" alt="Logo" />
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>