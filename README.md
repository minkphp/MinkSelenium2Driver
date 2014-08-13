Mink Selenium2 (webdriver) Driver
=================================
[![Latest Stable Version](https://poser.pugx.org/behat/mink-selenium2-driver/v/stable.svg)](https://packagist.org/packages/behat/mink-selenium2-driver)
[![Latest Unstable Version](https://poser.pugx.org/behat/mink-selenium2-driver/v/unstable.svg)](https://packagist.org/packages/behat/mink-selenium2-driver)
[![Total Downloads](https://poser.pugx.org/behat/mink-selenium2-driver/downloads.svg)](https://packagist.org/packages/behat/mink-selenium2-driver)
[![Build Status](https://travis-ci.org/Behat/MinkSelenium2Driver.svg?branch=master)](https://travis-ci.org/Behat/MinkSelenium2Driver)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/Behat/MinkSelenium2Driver/badges/quality-score.png?s=04d83b1e7471d2f60174b5ed17cd9dd3c9a0bc30)](https://scrutinizer-ci.com/g/Behat/MinkSelenium2Driver/)
[![Code Coverage](https://scrutinizer-ci.com/g/Behat/MinkSelenium2Driver/badges/coverage.png?s=abcab4bac44eed7d6e50879b7746e3d3d78e5d6c)](https://scrutinizer-ci.com/g/Behat/MinkSelenium2Driver/)
[![License](https://poser.pugx.org/behat/mink-selenium2-driver/license.svg)](https://packagist.org/packages/behat/mink-selenium2-driver)

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
        "behat/mink":                   "~1.5",
        "behat/mink-selenium2-driver":  "~1.1"
    }
}
```

``` bash
$> curl -sS http://getcomposer.org/installer | php
$> php composer.phar install
```

Copyright
---------

Copyright (c) 2012 Pete Otaqui <pete@otaqui.com>.

Maintainers
-----------

* Pete Otaqui [pete-otaqui](http://github.com/pete-otaqui)
