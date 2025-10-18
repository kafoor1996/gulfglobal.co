<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Initialize database connection
$pdo = getConnection();

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Message is required']);
    exit;
}

try {
    // Get WhatsApp settings
    $stmt = $pdo->prepare("SELECT * FROM whatsapp_settings ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$settings || $settings['method'] !== 'api') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'API method not configured']);
        exit;
    }

    // Get admin's WhatsApp number from site settings
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'whatsapp_number'");
    $stmt->execute();
    $adminPhone = $stmt->fetchColumn();

    if (!$adminPhone) {
        echo json_encode(['success' => false, 'error' => 'Admin WhatsApp number not configured']);
        exit;
    }

    // Prepare API request data
    $apiData = [
        'number' => $adminPhone,
        'type' => 'text',
        'message' => $message,
        'instance_id' => $settings['instance_id'],
        'access_token' => $settings['access_token']
    ];

    // Send to WhatsApp API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $settings['api_url']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo json_encode(['success' => false, 'error' => 'CURL Error: ' . $curlError]);
        exit;
    }

    $responseData = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300) {
        echo json_encode(['success' => true, 'response' => $responseData]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'API Error: ' . ($responseData['message'] ?? 'Unknown error'),
            'http_code' => $httpCode
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>
