"""
IoT Light Control System - FastAPI Backend
Bridge between Web UI and MQTT/Adafruit IO
"""

import os
import time
import threading
import logging
from typing import Optional
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import requests
import paho.mqtt.client as mqtt
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Configuration
ADAFRUIT_AIO_USERNAME = os.getenv("ADAFRUIT_AIO_USERNAME", "")
ADAFRUIT_AIO_KEY = os.getenv("ADAFRUIT_AIO_KEY", "")
ADAFRUIT_AIO_FEED = os.getenv("ADAFRUIT_AIO_FEED", "light")
HIVEMQ_HOST = os.getenv("HIVEMQ_HOST", "")
HIVEMQ_PORT = int(os.getenv("HIVEMQ_PORT", "8883"))
HIVEMQ_USER = os.getenv("HIVEMQ_USER", "")
HIVEMQ_PASS = os.getenv("HIVEMQ_PASS", "")
HIVEMQ_CMD_TOPIC = os.getenv("HIVEMQ_CMD_TOPIC", "smartHome/cmd")
HIVEMQ_TLS_INSECURE = int(os.getenv("HIVEMQ_TLS_INSECURE", "1"))
API_PORT = int(os.getenv("API_PORT", "8086"))
POLLING_INTERVAL = int(os.getenv("POLLING_INTERVAL", "3"))

# Global state
mqtt_client = None
polling_thread = None
stop_polling = threading.Event()
last_adafruit_state = None

# FastAPI app
app = FastAPI(
    title="IoT Light Control API",
    description="Bridge between Web UI and MQTT/Adafruit IO",
    version="1.0.0"
)

# CORS configuration
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Request models
class LightRequest(BaseModel):
    toggle: Optional[bool] = None
    state: Optional[int] = None

class LightResponse(BaseModel):
    feed: str
    state: int

# Adafruit IO Integration
def get_feed_value() -> Optional[int]:
    """Get current value from Adafruit IO feed"""
    if not ADAFRUIT_AIO_USERNAME or not ADAFRUIT_AIO_KEY:
        logger.warning("Adafruit credentials not configured")
        return None
    
    try:
        url = f"https://io.adafruit.com/api/v2/{ADAFRUIT_AIO_USERNAME}/feeds/{ADAFRUIT_AIO_FEED}/data/last"
        headers = {"X-AIO-Key": ADAFRUIT_AIO_KEY}
        response = requests.get(url, headers=headers, timeout=5)
        response.raise_for_status()
        data = response.json()
        value = int(data.get("value", 0))
        logger.info(f"Adafruit IO feed value: {value}")
        return value
    except Exception as e:
        logger.error(f"Error getting feed value: {e}")
        return None

def set_feed_value(value: int) -> bool:
    """Set value to Adafruit IO feed"""
    if not ADAFRUIT_AIO_USERNAME or not ADAFRUIT_AIO_KEY:
        logger.warning("Adafruit credentials not configured")
        return False
    
    try:
        url = f"https://io.adafruit.com/api/v2/{ADAFRUIT_AIO_USERNAME}/feeds/{ADAFRUIT_AIO_FEED}/data"
        headers = {"X-AIO-Key": ADAFRUIT_AIO_KEY}
        payload = {"value": str(value)}
        response = requests.post(url, headers=headers, json=payload, timeout=5)
        response.raise_for_status()
        logger.info(f"Set Adafruit IO feed to: {value}")
        return True
    except Exception as e:
        logger.error(f"Error setting feed value: {e}")
        return False

def resolve_light_state(toggle: Optional[bool], state: Optional[int]) -> int:
    """Resolve target light state based on request"""
    if toggle:
        current = get_feed_value()
        if current is None:
            current = 0
        return 1 if current == 0 else 0
    elif state is not None:
        return 1 if state else 0
    else:
        raise ValueError("Must provide either 'toggle' or 'state'")

# MQTT Bridge
def on_mqtt_connect(client, userdata, flags, rc):
    """Callback when MQTT client connects"""
    if rc == 0:
        logger.info("Connected to HiveMQ broker")
    else:
        logger.error(f"Failed to connect to HiveMQ, return code: {rc}")

def on_mqtt_disconnect(client, userdata, rc):
    """Callback when MQTT client disconnects"""
    logger.warning(f"Disconnected from HiveMQ, return code: {rc}")

def init_mqtt_client():
    """Initialize MQTT client"""
    global mqtt_client
    
    if not HIVEMQ_HOST or not HIVEMQ_USER:
        logger.warning("HiveMQ credentials not configured")
        return None
    
    try:
        client = mqtt.Client(client_id="fastapi_bridge")
        client.username_pw_set(HIVEMQ_USER, HIVEMQ_PASS)
        
        # TLS configuration
        if HIVEMQ_PORT == 8883:
            import ssl
            if HIVEMQ_TLS_INSECURE:
                client.tls_set(cert_reqs=ssl.CERT_NONE)
                client.tls_insecure_set(True)
            else:
                client.tls_set()
        
        client.on_connect = on_mqtt_connect
        client.on_disconnect = on_mqtt_disconnect
        
        client.connect(HIVEMQ_HOST, HIVEMQ_PORT, 60)
        client.loop_start()
        
        mqtt_client = client
        logger.info("MQTT client initialized")
        return client
    except Exception as e:
        logger.error(f"Error initializing MQTT client: {e}")
        return None

