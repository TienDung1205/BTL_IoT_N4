"""
Unit tests for IoT Light Control API
Tests all endpoints and edge cases
"""

import sys
import os
import pytest
from fastapi.testclient import TestClient
from unittest.mock import patch, MagicMock

# Add parent directory to path
sys.path.insert(0, os.path.dirname(__file__))

# Mock environment variables before importing
os.environ.update({
    "ADAFRUIT_AIO_USERNAME": "test_user",
    "ADAFRUIT_AIO_KEY": "test_key",
    "ADAFRUIT_AIO_FEED": "light",
    "HIVEMQ_HOST": "test.hivemq.cloud",
    "HIVEMQ_PORT": "8883",
    "HIVEMQ_USER": "test_mqtt_user",
    "HIVEMQ_PASS": "test_mqtt_pass",
    "HIVEMQ_CMD_TOPIC": "smartHome/cmd",
    "API_PORT": "8086",
    "POLLING_INTERVAL": "3"
})

# Import after setting env vars
from adafruit_api import app

client = TestClient(app)

# Mock functions
@pytest.fixture(autouse=True)
def mock_external_services():
    """Mock external services (Adafruit IO and MQTT)"""
    with patch('adafruit_api.get_feed_value') as mock_get, \
         patch('adafruit_api.set_feed_value') as mock_set, \
         patch('adafruit_api.publish_mqtt_command') as mock_mqtt, \
         patch('adafruit_api.init_mqtt_client') as mock_init, \
         patch('adafruit_api.start_polling_thread') as mock_poll:
        
        # Default return values
        mock_get.return_value = 0
        mock_set.return_value = True
        mock_mqtt.return_value = True
        mock_init.return_value = MagicMock()
        
        yield {
            'get_feed': mock_get,
            'set_feed': mock_set,
            'mqtt': mock_mqtt,
            'init': mock_init,
            'poll': mock_poll
        }

def test_health():
    """Test 1: Health check endpoint"""
    response = client.get("/health")
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "healthy"
    assert "service" in data
    assert "version" in data
    print("✅ Test 1 PASSED: Health check")

def test_toggle_on(mock_external_services):
    """Test 2: Toggle light from OFF to ON"""
    mock_external_services['get_feed'].return_value = 0
    
    response = client.post("/api/light", json={"toggle": True})
    assert response.status_code == 200
    data = response.json()
    assert data["state"] == 1
    assert data["feed"] == "light"
    
    # Verify Adafruit was updated
    mock_external_services['set_feed'].assert_called_with(1)
    # Verify MQTT was published
    mock_external_services['mqtt'].assert_called_with(1)
    print("✅ Test 2 PASSED: Toggle ON")

def test_set_state_0(mock_external_services):
    """Test 3: Set light state to OFF (0)"""
    response = client.post("/api/light", json={"state": 0})
    assert response.status_code == 200
    data = response.json()
    assert data["state"] == 0
    assert data["feed"] == "light"
    
    mock_external_services['set_feed'].assert_called_with(0)
    mock_external_services['mqtt'].assert_called_with(0)
    print("✅ Test 3 PASSED: Set state to 0")

def test_set_state_1(mock_external_services):
    """Test 4: Set light state to ON (1)"""
    response = client.post("/api/light", json={"state": 1})
    assert response.status_code == 200
    data = response.json()
    assert data["state"] == 1
    assert data["feed"] == "light"
    
    mock_external_services['set_feed'].assert_called_with(1)
    mock_external_services['mqtt'].assert_called_with(1)
    print("✅ Test 4 PASSED: Set state to 1")

def test_invalid_request(mock_external_services):
    """Test 5: Invalid request without toggle or state"""
    response = client.post("/api/light", json={})
    assert response.status_code == 400
    data = response.json()
    assert "detail" in data
    print("✅ Test 5 PASSED: Invalid request handling")

def test_get_status(mock_external_services):
    """Test 6: Get current light status"""
    mock_external_services['get_feed'].return_value = 1
    
    response = client.get("/api/light/status")
    assert response.status_code == 200
    data = response.json()
    assert data["state"] == 1
    assert data["status"] == "ON"
    assert data["feed"] == "light"
    
    # Test OFF state
    mock_external_services['get_feed'].return_value = 0
    response = client.get("/api/light/status")
    data = response.json()
    assert data["state"] == 0
    assert data["status"] == "OFF"
    print("✅ Test 6 PASSED: Get status")

def test_convenience_endpoints(mock_external_services):
    """Test 7: Convenience endpoints (/on, /off, /toggle)"""
    # Test /on
    response = client.post("/api/light/on")
    assert response.status_code == 200
    assert response.json()["state"] == 1
    mock_external_services['set_feed'].assert_called_with(1)
    
    # Test /off
    response = client.post("/api/light/off")
    assert response.status_code == 200
    assert response.json()["state"] == 0
    mock_external_services['set_feed'].assert_called_with(0)
    
    # Test /toggle
    mock_external_services['get_feed'].return_value = 0
    response = client.post("/api/light/toggle")
    assert response.status_code == 200
    assert response.json()["state"] == 1
    
    print("✅ Test 7 PASSED: Convenience endpoints")

def test_adafruit_error_handling(mock_external_services):
    """Test 8: Handle Adafruit IO errors"""
    mock_external_services['set_feed'].return_value = False
    
    response = client.post("/api/light", json={"state": 1})
    assert response.status_code == 500
    print("✅ Test 8 PASSED: Adafruit error handling")

def test_get_status_error(mock_external_services):
    """Test 9: Handle get status errors"""
    mock_external_services['get_feed'].return_value = None
    
    response = client.get("/api/light/status")
    assert response.status_code == 500
    print("✅ Test 9 PASSED: Get status error handling")

if __name__ == "__main__":
    print("\n" + "="*50)
    print("Running IoT Light Control API Tests")
    print("="*50 + "\n")
    
    # Run tests
    pytest.main([
        __file__,
        "-v",
        "--tb=short",
        "-s"
    ])
    
    print("\n" + "="*50)
    print("Test Suite Complete")
    print("="*50 + "\n")
