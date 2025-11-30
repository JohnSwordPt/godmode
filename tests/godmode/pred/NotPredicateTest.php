<?php

namespace tests\godmode\pred;

use godmode\pred\BehaviorPredicate;
use godmode\pred\NotPredicate;
use PHPUnit\Framework\TestCase;

class NotPredicateTest extends TestCase
{
    public function testReturnsFalseIfChildTrue()
    {
        $p1 = $this->createMock(BehaviorPredicate::class);
        $p1->method('evaluate')->willReturn(true);
        
        $not = new NotPredicate($p1);

        $this->assertFalse($not->evaluate());
    }

    public function testReturnsTrueIfChildFalse()
    {
        $p1 = $this->createMock(BehaviorPredicate::class);
        $p1->method('evaluate')->willReturn(false);
        
        $not = new NotPredicate($p1);

        $this->assertTrue($not->evaluate());
    }
}
