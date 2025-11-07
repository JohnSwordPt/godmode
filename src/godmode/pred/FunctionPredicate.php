<?php

namespace godmode\pred;

use Closure;

class FunctionPredicate extends BehaviorPredicate {

    protected $_f;

    public function __construct($f) {
        $this->_f = $f;
    }

    public function evaluate() :bool {
        if(is_callable($this->_f)) {
            return call_user_func_array($this->_f, []);
        }
        return false;
    }

}