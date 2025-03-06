<?php
// Loads config and session data
require_once('includes/setup.php');

// Only do something if they are logged in
if ($loggedInUser) {
	unset($_SESSION['user']);
	unset($_SESSION['totp_required']);
	unset($_SESSION['totp_secret']);
}

// Send back to home page
redirect('index.php');
