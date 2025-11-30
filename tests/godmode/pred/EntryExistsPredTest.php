<?php

namespace tests\godmode\pred;

use godmode\data\Entry;
use godmode\pred\EntryExistsPred;
use PHPUnit\Framework\TestCase;

class EntryExistsPredTest extends TestCase
{
    public function testReturnsTrueIfEntryExists()
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('exists')->willReturn(true);

        $pred = new EntryExistsPred($entry);
        $this->assertTrue($pred->evaluate());
    }

    public function testReturnsFalseIfEntryDoesNotExist()
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('exists')->willReturn(false);

        $pred = new EntryExistsPred($entry);
        $this->assertFalse($pred->evaluate());
    }
}
