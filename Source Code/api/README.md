# IoT Light Control API

FastAPI backend bridge giữa Web UI và MQTT/Adafruit IO cho hệ thống điều khiển đèn thông minh.

## 🚀 Tính Năng

- **REST API** - Endpoints để điều khiển đèn
- **Adafruit IO Integration** - Đồng bộ trạng thái với Adafruit IO
- **MQTT Bridge** - Giao tiếp với ESP32 qua HiveMQ Cloud
- **Polling Thread** - Auto-sync từ Adafruit IO mỗi 3 giây
- **Google Home Support** - Thông qua Adafruit IO + IFTTT

## 📋 Yêu Cầu

- Python 3.11+
- Adafruit IO account
- HiveMQ Cloud account (hoặc MQTT broker khác)

## 🔧 Cài Đặt

### 1. Clone repository

```bash
cd "Source Code/api"
```

### 2. Tạo virtual environment

```bash
python -m venv venv
```

### 3. Kích hoạt virtual environment

**Windows PowerShell:**

```powershell
venv\Scripts\Activate.ps1
```

**Windows CMD:**

```cmd
venv\Scripts\activate.bat
```

**Linux/Mac:**

```bash
source venv/bin/activate
```

### 4. Cài đặt dependencies

```bash
pip install -r requirements.txt
```

### 5. Cấu hình môi trường

Tạo file `.env` từ template:

```bash
cp .env.example .env
```

Chỉnh sửa `.env` với thông tin của bạn:

```env
# Adafruit IO Configuration
ADAFRUIT_AIO_USERNAME=your_username
ADAFRUIT_AIO_KEY=your_key_here
ADAFRUIT_AIO_FEED=light

# HiveMQ Cloud MQTT Configuration
HIVEMQ_HOST=your_host.hivemq.cloud
HIVEMQ_PORT=8883
HIVEMQ_USER=your_mqtt_user
HIVEMQ_PASS=your_mqtt_pass
HIVEMQ_CMD_TOPIC=smartHome/cmd
HIVEMQ_TLS_INSECURE=1

# API Configuration
API_PORT=8086
POLLING_INTERVAL=3
```

## 🧪 Testing

Chạy unit tests:

```bash
python test_api.py
```

Hoặc với pytest:

```bash
pytest test_api.py -v
```

**Kết quả mong đợi:** ✅ All 9 tests PASSED

## 🏃 Chạy API

### Development Mode

```bash
python -m uvicorn adafruit_api:app --host 0.0.0.0 --port 8086 --reload
```

### Production Mode

```bash
python -m uvicorn adafruit_api:app --host 0.0.0.0 --port 8086
```

Hoặc chạy trực tiếp:

```bash
python adafruit_api.py
```

API sẽ chạy tại: `http://localhost:8086`

## 📡 API Endpoints

### Health Check

```bash
GET /health
```

**Response:**

```json
{
  "status": "healthy",
  "service": "IoT Light Control API",
  "version": "1.0.0"
}
```

### Điều Khiển Đèn

#### 1. Toggle Light (Đảo trạng thái)

```bash
POST /api/light
Content-Type: application/json

{
  "toggle": true
}
```

**Response:**

```json
{
  "feed": "light",
  "state": 1
}
```

#### 2. Set State Explicit (Đặt trạng thái cụ thể)

```bash
POST /api/light
Content-Type: application/json

{
  "state": 1
}
```

#### 3. Lấy Trạng Thái Hiện Tại

```bash
GET /api/light/status
```

**Response:**

```json
{
  "feed": "light",
  "state": 1,
  "status": "ON"
}
```

#### 4. Bật Đèn (Convenience Endpoint)

```bash
POST /api/light/on
```

#### 5. Tắt Đèn (Convenience Endpoint)

```bash
POST /api/light/off
```

#### 6. Toggle Đèn (Convenience Endpoint)

```bash
POST /api/light/toggle
```

## 🐳 Docker

### Build và Run với Docker

```bash
docker build -t iot-fastapi .
docker run -p 8086:8086 --env-file .env iot-fastapi
```

### Sử dụng Docker Compose

```bash
docker-compose up -d
```

Dừng service:

```bash
docker-compose down
```

## 🔄 Workflow Hoàn Chỉnh

```
1. Web UI (esp32.js) click button
   ↓
2. toggleDevice() → POST /api/light {"toggle": true}
   ↓
3. API resolve state (0→1 hoặc 1→0)
   ↓
4. API ghi Adafruit feed light=1
   ↓
5. API publish MQTT smartHome/cmd: "light ON"
   ↓
6. ESP32 nhận "light ON"
   ↓
7. digitalWrite(LIGHT1, HIGH) + digitalWrite(LIGHT2, HIGH)
   ↓
8. Web UI cập nhật: "BẬT" (xanh)
```

## 🔍 Troubleshooting

### API không khởi động

- Kiểm tra port 8086 có bị chiếm dụng không
- Xác nhận file `.env` đã được tạo và có đúng thông tin

### Không kết nối được Adafruit IO

- Xác thực `ADAFRUIT_AIO_USERNAME` và `ADAFRUIT_AIO_KEY`
- Kiểm tra feed `light` đã được tạo trong Adafruit IO dashboard

### Không publish được MQTT

- Xác nhận HiveMQ credentials
- Kiểm tra kết nối mạng
- Xem logs để xác định lỗi

### Tests fail

```bash
# Xóa cache và chạy lại
rm -rf __pycache__
pytest test_api.py -v --tb=short
```

## 📝 Cấu Trúc Thư Mục

```
api/
├── adafruit_api.py      # FastAPI main application
├── test_api.py          # Unit tests
├── requirements.txt     # Python dependencies
├── .env.example         # Environment template
├── .env                 # Your config (gitignored)
├── .gitignore          # Git ignore rules
├── Dockerfile          # Docker build config
├── docker-compose.yml  # Docker compose config
└── README.md           # This file
```

## 🔐 Security Notes

- **KHÔNG commit file `.env`** vào Git
- Sử dụng `.env.example` làm template
- GitHub Secret Scanner sẽ chặn real secrets
- Trong production, sử dụng secrets management service

## 📚 Tech Stack

- **FastAPI** - Modern Python web framework
- **Uvicorn** - ASGI server
- **Paho MQTT** - MQTT client library
- **Requests** - HTTP library cho Adafruit IO
- **Python-dotenv** - Environment variables management
- **Pytest** - Testing framework

## 🤝 Contributing

1. Fork repository
2. Tạo feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📄 License

Distributed under the MIT License.

## 👥 Authors

- **Team IoT N4** - Initial work

## 🙏 Acknowledgments

- Adafruit IO for cloud platform
- HiveMQ for MQTT broker
- FastAPI community

---

**Version:** 1.0.0  
**Last Updated:** December 2025
