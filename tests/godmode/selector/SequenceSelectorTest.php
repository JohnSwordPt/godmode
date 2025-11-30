<?php

namespace tests\godmode\selector;

use godmode\core\BehaviorTask;
use godmode\selector\SequenceSelector;
use PHPUnit\Framework\TestCase;

class SequenceSelectorTest extends TestCase
{
    public function testSucceedsWhenAllChildrenSucceed()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::SUCCESS);
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->method('update')->willReturn(BehaviorTask::SUCCESS);

        $selector = new SequenceSelector();
        $selector->addTask($child1);
        $selector->addTask($child2);

        // First update: child1 succeeds, moves to child2, child2 succeeds -> Sequence succeeds
        // Note: The loop in SequenceSelector continues immediately if a child succeeds.
        $this->assertEquals(BehaviorTask::SUCCESS, $selector->update(0));
    }

    public function testFailsWhenOneChildFails()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::SUCCESS);
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->method('update')->willReturn(BehaviorTask::FAIL);
        
        $child3 = $this->createMock(BehaviorTask::class);
        $child3->expects($this->never())->method('update'); // Should not be reached

        $selector = new SequenceSelector();
        $selector->addTask($child1);
        $selector->addTask($child2);
        $selector->addTask($child3);

        $this->assertEquals(BehaviorTask::FAIL, $selector->update(0));
    }

    public function testReturnsRunningWhenChildIsRunning()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::SUCCESS);
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->method('update')->willReturn(BehaviorTask::RUNNING);
        
        $selector = new SequenceSelector();
        $selector->addTask($child1);
        $selector->addTask($child2);

        // First update: child1 succeeds, moves to child2, child2 returns RUNNING -> Sequence returns RUNNING
        $this->assertEquals(BehaviorTask::RUNNING, $selector->update(0));
    }

    public function testResumesFromRunningChild()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->expects($this->once())->method('update')->willReturn(BehaviorTask::SUCCESS); // Only called once
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->expects($this->exactly(2))->method('update')
            ->will($this->onConsecutiveCalls(BehaviorTask::RUNNING, BehaviorTask::SUCCESS));
        
        $selector = new SequenceSelector();
        $selector->addTask($child1);
        $selector->addTask($child2);

        // 1. child1 success, child2 running
        $this->assertEquals(BehaviorTask::RUNNING, $selector->update(0));

        // 2. child2 success -> Sequence success
        $this->assertEquals(BehaviorTask::SUCCESS, $selector->update(0));
    }
    
    public function testReset()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::SUCCESS);

        $child2 = $this->createMock(BehaviorTask::class);
        $child2->method('update')->willReturn(BehaviorTask::RUNNING);

        $selector = new SequenceSelector();
        $selector->addTask($child1);
        $selector->addTask($child2);

        $selector->update(0); // Returns RUNNING, child index is at 1 (child2)

        $selector->reset(); // Should reset index to 0 and deactivate current child

        // Next update should start from child1 again
        $child1->expects($this->once())->method('update')->willReturn(BehaviorTask::SUCCESS);
        
        $selector->update(0);
    }
}
