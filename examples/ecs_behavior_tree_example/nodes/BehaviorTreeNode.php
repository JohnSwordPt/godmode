<?php

namespace ECSBehaviorTreeDemo\Nodes;

use ECS\Node;
use ECSBehaviorTreeDemo\Components\BehaviorTreeComponent;
use ECSBehaviorTreeDemo\Components\HealthComponent;
use ECSDemo\Components\Position;

class BehaviorTreeNode extends Node
{
    public Position $position;
    public HealthComponent $health;
    public BehaviorTreeComponent $behaviorTree;
}
