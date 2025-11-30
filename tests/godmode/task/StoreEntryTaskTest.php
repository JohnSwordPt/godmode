<?php

namespace tests\godmode\task;

use godmode\core\BehaviorTask;
use godmode\data\MutableEntry;
use godmode\task\StoreEntryTask;
use PHPUnit\Framework\TestCase;

class StoreEntryTaskTest extends TestCase
{
    public function testStoresValueAndSucceeds()
    {
        $entry = $this->createMock(MutableEntry::class);
        $entry->expects($this->once())
            ->method('store')
            ->with('myValue');

        $task = new StoreEntryTask($entry, 'myValue');
        $this->assertEquals(BehaviorTask::SUCCESS, $task->update(0));
    }
}
