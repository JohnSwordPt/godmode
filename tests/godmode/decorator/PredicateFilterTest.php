<?php

namespace tests\godmode\decorator;

use godmode\core\BehaviorTask;
use godmode\decorator\PredicateFilter;
use godmode\pred\BehaviorPredicate;
use PHPUnit\Framework\TestCase;

class PredicateFilterTest extends TestCase
{
    private $childTask;
    private $predicate;

    public function testRunsChildWhenPredicateSucceeds()
    {
        $this->childTask = $this->createMock(BehaviorTask::class);
        $this->childTask->expects($this->once())->method('update')->willReturn(BehaviorTask::SUCCESS);

        $this->predicate = $this->createMock(BehaviorPredicate::class);
        $this->predicate->method('update')->willReturn(BehaviorTask::SUCCESS);

        $filter = new PredicateFilter($this->predicate, $this->childTask);
        $status = $filter->update(0);

        $this->assertEquals(BehaviorTask::SUCCESS, $status);
    }

    public function testReturnsFailureWhenPredicateFails()
    {
        $this->childTask = $this->createMock(BehaviorTask::class);
        $this->childTask->expects($this->never())->method('update');

        $this->predicate = $this->createMock(BehaviorPredicate::class);
        $this->predicate->method('update')->willReturn(BehaviorTask::FAIL);

        $filter = new PredicateFilter($this->predicate, $this->childTask);
        $status = $filter->update(0);

        $this->assertEquals(BehaviorTask::FAIL, $status);
    }
    
    public function testReturnsChildStatusWhenPredicateSucceeds()
    {
        $this->childTask = $this->createMock(BehaviorTask::class);
        $this->childTask->expects($this->once())->method('update')->willReturn(BehaviorTask::RUNNING);

        $this->predicate = $this->createMock(BehaviorPredicate::class);
        $this->predicate->method('update')->willReturn(BehaviorTask::SUCCESS);

        $filter = new PredicateFilter($this->predicate, $this->childTask);
        $status = $filter->update(0);

        $this->assertEquals(BehaviorTask::RUNNING, $status);
    }
}
