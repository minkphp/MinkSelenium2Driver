#!/usr/bin/env sh
set -e

echo '    Downloading selenium'
docker pull selenium/standalone-firefox:latest
echo '    Running selenium'
docker run -d -p 4444:4444 --network=host selenium/standalone-firefox:latest