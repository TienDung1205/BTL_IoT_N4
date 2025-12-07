// IoT Light Control - Web UI JavaScript
// Calls FastAPI instead of direct MQTT

// Global state
let lightState = 0; // 0 = OFF, 1 = ON

// API Configuration
const API_BASE_URL = 'http://localhost:8086';

// API Functions
async function makeApiCall(endpoint, method = 'GET', body = null) {
    try {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            }
        };
        
        if (body) {
            options.body = JSON.stringify(body);
        }
        
        const response = await fetch(`${API_BASE_URL}${endpoint}`, options);
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.detail || 'API request failed');
        }
        
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        showNotification('Lỗi: ' + error.message, 'error');
        throw error;
    }
}

// Toggle device function - now calls API
async function toggleDevice(deviceId) {
    console.log('Toggle device:', deviceId);
    
    try {
        // Call API to toggle light
        const response = await makeApiCall('/api/light', 'POST', { toggle: true });
        
        console.log('Toggle response:', response);
        lightState = response.state;
        updateLightUI();
        
        showNotification(
            `Đèn đã ${lightState ? 'BẬT' : 'TẮT'}`,
            'success'
        );
    } catch (error) {
        console.error('Toggle error:', error);
    }
}

// Turn light ON
async function turnOnLight() {
    console.log('Turn ON light');
    
    try {
        const response = await makeApiCall('/api/light/on', 'POST');
        
        console.log('Turn ON response:', response);
        lightState = response.state;
        updateLightUI();
        
        showNotification('Đèn đã BẬT', 'success');
    } catch (error) {
        console.error('Turn ON error:', error);
    }
}

// Turn light OFF
async function turnOffLight() {
    console.log('Turn OFF light');
    
    try {
        const response = await makeApiCall('/api/light/off', 'POST');
        
        console.log('Turn OFF response:', response);
        lightState = response.state;
        updateLightUI();
        
        showNotification('Đèn đã TẮT', 'success');
    } catch (error) {
        console.error('Turn OFF error:', error);
    }
}

// Get light status
async function getLightStatus() {
    console.log('Getting light status...');
    
    try {
        const response = await makeApiCall('/api/light/status', 'GET');
        
        console.log('Status response:', response);
        lightState = response.state;
        updateLightUI();
        
        return response;
    } catch (error) {
        console.error('Get status error:', error);
        return null;
    }
}

// Update UI based on light state
function updateLightUI() {
    // Update status badge
    const statusElements = document.querySelectorAll('[data-light-status]');
    statusElements.forEach(element => {
        if (lightState) {
            element.textContent = 'BẬT';
            element.className = 'badge badge-success';
        } else {
            element.textContent = 'TẮT';
            element.className = 'badge badge-danger';
        }
    });
    
    // Update toggle button
    const toggleBtn = document.getElementById('toggleLightBtn');
    if (toggleBtn) {
        if (lightState) {
            toggleBtn.textContent = 'TẮT ĐÈN';
            toggleBtn.className = 'btn btn-warning';
        } else {
            toggleBtn.textContent = 'BẬT ĐÈN';
            toggleBtn.className = 'btn btn-primary';
        }
    }
    
    // Update individual control buttons
    const onBtn = document.getElementById('lightOnBtn');
    const offBtn = document.getElementById('lightOffBtn');
    
    if (onBtn && offBtn) {
        if (lightState) {
            onBtn.disabled = true;
            offBtn.disabled = false;
        } else {
            onBtn.disabled = false;
            offBtn.disabled = true;
        }
    }
    
    // Update any light icons or indicators
    const lightIcons = document.querySelectorAll('.light-icon');
    lightIcons.forEach(icon => {
        if (lightState) {
            icon.classList.add('active');
            icon.classList.remove('inactive');
        } else {
            icon.classList.add('inactive');
            icon.classList.remove('active');
        }
    });
}

// Show notification (if you have a notification system)
function showNotification(message, type = 'info') {
    console.log(`[${type.toUpperCase()}] ${message}`);
    
    // If you have a toast/notification library, use it here
    // Example with Bootstrap toast:
    // const toast = new bootstrap.Toast(document.getElementById('notificationToast'));
    // document.getElementById('toastMessage').textContent = message;
    // toast.show();
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('ESP32 UI initialized');
    
    // Get initial status
    getLightStatus();
    
    // Setup event listeners
    const toggleBtn = document.getElementById('toggleLightBtn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => toggleDevice('light'));
    }
    
    const onBtn = document.getElementById('lightOnBtn');
    if (onBtn) {
        onBtn.addEventListener('click', turnOnLight);
    }
    
    const offBtn = document.getElementById('lightOffBtn');
    if (offBtn) {
        offBtn.addEventListener('click', turnOffLight);
    }
    
    // Auto-refresh status every 10 seconds
    setInterval(() => {
        getLightStatus();
    }, 10000);
    
    console.log('Event listeners attached');
});

// Health check function (optional)
async function checkApiHealth() {
    try {
        const response = await fetch(`${API_BASE_URL}/health`);
        const data = await response.json();
        console.log('API Health:', data);
        return data.status === 'healthy';
    } catch (error) {
        console.error('API Health check failed:', error);
        return false;
    }
}

// Check API health on load
checkApiHealth().then(healthy => {
    if (!healthy) {
        showNotification('Không thể kết nối đến API server', 'error');
    }
});
