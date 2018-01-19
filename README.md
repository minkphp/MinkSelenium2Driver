# Mink Facebook WebDriver extension

Currently forked from [https://github.com/minkphp/MinkSelenium2Driver/] and updated to use
the [facebook php webdriver](https://github.com/facebook/php-webdriver).

See [https://github.com/minkphp/MinkSelenium2Driver/issues/254] for the status of selenium 3 support.

Major updates include:

 - Switch to using facebook/webdriver
 - Selenium optional, can use chromedriver (or other jsonwire protocol servers instead)
 - Default to `chrome` instead of `firefox`
 - Update minimum php version to 5.6

## Using the Facebook WebDriver with behat

Subclass `Behat\MinkExtension\ServiceContainer\MinkExtension` and add the new driver factory.

```php
<?php

namespace SilverStripe\BehatExtension;

use Behat\MinkExtension\ServiceContainer\MinkExtension as BaseMinkExtension;
use SilverStripe\MinkFacebookWebDriver\FacebookFactory;

class MinkExtension extends BaseMinkExtension
{
    public function __construct()
    {
        parent::__construct();
        $this->registerDriverFactory(new FacebookFactory());
    }
}
```

Add this extension to your `behat.yml` (see below)
 
## Running chromedriver instead of selenium

Make sure you install chromedriver and have the service running

```
$ brew install chromedriver
$ chromedriver
Starting ChromeDriver 2.34.522932 (4140ab217e1ca1bec0c4b4d1b148f3361eb3a03e) on port 9515
Only local connections are allowed.
```

Set the wb_host to this server instead (substitute `SilverStripe\BehatExtension\MinkExtension`
for your class).

```
default:
  suites: []
  extensions:
    SilverStripe\BehatExtension\MinkExtension:
      default_session: facebook_web_driver
      javascript_session: facebook_web_driver
      facebook_web_driver:
        browser: chrome
        wd_host: "http://127.0.0.1:9515" #chromedriver port
```

## Maintainers

* Damian Mooyman [tractorcow](https://github.com/tractorcow)

Credit to original maintainers of MinkSelenium2Driver

* Christophe Coevoet [stof](https://github.com/stof)
* Pete Otaqui [pete-otaqui](http://github.com/pete-otaqui)

## License

MIT License

Copyright (c) 2012 Pete Otaqui <pete@otaqui.com>.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
