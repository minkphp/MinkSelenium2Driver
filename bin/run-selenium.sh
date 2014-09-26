#!/usr/bin/env sh
set -e

echo '    Starting XVFB'
sh -e /etc/init.d/xvfb start
export DISPLAY=:99.0
sleep 4

echo '    Downloading selenium'
curl -L http://selenium-release.storage.googleapis.com/2.43/selenium-server-standalone-2.43.1.jar > selenium.jar
echo '    Running selenium'
java -jar selenium.jar > /dev/null 2>&1 &
