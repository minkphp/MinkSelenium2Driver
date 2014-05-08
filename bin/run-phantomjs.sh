#!/usr/bin/env sh
set -e

phantomjs --version
echo '    Running PhantomJS'
phantomjs --webdriver=4444 > /tmp/webdriver_output.txt &
