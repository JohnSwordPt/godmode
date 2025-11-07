<?php

namespace godmode\data;

class EntryImpl implements MutableEntry
{

    /** @var Blackboard $_bb */
    protected $_bb;
    protected $_value;

    public function __construct(Blackboard $bb) {
        $this->_bb = $bb;
    }

    public function exists(): bool {
        return ($this->_value !== null);
    }

    public function value() {
        return $this->_bb->fromBlackboard($this->_value);
    }

    public function store($val) : void {
        $this->_value = (($val !== null) ? $this->_bb->toBlackboard($val) : null);
    }

    public function remove(): void {
        $this->store(null);
    }

}