<?php

namespace tests\godmode\task;

use godmode\core\BehaviorTask;
use godmode\task\FunctionTask;
use PHPUnit\Framework\TestCase;

class FunctionTaskTest extends TestCase
{
    public function testExecutesClosureAndReturnsStatus()
    {
        $task = new FunctionTask(function($dt) {
            return BehaviorTask::FAIL;
        });

        $this->assertEquals(BehaviorTask::FAIL, $task->update(0));
    }

    public function testReturnsSuccessIfClosureReturnsNonInt()
    {
        $task = new FunctionTask(function($dt) {
            return "some string";
        });

        $this->assertEquals(BehaviorTask::SUCCESS, $task->update(0));
    }
}
