<?php

namespace godmode\pred;

use Error;
use godmode\core\BehaviorTask;
use godmode\util\GetterSetterTrait;

class BehaviorPredicate extends BehaviorTask {

    use GetterSetterTrait;

    protected static $TRUE = null;
    protected static $FALSE = null;

    public function evaluate() : bool {
        throw new Error("not implemented");
    }

    public function updateTask(float $dt) :int {
        return ($this->evaluate()) ? BehaviorTask::SUCCESS : BehaviorTask::FAIL;
    }

    protected static function getTRUE() : BehaviorPredicate {
        if (null === BehaviorPredicate::$TRUE) {
            BehaviorPredicate::$TRUE = new ConstPredicate(true);
        }
        return BehaviorPredicate::$TRUE;
    }

    protected static function getFALSE() : BehaviorPredicate {
        if (null === BehaviorPredicate::$FALSE) {
            BehaviorPredicate::$FALSE = new ConstPredicate(false);
        }
        return BehaviorPredicate::$FALSE;
    }

    protected static function setTrue($value) :void {
        BehaviorPredicate::$TRUE = $value;
    }

    protected static function setFalse($value) :void {
        BehaviorPredicate::$FALSE = $value;
    }
}