<?php

namespace godmode\data;

use ArrayObject;
use godmode\core\ScopedResource;

class VectorScopedResource extends ArrayObject
{

    public function __construct(ScopedResource ...$tasks)
    {
        parent::__construct($tasks);
    }

    public function push(ScopedResource $task) : void
    {
        $this->append($task);
    }

    public function get(int $idx) : ScopedResource 
    {
        return $this->offsetGet($idx);
    }

    /**
     * @return ScopedResource[]
     */
    public function getArrayCopy() : array
    {
        return $this->getArrayCopy();
    }
}