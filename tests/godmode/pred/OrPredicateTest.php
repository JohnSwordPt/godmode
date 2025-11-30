<?php

namespace tests\godmode\pred;

use godmode\pred\BehaviorPredicate;
use godmode\pred\OrPredicate;
use PHPUnit\Framework\TestCase;

class OrPredicateTest extends TestCase
{
    public function testReturnsTrueIfAnyChildTrue()
    {
        $p1 = $this->createMock(BehaviorPredicate::class);
        $p1->method('evaluate')->willReturn(false);
        
        $p2 = $this->createMock(BehaviorPredicate::class);
        $p2->method('evaluate')->willReturn(true);

        $or = new OrPredicate();
        $or->addPred($p1);
        $or->addPred($p2);

        $this->assertTrue($or->evaluate());
    }

    public function testReturnsFalseIfAllChildrenFalse()
    {
        $p1 = $this->createMock(BehaviorPredicate::class);
        $p1->method('evaluate')->willReturn(false);
        
        $p2 = $this->createMock(BehaviorPredicate::class);
        $p2->method('evaluate')->willReturn(false);

        $or = new OrPredicate();
        $or->addPred($p1);
        $or->addPred($p2);

        $this->assertFalse($or->evaluate());
    }

    public function testReturnsFalseIfNoChildren()
    {
        $or = new OrPredicate();
        $this->assertFalse($or->evaluate()); // Identity for OR
    }

    public function testEvaluateStopsOnFirstTrue()
    {
        $p1 = $this->createMock(BehaviorPredicate::class);
        $p1->method('evaluate')->willReturn(true);
        
        $p2 = $this->createMock(BehaviorPredicate::class);
        $p2->expects($this->never())->method('evaluate');

        $or = new OrPredicate();
        $or->addPred($p1);
        $or->addPred($p2);

        $this->assertTrue($or->evaluate());
    }
}
