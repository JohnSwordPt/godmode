<?php

namespace tests\godmode\task;

use godmode\core\BehaviorTask;
use godmode\data\Entry;
use godmode\task\DelayTask;
use PHPUnit\Framework\TestCase;

class DelayTaskTest extends TestCase
{
    public function testWaitsForSpecifiedTime()
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('value')->willReturn(100.0); // Delay 100ms

        $task = new DelayTask($entry);

        // 1. First update (dt=0). elapsed=0. thisTime=100. returns RUNNING.
        $this->assertEquals(BehaviorTask::RUNNING, $task->update(0));

        // 2. Second update (dt=50). elapsed=50. returns RUNNING.
        $this->assertEquals(BehaviorTask::RUNNING, $task->update(50));

        // 3. Third update (dt=51). elapsed=101. returns SUCCESS.
        $this->assertEquals(BehaviorTask::SUCCESS, $task->update(51));
    }

    public function testResetsCorrectly()
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('value')->willReturn(10.0);

        $task = new DelayTask($entry);

        // Run to completion
        $task->update(0);
        $task->update(11); // Success

        // Reset via StatefulBehaviorTask mechanics or manual
        // Note: StatefulBehaviorTask automatically calls reset() on SUCCESS.
        
        // Next update should start fresh
        $this->assertEquals(BehaviorTask::RUNNING, $task->update(0));
    }
}
