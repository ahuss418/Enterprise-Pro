<?php
require_once('includes/setup.php');

$page = 'categories';
$title = 'Categories';

require_once('2fa_checker.php');

if ($loggedInUser && !$loggedInUser['admin']) {
    flash('error', 'You do not have permission to access <strong>' . htmlspecialchars($title) . "</strong> page!");
    redirect('/index.php');
}

$createCategoriesTableSQL = "
    CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `category_name` VARCHAR(255) NOT NULL,
        `icon` TEXT DEFAULT NULL
    )
";

if ($mysql->query($createCategoriesTableSQL) === false) {
    flash('error', 'Failed to create categories table: ' . $mysql->error);
    exit;
}

$action = $_GET['action'] ?? 'view';
$categoryId = $_GET['id'] ?? null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = $_POST['category_name'] ?? null;
    $icon = null;

    // Handle file upload (optional)
    if (!empty($_FILES['icon']['name'])) {
        $allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
        $fileType = $_FILES['icon']['type'];

        if (in_array($fileType, $allowedTypes)) {
            $fileName = uniqid() . '_' . basename($_FILES['icon']['name']);

            // Fix path separator and ensure target folder exists
            $targetDir = __DIR__ . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'icons';
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true); // Create the directory if it doesn't exist
            }

            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

            // Attempt to move the file
            if (move_uploaded_file($_FILES['icon']['tmp_name'], $targetPath)) {
                $icon = $fileName; // Save file name in the database
            } else {
                flash('error', 'Failed to upload icon. Check file permissions.');
            }
        } else {
            flash('error', 'Invalid file type. Only PNG, JPEG, and GIF files are allowed.');
        }
    }

    if ($categoryId) {
        // Update existing category (retain old icon if new one isn't provided)
        if ($icon) {
            $stmt = $mysql->prepare("UPDATE `categories` SET category_name = ?, icon = ? WHERE id = ?");
            $stmt->bind_param('ssi', $categoryName, $icon, $categoryId);
        } else {
            // Keep existing icon if no new one is uploaded
            $stmt = $mysql->prepare("UPDATE `categories` SET category_name = ? WHERE id = ?");
            $stmt->bind_param('si', $categoryName, $categoryId);
        }
        $stmt->execute();
        flash('success', 'Category updated successfully.');
    } else {
        // Insert new category (icon is optional)
        $stmt = $mysql->prepare("INSERT INTO `categories` (category_name, icon) VALUES (?, ?)");
        $stmt->bind_param('ss', $categoryName, $icon);
        $stmt->execute();
        flash('success', 'New category added successfully.');
    }

    echo "<script>
            setTimeout(function() {
                window.location.href = '/categories.php';
            }, 100);
          </script>";
    exit;
}
// Load category data for editing
$categoryData = null;
if ($action === 'edit' && $categoryId) {
    $stmt = $mysql->prepare("SELECT * FROM `categories` WHERE id = ?");
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $categoryData = $result->fetch_assoc();
    }
}

require_once('includes/header.php');
require_once('admin_sidepanel.php');
?><!-- If Action = View -->
<?php if ($action === 'view'): ?>
    <div class="block">
        <div class="block__body">
            <div class="block__title">
                <a href="?action=create" class="button button--primary button--small" style="float: right;">Create Category</a>
                Categories
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Icon</th>
                            <th>Datasets</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Use LEFT JOIN to get datasets matching category ID
                        $query = "
                            SELECT c.*, 
                                (
                                    SELECT GROUP_CONCAT(
                                        CONCAT(
                                            '<a href=\"/assets.php?dataset=', 
                                            ua.table_name, 
                                            '\">', 
                                            ua.name, 
                                            '</a>'
                                        ) SEPARATOR ', '
                                    )
                                    FROM `upload_asset_data` AS ua
                                    WHERE ua.category = c.id
                                ) AS datasets
                            FROM `categories` AS c
                        ";

                        $result = $mysql->query($query);

                        while ($row = $result->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td>
                                    <?php if ($row['icon']): ?>
                                        <img src="/static/images/icons/<?php echo htmlspecialchars($row['icon']); ?>"
                                            alt="<?php echo htmlspecialchars($row['category_name']); ?>"
                                            width="30" height="30">
                                    <?php else: ?>
                                        <span>No Icon</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['datasets'])): ?>
                                        <?php echo $row['datasets']; // Already properly encoded in query 
                                        ?>
                                    <?php else: ?>
                                        <span>None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?action=edit&id=<?php echo $row['id']; ?>" class="button button--small">Edit</a>
                                    <a href="?action=delete&id=<?php echo $row['id']; ?>"
                                        onclick="return confirm('Are you sure you want to delete this category?');"
                                        class="button button--danger button--small">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- If Action = Create OR Edit -->
<?php if ($action === 'create' || $action === 'edit'): ?>
    <div class="block">
        <div class="block__body">
            <div class="block__title">
                <?php echo $action === 'edit' ? 'Edit Category' : 'Create Category'; ?>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="input">
                    <label class="input__label">Category Name</label>
                    <input type="text"
                        name="category_name"
                        class="input__control"
                        value="<?php echo htmlspecialchars($categoryData['category_name'] ?? ''); ?>"
                        required>
                </div>

                <div class="input">
                    <label class="input__label">Icon (File Upload)</label>
                    <input type="file"
                        name="icon"
                        class="input__control">

                    <?php if ($action === 'edit' && $categoryData['icon']): ?>
                        <div>
                            <img src="/static/images/icons/<?php echo htmlspecialchars($categoryData['icon']); ?>"
                                alt="Current Icon"
                                width="30" height="30">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="input">
                    <button type="submit" class="button button--primary">
                        <?php echo $action === 'edit' ? 'Update Category' : 'Create Category'; ?>
                    </button>
                </div>

                <div class="input">
                    <a href="/categories.php" class="button button--error">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- If Action = Delete -->
<?php
if ($action === 'delete' && $categoryId) {
    $stmt = $mysql->prepare("DELETE FROM `categories` WHERE id = ?");
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        flash('success', "Category deleted successfully.");
    } else {
        flash('error', "Failed to delete category.");
    }

    echo "<script>
            setTimeout(function() {
                window.location.href = '/categories.php';
            }, 100);
          </script>";
    exit;
}
?>

<?php

// Include the footer file
require_once('includes/footer.php');
