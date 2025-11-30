<?php

namespace tests\godmode\pred;

use godmode\pred\ConstPredicate;
use PHPUnit\Framework\TestCase;

class ConstPredicateTest extends TestCase
{
    public function testReturnsTrueForTrue()
    {
        $pred = new ConstPredicate(true);
        $this->assertTrue($pred->evaluate());
    }

    public function testReturnsFalseForFalse()
    {
        $pred = new ConstPredicate(false);
        $this->assertFalse($pred->evaluate());
    }
}
