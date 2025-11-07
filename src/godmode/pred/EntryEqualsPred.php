<?php

namespace godmode\pred;

use godmode\data\Entry;

class EntryEqualsPred extends BehaviorPredicate {

    /** @var Entry $_entry */
    protected $_entry;
    protected $_value;

    public function __construct( Entry $entry, $value ) {
        $this->_entry = $entry;
        $this->_value = $value;
    }

    public function evaluate() : bool {
        $test1 = $this->_entry->exists();
        $test2 = $this->_entry->value();
        return ($this->_entry->exists() && $this->_entry->value() === $this->_value);
    }

}