<?php

namespace tests\godmode\pred;

use godmode\pred\FunctionPredicate;
use PHPUnit\Framework\TestCase;

class FunctionPredicateTest extends TestCase
{
    public function testReturnsResultOfClosure()
    {
        $pred = new FunctionPredicate(function() {
            return true;
        });
        $this->assertTrue($pred->evaluate());

        $pred = new FunctionPredicate(function() {
            return false;
        });
        $this->assertFalse($pred->evaluate());
    }
    
    public function testReturnsFalseIfCallableInvalid()
    {
        // Passing a non-callable
        $pred = new FunctionPredicate("not_a_function");
        $this->assertFalse($pred->evaluate());
    }
}
