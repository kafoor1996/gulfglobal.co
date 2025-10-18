<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$image_id = $_POST['image_id'] ?? '';

if (empty($image_id)) {
    echo json_encode(['success' => false, 'message' => 'Image ID required']);
    exit();
}

$pdo = getConnection();

try {
    // Get image info before deleting
    $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ?");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$image) {
        echo json_encode(['success' => false, 'message' => 'Image not found']);
        exit();
    }

    // Delete from database
    $stmt = $pdo->prepare("UPDATE product_images SET is_active = 0 WHERE id = ?");
    $result = $stmt->execute([$image_id]);

    if ($result) {
        // Optionally delete the physical file
        $file_path = '../' . $image['image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        echo json_encode(['success' => true, 'message' => 'Image deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete image']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