def publish_mqtt_command(light_value: int):
    """Publish light command to MQTT"""
    if not mqtt_client:
        logger.warning("MQTT client not initialized")
        return False
    
    try:
        command = f"light {'ON' if light_value else 'OFF'}"
        result = mqtt_client.publish(HIVEMQ_CMD_TOPIC, command, qos=1)
        if result.rc == mqtt.MQTT_ERR_SUCCESS:
            logger.info(f"Published MQTT command: {command}")
            return True
        else:
            logger.error(f"Failed to publish MQTT command, rc: {result.rc}")
            return False
    except Exception as e:
        logger.error(f"Error publishing MQTT command: {e}")
        return False

# Polling Thread
def poll_feed_and_bridge():
    """Background thread to poll Adafruit feed and bridge to MQTT"""
    global last_adafruit_state
    
    logger.info("Polling thread started")
    
    while not stop_polling.is_set():
        try:
            current_state = get_feed_value()
            
            if current_state is not None and current_state != last_adafruit_state:
                logger.info(f"Adafruit feed changed: {last_adafruit_state} -> {current_state}")
                publish_mqtt_command(current_state)
                last_adafruit_state = current_state
            
        except Exception as e:
            logger.error(f"Error in polling thread: {e}")
        
        stop_polling.wait(POLLING_INTERVAL)
    
    logger.info("Polling thread stopped")

def start_polling_thread():
    """Start background polling thread"""
    global polling_thread, last_adafruit_state
    
    # Initialize last state
    last_adafruit_state = get_feed_value()
    
    polling_thread = threading.Thread(target=poll_feed_and_bridge, daemon=True)
    polling_thread.start()
    logger.info("Polling thread initialized")

def stop_polling_thread():
    """Stop background polling thread"""
    stop_polling.set()
    if polling_thread:
        polling_thread.join(timeout=5)
    logger.info("Polling thread stopped")

# API Endpoints
@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {
        "status": "healthy",
        "service": "IoT Light Control API",
        "version": "1.0.0"
    }

@app.post("/api/light", response_model=LightResponse)
async def control_light(request: LightRequest):
    """
    Control light with toggle or explicit state
    - toggle: true/false to toggle current state
    - state: 0/1 to set explicit state
    """
    try:
        # Validate request
        if request.toggle is None and request.state is None:
            raise HTTPException(
                status_code=400,
                detail="Must provide either 'toggle' or 'state'"
            )
        
        # Resolve target state
        target_state = resolve_light_state(request.toggle, request.state)
        
        # Update Adafruit IO
        if not set_feed_value(target_state):
            raise HTTPException(
                status_code=500,
                detail="Failed to update Adafruit IO feed"
            )
        
        # Publish to MQTT
        publish_mqtt_command(target_state)
        
        return LightResponse(feed=ADAFRUIT_AIO_FEED, state=target_state)
    
    except HTTPException:
        raise
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))
    except Exception as e:
        logger.error(f"Error controlling light: {e}")
        raise HTTPException(status_code=500, detail="Internal server error")

@app.get("/api/light/status")
async def get_light_status():
    """Get current light status from Adafruit IO"""
    try:
        current_state = get_feed_value()
        if current_state is None:
            raise HTTPException(
                status_code=500,
                detail="Failed to get light status from Adafruit IO"
            )
        
        return {
            "feed": ADAFRUIT_AIO_FEED,
            "state": current_state,
            "status": "ON" if current_state else "OFF"
        }
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error getting light status: {e}")
        raise HTTPException(status_code=500, detail="Internal server error")

@app.post("/api/light/on", response_model=LightResponse)
async def turn_light_on():
    """Turn light ON"""
    return await control_light(LightRequest(state=1))

@app.post("/api/light/off", response_model=LightResponse)
async def turn_light_off():
    """Turn light OFF"""
    return await control_light(LightRequest(state=0))

@app.post("/api/light/toggle", response_model=LightResponse)
async def toggle_light():
    """Toggle light state"""
    return await control_light(LightRequest(toggle=True))

# Lifecycle events
@app.on_event("startup")
async def startup_event():
    """Initialize services on startup"""
    logger.info("Starting IoT Light Control API...")
    init_mqtt_client()
    start_polling_thread()
    logger.info("API started successfully")

@app.on_event("shutdown")
async def shutdown_event():
    """Cleanup on shutdown"""
    logger.info("Shutting down IoT Light Control API...")
    stop_polling_thread()
    if mqtt_client:
        mqtt_client.loop_stop()
        mqtt_client.disconnect()
    logger.info("API shutdown complete")

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(
        "adafruit_api:app",
        host="0.0.0.0",
        port=API_PORT,
        reload=False
    )
