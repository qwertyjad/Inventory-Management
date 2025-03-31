<?php
session_start();
include '../function.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$functions = new Functions();

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "CSRF token validation failed!";
    } else {
        $data = [
            'full_name' => trim($_POST['full_name']),
            'email' => filter_var($_POST['email'], FILTER_SANITIZE_EMAIL),
            'password' => $_POST['password'],
            'role' => 'staff' // Default role, can be modified if needed
        ];

        // Validate inputs
        if (empty($data['full_name']) || empty($data['email']) || empty($data['password'])) {
            $error = "All fields are required!";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format!";
        } elseif (strlen($data['password']) < 6) {
            $error = "Password must be at least 6 characters long!";
        } elseif ($functions->checkEmailExists($data['email'])) {
            $error = "Email is already in use!";
        } else {
            // Register the user
            $userId = $functions->register($data['full_name'], $data['email'], $data['password'], $data['role']);
            if ($userId) {
                // Generate and store OTP
                $otp = $functions->generateAndStoreOTP($userId);
                if ($otp) {
                    // Send OTP via email
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'hadizy.io@gmail.com'; // Replace with your email
                        $mail->Password = 'czdq kiqz ctsq lkiw'; // Replace with your app-specific password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        $mail->setFrom('your-email@gmail.com', 'ContructInv');
                        $mail->addAddress($data['email']);
                        $mail->isHTML(true);
                        $mail->Subject = 'Your OTP for ContructInv Registration';
                        $mail->Body = "Hello {$data['full_name']},<br>Your OTP is <strong>{$otp}</strong>. It expires in 5 minutes.";
                        $mail->send();

                        // Store temp user ID for 2FA verification
                        $_SESSION['temp_user_id'] = $userId;
                        header('Location: verify-2fa.php');
                        exit;
                    } catch (Exception $e) {
                        $error = "Failed to send OTP: {$mail->ErrorInfo}";
                    }
                } else {
                    $error = "Failed to generate OTP!";
                }
            } else {
                $error = "Registration failed!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - ContructInv</title>
    <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
    <link rel="stylesheet" href="../assets/libs/bootstrap/dist/css/bootstrap.min.css" />
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
        #toast-container .toast-message.error {
            background-color: #e53e3e;
        }
    </style>
</head>
<body class="bg-light min-vh-100 d-flex align-items-center justify-content-center">
    <div class="container">
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
                        <p class="text-center text-dark">Create your account</p>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger text-center"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <div class="mb-3">
                                <label class="form-label fw-medium text-dark">Full Name</label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium text-dark">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium text-dark">Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between gap-3 mb-5">
                                <a href="../" class="btn btn-outline-dark w-50 py-2 fw-medium">Back</a>
                                <button type="submit" class="btn btn-dark w-50 py-2 fw-medium text-white">Register</button>
                            </div>
                            <div class="text-center">
                                <p class="mb-0 text-dark">Already have an account?
                                <a href="login.php" class="text-dark"><strong>Sign In</strong></a>
                                </p>
                                
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>