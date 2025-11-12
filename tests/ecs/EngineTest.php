<?php

namespace ECSTests;

use PHPUnit\Framework\TestCase;
use ECS\Engine;
use ECS\Entity;
use ECS\Node;
use ECS\System;
use ECS\NodeList;

require_once __DIR__ . '/EcsMocks.php';

class EngineTest extends TestCase
{
    private Engine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new Engine();
    }

    public function testAddAndRemoveEntity()
    {
        $entity = new Entity("TestEntity");
        $this->engine->AddEntity($entity);
        $this->assertCount(1, $this->engine->Entities());
        $this->assertSame($entity, $this->engine->GetEntityByName("TestEntity"));

        $this->engine->RemoveEntity($entity);
        $this->assertCount(0, $this->engine->Entities());
        $this->assertNull($this->engine->GetEntityByName("TestEntity"));
    }

    public function testAddDuplicateEntityNameThrowsError()
    {
        $this->expectException(\Error::class);
        $entity1 = new Entity("DuplicateName");
        $entity2 = new Entity("DuplicateName");
        $this->engine->AddEntity($entity1);
        $this->engine->AddEntity($entity2);
    }

    public function testRemoveAllEntities()
    {
        $this->engine->AddEntity(new Entity("Entity1"));
        $this->engine->AddEntity(new Entity("Entity2"));
        $this->engine->RemoveAllEntities();
        $this->assertCount(0, $this->engine->Entities());
    }

    public function testAddAndRemoveSystem()
    {
        $system = new TestSystem();
        $this->engine->AddSystem($system, 1);
        $this->assertCount(1, $this->engine->GetSystems());
        $this->assertTrue($system->addedToEngine);
        $this->assertSame($system, $this->engine->GetSystem(TestSystem::class));

        $this->engine->RemoveSystem($system);
        $this->assertCount(0, $this->engine->GetSystems());
        $this->assertTrue($system->removedFromEngine);
    }

    public function testRemoveAllSystems()
    {
        $this->engine->AddSystem(new TestSystem(), 1);
        $this->engine->AddSystem(new TestSystem(), 2);
        $this->engine->RemoveAllSystems();
        $this->assertCount(0, $this->engine->GetSystems());
    }

    public function testSystemUpdateCalled()
    {
        $system = new TestSystem();
        $this->engine->AddSystem($system, 1);
        $this->engine->Update(0.1);
        $this->assertTrue($system->updated);
    }

    public function testGetNodeListCreatesAndManagesNodes()
    {
        $entity1 = (new Entity("Player"))
            ->add(new PositionComponent())
            ->add(new RenderableComponent());
        $entity2 = (new Entity("Enemy"))
            ->add(new PositionComponent()); // Missing RenderableComponent

        $this->engine->AddEntity($entity1);
        $this->engine->AddEntity($entity2);

        $nodeList = $this->engine->GetNodeList(TestNode::class);
        $this->assertInstanceOf(NodeList::class, $nodeList);
        $this->assertCount(1, $nodeList); // Only entity1 should match TestNode
        $nodeList->rewind(); // Ensure pointer is at the beginning
        $this->assertSame($entity1, $nodeList->current()->Entity);

        // Add missing component to entity2, it should now be in the NodeList
        $entity2->add(new RenderableComponent());
        $this->assertCount(2, $nodeList);
        $nodeList->rewind(); // Rewind again after modification
        // The order might change, so we need to check both entities
        $foundEntity1 = false;
        $foundEntity2 = false;
        foreach ($nodeList as $node) {
            if ($node->Entity === $entity1) {
                $foundEntity1 = true;
            }
            if ($node->Entity === $entity2) {
                $foundEntity2 = true;
            }
        }
        $this->assertTrue($foundEntity1 && $foundEntity2, "Both entities should be in the NodeList.");

        // Remove component from entity1, it should be removed from NodeList
        $entity1->remove(RenderableComponent::class);
        $this->assertCount(1, $nodeList);
        $nodeList->rewind(); // Rewind again after modification
        $this->assertSame($entity2, $nodeList->current()->Entity);
    }

    public function testReleaseNodeListCleansUp()
    {
        $entity = (new Entity("Test"))
            ->add(new PositionComponent())
            ->add(new RenderableComponent());
        $this->engine->AddEntity($entity);
        $nodeList = $this->engine->GetNodeList(TestNode::class);
        $this->assertCount(1, $nodeList);

        $this->engine->releaseNodeList(TestNode::class);
        // After releasing, the family for TestNode should be gone
        // We can't directly assert the family is gone, but we can check if GetNodeList creates a new one
        $newNodeList = $this->engine->GetNodeList(TestNode::class);
        $this->assertNotSame($nodeList, $newNodeList); // Should be a new NodeList instance
        $this->assertCount(1, $newNodeList); // Entity should still match and be added to new list
    }

    public function testGetNodeMetaDataCaching()
    {
        // First call, should calculate and cache
        $meta1 = $this->engine->GetNodeMetaData(TestNode::class, 'position');
        $this->assertEquals(PositionComponent::class, $meta1);

        // Second call, should retrieve from cache
        $meta2 = $this->engine->GetNodeMetaData(TestNode::class, 'position');
        $this->assertEquals(PositionComponent::class, $meta2);

        // Verify that different metadata is handled correctly
        $meta3 = $this->engine->GetNodeMetaData(TestNode::class, 'renderable');
        $this->assertEquals(RenderableComponent::class, $meta3);

        // Test with a node that has only one component
        $meta4 = $this->engine->GetNodeMetaData(SingleComponentNode::class, 'health');
        $this->assertEquals(HealthComponent::class, $meta4);
    }
}
