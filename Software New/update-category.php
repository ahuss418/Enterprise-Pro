<?php
require_once('includes/setup.php');

require_once('2fa_checker.php');


if ($loggedInUser && !$loggedInUser['admin']) {
    flash('error', 'You do not have permission to edit categories.');
    redirect('/upload.php');
}

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['assetId'], $data['categoryId'])) {
    $assetId = (int) $data['assetId'];
    $categoryId = (int) $data['categoryId'];

    $stmt = $mysql->prepare("UPDATE `upload_asset_data` SET `category` = ? WHERE `id` = ?");
    $stmt->bind_param('ii', $categoryId, $assetId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
}
