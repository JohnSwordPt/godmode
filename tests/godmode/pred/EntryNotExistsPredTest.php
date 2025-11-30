<?php

namespace tests\godmode\pred;

use godmode\data\Entry;
use godmode\pred\EntryNotExistsPred;
use PHPUnit\Framework\TestCase;

class EntryNotExistsPredTest extends TestCase
{
    public function testReturnsTrueIfEntryDoesNotExist()
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('exists')->willReturn(false);

        $pred = new EntryNotExistsPred($entry);
        $this->assertTrue($pred->evaluate());
    }

    public function testReturnsFalseIfEntryExists()
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('exists')->willReturn(true);

        $pred = new EntryNotExistsPred($entry);
        $this->assertFalse($pred->evaluate());
    }
}
