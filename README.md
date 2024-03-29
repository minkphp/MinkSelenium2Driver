Mink Selenium2 (webdriver) Driver
=================================
[![Latest Stable Version](https://poser.pugx.org/behat/mink-selenium2-driver/v/stable.svg)](https://packagist.org/packages/behat/mink-selenium2-driver)
[![Latest Unstable Version](https://poser.pugx.org/behat/mink-selenium2-driver/v/unstable.svg)](https://packagist.org/packages/behat/mink-selenium2-driver)
[![Total Downloads](https://poser.pugx.org/behat/mink-selenium2-driver/downloads.svg)](https://packagist.org/packages/behat/mink-selenium2-driver)
[![CI](https://github.com/minkphp/MinkSelenium2Driver/actions/workflows/tests.yml/badge.svg)](https://github.com/minkphp/MinkSelenium2Driver/actions/workflows/tests.yml)
[![License](https://poser.pugx.org/behat/mink-selenium2-driver/license.svg)](https://packagist.org/packages/behat/mink-selenium2-driver)
[![codecov](https://codecov.io/gh/minkphp/MinkSelenium2Driver/branch/master/graph/badge.svg?token=x2Q2iM3XYz)](https://codecov.io/gh/minkphp/MinkSelenium2Driver)

Usage Example
-------------

``` php
<?php

use Behat\Mink\Mink,
    Behat\Mink\Session,
    Behat\Mink\Driver\Selenium2Driver;

require_once __DIR__ . '/vendor/autoload.php';

$browserName = 'firefox';
$url = 'http://example.com';

$mink = new Mink(array(
    'selenium2' => new Session(new Selenium2Driver($browserName)),
));

$session = $mink->getSession('selenium2');
$session->visit($url);

$session->getPage()->findLink('Chat')->click();
```

Please refer to [MinkExtension-example](https://github.com/Behat/MinkExtension-example) for an executable example.

Installation
------------

``` json
{
    "require": {
        "behat/mink":                   "~1.5",
        "behat/mink-selenium2-driver":  "~1.1"
    }
}
```

``` bash
$> curl -sS https://getcomposer.org/installer | php
$> php composer.phar install
```

Testing
------------

1. Start WebDriver
    1. If you have Docker installed, run
    ```bash
    docker run -p 4444:4444 selenium/standalone-firefox:2.53.1
    ```
    2. If you do not have Docker, but you have Java
    ```bash
    curl -L https://selenium-release.storage.googleapis.com/2.53/selenium-server-standalone-2.53.1.jar > selenium-server-standalone-2.53.1.jar
    java -jar selenium-server-standalone-2.53.1.jar
    ```
2. Start WebServer by running
    ``` bash
    ./vendor/bin/mink-test-server
    ```
3. Start PhpUnit
    ```bash
    composer require --dev phpunit/phpunit
    ./vendor/bin/phpunit -v --coverage-clover=coverage.clover
    ```

Copyright
---------

Copyright (c) 2012 Pete Otaqui <pete@otaqui.com>.

Maintainers
-----------

* Christophe Coevoet [stof](https://github.com/stof)
* Pete Otaqui [pete-otaqui](https://github.com/pete-otaqui)
* Alexander Obuhovich [aik099](https://github.com/aik099)
