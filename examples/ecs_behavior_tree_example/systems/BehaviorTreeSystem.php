<?php

namespace ECSBehaviorTreeDemo\Systems;

use ECS\ListIteratingSystem;
use ECSBehaviorTreeDemo\Nodes\BehaviorTreeNode;
use godmode\core\BehaviorTask;

class BehaviorTreeSystem extends ListIteratingSystem
{
    public function __construct()
    {
        parent::__construct(BehaviorTreeNode::class, [$this, 'updateNode']);
    }

    public function updateNode(BehaviorTreeNode $node, float $dt): void
    {
        $behaviorTree = $node->behaviorTree->behaviorTree;
        $status = $behaviorTree->update($dt);

        if ($status === BehaviorTask::SUCCESS) {
            echo "Entity " . $node->Entity->Name . ": Behavior Tree SUCCEEDED!\n";
            $behaviorTree->reset();
        } elseif ($status === BehaviorTask::FAIL) {
            echo "Entity " . $node->Entity->Name . ": Behavior Tree FAILED!\n";
            $behaviorTree->reset();
        }
    }
}
