<?php

namespace Behat\Mink\Tests\Driver\Custom;

use Behat\Mink\Tests\Driver\TestCase;

class SelectTest extends TestCase
{
    public function testSetValueToSelectField()
    {
        $this->getSession()->visit($this->pathTo('/multiselect_form.html'));
        $select = $this->getSession()->getPage()->find('xpath', '//select[@name="select_number"]');
        $select->setValue('thirty');
        $this->assertEquals('30', $select->getValue());
    }
}
