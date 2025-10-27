<?php
$host = "localhost";    // DB host
$user = "root";          // DB username
$pass = "";              // DB password
$dbname = "crud_demo";   // DB name

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
