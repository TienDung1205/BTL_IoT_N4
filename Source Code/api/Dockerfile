FROM python:3.11-slim

# Set working directory
WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    gcc \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements first for better caching
COPY requirements.txt .

# Install Python dependencies
RUN pip install --no-cache-dir -r requirements.txt

# Copy application code
COPY adafruit_api.py .

# Create non-root user
RUN useradd -m -u 1000 apiuser && chown -R apiuser:apiuser /app

# Switch to non-root user
USER apiuser

# Expose port
EXPOSE 8086

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD python -c "import requests; requests.get('http://localhost:8086/health', timeout=5)"

# Run application
CMD ["python", "-m", "uvicorn", "adafruit_api:app", "--host", "0.0.0.0", "--port", "8086"]
