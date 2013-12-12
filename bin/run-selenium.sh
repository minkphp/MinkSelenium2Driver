#!/usr/bin/env sh
set -e

sh -e /etc/init.d/xvfb start
export DISPLAY=:99.0
sleep 4

curl http://selenium.googlecode.com/files/selenium-server-standalone-2.37.0.jar > selenium.jar
java -jar selenium.jar > /tmp/webdriver_output.txt &
