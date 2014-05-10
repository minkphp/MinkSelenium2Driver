<?php

namespace Behat\Mink\Tests\Driver\Custom;

use Behat\Mink\Tests\Driver\TestCase;

// TODO move these tests upstream
class ExtraTest extends TestCase
{
    public function testIssue178()
    {
        $session = $this->getSession();
        $session->visit($this->pathTo('/issue178.html'));

        $session->getPage()->findById('source')->setValue('foo');
        $this->assertEquals('foo', $session->getPage()->findById('target')->getText());
    }

    public function testIssue215()
    {
        $session = $this->getSession();
        $session->visit($this->pathTo('/issue215.html'));

        $this->assertContains("foo\nbar", $session->getPage()->findById('textarea')->getValue());
    }
}
