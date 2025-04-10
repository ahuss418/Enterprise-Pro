<?php
require_once('includes/setup.php');

$page = 'assets';
$title = 'Assets';

require_once('2fa_checker.php');

require_once('includes/header.php');

$query = "
    SELECT uad.id,
           uad.name AS asset,
           uad.table_name,
           uad.category,
           IFNULL(CONCAT(usr.first_name, ' ', usr.last_name), 'Unknown') AS uploaded_by,
           uad.uploaded_at,
           uad.is_hidden
    FROM upload_asset_data uad
    LEFT JOIN users usr ON uad.uploaded_by = usr.id
";

$result = $mysql->query($query);

if ($result === false) {
    flash('error', 'Failed to load data.');
    redirectToReferer();
}


// Fetch categories
$categoriesQuery = "SELECT * FROM `categories`";
$categoriesResult = $mysql->query($categoriesQuery);
$categories = [];
while ($cat = $categoriesResult->fetch_assoc()) {
    $categories[] = $cat;
}

// Handle changing dropdown without refreshing

?>

<div class="block">
    <div class="block__body">
        <div class="block__title">
            <!-- Anchor tag creating a button to add new assets with styling-->
            <a href="/upload.php" class="button button--primary button--small" style="float: right;">Upload</a>
            Assets
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Asset</th>
                        <th>Category</th>
                        <th>Uploaded By</th>
                        <th>Uploaded At</th>
                        <th>Action</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['asset']); ?></td>
                            <td>
                                <!-- Dropdown for category -->
                                <?php if ($loggedInUser && $loggedInUser['admin']) { ?>
                                    <select class="category-dropdown"
                                        data-asset-id="<?php echo $row['id']; ?>">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category) { ?>
                                            <option value="<?php echo $category['id']; ?>"
                                                <?php echo ($row['category'] == $category['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                <?php } else { ?>
                                    <?php
                                    $categoryId = $row['category'] ?? null;
                                    $categoryName = 'N/A';

                                    if ($categoryId) {
                                        foreach ($categories as $category) {
                                            if ($category['id'] == $categoryId) {
                                                $categoryName = htmlspecialchars($category['category_name']);
                                                break; // Stop looping once the match is found
                                            }
                                        }
                                    }

                                    echo $categoryName;
                                    ?>
                                <?php } ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['uploaded_by']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['uploaded_at']); ?></td>
                            <td>
                                <a href="assets.php?dataset=<?php echo htmlspecialchars($row['table_name']); ?>" class="button button--small">View</a>
                                <a href="assets.php?delete_dataset=<?php echo htmlspecialchars($row['id']); ?>" class="button button--danger button--small" onclick="return confirm('Are you sure you want to delete this asset?');">Delete</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
if (isset($_GET['dataset'])) {
    $tableName = $mysql->real_escape_string($_GET['dataset']);

    $getAssetNameQuery = "SELECT name FROM `upload_asset_data` WHERE table_name = '$tableName'";
    $assetResult = $mysql->query($getAssetNameQuery);
    $assetName = $assetResult->num_rows > 0 ? $assetResult->fetch_assoc()['name'] : $tableName;


    $checkTableSQL = "SHOW TABLES LIKE '$tableName'";
    $checkResult = $mysql->query($checkTableSQL);
    if ($checkResult->num_rows > 0) {
        $dataQuery = "SELECT * FROM `$tableName`";
        $dataResult = $mysql->query($dataQuery);

        if ($dataResult) {
            $data = [];
            while ($row = $dataResult->fetch_assoc()) {
                $data[] = $row;
            }

            if (count($data) > 0) {
                // Get column names
                $columns = array_keys($data[0]);

                // Step 2: Remove empty columns
                $emptyColumns = [];
                foreach ($columns as $column) {
                    $allEmpty = true;
                    foreach ($data as $row) {
                        if (!empty($row[$column]) && $row[$column] !== null) {
                            $allEmpty = false;
                            break;
                        }
                    }
                    if ($allEmpty) {
                        // Remove empty columns
                        foreach ($data as &$row) {
                            unset($row[$column]);
                        }
                        $emptyColumns[] = $column;
                    }
                }

                // Step 3: Display warning if columns were removed
                if (!empty($emptyColumns)) {
                    echo '<div class="block block--warning">
                            <div class="block__body">
                                Removed empty columns: <strong>' . implode(', ', array_map('htmlspecialchars', $emptyColumns)) . '</strong> from table <strong>' . htmlspecialchars($tableName) . '</strong>
                            </div>
                          </div>';
                }

                // Get the filtered column names after removing empty columns
                $filteredColumns = array_keys($data[0]);

                // Step 4: Display the table
                echo "<div class='block'>
                        <div class='block__body'>
                            <a href='/manage-asset.php?dataset=" . htmlspecialchars($tableName) . "' class='button button--primary button--small' style='float: right;'>Create Asset</a>
                            <div class='block__title'>Dataset: <strong>" . htmlspecialchars($assetName) . "</strong></div>
                            <div class='table-container'>
                                <table class='table'>
                                    <thead>
                                        <tr>";

                // Display filtered column headers
                foreach ($filteredColumns as $column) {
                    if ($column === 'asset_id' || $column === 'id') {
                        continue;
                    }
                    echo "<th>" . htmlspecialchars($column) . "</th>";
                }

                echo "          </tr>
                                    </thead>
                                    <tbody>";

                // Display table data dynamically
                foreach ($data as $row) {
                    echo "<tr>";
                    echo "<tr onclick=\"window.location.href='/manage-asset.php?dataset=" . htmlspecialchars($tableName) . "&id=" . htmlspecialchars($row['asset_id']) . "'\" style=\"cursor: pointer;\">";
                    foreach ($filteredColumns as $column) {
                        if ($column === 'asset_id' || $column === 'id') {
                            continue;
                        }
                        echo "<td>" . htmlspecialchars($row[$column]) . "</td>";
                    }
                    echo "</tr>";
                }

                echo "          </tbody>
                                </table>
                            </div>
                        </div>
                    </div>";
            } else {
                echo "<div class='block block--warning'><div class='block__body'>No data available in table: <strong>$tableName</strong></div></div>";
            }
        } else {
            echo '<div class="block block--danger"><div class="block__body">Error loading data: ' . $mysql->error . '</div></div>';
        }
    } else {
        echo '<div class="block block--danger">
                <div class="block__body">
                    Table <strong>' . htmlspecialchars($tableName) . '</strong> not found
                </div>
              </div>';
    }
} else if (isset($_GET['delete_dataset'])) {
    if ($loggedInUser && !$loggedInUser['admin']) {
        flash('error', 'You do not have permission to delete datasets');
        echo "<script>
        setTimeout(function() {
            window.location.href = '/assets.php';
        }, 100);
      </script>";
        exit();
    }
    $datasetId = $mysql->real_escape_string($_GET['delete_dataset']);
    $checkSQL = "SELECT * FROM `upload_asset_data` WHERE id = '$datasetId'";
    $checkResult = $mysql->query($checkSQL);
    if ($checkResult->num_rows > 0) {
        $uploadAsset = $checkResult->fetch_assoc();
        $tableName = $uploadAsset['table_name'];
        $displayName = $uploadAsset['name'];
        // Delete entries from asset_data
        $deleteAssetDataSQL = "DELETE FROM `asset_data` WHERE table_id = '$datasetId'";
        if ($mysql->query($deleteAssetDataSQL) === true) {
            flash('success', "Related entries in `asset_data` have been removed.");
        } else {
            flash('error', "Failed to remove related entries in `asset_data`: " . $mysql->error);
        }

        // Delete the main table
        $deleteSQL = "DELETE FROM `upload_asset_data` WHERE id = '$datasetId'";
        if ($mysql->query($deleteSQL) === true) {

            // Step 4: Drop the actual table in the database
            $dropTableSQL = "DROP TABLE IF EXISTS `$tableName`";
            if ($mysql->query($dropTableSQL) === true) {
                flash('success', "Dataset '$displayName' has been removed successfully.");
            } else {
                flash('error', "Failed to delete table '$tableName': " . $mysql->error);
            }
        } else {
            flash('error', "Failed to remove dataset: " . $mysql->error);
        }
        echo "<script>
        setTimeout(function() {
            window.location.href = '/assets.php';
        }, 100);
      </script>";
    } else {
        echo '<div class="block block--danger"><div class="block__body">No dataset found with ID: <strong>' . $datasetId . '</strong></div></div>';
    }
}
?>
<?php

// Include the footer file
require_once('includes/footer.php');
