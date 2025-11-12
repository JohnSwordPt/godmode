<?php

namespace ECSTests;

use PHPUnit\Framework\TestCase;
use ECS\NodePool;
use ECS\Node; // Assuming ECS\Node is the base Node class

require_once __DIR__ . '/EcsMocks.php';

class NodePoolTest extends TestCase
{
    private NodePool $nodePool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nodePool = new NodePool();
    }

    public function testGetCreatesNewNodeWhenPoolIsEmpty()
    {
        $node = $this->nodePool->get(MockNode::class);
        $this->assertInstanceOf(MockNode::class, $node);
    }

    public function testDisposeReturnsNodeToPool()
    {
        $node1 = $this->nodePool->get(MockNode::class);
        $this->nodePool->dispose($node1);

        $node2 = $this->nodePool->get(MockNode::class);
        $this->assertSame($node1, $node2); // Should return the same instance
    }

    public function testGetReturnsDifferentNodeForDifferentClasses()
    {
        $node1 = $this->nodePool->get(MockNode::class);
        $this->nodePool->dispose($node1);

        // Create another mock node class
        $anotherNodeClass = new class extends MockNode {};
        $node3 = $this->nodePool->get(get_class($anotherNodeClass));
        $this->assertInstanceOf(get_class($anotherNodeClass), $node3);
        $this->assertNotSame($node1, $node3); // Should be a different instance
    }

    public function testClearEmptiesAllPools()
    {
        $node1 = $this->nodePool->get(MockNode::class);
        $this->nodePool->dispose($node1);

        $this->nodePool->clear();

        $node2 = $this->nodePool->get(MockNode::class);
        $this->assertNotSame($node1, $node2); // Should create a new instance after clear
    }

    public function testDisposeHandlesMultipleNodesOfTheSameClass()
    {
        $node1 = $this->nodePool->get(MockNode::class);
        $node2 = $this->nodePool->get(MockNode::class);

        $this->nodePool->dispose($node1);
        $this->nodePool->dispose($node2);

        $retrievedNode2 = $this->nodePool->get(MockNode::class);
        $retrievedNode1 = $this->nodePool->get(MockNode::class);

        $this->assertSame($node2, $retrievedNode2); // LIFO for SplStack
        $this->assertSame($node1, $retrievedNode1);
    }
}
