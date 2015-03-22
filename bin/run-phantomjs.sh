#!/usr/bin/env sh
set -e

if [ "2" = "$PHANTOM_VERSION" ]; then
    mkdir travis-phantomjs
    wget https://s3.amazonaws.com/travis-phantomjs/phantomjs-2.0.0-ubuntu-12.04.tar.bz2 -O $PWD/travis-phantomjs/phantomjs-2.0.0-ubuntu-12.04.tar.bz2
    tar -xvf $PWD/travis-phantomjs/phantomjs-2.0.0-ubuntu-12.04.tar.bz2 -C $PWD/travis-phantomjs
    export PATH=$PWD/travis-phantomjs:$PATH
fi

phantomjs --version
echo '    Running PhantomJS'
phantomjs --webdriver=4444 > /tmp/webdriver_output.txt &
