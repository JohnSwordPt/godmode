<?php

namespace ECSBehaviorTreeDemo\Components;

use godmode\core\BehaviorTree;

class BehaviorTreeComponent
{
    public BehaviorTree $behaviorTree;

    public function __construct(\godmode\core\BehaviorTree $behaviorTree)
    {
        $this->behaviorTree = $behaviorTree;
    }
}
