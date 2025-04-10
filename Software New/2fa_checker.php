<?php
if ($loggedInUser) { // Check if the user is logged in
    if (isset($_SESSION["auth_required"]) && $_SESSION['auth_required'] == true) { // Check if they require authentication

        // Ensure `$page` is defined and normalize for comparison
        $currentPage = strtolower(trim($page ?? ''));

        // Allow only if user is on '2fa' or 'index' page
        if (!in_array($currentPage, ['2fa', 'index'])) {
            flash('error', 'You do not have permission to access <strong>' . htmlspecialchars($title) . "</strong> page!");
            redirect('/2fa.php'); // Redirect to 2FA page
        }
    }

    // Check if verified page
    if (!$loggedInUser['verified'] && !$loggedInUser['admin']) {
        flash('error', "You can't access this page as your account has not been verified yet");
        redirect('/index.php');
    }
} else {
    flash('error', 'You need to be logged in to access this page.');
    redirect('login.php');
}
