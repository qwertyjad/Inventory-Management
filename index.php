<?php
include_once 'session.php';
include_once 'function.php';

// Initialize session
Session::init();

// Check if user is logged in and redirect based on role
$function = new Functions();

if (Session::isLoggedIn()) {
    $role = Session::get('user_role'); // Changed from 'role' to 'user_role'
    if ($role === 'admin') {
        header('Location: admin/index.php');
        exit;
    } elseif ($role === 'staff') {
        header('Location: user/index.php');
        exit;
    } elseif ($role === 'user') {
        header('Location: user-dashboard.php');
        exit;
    }
}

// Handle form submission from modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guest_name'])) {
    $name = trim($_POST['guest_name']);
    if (!empty($name)) {
        // Use the updated saveGuestUser method to get the guest_user_id (existing or new)
        $guest_user_id = $function->saveGuestUser($name);
        if ($guest_user_id) {
            // Store guest user ID in session
            Session::set('guest_user_id', $guest_user_id);
            Session::set('guest_user_name', $name);
            Session::set('user_role', 'user'); // Changed from 'role' to 'user_role'
            header('Location: user-dashboard.php');
            exit;
        } else {
            $error = "Failed to save your name. Please try again.";
        }
    } else {
        $error = "Please enter a valid name.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory Management - Get Started</title>
    <link rel="shortcut icon" type="image/png" href="assets/images/logos/favicon.png" />
    <link href="assets/libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .welcome-card {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .btn-get-started {
            background-color: #2d3748;
            color: white;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 8px;
            transition: background-color 0.3s;
        }
        .btn-get-started:hover {
            background-color: #1a202c;
        }
    </style>
</head>
<body class="min-vh-100 d-flex align-items-center justify-content-center">
    <div class="welcome-card">
        <div class="d-flex align-items-center justify-content-center mb-4">
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
        <h2 class="h4 fw-semibold mb-4">Welcome to Inventory Management</h2>
        <p class="text-muted mb-4">Streamline your stock, optimize your business.</p>
        <button type="button" class="btn btn-get-started" data-bs-toggle="modal" data-bs-target="#nameModal">
            Get Started
        </button>
        <p class="mt-4 small text-muted">Already have an account? <a href="auth/login.php">Sign In</a></p>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="nameModal" tabindex="-1" aria-labelledby="nameModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nameModalLabel">Enter Your Name</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="guest_name" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="guest_name" name="guest_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>