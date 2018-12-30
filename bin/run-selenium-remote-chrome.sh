#!/usr/bin/env sh
set -e

echo '    Running selenium'
docker run -d -p 4444:4444 --network=host selenium/standalone-chrome:latest
