<?php
include_once 'session.php';
Session::init();

// Clear session and destroy
Session::destroy();

// Redirect to login page
header('Location: index.php');
exit;
?>