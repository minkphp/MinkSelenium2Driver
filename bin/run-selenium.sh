#!/usr/bin/env bash
set -e

echo '    Starting XVFB'
sh -e /etc/init.d/xvfb start
export DISPLAY=:99.0

echo '    Downloading selenium'
if [ "${SELENIUM_VERSION:-3}" = "2" ]; then
    curl -L http://selenium-release.storage.googleapis.com/2.53/selenium-server-standalone-2.53.0.jar > selenium.jar
else
    curl -L https://selenium-release.storage.googleapis.com/3.14/selenium-server-standalone-3.14.0.jar > selenium.jar
fi;
curl -L -O https://chromedriver.storage.googleapis.com/2.42/chromedriver_linux64.zip
unzip chromedriver_linux64.zip
echo '    Running selenium'
java -Dwebdriver.chrome.driver="$PWD/chromedriver" -jar selenium.jar -log /tmp/webdriver.log > /tmp/webdriver_output.txt 2>&1 &
