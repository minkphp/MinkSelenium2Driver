#!/usr/bin/env sh
set -e

echo '    Running selenium'
# do not use newer version, firefox is following w3c much faster then drivers
docker run -d -p 4444:4444 --network=host selenium/standalone-firefox:2.53.1
