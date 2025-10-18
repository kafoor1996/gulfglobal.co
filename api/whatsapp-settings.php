<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $pdo = getConnection();

    // Get WhatsApp method from site_settings
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'whatsapp_method'");
    $stmt->execute();
    $method = $stmt->fetchColumn();

    if (!$method) {
        $method = 'direct'; // Default method
    }

    // Get API credentials if method is 'api'
    $settings = ['method' => $method];

    if ($method === 'api') {
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_settings ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute();
        $apiSettings = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($apiSettings) {
            $settings['instance_id'] = $apiSettings['instance_id'];
            $settings['access_token'] = $apiSettings['access_token'];
            $settings['api_url'] = $apiSettings['api_url'];
        }
    }

    echo json_encode($settings);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
