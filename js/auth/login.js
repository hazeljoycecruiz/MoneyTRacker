/* JS */

// Add an event listener to the form with the ID 'loginForm' for the 'submit' event
document.getElementById('loginForm').addEventListener('submit', function(event) {
    // Prevent the default form submission to handle it via JavaScript
    event.preventDefault();

    // Get the values of the email and password fields from the form
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    // Hide any previous error or success messages
    document.getElementById('errorMessage').hidden = true;
    document.getElementById('successMessage').hidden = true;

    // Check if the email and password are provided, if not show an error message
    if (!email || !password) {
        showError("Email and password are required.");
        return; // Stop further execution if validation fails
    }

    // Send a POST request to 'index.php' with the email and password as URL-encoded parameters
    fetch('index.php', {
        method: 'POST',
        body: new URLSearchParams({ email, password }) // Encode data as URL parameters
    })
        // Handle the response from the server
        .then(response => response.json())  // Parse the JSON response
        .then(data => {
            // Check the status from the server response
            if (data.status === 'error') {
                // If there's an error, display the error message
                showError(data.message);
            } else if (data.status === 'success') {
                // If login is successful, display the success message and redirect
                showSuccess("Login successful! Redirecting...");
                setTimeout(() => {
                    window.location.href = 'dashboard.php'; // Redirect to dashboard after 1.5 seconds
                }, 1500);
            }
        })
        // Catch any errors that occur during the fetch process and show an error message
        .catch(() => showError("Something went wrong. Please try again."));
});

// Function to display an error message
function showError(message) {
    document.getElementById('errorMessage').innerText = message;  // Set the error message text
    document.getElementById('errorMessage').hidden = false;       // Make the error message visible
}

// Function to display a success message
function showSuccess(message) {
    document.getElementById('successMessage').innerText = message;  // Set the success message text
    document.getElementById('successMessage').hidden = false;       // Make the success message visible
}
