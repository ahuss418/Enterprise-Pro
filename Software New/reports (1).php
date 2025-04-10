<?php
require_once('includes/setup.php');
$page = 'reports';
$title = 'Reports';

require_once('2fa_checker.php');

if ($loggedInUser && !$loggedInUser['admin']) {
    flash('error', 'You do not have permission to access <strong>' . htmlspecialchars($title) . "</strong> page!");
    redirect('/index.php');
}

$createReportsTableSQL = "
    CREATE TABLE IF NOT EXISTS `reports` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `dataset` VARCHAR(255) NOT NULL,
        `asset_id` INT NOT NULL,
        `message` TEXT NOT NULL,
        `reported_by` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`reported_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    )
";

if ($mysql->query($createReportsTableSQL) === false) {
    flash('error', 'Failed to create reports table: ' . $mysql->error);
    exit;
}

$query = "
    SELECT 
        r.id,
        r.dataset,
        r.asset_id,
        r.message,
        r.created_at,
        uad.name AS dataset_name,
        uad.table_name AS dataset_table,
        u.first_name AS reported_first_name,
        u.last_name AS reported_last_name
    FROM 
        reports AS r
    LEFT JOIN 
        upload_asset_data AS uad ON r.dataset = uad.table_name
    LEFT JOIN 
        users AS u ON r.reported_by = u.id
    ORDER BY 
        r.created_at DESC
";
$result = $mysql->query($query);

require_once('includes/header.php');
require_once('admin_sidepanel.php');

?>

<div class="block">
    <div class="block__body">
        <div class="block__title">
            Reports
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Dataset</th>
                        <th>Asset ID</th>
                        <th>Reported By</th>
                        <th>Message</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <!-- Report ID -->
                            <td><?php echo htmlspecialchars($row['id']); ?></td>

                            <!-- Dataset -->
                            <td>
                                <a href="/assets.php?dataset=<?php echo htmlspecialchars($row['dataset_table']); ?>">
                                    <?php echo htmlspecialchars($row['dataset_name'] ?? $row['dataset']); ?>
                                </a>
                            </td>

                            <!-- Asset ID -->
                            <td><?php echo htmlspecialchars($row['asset_id']); ?></td>

                            <!-- Reported By -->
                            <td>
                                <?php
                                if ($row['reported_first_name']) {
                                    echo htmlspecialchars($row['reported_first_name'] . ' ' . $row['reported_last_name']);
                                } else {
                                    echo 'Guest';
                                }
                                ?>
                            </td>

                            <!-- Message -->
                            <td><?php echo htmlspecialchars($row['message']); ?></td>

                            <!-- Created At -->
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>

                            <!-- Action -->
                            <td>
                                <a href="?delete_report=<?php echo $row['id']; ?>"
                                    onclick="return confirm('Are you sure you want to delete this report?');"
                                    class="button button--danger button--small">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Step 3: Delete Report
if (isset($_GET['delete_report'])) {
    $reportId = $mysql->real_escape_string($_GET['delete_report']);

    $deleteSQL = "DELETE FROM `reports` WHERE `id` = '$reportId'";
    if ($mysql->query($deleteSQL) === true) {
        flash('success', 'Report deleted successfully.');
        echo "<script>
                setTimeout(function() {
                    window.location.href = '/reports.php';
                }, 100);
              </script>";
        exit;
    } else {
        flash('error', 'Failed to delete report: ' . $mysql->error);
    }
}
?>

<?php

// Include the footer file
require_once('includes/footer.php');
