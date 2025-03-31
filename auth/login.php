<?php
include_once '../session.php';
include_once '../function.php';

Session::init();
$function = new Functions();

// Generate CSRF token only if it doesn't exist
if (!Session::get('csrf_token')) {
    Session::set('csrf_token', bin2hex(random_bytes(32)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== Session::get('csrf_token')) {
        Session::set('msg', "<div class='toast-message error'>CSRF token validation failed!</div>");
        header('Location: login.php');
        exit;
    }

    // Validate and sanitize inputs
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Session::set('msg', "<div class='toast-message error'>Invalid email format!</div>");
        header('Location: login.php');
        exit;
    }

    $password = trim($_POST['password']);
    if (empty($password)) {
        Session::set('msg', "<div class='toast-message error'>Password cannot be empty!</div>");
        header('Location: login.php');
        exit;
    }

    // Attempt to log in
    $user = $function->login($email, $password);
    if ($user) {
        // Set session variables for logged-in user
        Session::set('user_id', $user['id']);
        Session::set('isLoggedIn', true);
        Session::set('user_role', $user['role']);
        Session::set('msg', "<div class='toast-message success'>Login successful!</div>");

        // Regenerate CSRF token for security
        Session::set('csrf_token', bin2hex(random_bytes(32)));

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header('Location: ../admin/index.php');
        } elseif ($user['role'] === 'staff') {
            header('Location: ../user/index.php');
        }
        exit;
    } else {
        Session::set('msg', "<div class='toast-message error'>Invalid email or password!</div>");
        header('Location: login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Inventory Management</title>
    <link href="../assets/libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        #toast-container .toast-message {
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
            color: white;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        #toast-container .toast-message.success {
            background-color: #38a169;
        }
        #toast-container .toast-message.error {
            background-color: #e53e3e;
        }
    </style>
</head>
<body class="bg-light min-vh-100 d-flex align-items-center justify-content-center">
    <div class="container">
        <?php
        $msg = Session::get('msg');
        if ($msg) {
            echo "<div id='toast-container'>";
            echo $msg;
            echo "</div>";
            Session::set('msg', null);
        }
        ?>
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="card shadow-lg border-1">
                    <div class="card-body p-5">
                    <div class="text-center mb-4 d-flex justify-content-center align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" 
                                 stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                                 class="lucide lucide-package me-2">
                                <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path>
                                <path d="M12 22V12"></path>
                                <path d="m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7"></path>
                                <path d="m7.5 4.27 9 5.15"></path>
                            </svg>
                            <h2 class="fw-bold text-dark">ContructInv</h2>
                        </div>
                        <form method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::get('csrf_token')); ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div><hr>

                            <button type="submit" class="btn btn-dark w-100">Sign In</button>
                        </form>
                        <p class="text-center mt-3">
                            Don't have an account? <a href="register.php" class="text-dark"><strong>Create one</strong></a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toastContainer = document.getElementById('toast-container');
            if (toastContainer) {
                const toasts = toastContainer.querySelectorAll('.toast-message');
                toasts.forEach(toast => {
                    setTimeout(() => { toast.style.opacity = '1'; }, 100);
                    setTimeout(() => { toast.style.opacity = '0'; }, 3000);
                    setTimeout(() => { toast.remove(); }, 3500);
                });
            }
        });
    </script>
</body>
</html>