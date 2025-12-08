// main.js

// Load dữ liệu DB
function loadDevicesFromDB() {
    fetch('getdevice.php')
        .then(res => res.json())
        .then(devices => updateDeviceUI(devices))
        .catch(err => console.error(err));
}

// Cập nhật UI
function updateDeviceUI(devices) {
    if (devices.light1) {
        
        document.getElementById("light1-status").textContent = devices.light1.status;
        document.getElementById("light1-auto").textContent = devices.light1.auto_mode == 1 ? "Bật" : "Tắt";
        document.getElementById("light1-btn").textContent = devices.light1.auto_mode == 1 ? "Tắt chế độ tự động" : "Bật chế độ tự động";
    }
    if (devices.light2) {
        document.getElementById("light2-status").textContent = devices.light2.status;
        document.getElementById("light2-btn").textContent = devices.light2.status === "ON" ? "Tắt" : "Bật";
    }
    if (devices.fan) {
        document.getElementById("fan-status").textContent = devices.fan.status;
        document.getElementById("fan-btn").textContent = devices.fan.status === "ON" ? "Tắt" : "Bật";
    }
    if (devices.dht22) {
        const val = JSON.parse(devices.dht22.status || '{}');
        document.getElementById("temp-value").textContent = val.temperature ?? "--";
        document.getElementById("hum-value").textContent = val.humidity ?? "--";
        document.getElementById("dht-last").textContent = new Date().toLocaleTimeString();
    }
    if (devices.LM393) {
        document.getElementById("lm393-status").textContent = devices.LM393.status ?? "--";
        document.getElementById("lm393-last").textContent = new Date().toLocaleTimeString();
    }
}

// Load UI khi page load
window.addEventListener('load', () => {
    loadDevicesFromDB();
});
