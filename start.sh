#!/bin/sh

echo "[STARTUP] Starting ChromeDriver on port 9515..."
nohup chromedriver --port=9515 --allowed-ips='' --allowed-origins='*' --whitelisted-ips='' > /tmp/chromedriver.log 2>&1 &
CHROMEDRIVER_PID=$!
echo "[STARTUP] ChromeDriver PID: $CHROMEDRIVER_PID"

sleep 3

echo "[STARTUP] Checking if ChromeDriver is accessible..."
if curl -s http://localhost:9515/status > /dev/null 2>&1; then
    echo "[STARTUP] ChromeDriver is running!"
else
    echo "[STARTUP] WARNING: ChromeDriver status check failed, but continuing..."
    cat /tmp/chromedriver.log
fi

echo "[STARTUP] Starting PHP server on port $PORT..."
exec php -S 0.0.0.0:$PORT index.php
