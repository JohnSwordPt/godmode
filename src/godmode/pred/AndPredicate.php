<?php

namespace godmode\pred;

use godmode\data\VectorBehaviorPredicate;

class AndPredicate extends BehaviorPredicate {

    /** @var VectorBehaviorPredicate $_preds */
    protected $_preds;

    public function __construct($preds = null) {
        $this->_preds = ($preds === null) ? new VectorBehaviorPredicate() : new VectorBehaviorPredicate($preds);
    }

    public function addPred($pred) :void {
        $this->_preds->push($pred);
    }

    public function getChildren() : VectorBehaviorPredicate {
        return $this->_preds;
    }

    public function evaluate() :bool {
        foreach ($this->_preds as $pred) {
            if (!$pred->evaluate()) {
                return false;
            }
        }
        return true;
    }

}