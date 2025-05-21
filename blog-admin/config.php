<?php

$host = "localhost";
$user = "xcelmoqy_blog_admin";
$pass = "9]U4vo#@OhQ&";
$db   = "xcelmoqy_blog_admin";



$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>


