<?php
require_once('includes/setup.php');

// Check whether the user is logged in
if (!$loggedInUser) {
    redirect('index.php'); //redirect to index.php if not
}

if ((isset($_SESSION['auth_required']) && $_SESSION['auth_required'] === false)) {
    flash('error', 'You have already verified your account.');
    redirect('index.php');
}

$page = 'MFA';
$title = 'Multi Factor Authentication';

$secret = '';
$new_secret = false;

// Check if the user already has a secret
if (isset($loggedInUser['auth_secret']) && $loggedInUser['auth_secret'] != null) {
    $secret = $loggedInUser['auth_secret'];
} else if (isset($_SESSION['auth_secret'])) {
    $secret = $_SESSION['auth_secret'];
    $new_secret = true;
} else {
    // Create a new secret and store it in session
    $secret = generateSecret();
    $_SESSION['auth_secret'] = $secret;
    $new_secret = true;
}

$qrCodeUrl = sprintf(
    "otpauth://totp/EnterprisePro:%s?secret=%s&issuer=EnterprisePro",
    urlencode($loggedInUser['email']),
    $secret
);

// Listen for form submission requests within 2FA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $mysql->real_escape_string($_POST['code']);
    $isValid = verifyCode($secret, $code);

    if ($isValid) {
        // Save the secret into the user's table if not already stored
        if ($loggedInUser) {
            if (!isset($loggedInUser['auth_secret'])) {
                $stmt = $mysql->prepare("UPDATE `users` SET `auth_secret` = ? WHERE `id` = ?");
                $stmt->bind_param('si', $secret, $loggedInUser['id']);
                $stmt->execute();
            }
        }

        $_SESSION['auth_required'] = false;

        flash('success', 'You have successfully verified your account.');
        redirect('index.php');
    } else {
        flash('error', 'Invalid two-factor authentication combination.');
        redirectToReferer();
    }
}

require_once('includes/header.php');
?>
<div class="block block--auth">
    <div class="block__section"></div>
    <div class="block__header">
        Multi Factor Authentication
    </div>
    <div class="block__body">
        <?php if ($new_secret): ?>
            <div id="qrcode" data-url="<?php echo htmlspecialchars($qrCodeUrl); ?>"></div>
        <?php endif; ?>
        <!-- 2FA Code Input Form -->
        <form action="" method="POST">
            <div class="input">
                <label for="input-code" class="input__label">Code</label>
                <input type="number" class="input__control" id="input-code" maxlength="6" name="code" required>
            </div>
            <div class="input">
                <button type="submit" class="button button--primary button--fluid">Verify</button>
            </div>
        </form>
    </div>
</div>


<?php

require_once('includes/footer.php');
