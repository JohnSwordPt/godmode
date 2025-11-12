<?php

namespace ECSTests;

use PHPUnit\Framework\TestCase;
use ECS\NodeList;
use ECS\Node;
use ECS\Entity;

require_once __DIR__ . '/EcsMocks.php';

class NodeListTest extends TestCase
{
    private NodeList $nodeList;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nodeList = new NodeList();
    }

    public function testPushDispatchesNodeAddedEvent()
    {
        $addedNodes = [];
        $this->nodeList->addNodeAdded(function (Node $node) use (&$addedNodes) {
            $addedNodes[] = $node;
        });

        $node1 = new MockNodeForList(1);
        $this->nodeList->push($node1);
        $this->assertCount(1, $addedNodes);
        $this->assertSame($node1, $addedNodes[0]);

        $node2 = new MockNodeForList(2);
        $this->nodeList->push($node2);
        $this->assertCount(2, $addedNodes);
        $this->assertSame($node2, $addedNodes[1]);
    }

    public function testOffsetUnsetDispatchesNodeRemovedEvent()
    {
        $removedNodes = [];
        $this->nodeList->addNodeRemoved(function (Node $node) use (&$removedNodes) {
            $removedNodes[] = $node;
        });

        $node1 = new MockNodeForList(1);
        $node2 = new MockNodeForList(2);
        $this->nodeList->push($node1);
        $this->nodeList->push($node2);

        $this->nodeList->offsetUnset(0); // Remove node1 (now at index 0)
        $this->assertCount(1, $removedNodes);
        $this->assertSame($node1, $removedNodes[0]);

        $this->nodeList->offsetUnset(0); // Remove node2 (which is now at index 0)
        $this->assertCount(2, $removedNodes);
        $this->assertSame($node2, $removedNodes[1]);
    }

    public function testRemoveAllDispatchesNodeRemovedEventForAllNodes()
    {
        $removedNodes = [];
        $this->nodeList->addNodeRemoved(function (Node $node) use (&$removedNodes) {
            $removedNodes[] = $node;
        });

        $node1 = new MockNodeForList(1);
        $node2 = new MockNodeForList(2);
        $this->nodeList->push($node1);
        $this->nodeList->push($node2);

        $this->nodeList->removeAll();
        $this->assertCount(2, $removedNodes);
        // SplDoublyLinkedList::pop() removes from the end, so order is LIFO
        $this->assertSame($node2, $removedNodes[0]);
        $this->assertSame($node1, $removedNodes[1]);
        $this->assertTrue($this->nodeList->isEmpty());
    }

    public function testRemoveNodeAddedRemovesListener()
    {
        $addedNodes = [];
        $listener = function (Node $node) use (&$addedNodes) {
            $addedNodes[] = $node;
        };

        $this->nodeList->addNodeAdded($listener);
        $this->nodeList->push(new MockNodeForList(1));
        $this->assertCount(1, $addedNodes);

        $this->nodeList->removeNodeAdded($listener);
        $this->nodeList->push(new MockNodeForList(2));
        $this->assertCount(1, $addedNodes); // Should not have added the second node
    }

    public function testRemoveNodeRemovedRemovesListener()
    {
        $removedNodes = [];
        $listener = function (Node $node) use (&$removedNodes) {
            $removedNodes[] = $node;
        };

        $node1 = new MockNodeForList(1);
        $this->nodeList->push($node1);

        $this->nodeList->addNodeRemoved($listener);
        $this->nodeList->offsetUnset(0);
        $this->assertCount(1, $removedNodes);

        $this->nodeList->removeNodeRemoved($listener);
        $node2 = new MockNodeForList(2);
        $this->nodeList->push($node2);
        $this->nodeList->offsetUnset(0); // Remove node2
        $this->assertCount(1, $removedNodes); // Should not have added the second node
    }
}
