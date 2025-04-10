<?php
require_once('includes/setup.php');
require_once('2fa_checker.php');

if (isset($_GET['id'])) {
    $page = 'Edit Asset';
    $title = 'Edit Asset';
} else {
    $page = 'Create Asset';
    $title = 'Create Asset';
}

require_once('includes/header.php');

if (isset($_GET['dataset'])) {
    $tableName = $mysql->real_escape_string($_GET['dataset']);
    $assetId = isset($_GET['id']) ? (int) $_GET['id'] : null;
    $existingData = [];

    // Step 1: Check if table exists in upload_asset_data
    $checkSQL = "SELECT id, name FROM `upload_asset_data` WHERE table_name = '$tableName'";
    $checkResult = $mysql->query($checkSQL);

    if ($checkResult->num_rows > 0) {
        $dataset = $checkResult->fetch_assoc();
        $datasetName = $dataset['name'];
        $datasetId = $dataset['id'];

        // Step 2: Get table headers dynamically
        $getColumnsSQL = "SHOW COLUMNS FROM `$tableName`";
        $columnsResult = $mysql->query($getColumnsSQL);

        if ($columnsResult->num_rows > 0) {
            $columns = [];
            while ($row = $columnsResult->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
        } else {
            flash('error', "No columns found for table '$tableName'.");
            header('Location: /assets.php');
            exit;
        }

        // Step 3: If `id` is present, fetch existing row
        if ($assetId) {
            $getAssetSQL = "SELECT * FROM `$tableName` WHERE asset_id = ?";
            $stmt = $mysql->prepare($getAssetSQL);
            $stmt->bind_param('i', $assetId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $existingData = $result->fetch_assoc(); // Store existing data
            } else {
                flash('error', "Asset with ID '$assetId' not found.");
                echo "<script>
                setTimeout(function() {
                    window.location.href = '/assets.php?dataset=$tableName';
                }, 100);
              </script>";
                exit;
            }
            $stmt->close();
        }

        // Step 4: Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [];
            $errors = [];

            foreach ($columns as $column) {
                $value = $_POST[$column] ?? null;

                // Validate Lat/Long
                if (in_array(strtolower($column), ['lat', 'latitude'])) {
                    if ($value === null || !is_numeric($value) || $value < -90 || $value > 90) {
                        $errors[] = "$column must be a valid latitude (-90 to 90).";
                    }
                }
                if (in_array(strtolower($column), ['long', 'longitude'])) {
                    if ($value === null || !is_numeric($value) || $value < -180 || $value > 180) {
                        $errors[] = "$column must be a valid longitude (-180 to 180).";
                    }
                }

                $data[$column] = $value !== '' ? $value : null; // Allow empty fields to be NULL
            }

            if (empty($errors)) {
                if ($assetId) {
                    // UPDATE existing asset
                    $updateSQL = "UPDATE `$tableName` SET ";
                    foreach ($data as $column => $value) {
                        $updateSQL .= "`$column` = '" . $mysql->real_escape_string($value) . "', ";
                    }
                    $updateSQL = rtrim($updateSQL, ', ') . " WHERE id = $assetId";

                    if ($mysql->query($updateSQL) === true) {
                        flash('success', "Asset updated successfully.");
                        echo "<script>
                                setTimeout(function() {
                                    window.location.href = '/assets.php?dataset=$tableName';
                                }, 100);
                              </script>";
                        exit;
                    } else {
                        flash('error', "Failed to update asset: " . $mysql->error);
                    }
                } else {
                    // INSERT new asset into `asset_data` FIRST to get `asset_id`
                    $insertAssetDataSQL = "INSERT INTO `asset_data` (`longitude`, `latitude`, `table_id`) VALUES (?, ?, ?)";
                    $stmt = $mysql->prepare($insertAssetDataSQL);

                    $longitude = $data['longitude'] ?? null;
                    $latitude = $data['latitude'] ?? null;

                    $stmt->bind_param('ddi', $longitude, $latitude, $datasetId);

                    if ($stmt->execute()) {
                        $assetId = $stmt->insert_id; // Get the new asset ID
                        $stmt->close();

                        // Add asset_id to data array
                        $data['asset_id'] = $assetId;

                        // Build the INSERT query dynamically
                        $insertSQL = "INSERT INTO `$tableName` (`" . implode('`, `', array_keys($data)) . "`) 
                                      VALUES ('" . implode("', '", array_map([$mysql, 'real_escape_string'], $data)) . "')";

                        if ($mysql->query($insertSQL) === true) {
                            flash('success', "New asset added successfully.");
                            echo "<script>
                                    setTimeout(function() {
                                        window.location.href = '/assets.php?dataset=$tableName';
                                    }, 100);
                                  </script>";
                            exit;
                        } else {
                            flash('error', "Failed to insert data into `$tableName`: " . $mysql->error);
                        }
                    } else {
                        flash('error', "Failed to insert into asset_data: " . $mysql->error);
                    }
                }
            } else {
                foreach ($errors as $error) {
                    flash('error', $error);
                }
            }
        }
?>

        <div class="block block--auth">
            <div class="block__header">
                <?php if ($assetId): ?>
                    Edit Asset in <strong><?php echo htmlspecialchars($datasetName); ?></strong>
                <?php else: ?>
                    Create New Asset in <strong><?php echo htmlspecialchars($datasetName); ?></strong>
                <?php endif; ?>
            </div>
            <div class="block__body">
                <form method="POST" action="">
                    <?php foreach ($columns as $column) {
                        // Skip `id` and `asset_id` for new asset creation
                        if (!$assetId && in_array($column, ['id', 'asset_id'])) {
                            continue;
                        }
                    ?>
                        <div class="input">
                            <label for="<?php echo htmlspecialchars($column); ?>" class="input__label">
                                <?php echo ucwords(str_replace('_', ' ', htmlspecialchars($column))); ?>
                            </label>
                            <input type="text" id="<?php echo htmlspecialchars($column); ?>"
                                name="<?php echo htmlspecialchars($column); ?>"
                                class="input__control"
                                value="<?php echo htmlspecialchars($existingData[$column] ?? $_POST[$column] ?? ''); ?>"
                                placeholder="Enter <?php echo htmlspecialchars($column); ?>"
                                <?php
                                // Disable `id` and `asset_id` when editing
                                if ($assetId && in_array($column, ['id', 'asset_id'])) echo 'disabled';

                                // Add required validation for lat/long
                                if (in_array(strtolower($column), ['lat', 'latitude', 'long', 'longitude'])) echo 'required';
                                ?>>
                        </div>
                    <?php } ?>

                    <div class="input">
                        <button type="submit" class="button button--primary button--fluid">
                            <?php echo $assetId ? 'Update Asset' : 'Create Asset'; ?>
                        </button>
                    </div>

                    <div class="input">
                        <a href="/assets.php" class="button button--secondary button--fluid">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

<?php
    } else {
        flash('error', "Dataset '$tableName' not found.");
        header('Location: /assets.php');
        exit;
    }
}

require_once('includes/footer.php');
