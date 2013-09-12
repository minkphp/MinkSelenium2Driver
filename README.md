Mink Selenium2 (webdriver) Driver
=================================

- [![Build Status](https://secure.travis-ci.org/Behat/MinkSelenium2Driver.png?branch=master)](http://travis-ci.org/Behat/MinkSelenium2Driver)

Usage Example
-------------

``` php
<?php

use Behat\Mink\Mink,
    Behat\Mink\Session,
    Behat\Mink\Driver\Selenium2Driver;

use Selenium\Client as SeleniumClient;

$url = 'http://example.com';

$mink = new Mink(array(
    'selenium2' => new Session(new Selenium2Driver($browser, null, $url)),
));

$mink->getSession('selenium2')->getPage()->findLink('Chat')->click();
```

Please refer to [MinkExtension-example](https://github.com/Behat/MinkExtension-example) for an executable example.

Installation
------------

``` json
{
    "require": {
        "behat/mink":                   "1.4.*",
        "behat/mink-selenium2-driver":  "1.0.*"
    }
}
```

``` bash
$> curl http://getcomposer.org/installer | php
$> php composer.phar install
```

Testing
-------

 1. Run `./composer.phar install --dev`
 2. Start a webserver: php -S 127.0.0.1:31337 -t vendor/behat/mink/tests/Behat/Mink/Driver/web-fixtures
 3. Copy `phpunit.xml.dist` to `phpunit.xml` and set `WEB_FIXTURES_HOST` to `http://127.0.0.1:31337`
 4. `phpunit --debug`

Copyright
---------

Copyright (c) 2012 Pete Otaqui <pete@otaqui.com>.

Maintainers
-----------

* Pete Otaqui [pete-otaqui](http://github.com/pete-otaqui)
