<?php
include_once 'session.php';
include_once 'function.php';

// Initialize session
Session::init();

// Check if user is logged in and redirect based on role
$function = new Functions();
if (Session::isLoggedIn()) {
    $user_id = Session::get('user_id');
    $user = $function->GetUserInfo($user_id);
    if ($user) {
        $role = strtolower($user['role']);
        if ($role === 'admin') {
            header('Location: admin/index.php');
            exit;
        } elseif ($role === 'staff') {
            header('Location: user/index.php');
            exit;
        } else {
            // If role is neither 'admin' nor 'staff', log them out
            Session::destroy();
            Session::set('msg', "<div class='toast-message error'>Invalid user role!</div>");
            header('Location: auth/login.php');
            exit;
        }
    } else {
        // If user info cannot be retrieved, log them out
        Session::destroy();
        Session::set('msg', "<div class='toast-message error'>Unable to retrieve user information!</div>");
        header('Location: auth/login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory Management</title>
    <link rel="shortcut icon" type="image/png" href="assets/images/logos/favicon.png" />
    <link href="assets/libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light min-vh-100 d-flex align-items-center justify-content-center">
    <div class="container-fluid">
        <div class="row w-75 mx-auto shadow-lg rounded-3 overflow-hidden bg-white">
            <!-- Left Side: Image and Title -->
            <div class="col-md-6 d-flex flex-column align-items-center justify-content-center p-5 bg-light">
                <img src="assets/images/icon/boxes-amico.png" alt="Inventory Icon" class="w-50 mb-4">
                <div class="d-flex align-items-center mb-2"> 
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" 
                         stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                         class="lucide lucide-package me-2">
                        <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path>
                        <path d="M12 22V12"></path>
                        <path d="m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7"></path>
                        <path d="m7.5 4.27 9 5.15"></path>
                    </svg>
                    <h1 class="fw-bold text-dark">ContructInv</h1>
                </div>
                <p class="text-muted text-center">Streamline your stock, optimize your business.</p>
            </div>

            <!-- Right Side: Button Panel -->
            <div class="col-md-6 d-flex flex-column align-items-center justify-content-center p-5 bg-dark text-white">
                <h2 class="h4 fw-semibold mb-4">Welcome</h2>
                <a href="auth/login.php" class="btn btn-light w-75 py-2 mb-3 fw-medium">Sign In</a>
                <a href="auth/register.php" class="btn btn-outline-light w-75 py-2 fw-medium">Create Account</a>
                <p class="mt-4 small text-light">Securely manage your inventory with ease.</p>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>