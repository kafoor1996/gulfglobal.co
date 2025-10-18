-- Site Settings Table
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'Gulf Global Co'),
('site_email', 'info@gulfglobal.co'),
('site_phone', '+91 97893 50475'),
('site_address', 'Your Business Address'),
('whatsapp_number', '919789350475')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
