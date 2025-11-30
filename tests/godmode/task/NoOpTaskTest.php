<?php

namespace tests\godmode\task;

use godmode\core\BehaviorTask;
use godmode\task\NoOpTask;
use PHPUnit\Framework\TestCase;

class NoOpTaskTest extends TestCase
{
    public function testReturnsConfiguredStatus()
    {
        $task = new NoOpTask(BehaviorTask::SUCCESS);
        $this->assertEquals(BehaviorTask::SUCCESS, $task->update(0));

        $task = new NoOpTask(BehaviorTask::FAIL);
        $this->assertEquals(BehaviorTask::FAIL, $task->update(0));

        $task = new NoOpTask(BehaviorTask::RUNNING);
        $this->assertEquals(BehaviorTask::RUNNING, $task->update(0));
    }
}
