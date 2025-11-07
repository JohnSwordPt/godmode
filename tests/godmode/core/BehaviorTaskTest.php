<?php

namespace godmode\core;

use PHPUnit\Framework\TestCase;

class BehaviorTaskTest extends TestCase
{
    public function testUpdateReturnsSuccessByDefault()
    {
        $task = new BehaviorTask();
        $this->assertEquals(BehaviorTask::SUCCESS, $task->update(0.0));
    }

    public function testDescriptionReturnsClassName()
    {
        $task = new BehaviorTask();
        $this->assertEquals('godmode\core\BehaviorTask', $task->description());
    }

    public function testDescriptionReturnsNameWhenSet()
    {
        $task = new BehaviorTask();
        $task->_name = 'Test Task';
        $this->assertEquals('"Test Task" godmode\core\BehaviorTask', $task->description());
    }

    public function testUpdateCallsUpdateTask()
    {
        // Create a stub for the BehaviorTask class.
        $task = new class extends BehaviorTask {
            protected function updateTask(float $dt): int
            {
                return BehaviorTask::RUNNING;
            }
        };

        $this->assertEquals(BehaviorTask::RUNNING, $task->update(0.0));
    }

    public function testDeactivateCallsDeactivateInternal()
    {
        $task = new class extends BehaviorTask {
            public $deactivateInternalCalled = false;

            protected function deactivateInternal(): void
            {
                $this->deactivateInternalCalled = true;
            }
        };

        $task->deactivate();
        $this->assertTrue($task->deactivateInternalCalled);
    }
}
