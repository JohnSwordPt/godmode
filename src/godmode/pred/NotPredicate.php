<?php

namespace godmode\pred;

use godmode\data\VectorBehaviorPredicate;

class NotPredicate extends BehaviorPredicate
{

    /** @var BehaviorPredicate $_pred */
    protected $_pred;

    public function __construct(BehaviorPredicate $pred) {
        $this->_pred = $pred;
    }

    public function getPred() : BehaviorPredicate {
        return $this->_pred;
    }

    public function getChildren() : VectorBehaviorPredicate {
        return new VectorBehaviorPredicate([$this->_pred]);
    }

    public function evaluate() :bool {
        return !$this->_pred->evaluate();
    }
    
}
