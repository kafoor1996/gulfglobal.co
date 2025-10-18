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
    $whatsapp_method = $_POST['whatsapp_method'] ?? 'direct';
    $instance_id = $_POST['instance_id'] ?? '';
    $access_token = $_POST['access_token'] ?? '';
    $api_url = $_POST['api_url'] ?? 'https://dealsms.in/api/send';

    try {
        // Update or insert WhatsApp settings
        $stmt = $pdo->prepare("
            INSERT INTO whatsapp_settings (method, instance_id, access_token, api_url, updated_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            method = VALUES(method),
            instance_id = VALUES(instance_id),
            access_token = VALUES(access_token),
            api_url = VALUES(api_url),
            updated_at = VALUES(updated_at)
        ");

        $stmt->execute([$whatsapp_method, $instance_id, $access_token, $api_url]);
        $message = "WhatsApp settings updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Get current settings
try {
    $stmt = $pdo->prepare("SELECT * FROM whatsapp_settings ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching settings: " . $e->getMessage();
    $settings = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Settings - Admin Panel</title>
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
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
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

        .api-settings {
            display: none;
            background: #f8f9ff;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .api-settings.show {
            display: block;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fab fa-whatsapp"></i> WhatsApp Settings</h1>
            <p>Configure WhatsApp integration methods</p>
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
                <div class="form-group">
                    <label>WhatsApp Method</label>
                    <div class="method-options">
                        <label class="method-option <?php echo ($settings['method'] ?? 'direct') == 'direct' ? 'selected' : ''; ?>">
                            <input type="radio" name="whatsapp_method" value="direct" <?php echo ($settings['method'] ?? 'direct') == 'direct' ? 'checked' : ''; ?>>
                            <i class="fas fa-link"></i>
                            <h3>Direct Link</h3>
                            <p>Open WhatsApp directly with pre-filled message</p>
                        </label>
                        <label class="method-option <?php echo ($settings['method'] ?? '') == 'api' ? 'selected' : ''; ?>">
                            <input type="radio" name="whatsapp_method" value="api" <?php echo ($settings['method'] ?? '') == 'api' ? 'checked' : ''; ?>>
                            <i class="fas fa-code"></i>
                            <h3>API Call</h3>
                            <p>Send messages via WhatsApp API</p>
                        </label>
                    </div>
                </div>

                <div class="api-settings <?php echo ($settings['method'] ?? '') == 'api' ? 'show' : ''; ?>" id="api-settings">
                    <h3><i class="fas fa-cog"></i> API Configuration</h3>

                    <div class="form-group">
                        <label for="api_url">API URL</label>
                        <input type="url" id="api_url" name="api_url"
                               value="<?php echo htmlspecialchars($settings['api_url'] ?? 'https://dealsms.in/api/send'); ?>"
                               placeholder="https://dealsms.in/api/send">
                    </div>

                    <div class="form-group">
                        <label for="instance_id">Instance ID</label>
                        <input type="text" id="instance_id" name="instance_id"
                               value="<?php echo htmlspecialchars($settings['instance_id'] ?? ''); ?>"
                               placeholder="68F32BAC7AB86">
                    </div>

                    <div class="form-group">
                        <label for="access_token">Access Token</label>
                        <input type="text" id="access_token" name="access_token"
                               value="<?php echo htmlspecialchars($settings['access_token'] ?? ''); ?>"
                               placeholder="68dbd933b8e16">
                    </div>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </form>
        </div>
    </div>

    <script>
        // Handle method selection
        document.querySelectorAll('input[name="whatsapp_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const apiSettings = document.getElementById('api-settings');
                if (this.value === 'api') {
                    apiSettings.classList.add('show');
                } else {
                    apiSettings.classList.remove('show');
                }

                // Update visual selection
                document.querySelectorAll('.method-option').forEach(option => {
                    option.classList.remove('selected');
                });
                this.closest('.method-option').classList.add('selected');
            });
        });
    </script>
</body>
</html>
