#!/usr/bin/env sh
set -e
curl http://selenium.googlecode.com/files/selenium-server-standalone-2.37.0.jar > selenium.jar
java -jar selenium.jar > /dev/null &
