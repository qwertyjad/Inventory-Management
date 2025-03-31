<?php
session_start();
include '../function.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$functions = new Functions();

// Ensure the user has passed the initial login or registration step
if (!isset($_SESSION['temp_user_id'])) {
    $_SESSION['msg'] = "<div class='toast-message error'>Please log in or register first!</div>";
    header('Location: login.php');
    exit;
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['temp_user_id'];
$user = $functions->GetUserInfo($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['msg'] = "<div class='toast-message error'>CSRF token validation failed!</div>";
        header('Location: verify-2fa.php');
        exit;
    }

    if (isset($_POST['resend_otp'])) {
        // Resend OTP
        $otp = $functions->generateAndStoreOTP($user_id);
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
                $mail->addAddress($user['email']);
                $mail->isHTML(true);
                $mail->Subject = 'Your New OTP for ContructInv Verification';
                $mail->Body = "Hello {$user['full_name']},<br>Your new OTP is <strong>{$otp}</strong>. It expires in 5 minutes.";
                $mail->send();

                $_SESSION['msg'] = "<div class='toast-message success'>A new OTP has been sent to your email!</div>";
            } catch (Exception $e) {
                $_SESSION['msg'] = "<div class='toast-message error'>Failed to send OTP: {$mail->ErrorInfo}</div>";
            }
        } else {
            $_SESSION['msg'] = "<div class='toast-message error'>Failed to generate a new OTP!</div>";
        }
        header('Location: verify-2fa.php');
        exit;
    }

    $code = trim($_POST['code']);
    if ($functions->verifyOTP($user_id, $code)) {
        // OTP verification successful, complete the login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['isLoggedIn'] = true;
        $_SESSION['user_role'] = $user['role'];
        unset($_SESSION['temp_user_id']); // Clear temp user ID

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header('Location: ../admin/index.php');
        } elseif ($user['role'] === 'staff') {
            header('Location: ../user/index.php');
        }
        exit;
    } else {
        $_SESSION['msg'] = "<div class='toast-message error'>Invalid or expired OTP!</div>";
        header('Location: verify-2fa.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify OTP - ContructInv</title>
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
        if (isset($_SESSION['msg'])) {
            echo "<div id='toast-container'>";
            echo $_SESSION['msg'];
            echo "</div>";
            unset($_SESSION['msg']);
        }
        ?>
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="card shadow-lg border-0">
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
                        <p class="text-center text-dark">Verify your email</p>
                        <p class="text-center text-muted">Enter the OTP sent to your email.</p>

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <div class="mb-3">
                                <label class="form-label fw-medium text-dark">OTP Code</label>
                                <input type="text" class="form-control" name="code" required>
                            </div>
                            <div class="d-flex justify-content-between gap-3 mb-5">
                                <a href="login.php" class="btn btn-outline-dark w-50 py-2 fw-medium">Back</a>
                                <button type="submit" class="btn btn-dark w-50 py-2 fw-medium text-white">Verify</button>
                            </div>
                        </form>
                        <form method="POST" class="text-center">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="resend_otp" value="1">
                            <button type="submit" class="btn btn-link">Resend OTP</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
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