1.4.0 / 2019-08-28
==================

###### Changes
* Dropping support for PHP 5.3
* Updated documentation about testing
* Syn library is [0.0.2](https://github.com/bitovi/syn/tree/v0.0.2)
* `setDesiredCapabilities` will throw an exception if you will try to set capabilities on already started session
* Added `getDesiredCapabilities` to allow fetch capabilities
* Minors improvements to driver when running against W3C compatible driver
 
###### Testsuite
* Adding testing on PHP 7.1, 7.2, 7.3 and 7.4beta

###### Why it's a minor version bump

In order to understand why we've bumped minor version of a library please refer to documentation on SemVer

https://semver.org/#what-should-i-do-if-i-update-my-own-dependencies-without-changing-the-public-api

As another example please refer to doctrine-project: https://www.doctrine-project.org/2017/07/25/php-7.1-requirement-and-composer.html


1.3.1 / 2016-03-05
==================

Bug fixes:

* Fixed the handling of cookies with semicolon in the value

Testsuite:

* Add testing on PHP 7

1.3.0 / 2015-09-21
==================

New features:

* Updated the driver to use findElementsXpaths for Mink 1.7 and forward compatibility with Mink 2

Testsuite:

* Fixed the window name test for the chrome driver
* Add testing on PhantomJS 2

Misc:

* Updated the repository structure to PSR-4

1.2.0 / 2014-09-29
==================

BC break:

* Changed the behavior of `getValue` for checkboxes according to the BC break in Mink 1.6

New features:

* Added the support of the `chromeOptions` argument in capabilities
* Added the support of select elements in `setValue`
* Added the support of checbox and radio elements in `setValue`
* Added the support of HTML5 input types in `setValue` (for those supported by WebDriver itself)
* Added `getWebDriverSessionId` to get the WebDriver session id
* Added a way to configure the webdriver timeouts
* Implemented `getOuterHtml`
* Implemented `getWindowNames` and `getWindowName`
* Implemented `maximizeWindow`
* Implemented `submitForm`
* Implemented `isSelected`

Bug fixes:

* Fixed the selection of options for radio groups
* Fixed `getValue` for radio groups
* Fixed the selection of options for multiple selects to ensure the change event is triggered only once
* Fixed mouse interactions to use the webDriver API rather than using JS and emulating events
* Fixed duplicate change events being triggered when setting the value
* Fixed the code to throw exceptions for invalid usages of the driver
* Fixed the implementation of `mouseOver`
* Fixed `evaluateScript` and `executeScript` to support all syntaxes required by the Mink API
* Fixed the retrieval of HTML attributes in `getAttribute`
* Fixed form interactions to use the webDriver API rather than using JS and emulating change events
* Fixed the clearing of the value when the caret is at the beginning of the field in `setValue`

Testing:

* Updated the testsuite to use the new Mink 1.6 driver testsuite
* Added testing on HHVM
