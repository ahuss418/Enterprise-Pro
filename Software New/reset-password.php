<?php

use PHPMailer\PHPMailer\PHPMailer;

// Include the initialisation file
require_once('includes/setup.php');

// Check if the user is logged in and redirect to 'index'
if ($loggedInUser) {
    redirect('index.php');
}

// Check that form is submitted by POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reset'])) {
        // Process changing password form
        if (isset($_GET['token'])) {
            $token = $_GET['token'];
            if (!empty($token)) {
                $email = $mysql->real_escape_string($_POST['reset-email']);
                if (empty($email)) {
                    flash('error', 'Email is required.');
                    redirectToReferer();
                }

                $password = $mysql->real_escape_string($_POST['reset-password']);
                $confirmPassword = $mysql->real_escape_string($_POST['reset-confirm-password']);

                if (empty($password)) {
                    flash('error', 'Password is required.');
                    redirectToReferer();
                }

                // Ensure the password is at least 8 characters long
                if (strlen($password) < 8) {
                    flash('error', 'Password must be at least 8 characters.');
                    redirectToReferer();
                }

                // Ensure password and confirmation match
                if (empty($confirmPassword) || $confirmPassword != $password) {
                    flash('error', 'Password does not match.');
                    redirectToReferer();
                }

                // Query the database for a user with the given reset token that has not expired
                $userQuery = $mysql->query("
                    SELECT *
                    FROM users
                    WHERE reset_token = '{$token}'
                    AND reset_token_expire > NOW()
                    LIMIT 1
                ");

                $user = $userQuery->fetch_assoc();

                // If no user or token expired, display error message
                if (!$user) {
                    flash('error', 'The reset password link is either expired or invalid.');
                    redirectToReferer();
                }

                // Hash the password
                $passwordHashed = md5($password);

                // Update the user's password in the database
                $updateQuery = $mysql->query("
                    UPDATE users
                    SET password = '{$passwordHashed}',
                        reset_token = NULL,
                        reset_token_expire = NULL
                    WHERE id = {$user['id']}
                ");

                if ($updateQuery) {
                    flash('success', 'Your password has been updated successfully.');
                    redirect('login.php');
                } else {
                    flash('error', 'Failed to update password: ' . $mysql->error);
                    redirectToReferer();
                }
            } else {
                flash('error', 'Invalid or expired token.');
                redirect('reset-password.php');
            }
        }
    } elseif (isset($_POST['send'])) {
        // Process sending email to user to reset password
        $email = $mysql->real_escape_string($_POST['email']);

        if (empty($email)) {
            flash('error', 'Email is required.');
            redirectToReferer();
        }

        // Query the database for a user with that email
        $userQuery = $mysql->query("SELECT * FROM users WHERE email = '{$email}' LIMIT 1");
        $user = $userQuery->fetch_assoc();

        if (!$user) {
            flash('error', 'Account with that email was not found.');
            redirectToReferer();
        }

        // Generate token and expiry time
        $token = generateSecret();
        $token_expire = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        // Update the user record with token
        $updateQuery = $mysql->query("
            UPDATE users
            SET reset_token_expire = '$token_expire',
                reset_token = '$token'
            WHERE id = {$user['id']}
        ");

        if (!$updateQuery) {
            flash('error', 'Failed to create reset token: ' . $mysql->error);
            redirectToReferer();
        }

        // Include classes for sending emails
        require("vendor/phpmailer/phpmailer/src/PHPMailer.php");
        require("vendor/phpmailer/phpmailer/src/SMTP.php");
        require("vendor/phpmailer/phpmailer/src/Exception.php");

        // Send email
        $mail = new PHPMailer();
        $mail->isSMTP();

        $mail->CharSet = "UTF-8";
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPDebug = 0;
        $mail->Port = 587;
        $mail->SMTPAuth = true;
        $mail->isHTML(true);

        // Auth
        $mail->Username = "enterprisepro70@gmail.com";
        $mail->Password = "wpnr jfeu enjj cflu";
        $mail->FromName = "Enterprise Pro";

        $mail->Subject = "Password Reset Request";
        $mail->Body = "You requested to reset your password. Click the link below to reset it:<br><br>
            <a href='http://localhost/reset-password.php?token=$token'>Reset Password</a>";
        $mail->addAddress($email);

        if (!$mail->send()) {
            flash('error', 'Failed to send reset email: ' . $mail->ErrorInfo);
        } else {
            flash('success', 'A password reset link has been sent to your email.');
            redirect('index.php');
        }
    }
}

// Check if reset token is present
$resettingPassword = false;
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $userQuery = $mysql->query("
        SELECT *
        FROM users
        WHERE reset_token = '{$token}'
        AND reset_token_expire > NOW()
        LIMIT 1
    ");
    $user = $userQuery->fetch_assoc();

    if (!$user) {
        flash('error', 'The reset password link is either expired or invalid.');
        redirect('index.php');
    } else {
        $resettingPassword = true;
        flash('success', 'Reset link is valid. You can now update your password.');
    }
}

$page = 'reset-password';
$title = 'Reset Password';

require_once('includes/header.php');
?>

<div class="block block--auth">
    <div class="block__header">
        Reset Password
    </div>
    <div class="block__body">
        <?php if ($resettingPassword) { ?>
            <form action="" method="POST">
                <div class="input">
                    <label>Email</label>
                    <input type="email" class="input__control" name="reset-email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly>
                </div>
                <div class="input">
                    <label>New Password</label>
                    <input type="password" class="input__control" name="reset-password" required>
                </div>
                <div class="input">
                    <label>Confirm Password</label>
                    <input type="password" class="input__control" name="reset-confirm-password" required>
                </div>
                <div class="input">
                    <button type="submit" name="reset" class="button button--primary button--fluid">Reset Password</button>
                </div>
            </form>
        <?php } else { ?>
            <form action="" method="POST">
                <div class="input">
                    <label>Email</label>
                    <input type="email" class="input__control" name="email" required>
                </div>
                <div class="input">
                    <button type="submit" name="send" class="button button--primary button--fluid">Send Reset Link</button>
                </div>
            </form>
        <?php } ?>
    </div>
    <div class="block__footer">
        <a href="/login.php">Return to Login</a>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>