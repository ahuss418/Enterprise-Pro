<?php
require_once('includes/setup.php');

$page = 'admin';
$title = 'Admin';

require_once('2fa_checker.php');

if ($loggedInUser && !$loggedInUser['admin']) {
    flash('error', 'You do not have permission to access <strong>' . htmlspecialchars($title) . "</strong> page!");
    redirect('/index.php');
}

require_once('includes/header.php');
require_once('admin_sidepanel.php');
require_once('includes/footer.php');
