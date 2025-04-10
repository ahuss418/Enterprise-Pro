<?php
require_once('includes/setup.php');

// Logged in users should not be able to access register page
if (!$loggedInUser) {
    redirect('index.php');
}

$page = 'upload';
$title = 'Upload';

require_once('2fa_checker.php');

// if (!$loggedInUser['email_confirmed']) {
//     flash('error', 'Please verify your email to gain full access to the site.');
//     redirect('index.php');
// }

// Step 1: Create the `asset_data` table if it doesn't exist (before validation)
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

if ($mysql->query($createUploadAssetDataTableSQL) === false) {
    flash('error', 'Failed to create upload_asset_data table: ' . $mysql->error);
    redirectToReferer();
}

// Step 2: Create the `asset_data` table if it doesn't exist
$createAssetDataTableSQL = "
    CREATE TABLE IF NOT EXISTS `asset_data` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `longitude` DOUBLE DEFAULT NULL,
        `latitude` DOUBLE DEFAULT NULL,
        `table_id` INT,
        FOREIGN KEY (`table_id`) REFERENCES `upload_asset_data`(`id`) ON DELETE CASCADE
    )
";

if ($mysql->query($createAssetDataTableSQL) === false) {
    flash('error', 'Failed to create asset_data table: ' . $mysql->error);
    redirectToReferer();
}

if (isset($_POST['upload'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['csv_file']['tmp_name'];
        $fileName = $_FILES['csv_file']['name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        // Only allow CSV files
        if ($fileExtension !== 'csv') {
            flash('error', 'Invalid file type. Only CSV files are allowed.');
            redirectToReferer();
        }

        $assetName = pathinfo($fileName, PATHINFO_FILENAME);

        // Check if the file name already exists in `upload_asset_data`
        $checkSQL = "SELECT COUNT(*) FROM `upload_asset_data` WHERE `name` = ?";
        $stmt = $mysql->prepare($checkSQL);
        $stmt->bind_param('s', $assetName);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            flash('error', "A table based on the file '$assetName' already exists.");
            redirectToReferer();
        }

        // Read CSV file
        $file = fopen($fileTmpPath, 'r');
        if ($file === false) {
            flash('error', 'Failed to open uploaded file.');
            redirectToReferer();
        }

        $headers = fgetcsv($file);
        if ($headers === false) {
            flash('error', 'Failed to read headers from CSV file.');
            redirectToReferer();
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

        // VALIDATION LOGIC:
        if (!$hasLongitudeLatitude) {
            flash('error', 'CSV file must contain longitude and latitude.');
            redirectToReferer();
        }

        $tableName = 'data_' . uniqid();

        // Create table dynamically
        $uniqueHeaders = [];
        $createTableSQL = "CREATE TABLE `$tableName` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `asset_id` INT,
            FOREIGN KEY (`asset_id`) REFERENCES `asset_data`(`id`) ON DELETE CASCADE
        ";

        foreach ($cleanHeaders as $column) {
            $originalColumn = $column;
            $suffix = 1;
            while (in_array($column, $uniqueHeaders)) {
                $column = $originalColumn . '_' . $suffix;
                $suffix++;
            }

            $uniqueHeaders[] = $column;
            $createTableSQL .= ", `$column` TEXT";
        }
        $createTableSQL .= ")";

        if ($mysql->query($createTableSQL) === false) {
            flash('error', 'Failed to create table: ' . $mysql->error);
            redirectToReferer();
        }

        // Insert into `upload_asset_data` (use a separate statement)
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

        if ($stmtDynamic === false) {
            flash('error', 'Failed to prepare insert statement for dynamic table: ' . $mysql->error);
            redirectToReferer();
        }

        // Prepare statement for asset_data insert
        $insertAssetDataSQL = "INSERT INTO `asset_data` (`longitude`, `latitude`, `table_id`) VALUES (?, ?, ?)";
        $stmtAsset = $mysql->prepare($insertAssetDataSQL);

        if ($stmtAsset === false) {
            flash('error', 'Failed to prepare insert statement for asset_data: ' . $mysql->error);
            redirectToReferer();
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
            $insertAssetDataSQL = "INSERT INTO `asset_data` (`longitude`, `latitude`, `table_id`) VALUES (?, ?, ?)";
            $stmtAsset = $mysql->prepare($insertAssetDataSQL);
            $stmtAsset->bind_param('ddi', $longitude, $latitude, $tableId);
            $stmtAsset->execute();
            $assetId = $mysql->insert_id;
            $stmtAsset->close();

            // Insert into dynamic table
            $rowData = array_merge($row, [$assetId]);

            // Create the bind types string (s for strings, i for integer)
            $bindTypes = str_repeat('s', count($row)) . 'i';

            // Prepare bind_param using call_user_func_array()
            $stmtDynamic->bind_param($bindTypes, ...$rowData);
            $stmtDynamic->execute();
        }

        fclose($file);
        $stmtDynamic->close();

        flash('success', "CSV file imported successfully!");
        redirectToReferer();
    }
}

require_once('includes/header.php');
?>

<div id="upload-page">
    <!-- Single Upload Form -->
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <label>Select CSV File:</label>
        <input type="file" name="csv_file" accept=".csv" required>
        <button type="submit" name="upload" class="button button--primary">Upload</button>
    </form>

    <hr>

    <!-- Bulk Upload Section -->
    <div class="block">
        <div class="block__body">
            <div class="block__title">Bulk Upload</div>

            <!-- Drag and Drop Area -->
            <div id="bulk-upload-drop-area">
                Drag & Drop CSV files here or <span class="file-upload-text">click to select</span>
                <input type="file" id="bulk-upload-input" accept=".csv" multiple hidden>
            </div>

            <!-- Table to show queued files -->
            <div class="table-container">
                <table id="bulk-upload-table" class="table">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows will be added dynamically using JS -->
                    </tbody>
                </table>
            </div>

            <!-- Upload All Button -->
            <button id="bulk-upload-all-button" class="button button--primary" disabled>Upload All</button>
        </div>
    </div>
</div>
<?php

// Include the footer file
require_once('includes/footer.php');
