<?php

namespace ECS;

use SplStack;

/**
 * A pool of nodes for reuse to reduce object creation and garbage collection.
 */
class NodePool
{
    /**
     * @var array<string, SplStack<Node>>
     */
    private array $pools = [];

    /**
     * Retrieves a node from the pool for the given node class.
     * If no node is available, a new instance is created.
     *
     * @param string $nodeClass The class name of the node to retrieve.
     * @return Node
     */
    public function get(string $nodeClass): Node
    {
        if (!isset($this->pools[$nodeClass])) {
            $this->pools[$nodeClass] = new SplStack();
        }

        if (!$this->pools[$nodeClass]->isEmpty()) {
            return $this->pools[$nodeClass]->pop();
        }

        return new $nodeClass();
    }

    /**
     * Disposes a node by returning it to its respective pool.
     *
     * @param Node $node The node to dispose.
     * @return void
     */
    public function dispose(Node $node): void
    {
        $nodeClass = get_class($node);
        if (!isset($this->pools[$nodeClass])) {
            $this->pools[$nodeClass] = new SplStack();
        }
        // Reset node properties if necessary before pooling
        // For now, we assume nodes are reset by the ComponentMatchingFamily when reused.
        $this->pools[$nodeClass]->push($node);
    }

    /**
     * Clears all node pools.
     * @return void
     */
    public function clear(): void
    {
        $this->pools = [];
    }
}
