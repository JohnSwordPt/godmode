<?php

namespace tests\godmode\pred;

use godmode\data\Entry;
use godmode\pred\EntryEqualsPred;
use PHPUnit\Framework\TestCase;

class EntryEqualsPredTest extends TestCase
{
    public function testReturnsTrueIfEntryExistsAndMatchesValue()
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('exists')->willReturn(true);
        $entry->method('value')->willReturn('test_val');

        $pred = new EntryEqualsPred($entry, 'test_val');
        $this->assertTrue($pred->evaluate());
    }

    public function testReturnsFalseIfEntryDoesNotExist()
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('exists')->willReturn(false);
        // value() might not be called if exists() is checked first, but it depends on implementation order.
        // The implementation: ($this->_entry->exists() && $this->_entry->value() === $this->_value)
        // Short-circuiting should prevent value() call.
        
        $pred = new EntryEqualsPred($entry, 'test_val');
        $this->assertFalse($pred->evaluate());
    }

    public function testReturnsFalseIfEntryValueMismatch()
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('exists')->willReturn(true);
        $entry->method('value')->willReturn('wrong_val');

        $pred = new EntryEqualsPred($entry, 'test_val');
        $this->assertFalse($pred->evaluate());
    }
    
    public function testStrictEquality()
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('exists')->willReturn(true);
        $entry->method('value')->willReturn('1');

        $pred = new EntryEqualsPred($entry, 1); // 1 (int) !== '1' (string)
        $this->assertFalse($pred->evaluate());
    }
}
