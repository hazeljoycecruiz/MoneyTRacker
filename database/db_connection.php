<?php

// Database credentials
$host = 'localhost'; // Hostname (usually localhost for XAMPP)
$username = 'root'; // Default username for XAMPP
$password = ''; // Default password for XAMPP (usually empty)
$database = 'moneytracker1'; // Name of your database

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
