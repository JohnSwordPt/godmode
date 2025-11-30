<?php

namespace tests\godmode\pred;

use godmode\pred\AndPredicate;
use godmode\pred\BehaviorPredicate;
use PHPUnit\Framework\TestCase;

class AndPredicateTest extends TestCase
{
    public function testReturnsTrueIfAllChildrenTrue()
    {
        $p1 = $this->createMock(BehaviorPredicate::class);
        $p1->method('evaluate')->willReturn(true);
        
        $p2 = $this->createMock(BehaviorPredicate::class);
        $p2->method('evaluate')->willReturn(true);

        $and = new AndPredicate();
        $and->addPred($p1);
        $and->addPred($p2);

        $this->assertTrue($and->evaluate());
    }

    public function testReturnsFalseIfAnyChildFalse()
    {
        $p1 = $this->createMock(BehaviorPredicate::class);
        $p1->method('evaluate')->willReturn(true);
        
        $p2 = $this->createMock(BehaviorPredicate::class);
        $p2->method('evaluate')->willReturn(false);

        $and = new AndPredicate();
        $and->addPred($p1);
        $and->addPred($p2);

        $this->assertFalse($and->evaluate());
    }

    public function testReturnsTrueIfNoChildren()
    {
        $and = new AndPredicate();
        $this->assertTrue($and->evaluate()); // Default loop over empty array returns true (identity for AND)
    }

    public function testEvaluateStopsOnFirstFalse()
    {
        $p1 = $this->createMock(BehaviorPredicate::class);
        $p1->method('evaluate')->willReturn(false);
        
        $p2 = $this->createMock(BehaviorPredicate::class);
        $p2->expects($this->never())->method('evaluate');

        $and = new AndPredicate();
        $and->addPred($p1);
        $and->addPred($p2);

        $this->assertFalse($and->evaluate());
    }
}
