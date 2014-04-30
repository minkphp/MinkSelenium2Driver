#!/usr/bin/env sh
set -e
set -x

sh -e /etc/init.d/xvfb start
export DISPLAY=:99.0
sleep 4

curl -L selenium-release.storage.googleapis.com/2.41/selenium-server-standalone-2.41.0.jar > selenium.jar
java -jar selenium.jar > /dev/null 2> /tmp/webdriver_output.txt &
