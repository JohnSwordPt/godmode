<?php

namespace tests\godmode\task;

use godmode\core\BehaviorTask;
use godmode\data\MutableEntry;
use godmode\task\RemoveEntryTask;
use PHPUnit\Framework\TestCase;

class RemoveEntryTaskTest extends TestCase
{
    public function testRemovesEntryAndSucceeds()
    {
        $entry = $this->createMock(MutableEntry::class);
        $entry->expects($this->once())->method('remove');

        $task = new RemoveEntryTask($entry);
        $this->assertEquals(BehaviorTask::SUCCESS, $task->update(0));
    }
}
