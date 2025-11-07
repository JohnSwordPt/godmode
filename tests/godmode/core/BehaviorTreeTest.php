<?php

namespace godmode\core;

use PHPUnit\Framework\TestCase;

class BehaviorTreeTest extends TestCase
{
    public function testUpdateCallsRootTaskUpdate()
    {
        // Create a mock for the BehaviorTask class.
        $rootTaskMock = $this->createMock(BehaviorTask::class);

        // Expect the update() method to be called exactly once.
        $rootTaskMock->expects($this->once())
                     ->method('update')
                     ->with(0.5); // We can even assert the argument it's called with.

        /** @var BehaviorTask $rootTaskMock */           
        $tree = new BehaviorTree($rootTaskMock);
        $tree->update(0.5);
    }

    public function testUpdateReturnsStatusFromRootTask()
    {
        $rootTaskMock = $this->createMock(BehaviorTask::class);

        // Configure the mock's update method to return a specific status.
        $rootTaskMock->method('update')
                     ->willReturn(BehaviorTask::SUCCESS);

        /** @var BehaviorTask $rootTaskMock */   
        $tree = new BehaviorTree($rootTaskMock);
        $status = $tree->update(0.16);

        $this->assertEquals(BehaviorTask::SUCCESS, $status);
    }

    public function testDebugModeGeneratesStatusString()
    {
        $task = new class extends BehaviorTask {
            public function description(): String
            {
                return 'Test Task';
            }
        };

        $tree = new BehaviorTree($task);
        $tree->debug = true;
        $tree->update(0.1);

        // The default updateTask returns SUCCESS
        $expectedStatusString = '[\"Test Task\" godmode\\core\\BehaviorTask]:SUCCESS';
        $this->assertStringContainsString('SUCCESS', $tree->treeStatus());
        $this->assertStringContainsString('Test Task', $tree->treeStatus());
    }

    public function testTreeStatusIsEmptyWhenDebugIsOff()
    {
        $task = new BehaviorTask();
        $tree = new BehaviorTree($task);
        $tree->debug = false; // Ensure debug is off
        $tree->update(0.1);

        $this->assertEmpty($tree->treeStatus());
    }
}
