<?php
require_once('includes/setup.php');

$page = 'report-asset';
$title = 'Report Issue';

require_once('2fa_checker.php');

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

$dataset = $_GET['dataset'] ?? null;
$assetId = $_GET['id'] ?? null;

$reportedBy = $loggedInUser['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dataset = $_POST['dataset'] ?? null;
    $assetId = $_POST['asset_id'] ?? null;
    $message = $_POST['message'] ?? null;

    if (!$dataset || !$assetId || !$message) {
        flash('error', 'All fields are required.');
    } else {
        // Insert into `reports` table
        $stmt = $mysql->prepare("
            INSERT INTO `reports` (dataset, asset_id, message, reported_by) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param('sisi', $dataset, $assetId, $message, $reportedBy);

        if ($stmt->execute()) {
            flash('success', 'Report submitted successfully.');
            echo "<script>
                setTimeout(function() {
                    window.location.href = '/index.php';
                }, 100);
            </script>";
            exit;
        } else {
            flash('error', 'Failed to submit report: ' . $mysql->error);
        }
    }
}

require_once('includes/header.php');


?>

<div class="block">
    <div class="block__body">
        <div class="block__title">Report Issue</div>
        <form action="" method="POST">

            <!-- Dataset -->
            <div class="input">
                <label class="input__label">Dataset</label>
                <input type="text"
                    name="dataset"
                    class="input__control"
                    value="<?php echo htmlspecialchars($dataset); ?>"
                    readonly>
            </div>

            <!-- Asset ID -->
            <div class="input">
                <label class="input__label">Asset ID</label>
                <input type="text"
                    name="asset_id"
                    class="input__control"
                    value="<?php echo htmlspecialchars($assetId); ?>"
                    readonly>
            </div>

            <!-- Message -->
            <div class="input">
                <label class="input__label">Message</label>
                <textarea name="message" class="input__control" rows="6" placeholder="Explain the issue..." required></textarea>
            </div>

            <!-- Submit Button -->
            <div class="input">
                <button type="submit" class="button button--primary">Submit Report</button>
                <a href="/index.php" class="button button--error">Cancel</a>
            </div>
        </form>
    </div>
</div>


<?php

// Include the footer file
require_once('includes/footer.php');
