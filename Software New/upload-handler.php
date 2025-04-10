<?php
require_once('includes/setup.php');

require_once('2fa_checker.php');

header('Content-Type: application/json');

// Ensure tables exist
$createUploadAssetDataTableSQL = "
    CREATE TABLE IF NOT EXISTS `upload_asset_data` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) UNIQUE NOT NULL,
        `table_name` VARCHAR(255) NOT NULL,
        `uploaded_by` VARCHAR(255) DEFAULT NULL,
        `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `category` VARCHAR(255) DEFAULT NULL,
        `is_hidden` BOOLEAN DEFAULT 0
    )
";
$mysql->query($createUploadAssetDataTableSQL);

$createAssetDataTableSQL = "
    CREATE TABLE IF NOT EXISTS `asset_data` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `longitude` DOUBLE DEFAULT NULL,
        `latitude` DOUBLE DEFAULT NULL,
        `table_id` INT,
        FOREIGN KEY (`table_id`) REFERENCES `upload_asset_data`(`id`) ON DELETE CASCADE
    )
";
$mysql->query($createAssetDataTableSQL);

// Validate uploaded file
if (!isset($_FILES['csv_file'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$fileTmpPath = $_FILES['csv_file']['tmp_name'];
$fileName = $_FILES['csv_file']['name'];
$fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

// Validate CSV file type
if ($fileExtension !== 'csv') {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only CSV files are allowed.']);
    exit;
}

$assetName = pathinfo($fileName, PATHINFO_FILENAME);

// Check for duplicate file name
$checkSQL = "SELECT COUNT(*) FROM `upload_asset_data` WHERE `name` = ?";
$stmt = $mysql->prepare($checkSQL);
$stmt->bind_param('s', $assetName);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    echo json_encode(['success' => false, 'error' => "A table based on the file '$assetName' already exists."]);
    exit;
}

// Read CSV file
$file = fopen($fileTmpPath, 'r');
if ($file === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to open uploaded file.']);
    exit;
}

$headers = fgetcsv($file);
if ($headers === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to read headers from CSV file.']);
    exit;
}

// Clean headers and convert to lowercase
$cleanHeaders = array_map(function ($header) {
    return preg_replace('/[^a-zA-Z0-9]/', '', strtolower(trim($header)));
}, $headers);

// MAPPING OF ALTERNATE HEADERS
$headerMap = [
    'lat' => 'latitude',
    'long' => 'longitude'
];

$mappedHeaders = [];
foreach ($cleanHeaders as $header) {
    $mappedHeaders[] = $headerMap[$header] ?? $header;
}

// REQUIRED HEADERS VALIDATION
$requiredHeaders = ['longitude', 'latitude'];
$hasLongitudeLatitude = count(array_intersect($requiredHeaders, $mappedHeaders)) === 2;

if (!$hasLongitudeLatitude) {
    echo json_encode(['success' => false, 'error' => 'CSV file must contain longitude and latitude.']);
    exit;
}

// Create table for data
$tableName = 'data_' . uniqid();

$uniqueHeaders = [];
$createTableSQL = "CREATE TABLE `$tableName` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `asset_id` INT,
    FOREIGN KEY (`asset_id`) REFERENCES `asset_data`(`id`) ON DELETE CASCADE
";

foreach ($cleanHeaders as $column) {
    $originalColumn = $column;
    $suffix = 1;

    // Ensure unique column names
    while (in_array($column, $uniqueHeaders)) {
        $column = $originalColumn . '_' . $suffix;
        $suffix++;
    }

    $uniqueHeaders[] = $column;
    $createTableSQL .= ", `$column` TEXT";
}
$createTableSQL .= ")";

if ($mysql->query($createTableSQL) === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to create table: ' . $mysql->error]);
    exit;
}

// Insert into `upload_asset_data`
$uploadedBy = $loggedInUser ? $loggedInUser['id'] : null;
$insertAssetSQL = "INSERT INTO `upload_asset_data` (`name`, `table_name`, `uploaded_by`) VALUES (?, ?, ?)";
$stmtUpload = $mysql->prepare($insertAssetSQL);
$stmtUpload->bind_param('sss', $assetName, $tableName, $uploadedBy);
$stmtUpload->execute();
$tableId = $mysql->insert_id;
$stmtUpload->close();

$skippedRows = 0;

// Prepare statement for dynamic table insert
$insertSQL = "INSERT INTO `$tableName` (`" . implode('`,`', $uniqueHeaders) . "`, `asset_id`) VALUES (" . implode(',', array_fill(0, count($uniqueHeaders), '?')) . ", ?)";
$stmtDynamic = $mysql->prepare($insertSQL);

// Prepare statement for asset_data insert
$insertAssetDataSQL = "INSERT INTO `asset_data` (`longitude`, `latitude`, `table_id`) VALUES (?, ?, ?)";
$stmtAsset = $mysql->prepare($insertAssetDataSQL);

if ($stmtDynamic === false || $stmtAsset === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to prepare statement for data insertion.']);
    exit;
}

// Process rows
while (($row = fgetcsv($file)) !== false) {
    $longitude = $row[array_search('longitude', $mappedHeaders)] ?? null;
    $latitude = $row[array_search('latitude', $mappedHeaders)] ?? null;

    if (!$longitude || !$latitude) {
        $skippedRows++;
        continue;
    }

    // Insert into `asset_data`
    $stmtAsset->bind_param('ddi', $longitude, $latitude, $tableId);
    $stmtAsset->execute();
    $assetId = $mysql->insert_id;

    // Insert into dynamic table
    $rowData = array_merge($row, [$assetId]);
    $bindTypes = str_repeat('s', count($row)) . 'i';
    $stmtDynamic->bind_param($bindTypes, ...$rowData);
    $stmtDynamic->execute();
}

// Close statements and file
fclose($file);
$stmtDynamic->close();
$stmtAsset->close();

echo json_encode([
    'success' => true,
    'message' => "CSV file imported successfully! Skipped $skippedRows rows."
]);
