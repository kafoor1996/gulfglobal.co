// WhatsApp Integration with Local Storage
class WhatsAppManager {
    constructor() {
        this.settings = null;
        this.settingsLoaded = false;
        this.loadSettings();
        this.loadUserData();
    }

    // Load WhatsApp settings from server
    async loadSettings() {
        try {
            const response = await fetch(`api/whatsapp-settings.php?t=${Date.now()}`);
            this.settings = await response.json();
            this.settingsLoaded = true;
        } catch (error) {
            console.error('Error loading WhatsApp settings:', error);
            // Fallback to direct method
            this.settings = { method: 'direct' };
            this.settingsLoaded = true;
        }
    }

    // Load user data from localStorage
    loadUserData() {
        this.userData = {
            name: localStorage.getItem('whatsapp_name') || '',
            phone: localStorage.getItem('whatsapp_phone') || ''
        };
    }

    // Save user data to localStorage
    saveUserData(name, phone) {
        localStorage.setItem('whatsapp_name', name);
        localStorage.setItem('whatsapp_phone', phone);
        this.userData = { name, phone };
    }

    // Get user data with prompt if not available
    async getUserData() {
        if (!this.userData.name || !this.userData.phone) {
            return await this.promptUserData();
        }
        return this.userData;
    }

    // Prompt user for name and phone
    async promptUserData() {
        return new Promise((resolve) => {
            const modal = this.createUserDataModal();
            document.body.appendChild(modal);

            const form = modal.querySelector('#userDataForm');
            const nameInput = modal.querySelector('#userName');
            const phoneInput = modal.querySelector('#userPhone');

            // Load existing data if available
            nameInput.value = this.userData.name;
            phoneInput.value = this.userData.phone;

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const name = nameInput.value.trim();
                const phone = phoneInput.value.trim();

                if (name && phone) {
                    this.saveUserData(name, phone);
                    document.body.removeChild(modal);
                    resolve({ name, phone });
                } else {
                    alert('Please enter both name and phone number');
                }
            });

            // Close modal on cancel
            modal.querySelector('.close-modal').addEventListener('click', () => {
                document.body.removeChild(modal);
                resolve(null);
            });
        });
    }

    // Create user data modal
    createUserDataModal() {
        const modal = document.createElement('div');
        modal.className = 'whatsapp-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fab fa-whatsapp"></i> WhatsApp Contact Info</h3>
                    <button class="close-modal">&times;</button>
                </div>
                <form id="userDataForm">
                    <div class="form-group">
                        <label for="userName">Your Name</label>
                        <input type="text" id="userName" name="name" required
                               placeholder="Enter your name">
                    </div>
                    <div class="form-group">
                        <label for="userPhone">WhatsApp Number</label>
                        <input type="tel" id="userPhone" name="phone" required
                               placeholder="Enter your WhatsApp number">
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel close-modal">Cancel</button>
                        <button type="submit" class="btn-submit">Save & Continue</button>
                    </div>
                </form>
            </div>
        `;

        // Add modal styles
        const style = document.createElement('style');
        style.textContent = `
            .whatsapp-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            }
            .modal-content {
                background: white;
                border-radius: 15px;
                padding: 0;
                max-width: 400px;
                width: 90%;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            }
            .modal-header {
                background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
                color: white;
                padding: 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-radius: 15px 15px 0 0;
            }
            .modal-header h3 {
                margin: 0;
                font-size: 1.2rem;
            }
            .close-modal {
                background: none;
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .modal-content form {
                padding: 20px;
            }
            .form-group {
                margin-bottom: 15px;
            }
            .form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
                color: #333;
            }
            .form-group input {
                width: 100%;
                padding: 10px;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                font-size: 16px;
            }
            .form-group input:focus {
                outline: none;
                border-color: #25d366;
            }
            .modal-actions {
                display: flex;
                gap: 10px;
                justify-content: flex-end;
                margin-top: 20px;
            }
            .btn-cancel, .btn-submit {
                padding: 10px 20px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
            }
            .btn-cancel {
                background: #f0f0f0;
                color: #333;
            }
            .btn-submit {
                background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
                color: white;
            }
        `;
        document.head.appendChild(style);

        return modal;
    }

    // Send WhatsApp message
    async sendMessage(message, phone = null) {
        // Prevent multiple calls
        if (this.sending) {
            return;
        }

        this.sending = true;

        try {
            // Ensure settings are loaded
            if (!this.settingsLoaded) {
                await this.loadSettings();
            }


            if (this.settings.method === 'api') {
                // API method: Get user data and send via API
                const userData = await this.getUserData();
                if (!userData) return;
                await this.sendViaAPI(message, userData);
            } else {
                // Direct method: Open WhatsApp directly without popup
                this.sendViaDirectLink(message);
            }

            // Show success message after sending
            this.showSuccessMessage();
        } finally {
            this.sending = false;
        }
    }

    // Send via direct WhatsApp link (hardcoded admin number)
    sendViaDirectLink(message) {
        // Hardcoded admin WhatsApp number
        const adminPhone = '919789350475';
        const whatsappUrl = `https://wa.me/${adminPhone}?text=${encodeURIComponent(message)}`;
        window.open(whatsappUrl, '_blank');
    }

    // Send via API (to admin's WhatsApp number)
    async sendViaAPI(message, userData) {
        try {
            const response = await fetch('api/send-whatsapp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message
                })
            });

            const result = await response.json();

            if (result.success) {
                // Message sent successfully - no alert needed
            } else {
                alert('Failed to send message: ' + result.error);
            }
        } catch (error) {
            console.error('Error sending WhatsApp message:', error);
            alert('Error sending message. Please try again.');
        }
    }

    // Show success message modal
    showSuccessMessage() {
        const modal = this.createSuccessModal();
        document.body.appendChild(modal);

        // Auto remove after 3 seconds
        setTimeout(() => {
            if (document.body.contains(modal)) {
                document.body.removeChild(modal);
            }
        }, 3000);
    }

    // Create success message modal
    createSuccessModal() {
        const modal = document.createElement('div');
        modal.className = 'whatsapp-success-modal';
        modal.innerHTML = `
            <div class="success-modal-content">
                <div class="success-icon">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <h3>Message Sent Successfully!</h3>
                <p>Thank you for contacting us. We'll get back to you soon!</p>
                <button class="btn-close-success">OK</button>
            </div>
        `;

        // Add modal styles
        const style = document.createElement('style');
        style.textContent = `
            .whatsapp-success-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10001;
                animation: fadeIn 0.3s ease;
            }
            .success-modal-content {
                background: white;
                border-radius: 15px;
                padding: 2rem;
                text-align: center;
                max-width: 400px;
                width: 90%;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
                animation: slideIn 0.3s ease;
            }
            .success-icon {
                font-size: 3rem;
                color: #25d366;
                margin-bottom: 1rem;
            }
            .success-modal-content h3 {
                color: #2c3e50;
                margin-bottom: 1rem;
                font-size: 1.3rem;
            }
            .success-modal-content p {
                color: #666;
                margin-bottom: 1.5rem;
                line-height: 1.5;
            }
            .btn-close-success {
                background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
                color: white;
                padding: 0.75rem 2rem;
                border: none;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.3s;
            }
            .btn-close-success:hover {
                transform: translateY(-2px);
            }
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes slideIn {
                from { transform: translateY(-50px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);

        // Close modal on button click
        modal.querySelector('.btn-close-success').addEventListener('click', () => {
            document.body.removeChild(modal);
        });

        return modal;
    }

    // Force reload settings (useful for testing)
    async reloadSettings() {
        await this.loadSettings();
    }

    // Initialize WhatsApp buttons
    initButtons() {
        // Only initialize buttons that have data-message attribute (not Buy Now buttons)
        document.querySelectorAll('.whatsapp-btn, .btn-whatsapp[data-message]').forEach(button => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();

                // Ensure settings are loaded before proceeding
                if (!this.settingsLoaded) {
                    await this.loadSettings();
                }

                const message = button.dataset.message || 'Hello, I am interested in your products.';

                await this.sendMessage(message);
            });
        });
    }
}

    // Initialize WhatsApp Manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.whatsappManager = new WhatsAppManager();
    window.whatsappManager.initButtons();

});

// Export for use in other scripts
window.WhatsAppManager = WhatsAppManager;
