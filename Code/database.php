<?php
//db connection 
$mysql = new mysqli("localhost", "root", "", "enterprise_pro");

if ($mysql->connect_error) {
	die("[ERROR] Connection failed: " . mysqli_connect_error());
}
