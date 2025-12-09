
function toggleDevice(deviceId) {
    const topic = "smartHome/" + deviceId + "/set";   // ví dụ: home/light2/set
    const message = new Paho.Message("TOGGLE");  // nội dung gửi
    message.destinationName = topic;
    
    if (client && client.isConnected()) {
        client.send(message);
         Swal.fire({
            toast: true,
            position: 'top-start', // góc trái trên
            icon: 'success',
            title: `Đã gửi lệnh TOGGLE tới ${deviceId}`,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    } else {
        console.log("MQTT chưa kết nối!");
    }
}
function controlLight(deviceId, mode) {
    const topic = "smartHome/" + deviceId + "/set";
    const message = new Paho.Message(mode); // ON | OFF | AUTO
    message.destinationName = topic;

    if (client && client.isConnected()) {
        client.send(message);

        let text = "";
        if (mode === "ON") text = "BẬT";
        else if (mode === "OFF") text = "TẮT";
        else if (mode === "AUTO") text = "TỰ ĐỘNG";

        Swal.fire({
            toast: true,
            position: 'top-start',
            icon: 'success',
            title: `Đã gửi lệnh ${text} tới ${deviceId}`,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });

    } else {
        Swal.fire({
            icon: 'error',
            title: 'MQTT chưa kết nối',
            timer: 2000,
            showConfirmButton: false
        });
    }
}
