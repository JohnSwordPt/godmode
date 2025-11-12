<?php

namespace ECSTests;

use ECS\Node;
use ECS\Entity;

// Mock Component classes
class PositionComponent {}
class RenderableComponent {}
class HealthComponent {}
class ComponentA {}
class ComponentB {}
class ComponentC {}

// Mock Node class for testing GetNodeList and GetNodeMetaData
class TestNode extends Node
{
    /** @var PositionComponent $position */
    public $position;
    /** @var RenderableComponent $renderable */
    public $renderable;
}

// Mock Node class with only one component
class SingleComponentNode extends Node
{
    /** @var HealthComponent $health */
    public $health;
}

// Mock Node class for testing NodePool
class MockNode extends Node
{
    public $resetCount = 0;

    public function reset()
    {
        $this->Entity = null;
        // Reset any other properties that would be set by ComponentMatchingFamily
        $this->resetCount++;
    }
}

// Mock Node class for testing NodeList
class MockNodeForList extends Node
{
    public $id;
    public function __construct($id = null) {
        $this->id = $id;
    }
}

// Mock System class
class TestSystem extends \ECS\System
{
    public $addedToEngine = false;
    public $removedFromEngine = false;
    public $updated = false;
    public $priority = 0;

    public function AddToEngine(\ECS\Engine $engine)
    {
        $this->addedToEngine = true;
    }

    public function RemoveFromEngine(\ECS\Engine $engine)
    {
        $this->removedFromEngine = true;
    }

    public function Update($time)
    {
        $this->updated = true;
    }
}
