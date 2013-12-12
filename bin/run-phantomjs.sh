#!/usr/bin/env sh
set -e

phantomjs --version
phantomjs --webdriver=4444 > /tmp/webdriver_output.txt &
