<?php

namespace tests\godmode\selector;

use godmode\core\BehaviorTask;
use godmode\selector\ParallelSelector;
use PHPUnit\Framework\TestCase;

class ParallelSelectorTest extends TestCase
{
    public function testAllSuccessModeSucceedsOnlyWhenAllSucceed()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::SUCCESS);
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->method('update')->willReturn(BehaviorTask::SUCCESS);

        $selector = new ParallelSelector(ParallelSelector::ALL_SUCCESS);
        $selector->addTask($child1);
        $selector->addTask($child2);

        $this->assertEquals(BehaviorTask::SUCCESS, $selector->update(0));
    }

    public function testAllSuccessModeFailsIfAnyFails()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::SUCCESS);
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->method('update')->willReturn(BehaviorTask::FAIL);

        $selector = new ParallelSelector(ParallelSelector::ALL_SUCCESS);
        $selector->addTask($child1);
        $selector->addTask($child2);

        $this->assertEquals(BehaviorTask::FAIL, $selector->update(0));
    }

    public function testAllSuccessModeRunsUntilAllDone()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::SUCCESS);
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->method('update')->willReturn(BehaviorTask::RUNNING);

        $selector = new ParallelSelector(ParallelSelector::ALL_SUCCESS);
        $selector->addTask($child1);
        $selector->addTask($child2);

        $this->assertEquals(BehaviorTask::RUNNING, $selector->update(0));
    }
    
    public function testAnySuccessModeSucceedsIfAnySucceeds()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::RUNNING);
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->method('update')->willReturn(BehaviorTask::SUCCESS);

        $selector = new ParallelSelector(ParallelSelector::ANY_SUCCESS);
        $selector->addTask($child1);
        $selector->addTask($child2);

        $this->assertEquals(BehaviorTask::SUCCESS, $selector->update(0));
    }
    
    public function testResetDeactivatesChildren()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::RUNNING);
        $child1->expects($this->once())->method('deactivate'); // Expectation: child should be deactivated on reset

        $selector = new ParallelSelector(ParallelSelector::ALL_SUCCESS);
        $selector->addTask($child1);

        $selector->update(0); // Runs child
        $selector->deactivate(); // Should trigger reset -> deactivate child
    }
}