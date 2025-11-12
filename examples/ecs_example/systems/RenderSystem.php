<?php

namespace ECSDemo\Systems;

use ECS\ListIteratingSystem;
use ECSDemo\Nodes\RenderNode;

class RenderSystem extends ListIteratingSystem
{
    public function __construct()
    {
        parent::__construct(RenderNode::class, [$this, 'updateNode']);
    }

    public function updateNode(RenderNode $node, float $time): void
    {
        echo "Rendering entity '{$node->Entity->Name}' at ({$node->position->x}, {$node->position->y}) with sprite '{$node->renderable->sprite}'\n";
    }
}

