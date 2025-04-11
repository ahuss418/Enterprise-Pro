<?php
require_once('includes/setup.php');

$page = 'users';
$title = 'Users';

require_once('2fa_checker.php');

if ($loggedInUser && !$loggedInUser['admin']) {
    flash('error', 'You do not have permission to access <strong>' . htmlspecialchars($title) . "</strong> page!");
    redirect('/index.php');
}

$query = "
    SELECT 
        u.*,
        CONCAT(u.first_name, ' ', u.last_name) AS full_name
    FROM 
        users AS u
    ORDER BY 
        u.created_at DESC
";

$result = $mysql->query($query);

require_once('includes/header.php');
require_once('admin_sidepanel.php');
?>

<div class="block">
    <div class="block__body">
        <div class="block__title">
            Users
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Admin</th>
                        <th>Verified</th>
                        <th>Email Confirmed</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <!-- User ID -->
                            <td><?php echo htmlspecialchars($row['id']); ?></td>

                            <!-- Full Name -->
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>

                            <!-- Email -->
                            <td><?php echo htmlspecialchars($row['email']); ?></td>

                            <!-- Address -->
                            <td>
                                <?php if (!empty($row['address_line_one'])): ?>
                                    <?php echo htmlspecialchars($row['address_line_one']); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($row['address_line_two'])): ?>
                                    <?php echo htmlspecialchars($row['address_line_two']); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($row['town'])): ?>
                                    <?php echo htmlspecialchars($row['town']); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($row['county'])): ?>
                                    <?php echo htmlspecialchars($row['county']); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($row['postcode'])): ?>
                                    <?php echo htmlspecialchars($row['postcode']); ?>
                                <?php endif; ?>
                            </td>

                            <!-- Admin -->
                            <td><?php echo $row['admin'] ? 'Yes' : 'No'; ?></td>

                            <!-- Verified -->
                            <td><?php echo $row['verified'] ? 'Yes' : 'No'; ?></td>

                            <!-- Email Confirmed -->
                            <td><?php echo $row['email_confirmed'] ? 'Yes' : 'No'; ?></td>

                            <!-- Created At -->
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>

                            <!-- Action -->
                            <td>
                                <?php if ($row['verified']) { ?>
                                    <a href="?toggle_verify=<?php echo $row['id']; ?>&status=0"
                                        class="button button--danger button--small">
                                        Unverify
                                    </a>
                                <?php } else { ?>
                                    <a href="?toggle_verify=<?php echo $row['id']; ?>&status=1"
                                        class="button button--primary button--small">
                                        Verify
                                    </a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Step 2: Handle Verification/Unverification
if (isset($_GET['toggle_verify']) && isset($_GET['status'])) {
    $userId = (int) $_GET['toggle_verify'];
    $status = (int) $_GET['status'];

    // Prevent editing your own account
    if ($userId !== $loggedInUser['id']) {
        $updateSQL = "UPDATE `users` SET `verified` = ? WHERE `id` = ?";
        $stmt = $mysql->prepare($updateSQL);
        $stmt->bind_param('ii', $status, $userId);

        if ($stmt->execute()) {
            flash('success', $status ? 'User verified successfully.' : 'User unverified.');
            echo "<script>
                    setTimeout(function() {
                        window.location.href = '/users.php';
                    }, 100);
                  </script>";
            exit;
        } else {
            flash('error', 'Failed to update user verification status.');
        }
    } else {
        flash('error', "You can't modify your own account.");
    }
}

// Include the footer file
require_once('includes/footer.php');
