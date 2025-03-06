<?php
require_once('includes/setup.php');

// Logged in users should not be able to access register page
if ($loggedInUser) {
	redirect('index.php');
}

// Form processor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	$email = $mysql->real_escape_string($_POST['email']);
	$password = $mysql->real_escape_string($_POST['password']);


	// Make sure form is not empty
	if (empty($email) || empty($password)) {
		flash('error', 'Please fill the entire login form.');
		redirectToReferer();
	}
	// Hash the password
	$passwordHashed = md5($password);

	// Get the user from the database
	$userQuery = $mysql->query("
		SELECT *
		FROM users
		WHERE email = '{$email}'
		AND password = '{$passwordHashed}'
		LIMIT 1
	");

	$user = $userQuery->fetch_assoc();
	if (!$user) {
		flash('error', 'Please enter a valid email or password.');
		redirectToReferer();
	}

	// Set session data for all pages
	// $_SESSION['totp_required'] = true;
	$_SESSION['user'] = $user['id'];

	flash('success', 'You have successfully logged in: ' . "{$user['first_name']}");
	redirect('index.php');
}

$page = 'login';
$title = 'Login';

require_once('includes/header.php');

?>
<div class="block block--auth">
	<div class="block__header">
		Login
	</div>
	<div class="block__body">
		<!-- Form for /login  POST request -->
		<form action="" method="POST">
			<div class="input">
				<label for="input-email" class="input__label">Email</label>
				<input type="email" class="input__control" id="input-email" name="email">
			</div>
			<div class="input">
				<label for="input-password" class="input__label">Password</label>
				<input type="password" class="input__control" id="input-password" name="password">
			</div>
			<div class="input">
				<button type="submit" class="button button--primary button--fluid">Login</button>
			</div>
		</form>
	</div>
	<div class="block__footer">
		<a href="/reset-password.php">Forgotten Password</a>
	</div>
	<div style="display: flex; align-items: center; justify-content: center;">
		<div style="display: flex; align-items: center; width: 50%; max-width: 300px;">
			<div style="flex-grow: 1; height: 1px; background-color: black;"></div>
			<span style="margin: 0 10px;">OR</span>
			<div style="flex-grow: 1; height: 1px; background-color: black;"></div>
		</div>

	</div>

	<div class="block__footer">
		<a href="/register.php" class="button button--primary">Create account</a>
	</div>
</div>

<?php

// Include the footer file
require_once('includes/footer.php');
