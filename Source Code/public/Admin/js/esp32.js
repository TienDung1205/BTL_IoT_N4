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
            title: `Đã gửi lệnh BẬT/TẮT tới ${deviceId}`,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    } else {
        console.log("MQTT chưa kết nối!");
    }
}
