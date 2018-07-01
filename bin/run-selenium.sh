#!/usr/bin/env sh
set -e

echo '    Starting XVFB'
sh -e /etc/init.d/xvfb start
export DISPLAY=:99.0

echo '    Downloading selenium'
curl -L https://selenium-release.storage.googleapis.com/3.13/selenium-server-standalone-3.13.0.jar > selenium.jar
curl -L https://chromedriver.storage.googleapis.com/2.40/chromedriver_linux64.zip > chromedriver_linux64.zip
unzip chromedriver_linux64.zip
echo '    Running selenium'
java -jar selenium.jar -log /tmp/webdriver.log > /tmp/webdriver_output.txt 2>&1 &
