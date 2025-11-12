<?php

namespace ECSDemo\Nodes;

use ECS\Node;
use ECSDemo\Components\Position;
use ECSDemo\Components\Renderable;

class RenderNode extends Node
{
    /** @var Position $position */
    public Position $position;

    /** @var Renderable $renderable */
    public Renderable $renderable;
}
