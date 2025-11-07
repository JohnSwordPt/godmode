<?php

namespace godmode\pred;

class ConstPredicate extends BehaviorPredicate {

    protected $_value = null;

    public function __construct(bool $value) {
        $this->_value = $value;
    }

    public function evaluate() : bool {
        return $this->_value;
    }

}