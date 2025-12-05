
// const API_URL = "../api/api.php?action=";
// const API_KEY = "123456";

// async function loadDevices() {
//   try {
//     const res = await fetch(`${API_URL}devices&key=${API_KEY}`);
//     const devices = await res.json();

//     // Lấy từng thiết bị riêng
//     const light1 = devices.find(d => d.device_id === "light1");
//     const light2 = devices.find(d => d.device_id === "light2");
//     const fan    = devices.find(d => d.device_id === "fan");
//     const dht22  = devices.find(d => d.device_id === "dht22");
//     const LM393  = devices.find(d => d.device_id === "LM393");

//     // Cập nhật giao diện
//     if (light1) {
//       document.getElementById("light1-status").textContent = light1.status;
//       document.getElementById("light1-auto").textContent = light1.auto_mode == 1 ? "Bật" : "Tắt";
//       document.getElementById("light1-btn").textContent = light1.auto_mode == 1 ? "Tắt chế độ tự động" : "Bật chế độ tự động";
//     }

//     if (light2) {
//       document.getElementById("light2-status").textContent = light2.status;
//       document.getElementById("light2-btn").textContent = light2.status === "ON" ? "Tắt" : "Bật";
//     }

//     if (fan) {
//       document.getElementById("fan-status").textContent = fan.status;
//       document.getElementById("fan-btn").textContent = fan.status === "ON" ? "Tắt" : "Bật";
//     }

//     if (dht22 && dht22.status) {
//       const status = typeof dht22.status === "string" ? JSON.parse(dht22.status) : dht22.status;
//       document.getElementById("temp-value").textContent = status.temperature;
//       document.getElementById("hum-value").textContent  = status.humidity;
//       document.getElementById("dht-last").textContent = new Date(dht22.last_seen).toLocaleTimeString();
//     }
//     if (LM393 && LM393.status) {
//       document.getElementById("lm393-status").textContent  = LM393.status === "BRIGHT" ? "Sáng" : "Tối";
//       document.getElementById("lm393-last").textContent = new Date(LM393.last_seen).toLocaleTimeString();
//     }

//   } catch (err) {
//     console.error("Lỗi tải thiết bị:", err);
//   }
// }

// async function toggleAutoMode(device_id) {
//   const autoEl = document.getElementById(device_id + "-auto");
//   // Nếu text hiện tại là "bật" → gửi 0, nếu "tắt" → gửi 1
//   const newMode = autoEl.textContent === "Bật" ? 0 : 1;

//   await sendAutoModeCommand(device_id, newMode);

//   // Cập nhật text hiển thị
//   autoEl.textContent = newMode === 1 ? "Bật" : "Tắt";
// }


// async function sendAutoModeCommand(device_id, auto_mode) {
//   const cmd = JSON.stringify({ auto_mode: auto_mode });

//   await fetch(`${API_URL}command&key=${API_KEY}`, {
//     method: "POST",
//     headers: { "Content-Type": "application/json" },
//     body: JSON.stringify({ device_id, cmd })
//   });

//   Swal.fire({
//     toast: true,
//     position: 'top-start',
//     icon: 'success',
//     title: `Đã gửi lệnh ${auto_mode == 1 ? 'Bật' : 'Tắt'} chế độ tự động tới ${device_id}`,
//     showConfirmButton: false,
//     timer: 2000
//   });
//   loadDevices();
// }



// async function sendCommand(device_id, cmd) {
//   await fetch(`${API_URL}command&key=${API_KEY}`, {
//     method: "POST",
//     headers: { "Content-Type": "application/json" },
//     body: JSON.stringify({ device_id, cmd })
//   });
// //   //debug
// //   console.log("Gửi lệnh:", device_id, cmd); 
// //     const result = await res.json();
// //   console.log("Kết quả server trả về:", result);

//   // alert(`Đã gửi lệnh ${cmd} tới ${device_id}`);
//   // console.log(`Đã gửi lệnh ${cmd} tới ${device_id}`);
//   Swal.fire({
//     toast: true,
//     position: 'top-start',
//     icon: 'success',
//     title: `Đã gửi lệnh ${cmd} tới ${device_id}`,
//     showConfirmButton: false,
//     timer: 2000
//   });
//   loadDevices();
// }


// async function loadDHT22() {
//   try {
//     const res = await fetch(`${API_URL}devices&key=${API_KEY}`);
//     const devices = await res.json();

//     let dht = devices.find(d => d.device_id === "dht22");

//     if (dht && dht.status) {
//       // Nếu status là string JSON, parse ra object
//       const status = typeof dht.status === "string" ? JSON.parse(dht.status) : dht.status;

//       document.getElementById("temp-value").textContent = status.temperature;
//       document.getElementById("hum-value").textContent = status.humidity;
//       document.getElementById("dht-last").textContent = new Date(dht.last_seen).toLocaleTimeString();
//     }
//   } catch (err) {
//     console.error("Lỗi tải DHT22:", err);
//   }
// }

// // Gọi khi trang load + auto refresh 10s/lần
// loadDHT22();
// setInterval(loadDHT22, 10000);


// loadDevices();
// setInterval(loadDevices, 5000);




// Kết nối MQTT WebSocket
const client = mqtt.connect('ws://localhost:9001');

// Khi kết nối broker
client.on('connect', () => {
  // Subscribe tất cả topic thiết bị
  client.subscribe('home/devices/#');
});

// Nhận dữ liệu
client.on('message', (topic, message) => {
  const data = JSON.parse(message.toString());

  // Ví dụ topic: home/devices/light1
  const device_id = topic.split('/').pop(); // lấy light1, fan, dht22...
  
  if(device_id === 'dht22') {
    document.getElementById("temp-value").textContent = data.temperature;
    document.getElementById("hum-value").textContent = data.humidity;
    document.getElementById("dht-last").textContent = new Date(data.last_seen).toLocaleTimeString();
  } else {
    // cập nhật trạng thái các thiết bị khác giống loadDevices()
    document.getElementById(`${device_id}-status`).textContent = data.status;
    if(data.auto_mode !== undefined)
      document.getElementById(`${device_id}-auto`).textContent = data.auto_mode==1?"Bật":"Tắt";
  }
});

// Gửi lệnh (thay fetch bằng publish)
function sendCommand(device_id, cmd) {
  client.publish('home/commands', JSON.stringify({ device_id, cmd }));
  Swal.fire({
    toast: true,
    position: 'top-start',
    icon: 'success',
    title: `Đã gửi lệnh ${cmd} tới ${device_id}`,
    showConfirmButton: false,
    timer: 2000
  });
}
