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
