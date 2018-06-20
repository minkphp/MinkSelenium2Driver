#!/usr/bin/env sh
set -e

echo '    Downloading selenium'
docker pull selenium/standalone-firefox:2.53.1
echo '    Running selenium'
docker run -d -p 4444:4444 --network=host selenium/standalone-firefox:2.53.1
