<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$pdo = getConnection();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $site_name = $_POST['site_name'] ?? '';
    $site_email = $_POST['site_email'] ?? '';
    $site_phone = $_POST['site_phone'] ?? '';
    $site_address = $_POST['site_address'] ?? '';
    $whatsapp_number = $_POST['whatsapp_number'] ?? '';
    $whatsapp_method = $_POST['whatsapp_method'] ?? 'direct';

    try {
        // Update or insert site settings
        $stmt = $pdo->prepare("
            INSERT INTO site_settings (setting_key, setting_value, updated_at)
            VALUES
            ('site_name', ?, NOW()),
            ('site_email', ?, NOW()),
            ('site_phone', ?, NOW()),
            ('site_address', ?, NOW()),
            ('whatsapp_number', ?, NOW()),
            ('whatsapp_method', ?, NOW())
            ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            updated_at = VALUES(updated_at)
        ");

        $stmt->execute([$site_name, $site_email, $site_phone, $site_address, $whatsapp_number, $whatsapp_method]);
        $message = "Settings updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Get current settings
$settings = [];
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    $error = "Error fetching settings: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c5aa0 0%, #667eea 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
        }

        .form-container {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .method-options {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .method-option {
            flex: 1;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .method-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .method-option.selected {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .method-option input[type="radio"] {
            display: none;
        }

        .method-option i {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 10px;
        }

        .test-section {
            margin-top: 40px;
            padding: 30px;
            background: #f8f9ff;
            border-radius: 15px;
            border: 2px solid #e0e0e0;
        }

        .test-section h3 {
            color: #2c5aa0;
            margin-bottom: 10px;
            font-size: 1.3rem;
        }

        .test-section p {
            color: #666;
            margin-bottom: 20px;
        }

        .btn-test {
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-test:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 211, 102, 0.4);
        }

        .test-results {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            background: white;
            border: 1px solid #e0e0e0;
            min-height: 50px;
            font-family: monospace;
            font-size: 14px;
        }

        .test-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .test-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .back-btn:hover {
            color: #5a67d8;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-cog"></i> Site Settings</h1>
            <p>Configure your website settings</p>
        </div>

        <div class="form-container">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="site_name">Site Name</label>
                        <input type="text" id="site_name" name="site_name"
                               value="<?php echo htmlspecialchars($settings['site_name'] ?? 'Gulf Global Co'); ?>"
                               placeholder="Enter site name">
                    </div>
                    <div class="form-group">
                        <label for="site_email">Contact Email</label>
                        <input type="email" id="site_email" name="site_email"
                               value="<?php echo htmlspecialchars($settings['site_email'] ?? ''); ?>"
                               placeholder="Enter contact email">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="site_phone">Phone Number</label>
                        <input type="tel" id="site_phone" name="site_phone"
                               value="<?php echo htmlspecialchars($settings['site_phone'] ?? ''); ?>"
                               placeholder="Enter phone number">
                    </div>
                    <div class="form-group">
                        <label for="whatsapp_number">WhatsApp Number</label>
                        <input type="tel" id="whatsapp_number" name="whatsapp_number"
                               value="<?php echo htmlspecialchars($settings['whatsapp_number'] ?? ''); ?>"
                               placeholder="Enter WhatsApp number">
                    </div>
                </div>

                <div class="form-group">
                    <label for="site_address">Address</label>
                    <textarea id="site_address" name="site_address"
                              placeholder="Enter full address"><?php echo htmlspecialchars($settings['site_address'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>WhatsApp Method</label>
                    <div class="method-options">
                        <label class="method-option <?php echo ($settings['whatsapp_method'] ?? 'direct') == 'direct' ? 'selected' : ''; ?>">
                            <input type="radio" name="whatsapp_method" value="direct" <?php echo ($settings['whatsapp_method'] ?? 'direct') == 'direct' ? 'checked' : ''; ?>>
                            <i class="fas fa-link"></i>
                            <h3>Direct Link</h3>
                            <p>Open WhatsApp directly with pre-filled message</p>
                        </label>
                        <label class="method-option <?php echo ($settings['whatsapp_method'] ?? '') == 'api' ? 'selected' : ''; ?>">
                            <input type="radio" name="whatsapp_method" value="api" <?php echo ($settings['whatsapp_method'] ?? '') == 'api' ? 'checked' : ''; ?>>
                            <i class="fas fa-code"></i>
                            <h3>API Call</h3>
                            <p>Send messages via WhatsApp API</p>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Save Settings
                </button>

                <div class="test-section">
                    <h3 id="whatsapp-test-title"><i class="fas fa-flask"></i> WhatsApp Testing - Loading...</h3>
                    <p>Test the WhatsApp functionality with current settings from database</p>
                    <button type="button" class="btn btn-test" id="test-whatsapp">
                        <i class="fab fa-whatsapp"></i> Test WhatsApp
                    </button>
                    <div id="test-results" class="test-results"></div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Load current WhatsApp method and update title
        async function loadWhatsAppMethod() {
            try {
                const response = await fetch('../api/whatsapp-settings.php?t=' + Date.now());
                const settings = await response.json();

                const titleElement = document.getElementById('whatsapp-test-title');
                const method = settings.method.toUpperCase();
                titleElement.innerHTML = `<i class="fas fa-flask"></i> WhatsApp Testing - ${method}`;

            } catch (error) {
                const titleElement = document.getElementById('whatsapp-test-title');
                titleElement.innerHTML = `<i class="fas fa-flask"></i> WhatsApp Testing - Error`;
            }
        }

        // Load method when page loads
        document.addEventListener('DOMContentLoaded', loadWhatsAppMethod);

        // Handle method selection
        document.querySelectorAll('input[name="whatsapp_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Update visual selection
                document.querySelectorAll('.method-option').forEach(option => {
                    option.classList.remove('selected');
                });
                this.closest('.method-option').classList.add('selected');
            });
        });

        // WhatsApp Testing
        document.getElementById('test-whatsapp').addEventListener('click', async function() {
            const resultsDiv = document.getElementById('test-results');
            resultsDiv.innerHTML = 'Testing WhatsApp method...';
            resultsDiv.className = 'test-results';

            try {
                // Get method from database
                const response = await fetch('../api/whatsapp-settings.php?t=' + Date.now());
                const settings = await response.json();

                const method = settings.method.toUpperCase();
                resultsDiv.innerHTML = `Testing ${method} Method...`;

                if (settings.method === 'api') {
                    // Test API method with your JSON
                    resultsDiv.innerHTML += '\n\n📤 Sending API test message...';

                    const testData = {
                        number: "919789350475",
                        type: "text",
                        message: "hello testing from admin",
                        instance_id: "68F32BAC7AB86",
                        access_token: "68dbd933b8e16"
                    };

                    const apiResponse = await fetch('../api/send-whatsapp.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            message: testData.message
                        })
                    });

                    const responseText = await apiResponse.text();
                    console.log('Raw API Response:', responseText);

                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        resultsDiv.innerHTML += '\n❌ JSON Parse Error!';
                        resultsDiv.innerHTML += '\n🔍 Raw Response: ' + responseText;
                        resultsDiv.innerHTML += '\n🔍 Parse Error: ' + parseError.message;
                        resultsDiv.className = 'test-results test-error';
                        return;
                    }

                    if (result.success) {
                        resultsDiv.innerHTML += '\n✅ API Test Successful!';
                        resultsDiv.innerHTML += '\n📱 Message sent via dealsms API';
                        resultsDiv.innerHTML += '\n📊 Response: ' + JSON.stringify(result);
                        resultsDiv.className = 'test-results test-success';
                    } else {
                        resultsDiv.innerHTML += '\n❌ API Test Failed!';
                        resultsDiv.innerHTML += '\n🔍 Error: ' + result.error;
                        resultsDiv.className = 'test-results test-error';
                    }

                } else {
                    // Test Direct method - open WhatsApp
                    resultsDiv.innerHTML += '\n\n📱 Opening WhatsApp directly...';

                    const adminNumber = "919789350475";
                    const testMessage = "hello testing from admin";
                    const whatsappUrl = `https://wa.me/${adminNumber}?text=${encodeURIComponent(testMessage)}`;

                    // Open WhatsApp
                    window.open(whatsappUrl, '_blank');

                    resultsDiv.innerHTML += '\n✅ Direct Test Successful!';
                    resultsDiv.innerHTML += '\n📱 WhatsApp opened with message';
                    resultsDiv.innerHTML += '\n🔗 URL: ' + whatsappUrl;
                    resultsDiv.className = 'test-results test-success';
                }

            } catch (error) {
                resultsDiv.innerHTML += '\n❌ Test Failed: ' + error.message;
                resultsDiv.className = 'test-results test-error';
            }
        });
    </script>
</body>
</html>
