#!/usr/bin/env sh
set -e

echo '    Starting XVFB'
sh -e /etc/init.d/xvfb start
export DISPLAY=:99.0

echo '    Downloading selenium'
curl -L http://selenium-release.storage.googleapis.com/2.53/selenium-server-standalone-2.53.1.jar > selenium.jar
echo '    Running selenium'
java -jar selenium.jar -log /tmp/webdriver.log > /tmp/webdriver_output.txt 2>&1 &
