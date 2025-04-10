<?php
require_once('includes/setup.php');

// Logged in users should not be able to access register page
if ($loggedInUser) {
	redirect('index.php');
}

// Form processor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Full Name
	$firstName = $mysql->real_escape_string($_POST['first-name']);
	$lastName = $mysql->real_escape_string($_POST['last-name']);

	// Email
	$email = $mysql->real_escape_string($_POST['email']);

	// Password 
	$password = $mysql->real_escape_string($_POST['password']);
	$confirmPassword = $mysql->real_escape_string($_POST['confirm-password']);

	// Address Details
	$addressLine1 = $mysql->real_escape_string($_POST['address-1']);
	$addressLine2 = $mysql->real_escape_string($_POST['address-2']);
	$town = $mysql->real_escape_string($_POST['town']);
	$county = $mysql->real_escape_string($_POST['county']);
	$postcode = $mysql->real_escape_string($_POST['postcode']);

	// Local Area Status
	$liveLocal = isset($_POST['live-local']) ? 1 : 0;
	$workLocal = isset($_POST['work-local']) ? 1 : 0;
	$studyLocal = isset($_POST['study-local']) ? 1 : 0;

	// Make sure form is not empty
	if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword) || empty($addressLine1) || empty($town) || empty($county) || empty($postcode)) {
		flash('error', 'Please fill the entire registration form');
		redirectToReferer();
	}

	// Password must contain eight or more characters, including one digit and at least one upper case character
	if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) {
		flash('error', 'Password must contain eight or more characters, including one digit and at least one upper case character');
		redirectToReferer();
	}

	// Double check both passwords work
	if (empty($confirmPassword) || $confirmPassword != $password) {
		flash('error', 'Password does not match with the confirmation password');
		redirectToReferer();
	}

	// Make sure no one already has this email
	$userQuery = $mysql->query("
		SELECT *
		FROM users
		WHERE email='{$email}'
		LIMIT 1
	");

	$user = $userQuery->fetch_assoc();
	if ($user) {
		flash('error', 'This email is already used by another user.');
		redirectToReferer();
	}

	$admin = 0;
	$verified = 0;

	# Encrypt the password
	$passwordHashed = md5($password);

	// Add the user to the database
	$userQuery = $mysql->query("
    INSERT INTO users (
        first_name, last_name, email, password, 
        address_line_one, address_line_two, town, county, postcode, 
        status_live_local, status_work_local, status_study_local, 
        admin, verified
    ) VALUES (
        '{$firstName}', '{$lastName}', '{$email}', '{$passwordHashed}', 
        '{$addressLine1}', '{$addressLine2}', '{$town}', '{$county}', '{$postcode}', 
        {$liveLocal}, {$workLocal}, {$studyLocal}, 
        {$admin}, {$verified}
    )");

	if (!$userQuery) {
		flash('error', $mysql->error);
		echo "Error: " . $mysql->error;
		redirectToReferer();
	} else {
		// Query succeeded, fetch new user account
		$userQuery = $mysql->query("
            SELECT *
            FROM users
            ORDER BY id DESC
            LIMIT 1
        ");

		$user = $userQuery->fetch_assoc();

		// Set session data for all pages
		$_SESSION['user'] = $user['id'];
		$_SESSION['auth_required'] = true;

		// Welcome the user
		flash('success', 'You have successfully logged in: ' . "{$user['first_name']}");
		redirect('2fa.php');
	}
}

$page = 'register';
$title = 'Register';

require_once('includes/header.php');

?>
<div class="block block--auth">
	<div class="block__section">

	</div>
	<div class="block__header">Register</div>
	<div class="block__body">
		<!-- Form for /register POST request -->
		<form action="" method="POST">
			<!-- Name Section -->
			<div class="input">
				<label for="input-first-name" class="input__label">First Name</label>
				<input type="text" class="input__control" id="input-first-name" name="first-name">
			</div>
			<div class="input">
				<label for="input-last-name" class="input__label">Last Name</label>
				<input type="text" class="input__control" id="input-last-name" name="last-name">
			</div>

			<!-- Email Section -->
			<div class="input">
				<label for="input-email" class="input__label">Email</label>
				<input type="email" class="input__control" id="input-email" name="email">
			</div>

			<!-- Password Section -->
			<div class="input">
				<label for="input-password" class="input__label">Password</label>
				<input type="password" class="input__control" id="input-password" name="password">
			</div>
			<div class="input">
				<label for="input-confirm-password" class="input__label">Confirm Password</label>
				<input type="password" class="input__control" id="input-confirm-password" name="confirm-password">
			</div>

			<!-- Address Section -->
			<div class="input">
				<label for="input-address-1" class="input__label">Address Line 1</label>
				<input type="text" class="input__control" id="input-address-1" name="address-1">
			</div>
			<div class="input">
				<label for="input-address-2" class="input__label">Address Line 2</label>
				<input type="text" class="input__control" id="input-address-2" name="address-2">
			</div>

			<div class="input">
				<label for="input-town" class="input__label">Town</label>
				<input type="text" class="input__control" id="input-town" name="town">
			</div>

			<div class="input">
				<label for="input-county" class="input__label">County</label>
				<input type="text" class="input__control" id="input-county" name="county">
			</div>

			<div class="input">
				<label for="input-postcode" class="input__label">Postcode</label>
				<input type="text" class="input__control" id="input-postcode" name="postcode">
			</div>


			<div class="input">
				<button type="submit" class="button button--primary button--fluid">Register</button>
			</div>
		</form>
	</div>
	<div class="block__footer">
		<a href="/login.php">Already have an account? Login</a>
	</div>
</div>


<?php
require_once('includes/footer.php');
