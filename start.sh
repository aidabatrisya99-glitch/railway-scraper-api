#!/bin/bash
# Start ChromeDriver in the background
chromedriver --port=9515 --allowed-ips='' --allowed-origins='*' --whitelisted-ips='' > /tmp/chromedriver.log 2>&1 &

# Wait for ChromeDriver to be ready
echo "Waiting for ChromeDriver to start..."
for i in {1..10}; do
    if curl -s http://localhost:9515/status > /dev/null 2>&1; then
        echo "ChromeDriver is ready!"
        break
    fi
    echo "Waiting... ($i/10)"
    sleep 1
done

# Check if ChromeDriver is actually running
if curl -s http://localhost:9515/status > /dev/null 2>&1; then
    echo "ChromeDriver successfully started on port 9515"
else
    echo "ERROR: ChromeDriver failed to start!"
    exit 1
fi

# Start PHP server
echo "Starting PHP server on port $PORT..."
php -S 0.0.0.0:$PORT index.php
