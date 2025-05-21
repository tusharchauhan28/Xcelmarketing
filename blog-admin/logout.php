<?php
session_start();

// Destroy the session and logout
session_destroy();
header('Location: login.php'); // Redirect to login page after logout
exit();
?>
