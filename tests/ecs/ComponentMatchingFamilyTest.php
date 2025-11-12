<?php

namespace ECSTests;

use PHPUnit\Framework\TestCase;
use ECS\ComponentMatchingFamily;
use ECS\Engine;
use ECS\Entity;
use ECS\Node;
use ECS\NodePool;
use ECS\NodeList;

require_once __DIR__ . '/EcsMocks.php';

class ComponentMatchingFamilyTest extends TestCase
{
    private ComponentMatchingFamily $family;
    private \PHPUnit\Framework\MockObject\MockObject $mockEngine;
    private \PHPUnit\Framework\MockObject\MockObject $mockNodePool;
    private string $nodeClass = TestNode::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockEngine = $this->createMock(Engine::class);
        $this->mockNodePool = $this->createMock(NodePool::class);

        // Configure mockEngine for GetNodeMetaData
        $this->mockEngine->method('GetNodeMetaData')
                         ->willReturnMap([
                             [TestNode::class, 'position', PositionComponent::class],
                             [TestNode::class, 'renderable', RenderableComponent::class],
                         ]);

        $this->family = new ComponentMatchingFamily($this->nodeClass, $this->mockEngine, $this->mockNodePool);
    }

    public function testNewEntityAddsMatchingEntityToNodeList()
    {
        $entity = (new Entity("TestEntity"))
            ->add(new PositionComponent())
            ->add(new RenderableComponent());

        $node = new TestNode(); // Mock node to be returned by pool
        $this->mockNodePool->expects($this->once())
                           ->method('get')
                           ->with($this->nodeClass)
                           ->willReturn($node);

        $this->family->NewEntity($entity);

        $nodeList = $this->family->NodeList();
        $this->assertCount(1, $nodeList);
        $nodeList->rewind();
        $this->assertSame($entity, $nodeList->current()->Entity);
        $this->assertInstanceOf(PositionComponent::class, $nodeList->current()->position);
        $this->assertInstanceOf(RenderableComponent::class, $nodeList->current()->renderable);
    }

    public function testNewEntityDoesNotAddNonMatchingEntity()
    {
        $entity = (new Entity("TestEntity"))
            ->add(new PositionComponent()); // Missing RenderableComponent

        $this->mockNodePool->expects($this->never())
                           ->method('get');

        $this->family->NewEntity($entity);

        $nodeList = $this->family->NodeList();
        $this->assertCount(0, $nodeList);
    }

    public function testComponentAddedToEntityAddsMatchingEntity()
    {
        $entity = (new Entity("TestEntity"))
            ->add(new PositionComponent()); // Initially non-matching

        $this->family->NewEntity($entity); // Should not add to list
        $this->assertCount(0, $this->family->NodeList());

        $node = new TestNode();
        $this->mockNodePool->expects($this->once())
                           ->method('get')
                           ->with($this->nodeClass)
                           ->willReturn($node);

        $entity->add(new RenderableComponent()); // Now matching
        $this->family->ComponentAddedToEntity($entity, RenderableComponent::class);

        $nodeList = $this->family->NodeList();
        $this->assertCount(1, $nodeList);
        $nodeList->rewind();
        $this->assertSame($entity, $nodeList->current()->Entity);
    }

    public function testComponentRemovedFromEntityRemovesNonMatchingEntity()
    {
        $entity = (new Entity("TestEntity"))
            ->add(new PositionComponent())
            ->add(new RenderableComponent());

        $node = new TestNode();
        $this->mockNodePool->method('get')->willReturn($node); // Setup for NewEntity
        $this->family->NewEntity($entity);
        $this->assertCount(1, $this->family->NodeList());

        $this->mockNodePool->expects($this->once())
                           ->method('dispose')
                           ->with($node);

        $entity->remove(RenderableComponent::class); // Now non-matching
        $this->family->ComponentRemovedFromEntity($entity, RenderableComponent::class);

        $this->assertCount(0, $this->family->NodeList());
    }

    public function testRemoveEntityRemovesEntityFromNodeList()
    {
        $entity = (new Entity("TestEntity"))
            ->add(new PositionComponent())
            ->add(new RenderableComponent());

        $node = new TestNode();
        $this->mockNodePool->method('get')->willReturn($node); // Setup for NewEntity
        $this->family->NewEntity($entity);
        $this->assertCount(1, $this->family->NodeList());

        $this->mockNodePool->expects($this->once())
                           ->method('dispose')
                           ->with($node);

        $this->family->RemoveEntity($entity);
        $this->assertCount(0, $this->family->NodeList());
    }

    public function testCleanUpDisposesAllNodes()
    {
        $entity1 = (new Entity("Entity1"))
            ->add(new PositionComponent())
            ->add(new RenderableComponent());
        $entity2 = (new Entity("Entity2"))
            ->add(new PositionComponent())
            ->add(new RenderableComponent());

        $node1 = new TestNode();
        $node2 = new TestNode();
        $this->mockNodePool->method('get')
                           ->willReturnOnConsecutiveCalls($node1, $node2);

        $this->family->NewEntity($entity1);
        $this->family->NewEntity($entity2);
        $this->assertCount(2, $this->family->NodeList());

        $this->mockNodePool->expects($this->exactly(2))
                           ->method('dispose')
                           ->withConsecutive([$node1], [$node2]);

        $this->family->cleanUp();
        $this->assertCount(0, $this->family->NodeList());
    }
}
