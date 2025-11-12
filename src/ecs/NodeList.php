<?php

namespace ECS;

use SplDoublyLinkedList;
use Closure;

/**
 * A collection of nodes.
 */
class NodeList extends SplDoublyLinkedList
{
    /**
     * A signal that is dispatched whenever a node is added to the node list.
     */
    public array $nodeAdded;

    /**
     * A signal that is dispatched whenever a node is removed from the node list.
     */
    public array $nodeRemoved;

    public function __construct()
    {
        $this->nodeAdded = [];
        $this->nodeRemoved = [];
    }

    public function addNodeAdded(Closure $closure)
    {
        $this->nodeAdded[] = $closure;
    }

    public function removeNodeAdded(Closure $closure)
    {
        $index = array_search($closure, $this->nodeAdded, true);
        if ($index !== false) {
            unset($this->nodeAdded[$index]);
        }
    }

    public function addNodeRemoved(Closure $closure)
    {
        $this->nodeRemoved[] = $closure;
    }

    public function removeNodeRemoved(Closure $closure)
    {
        $index = array_search($closure, $this->nodeRemoved, true);
        if ($index !== false) {
            unset($this->nodeRemoved[$index]);
        }
    }

    public function push($node): void
    {
        parent::push($node);
        foreach ($this->nodeAdded as $closure) {
            $closure($node);
        }
    }

    public function offsetUnset($index): void
    {
        $node = $this->offsetGet($index);
        parent::offsetUnset($index);
        foreach ($this->nodeRemoved as $closure) {
            $closure($node);
        }
    }

    public function removeAll(): void
    {
        while (!$this->isEmpty()) {
            $node = $this->pop();
            foreach ($this->nodeRemoved as $closure) {
                $closure($node);
            }
        }
    }
}
