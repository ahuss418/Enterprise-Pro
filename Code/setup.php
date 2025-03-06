<?php
session_start();

require_once('database.php');
require_once('functions.php');

// Define an array with labels for sections or categories
$categories = [
	'home' => ['label' => 'Home', 'url' => '/'],
	'reports' => ['label' => 'Reports', 'url' => '/reports.php', "permission" => ['admin']],
	'assets' => ['label' => 'Assets', 'url' => '/assets.php', "permission" => ['admin']],
	'categories' => ['label' => 'Categories', 'url' => '/categories.php', "permission" => ['admin']],
	'settings' => ['label' => 'Settings', 'url' => '/settings.php', "permission" => ['admin']],
];

$loggedInUser = null;

// Secret key for the Google Maps API
$API_KEY = "AIzaSyBu-BbxHf0trEzWvtHNKM8iijj9e1yZWXw";

if (array_key_exists('user', $_SESSION)) {  // if user exists in session, store them as logged in user variable
	$userQuery = $mysql->query("
		SELECT *
		FROM users
		WHERE id = {$_SESSION['user']}
		LIMIT 1
	");

	$loggedInUser = $userQuery->fetch_assoc();
}
