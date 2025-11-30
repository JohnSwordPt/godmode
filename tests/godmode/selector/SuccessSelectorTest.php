<?php

namespace tests\godmode\selector;

use godmode\core\BehaviorTask;
use godmode\selector\SuccessSelector;
use PHPUnit\Framework\TestCase;

class SuccessSelectorTest extends TestCase
{
    public function testSucceedsOnFirstSuccess()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::FAIL);
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->method('update')->willReturn(BehaviorTask::SUCCESS);
        
        $child3 = $this->createMock(BehaviorTask::class);
        $child3->expects($this->never())->method('update');

        $selector = new SuccessSelector();
        $selector->addTask($child1);
        $selector->addTask($child2);
        $selector->addTask($child3);

        $this->assertEquals(BehaviorTask::SUCCESS, $selector->update(0));
    }

    public function testReturnsRunningIfChildRunning()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::FAIL);
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->method('update')->willReturn(BehaviorTask::RUNNING);

        $selector = new SuccessSelector();
        $selector->addTask($child1);
        $selector->addTask($child2);

        $this->assertEquals(BehaviorTask::RUNNING, $selector->update(0));
    }

    public function testSucceedsEvenIfAllChildrenFail()
    {
        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::FAIL);
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->method('update')->willReturn(BehaviorTask::FAIL);

        $selector = new SuccessSelector();
        $selector->addTask($child1);
        $selector->addTask($child2);

        // Unlike PrioritySelector which would FAIL here, SuccessSelector returns SUCCESS
        $this->assertEquals(BehaviorTask::SUCCESS, $selector->update(0));
    }
}
