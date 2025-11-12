<?php

namespace ECSTests;

use PHPUnit\Framework\TestCase;
use ECS\Entity;
use ECS\Engine;

require_once __DIR__ . '/EcsMocks.php';

class EntityTest extends TestCase
{
    private Entity $entity;
    private Engine $mockEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entity = new Entity("TestEntity");
        $this->mockEngine = $this->createMock(Engine::class);
    }

    public function testConstructorGeneratesUniqueNameIfNoneProvided()
    {
        $entity1 = new Entity();
        $entity2 = new Entity();
        $this->assertStringStartsWith('_entity', $entity1->Name);
        $this->assertStringStartsWith('_entity', $entity2->Name);
        $this->assertNotEquals($entity1->Name, $entity2->Name);
    }

    public function testConstructorUsesProvidedName()
    {
        $this->assertEquals("TestEntity", $this->entity->Name);
    }

    public function testAddGetComponent()
    {
        $componentA = new ComponentA();
        $this->entity->add($componentA);
        $this->assertSame($componentA, $this->entity->get(ComponentA::class));
    }

    public function testAddGetComponentWithCustomClass()
    {
        $componentA = new ComponentA();
        // Treat ComponentA as ComponentB for testing purposes
        $this->entity->add($componentA, ComponentB::class);
        $this->assertNull($this->entity->get(ComponentA::class));
        $this->assertSame($componentA, $this->entity->get(ComponentB::class));
    }

    public function testHasComponent()
    {
        $componentA = new ComponentA();
        $this->entity->add($componentA);
        $this->assertTrue($this->entity->has(ComponentA::class));
        $this->assertFalse($this->entity->has(ComponentB::class));
    }

    public function testRemoveComponent()
    {
        $componentA = new ComponentA();
        $this->entity->add($componentA);
        $this->assertTrue($this->entity->has(ComponentA::class));

        $removedComponent = $this->entity->remove(ComponentA::class);
        $this->assertSame($componentA, $removedComponent);
        $this->assertFalse($this->entity->has(ComponentA::class));
        $this->assertNull($this->entity->get(ComponentA::class));
    }

    public function testRemoveNonExistentComponentReturnsNull()
    {
        $this->assertNull($this->entity->remove(ComponentA::class));
    }

    public function testGetAllComponents()
    {
        $componentA = new ComponentA();
        $componentB = new ComponentB();
        $this->entity->add($componentA);
        $this->entity->add($componentB);

        $allComponents = $this->entity->getAll();
        $this->assertCount(2, $allComponents);
        $this->assertArrayHasKey(ComponentA::class, $allComponents);
        $this->assertArrayHasKey(ComponentB::class, $allComponents);
        $this->assertSame($componentA, $allComponents[ComponentA::class]);
        $this->assertSame($componentB, $allComponents[ComponentB::class]);
    }

    public function testSetAndGetEngine()
    {
        $this->assertNull($this->entity->getEngine());
        $this->entity->setEngine($this->mockEngine);
        $this->assertSame($this->mockEngine, $this->entity->getEngine());
    }

    public function testAddComponentNotifiesEngine()
    {
        $this->entity->setEngine($this->mockEngine);
        $this->mockEngine->expects($this->once())
                         ->method('ComponentAdded')
                         ->with($this->entity, ComponentA::class);

        $this->entity->add(new ComponentA());
    }

    public function testRemoveComponentNotifiesEngine()
    {
        $this->entity->setEngine($this->mockEngine);
        $this->entity->add(new ComponentA()); // Add first so it can be removed

        $this->mockEngine->expects($this->once())
                         ->method('ComponentRemoved')
                         ->with($this->entity, ComponentA::class);

        $this->entity->remove(ComponentA::class);
    }

    public function testAddComponentDoesNotNotifyEngineIfNoEngineSet()
    {
        $this->mockEngine->expects($this->never())
                         ->method('ComponentAdded');

        $this->entity->add(new ComponentA());
    }

    public function testRemoveComponentDoesNotNotifyEngineIfNoEngineSet()
    {
        $this->entity->add(new ComponentA()); // Add first
        $this->mockEngine->expects($this->never())
                         ->method('ComponentRemoved');

        $this->entity->remove(ComponentA::class);
    }
}
