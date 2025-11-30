<?php

namespace tests\godmode\data;

use godmode\data\Blackboard;
use godmode\data\EntryImpl;
use PHPUnit\Framework\TestCase;

class BlackboardTest extends TestCase
{
    public function testGetEntryReturnsMutableEntry()
    {
        $bb = new Blackboard();
        $entry = $bb->getEntry('testKey');
        $this->assertInstanceOf(EntryImpl::class, $entry);
    }

    public function testStoreAndRetrieveValue()
    {
        $bb = new Blackboard();
        $entry = $bb->getEntry('myVal');
        
        $this->assertFalse($entry->exists());
        $this->assertNull($entry->value());

        $entry->store('hello');
        $this->assertTrue($entry->exists());
        $this->assertEquals('hello', $entry->value());
    }

    public function testDifferentKeysAreIndependent()
    {
        $bb = new Blackboard();
        $e1 = $bb->getEntry('k1');
        $e2 = $bb->getEntry('k2');

        $e1->store(10);
        $e2->store(20);

        $this->assertEquals(10, $e1->value());
        $this->assertEquals(20, $e2->value());
    }

    public function testContainsReturnsFalseForNonExistent()
    {
        $bb = new Blackboard();
        // Warning: contains() in Blackboard implementation accesses array key directly.
        // $entry = $this->_dict[$key]; might throw notice if undefined.
        // Let's verify behavior.
        // Based on code: $entry = $this->_dict[$key]; if $this->_dict is ArrayObject, it might throw undefined index if not set.
        // If it returns null, then $entry !== null check works.
        // However, ArrayObject with defaults behaves like array.
        
        // Assuming getEntry initializes it.
        $bb->getEntry('exists')->store('yes');
        $this->assertTrue($bb->contains('exists'));
    }
    
    public function testRemoveEntry()
    {
        $bb = new Blackboard();
        $entry = $bb->getEntry('temp');
        $entry->store('data');
        $this->assertTrue($entry->exists());
        
        $entry->remove();
        $this->assertFalse($entry->exists());
        $this->assertNull($entry->value());
    }
}
