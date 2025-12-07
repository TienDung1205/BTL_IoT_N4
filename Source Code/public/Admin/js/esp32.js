function toggleDevice(deviceId) {
    const topic = "smartHome/" + deviceId + "/set";   // ví dụ: home/light2/set
    const message = new Paho.Message("TOGGLE");  // nội dung gửi
    message.destinationName = topic;

    if (client && client.isConnected()) {
        client.send(message);
        console.log("Đã gửi lệnh TOGGLE cho " + deviceId);
    } else {
        console.log("MQTT chưa kết nối!");
    }
}
