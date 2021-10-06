<?php

namespace Behat\Mink\Tests\Driver\Custom;

use Behat\Mink\Tests\Driver\TestCase;
use Yoast\PHPUnitPolyfills\Polyfills\AssertIsType;

class WindowNameTest extends TestCase
{
    use AssertIsType;

    public function testWindowNames()
    {
        $session = $this->getSession();
        $session->start();

        $windowNames = $session->getWindowNames();
        $this->assertArrayHasKey(0, $windowNames);

        $windowName = $session->getWindowName();

        $this->assertIsString($windowName);
        $this->assertContains($windowName, $windowNames, 'The current window name is one of the available window names.');
    }
}
