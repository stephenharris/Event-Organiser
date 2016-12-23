#!/usr/bin/env bash

WP_CORE_DIR=/tmp/wordpress/

# Used when waiting for stuff
NAP_LENGTH=1
SELENIUM_PORT=4444

# Wait for a specific port to respond to connections.
wait_for_port() {
    local PORT=$1
    while echo | telnet localhost $PORT 2>&1 | grep -qe 'Connection refused'; do
        echo "Connection refused on port $PORT. Waiting $NAP_LENGTH seconds..."
        sleep $NAP_LENGTH
    done
}

rm -f /tmp/.X0-lock

Xvfb & export DISPLAY=localhost:0.0

echo 'start php';
php -S localhost:8000 -t $WP_CORE_DIR -d disable_functions=mail > /dev/null 2>&1 &

# Start Selenium
wget http://selenium-release.storage.googleapis.com/2.53/selenium-server-standalone-2.53.0.jar
java -jar selenium-server-standalone-2.53.0.jar -p $SELENIUM_PORT > /dev/null 2>&1 &

# Wait for Selenium, if necessary
wait_for_port $SELENIUM_PORT

echo 'waiting to start tests...';
sleep 5
