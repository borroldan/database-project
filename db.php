<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "csapatsport";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Hiba: " . $conn->connect_error);
}
?>