<?php

namespace godmode\pred;

use godmode\data\Entry;

class EntryExistsPred extends BehaviorPredicate {

    /** @var Entry $_entry */
    protected $_entry;

    public function __construct( Entry $entry ) {
        $this->_entry = $entry;
    }

    public function evaluate() : bool {
        return $this->_entry->exists();
    }

}