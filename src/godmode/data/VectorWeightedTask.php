<?php

namespace godmode\data;

use ArrayObject;
use godmode\selector\WeightedTask;

class VectorWeightedTask extends ArrayObject
{

    public function __construct(WeightedTask ...$tasks)
    {
        parent::__construct($tasks);
    }

    public function push(WeightedTask $task) : void
    {
        $this->append($task);
    }

    public function get(int $idx) : WeightedTask 
    {
        return $this->offsetGet($idx);
    }

    /**
     * @return WeightedTask[]
     */
    public function getArrayCopy() : array
    {
        return $this->getArrayCopy();
    }
}