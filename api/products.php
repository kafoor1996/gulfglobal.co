<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $pdo = getConnection();

    // Get all active products
    $stmt = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format products for frontend
    $formatted_products = [];
    foreach ($products as $product) {
        $formatted_products[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'price' => floatval($product['price']),
            'category' => $product['category'],
            'image' => $product['image'] ?: 'default.jpg',
            'is_hot_sale' => (bool)$product['is_hot_sale'],
            'created_at' => $product['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'products' => $formatted_products,
        'total' => count($formatted_products)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch products',
        'message' => $e->getMessage()
    ]);
}
?>
