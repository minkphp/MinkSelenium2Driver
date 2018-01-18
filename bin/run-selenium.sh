#!/usr/bin/env sh
set -e

echo '    Starting XVFB'
sh -e /etc/init.d/xvfb start
export DISPLAY=:99.0

echo '    Downloading selenium'
curl -L http://selenium-release.storage.googleapis.com/3.5/selenium-server-standalone-3.5.3.jar > selenium.jar
echo '    Running selenium'
java -jar selenium.jar -log /tmp/webdriver.log > /tmp/webdriver_output.txt 2>&1 &
