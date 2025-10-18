<?php
require_once 'config/database.php';

try {
    $pdo = getConnection();

    // Read and execute the SQL file
    $sql = file_get_contents('setup_whatsapp.sql');
    $pdo->exec($sql);

    echo "WhatsApp settings table created successfully!\n";
    echo "You can now access WhatsApp settings in the admin panel.\n";

} catch (PDOException $e) {
    echo "Error setting up WhatsApp settings: " . $e->getMessage() . "\n";
}
?>
