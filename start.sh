#!/bin/sh
set -e

# Start ChromeDriver in background
chromedriver --port=9515 --allowed-ips='' --allowed-origins='*' --whitelisted-ips='' --verbose &
CHROMEDRIVER_PID=$!
echo "[STARTUP] ChromeDriver started with PID: $CHROMEDRIVER_PID"

# Trap signals to kill ChromeDriver when container stops
trap "kill $CHROMEDRIVER_PID 2>/dev/null" EXIT INT TERM

# Wait for ChromeDriver to be ready
sleep 3

# Start PHP server in foreground (keeps container alive)
exec php -S 0.0.0.0:${PORT:-8080} index.php
