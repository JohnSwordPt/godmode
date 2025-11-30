<?php

namespace tests\godmode\selector;

use godmode\core\BehaviorTask;
use godmode\selector\PrioritySelector;
use PHPUnit\Framework\TestCase;

class PrioritySelectorTest extends TestCase
{
    public function testSucceedsIfFirstChildSucceeds()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::SUCCESS);
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->expects($this->never())->method('update');

        $selector = new PrioritySelector();
        $selector->addTask($child1);
        $selector->addTask($child2);

        $this->assertEquals(BehaviorTask::SUCCESS, $selector->update(0));
    }

    public function testFallsBackToSecondChildIfFirstFails()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::FAIL);
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->method('update')->willReturn(BehaviorTask::SUCCESS);

        $selector = new PrioritySelector();
        $selector->addTask($child1);
        $selector->addTask($child2);

        $this->assertEquals(BehaviorTask::SUCCESS, $selector->update(0));
    }

    public function testReturnsRunningIfChildRuns()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::FAIL);
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->method('update')->willReturn(BehaviorTask::RUNNING);
        
        $selector = new PrioritySelector();
        $selector->addTask($child1);
        $selector->addTask($child2);

        $this->assertEquals(BehaviorTask::RUNNING, $selector->update(0));
    }

    public function testInterruptionByHigherPriorityTask()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child2 = $this->createMock(BehaviorTask::class);

        $selector = new PrioritySelector();
        $selector->addTask($child1);
        $selector->addTask($child2);

        // 1. First update: child1 fails, child2 runs
        $child1->method('update')->willReturnOnConsecutiveCalls(BehaviorTask::FAIL, BehaviorTask::SUCCESS);
        $child2->method('update')->willReturn(BehaviorTask::RUNNING);

        $this->assertEquals(BehaviorTask::RUNNING, $selector->update(0));

        // 2. Second update: child1 succeeds. It should interrupt child2.
        // Expect child2 to be deactivated.
        $child2->expects($this->once())->method('deactivate');

        $this->assertEquals(BehaviorTask::SUCCESS, $selector->update(0));
    }
}
