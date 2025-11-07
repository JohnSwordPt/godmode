<?php

namespace godmode\data;

use ArrayObject;
use godmode\core\BehaviorTask;

class VectorBehaviorTask extends ArrayObject
{

    public function __construct(BehaviorTask ...$tasks)
    {
        parent::__construct($tasks);
    }

    public function push(BehaviorTask $task) : void
    {
        $this->append($task);
    }

    public function get(int $idx) : BehaviorTask 
    {
        return $this->offsetGet($idx);
    }

    /**
     * @return BehaviorTask[]
     */
    public function getArrayCopy() : array
    {
        return parent::getArrayCopy();
    }
}