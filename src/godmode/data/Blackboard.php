<?php

namespace godmode\data;

use ArrayObject;

class Blackboard {

    /** @var ArrayObject $_dict */
    protected $_dict = null;

    public function __construct() {
        $this->_dict = new ArrayObject();
    }

    public static function staticEntry($value) : Entry {
        return new StaticEntry($value);
    }

    public function getEntry($key) : MutableEntry {
        $entry = $this->_dict[$key] ?? null;
        if ($entry === null) {
            $entry = new EntryImpl($this);
            $this->_dict[$key] = $entry;
        }
        return $entry;
    }

    public function contains($key) : bool {
        $entry = $this->_dict[$key];
        return ($entry !== null && $entry->exists());
    }

    public function toBlackboard($val) {
        return $val;
    }

    public function fromBlackboard($val) {
        return $val;
    }

    public function getAll() : ArrayObject {
        return $this->_dict;
    }

}