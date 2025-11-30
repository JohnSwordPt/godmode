<?php

namespace tests\godmode\selector;

use godmode\core\BehaviorTask;
use godmode\selector\IterateSelector;
use PHPUnit\Framework\TestCase;

class IterateSelectorTest extends TestCase
{
    public function testRunsAllChildrenRegardlessOfStatus()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::SUCCESS);
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->method('update')->willReturn(BehaviorTask::FAIL);
        
        $child3 = $this->createMock(BehaviorTask::class);
        $child3->method('update')->willReturn(BehaviorTask::SUCCESS);

        $selector = new IterateSelector();
        $selector->addTask($child1);
        $selector->addTask($child2);
        $selector->addTask($child3);

        // Should run all in one go if they complete immediately
        $this->assertEquals(BehaviorTask::SUCCESS, $selector->update(0));
    }

    public function testStopsOnRunningChild()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::SUCCESS);
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->method('update')->willReturn(BehaviorTask::RUNNING);
        
        $child3 = $this->createMock(BehaviorTask::class);
        $child3->expects($this->never())->method('update');

        $selector = new IterateSelector();
        $selector->addTask($child1);
        $selector->addTask($child2);
        $selector->addTask($child3);

        $this->assertEquals(BehaviorTask::RUNNING, $selector->update(0));
    }

    public function testResumesFromRunningChild()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->expects($this->once())->method('update')->willReturn(BehaviorTask::SUCCESS);
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->expects($this->exactly(2))->method('update')
            ->will($this->onConsecutiveCalls(BehaviorTask::RUNNING, BehaviorTask::FAIL));
            
        $child3 = $this->createMock(BehaviorTask::class);
        $child3->expects($this->once())->method('update')->willReturn(BehaviorTask::SUCCESS);

        $selector = new IterateSelector();
        $selector->addTask($child1);
        $selector->addTask($child2);
        $selector->addTask($child3);

        // 1. Runs child1 (Success) -> child2 (Running)
        $this->assertEquals(BehaviorTask::RUNNING, $selector->update(0));

        // 2. Runs child2 (Fail) -> child3 (Success) -> Done
        $this->assertEquals(BehaviorTask::SUCCESS, $selector->update(0));
    }
}
