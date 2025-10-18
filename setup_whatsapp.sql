-- WhatsApp Settings Table
CREATE TABLE IF NOT EXISTS whatsapp_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method ENUM('direct', 'api') NOT NULL DEFAULT 'direct',
    instance_id VARCHAR(255) NULL,
    access_token VARCHAR(255) NULL,
    api_url VARCHAR(500) DEFAULT 'https://dealsms.in/api/send',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO whatsapp_settings (method, instance_id, access_token, api_url)
VALUES ('direct', NULL, NULL, 'https://dealsms.in/api/send')
ON DUPLICATE KEY UPDATE method = VALUES(method);
