<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$product_id = $_GET['id'] ?? '';

if (empty($product_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID required']);
    exit();
}

$pdo = getConnection();

try {
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? AND is_active = 1 ORDER BY image_type, sort_order");
    $stmt->execute([$product_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($images);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
