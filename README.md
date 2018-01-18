Mink Selenium3 (webdriver) Driver
=================================

Currently forked from https://github.com/minkphp/MinkSelenium2Driver/, docs forthcoming.

See [https://github.com/minkphp/MinkSelenium2Driver/issues/254] for the status of selenium 3 support.

Major updates include:

 - Switch to using facebook/webdriver
 - Support selenium 3 (only)
 - Default to `chrome` instead of `firefox`
 - Update minimum php version to 5.6
 
Running Chrome
--------------

Make sure you install chromedriver and have the service running

```
$ brew install chromedriver
$ chromedriver
Starting ChromeDriver 2.34.522932 (4140ab217e1ca1bec0c4b4d1b148f3361eb3a03e) on port 9515
Only local connections are allowed.
```

Startup selenium

Copyright
---------

Copyright (c) 2012 Pete Otaqui <pete@otaqui.com>.

Maintainers
-----------

* Christophe Coevoet [stof](https://github.com/stof)
* Pete Otaqui [pete-otaqui](http://github.com/pete-otaqui)
