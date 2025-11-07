<?php

namespace godmode\pred;

use ArrayObject;
use godmode\core\BehaviorTaskContainer;
use godmode\data\VectorBehaviorPredicate;
use godmode\data\VectorBehaviorTask;

class OrPredicate extends BehaviorPredicate implements BehaviorTaskContainer {

    /** @var VectorBehaviorPredicate $_preds */
    protected $_preds;

    public function __construct($preds = null) {
        $this->_preds = $preds ?? new VectorBehaviorPredicate();
    }

    public function addPred(BehaviorPredicate $pred) :void {
        $this->_preds->push($pred);
    }

    public function getChildren() : ArrayObject {
        return $this->_preds;
    }

    public function evaluate() :bool {
        foreach ($this->_preds as $pred) {
            if ($pred->evaluate()) {
                return true;
            }
        }
        return false;
    }

}