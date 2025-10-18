# WhatsApp Integration Guide

## Overview
This WhatsApp integration provides two methods for sending messages:
1. **Direct Link Method** - Opens WhatsApp directly with pre-filled message
2. **API Method** - Sends messages via WhatsApp API (dealsms.in)

## Features
- ✅ Local storage for user name and phone number
- ✅ Two WhatsApp sending methods (direct link and API)
- ✅ Admin panel configuration
- ✅ Automatic user data collection
- ✅ Responsive design

## Setup Instructions

### 1. Database Setup
Run the setup script to create the WhatsApp settings table:
```bash
php setup_whatsapp.php
```

### 2. Admin Configuration
1. Login to admin panel
2. Navigate to "WhatsApp Settings"
3. Choose your preferred method:
   - **Direct Link**: Simple, opens WhatsApp directly
   - **API Method**: Requires API credentials

### 3. API Configuration (if using API method)
Fill in the following details:
- **API URL**: `https://dealsms.in/api/send`
- **Instance ID**: Your WhatsApp instance ID
- **Access Token**: Your API access token

## How It Works

### User Experience
1. User clicks any WhatsApp button
2. If first time, user is prompted for name and phone number
3. Data is saved to localStorage for future use
4. Message is sent using configured method

### Local Storage
The system automatically saves:
- User's name
- User's WhatsApp number
- Settings are remembered across sessions

### Two Sending Methods

#### Method 1: Direct Link (Default)
- Opens WhatsApp Web/App directly
- Pre-fills message with user details
- No API costs or setup required

#### Method 2: API Call
- Sends message via WhatsApp API
- Requires API credentials
- More reliable for business use

## API Endpoints

### Get WhatsApp Settings
```
GET /api/whatsapp-settings.php
```
Returns current WhatsApp configuration.

### Send WhatsApp Message
```
POST /api/send-whatsapp.php
Content-Type: application/json

{
    "number": "919789350475",
    "message": "Hello, I am interested in your products."
}
```

## File Structure
```
httpdocs/
├── admin/
│   └── whatsapp-settings.php     # Admin configuration page
├── api/
│   ├── whatsapp-settings.php     # Get settings API
│   └── send-whatsapp.php         # Send message API
├── js/
│   └── whatsapp.js               # WhatsApp functionality
├── setup_whatsapp.sql            # Database schema
└── setup_whatsapp.php            # Setup script
```

## Usage Examples

### Basic WhatsApp Button
```html
<a href="#" class="whatsapp-btn" data-message="Hello, I need help with your products.">
    <i class="fab fa-whatsapp"></i> Contact Us
</a>
```

### WhatsApp Button with Custom Message
```html
<button class="btn btn-whatsapp" data-message="I want to order your premium products.">
    <i class="fab fa-whatsapp"></i> Order Now
</button>
```

## JavaScript API

### Initialize WhatsApp Manager
```javascript
const whatsapp = new WhatsAppManager();
```

### Send Message Programmatically
```javascript
// Send message with user data collection
await whatsapp.sendMessage("Hello, I need assistance.");

// Send to specific number
await whatsapp.sendMessage("Hello", "919789350475");
```

### Get User Data
```javascript
const userData = await whatsapp.getUserData();
console.log(userData.name, userData.phone);
```

## Configuration Options

### Admin Panel Settings
- **Method**: Choose between 'direct' or 'api'
- **Instance ID**: Required for API method
- **Access Token**: Required for API method
- **API URL**: Custom API endpoint

### Local Storage Keys
- `whatsapp_name`: User's name
- `whatsapp_phone`: User's phone number

## Troubleshooting

### Common Issues
1. **API not working**: Check credentials in admin panel
2. **User data not saving**: Check browser localStorage support
3. **Messages not sending**: Verify API endpoint and credentials

### Debug Mode
Enable console logging by adding:
```javascript
window.whatsappManager.debug = true;
```

## Security Notes
- API credentials are stored securely in database
- User data is stored locally in browser
- No sensitive data is exposed in frontend
- All API calls are server-side

## Browser Support
- Modern browsers with localStorage support
- Mobile browsers (iOS Safari, Chrome Mobile)
- WhatsApp Web integration
- Progressive Web App compatible
