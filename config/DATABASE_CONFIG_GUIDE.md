# Database Configuration Guide

This guide explains how to configure your database settings for different environments.

## Available Configuration Files

### 1. `database.php` (Production/Default)
- **Host**: localhost
- **Database**: gulf_global_co
- **Username**: root
- **Password**: MySQL123!

### 2. `database_local.php` (Local Development)
- **Host**: localhost
- **Database**: mediawor_gulf_global_co
- **Username**: mediawor_gulf_global_co
- **Password**: 69]j6S0xW8QzBlcw

## How to Switch Between Configurations

### Option 1: Rename Files
To use the local configuration:
```bash
# Backup the original
mv database.php database_production.php

# Use local configuration
mv database_local.php database.php
```

### Option 2: Modify Include Statements
In your PHP files, change the include statement from:
```php
require_once 'config/database.php';
```

To:
```php
require_once 'config/database_local.php';
```

### Option 3: Environment-Based Configuration
You can create a simple environment detection system:

```php
// In your main files, use this logic:
if (file_exists('config/database_local.php')) {
    require_once 'config/database_local.php';
} else {
    require_once 'config/database.php';
}
```

## Database Setup

### For Local Development (mediawor_gulf_global_co)
1. Make sure MySQL is running
2. Create the database: `mediawor_gulf_global_co`
3. Create the user: `mediawor_gulf_global_co`
4. Grant privileges to the user
5. Use the `database_local.php` configuration

### For Production (gulf_global_co)
1. Use the default `database.php` configuration
2. Ensure the database `gulf_global_co` exists
3. Ensure the root user has proper privileges

## Security Notes

- Never commit database credentials to version control
- Use environment variables for production deployments
- Consider using `.env` files for sensitive configuration
- Always use strong passwords
- Limit database user privileges to only what's necessary

## Troubleshooting

### Connection Issues
1. Verify MySQL is running: `sudo service mysql status`
2. Check database exists: `SHOW DATABASES;`
3. Verify user permissions: `SHOW GRANTS FOR 'username'@'localhost';`
4. Test connection manually:
   ```php
   $pdo = new PDO("mysql:host=localhost;dbname=mediawor_gulf_global_co", "mediawor_gulf_global_co", "69]j6S0xW8QzBlcw");
   ```

### Permission Issues
If you get permission errors, run:
```sql
GRANT ALL PRIVILEGES ON mediawor_gulf_global_co.* TO 'mediawor_gulf_global_co'@'localhost';
FLUSH PRIVILEGES;
```

## File Structure
```
config/
├── database.php              # Production configuration
├── database_local.php        # Local development configuration
└── DATABASE_CONFIG_GUIDE.md  # This guide
```
