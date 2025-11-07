<?php

namespace godmode\data;

use ArrayObject;
use godmode\pred\BehaviorPredicate;

class VectorBehaviorPredicate extends ArrayObject
{

    public function push(BehaviorPredicate $task) : void
    {
        $this->append($task);
    }

    public function get(int $idx) : BehaviorPredicate 
    {
        return $this->offsetGet($idx);
    }

    /**
     * @return BehaviorPredicate[]
     */
    public function getArrayCopy() : array
    {
        return $this->getArrayCopy();
    }
}