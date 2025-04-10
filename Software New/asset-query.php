<?php
require_once('includes/setup.php');

// Get parameters from the URL
$dataset = $_GET['dataset'] ?? null;
$assetId = $_GET['asset_id'] ?? null;

if (!$dataset || !$assetId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing dataset or asset_id']);
    exit;
}

// Escape values to prevent SQL Injection
$dataset = $mysql->real_escape_string($dataset);
$assetId = (int) $assetId;

// Step 1: Verify the table exists
$tableCheckQuery = "SELECT COUNT(*) AS count 
                    FROM information_schema.tables 
                    WHERE table_name = '$dataset'";

$tableResult = $mysql->query($tableCheckQuery);
if (!$tableResult || $tableResult->fetch_assoc()['count'] == 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => "Dataset '$dataset' not found"]);
    exit;
}

// Step 2: Get column names dynamically
$columns = [];
$columnQuery = "SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE table_name = '$dataset'";

$columnResult = $mysql->query($columnQuery);
if ($columnResult) {
    while ($row = $columnResult->fetch_assoc()) {
        $columns[] = $row['COLUMN_NAME'];
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch table columns']);
    exit;
}

if (empty($columns)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'No columns found in table']);
    exit;
}

// Step 3: Build the dynamic query to get the asset
$selectColumns = '`' . implode('`, `', $columns) . '`';

$query = "SELECT $selectColumns 
          FROM `$dataset` 
          WHERE `asset_id` = ?";

$stmt = $mysql->prepare($query);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare query: ' . $mysql->error]);
    exit;
}

$stmt->bind_param('i', $assetId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => "No asset found with ID $assetId"]);
    exit;
}

$data = $result->fetch_assoc();

$filteredData = [];

foreach ($data as $key => $value) {
    if (!in_array($key, ['id', 'asset_id', 'lat', 'long', 'longitude', 'latitude']) && $value !== null && $value !== '') {
        $formattedKey = ucwords(str_replace('_', ' ', $key));
        $filteredData[$formattedKey] = $value;
    }
}

// Step 4: Return data as JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $filteredData
], JSON_PRETTY_PRINT);

$stmt->close();
