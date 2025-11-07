<?php

namespace godmode\selector;

use godmode\core\BehaviorTask;

class WeightedTask
{

    public $task;
    public $weight;
    public $skip;
    public $hasRun;

    public function __construct(BehaviorTask $task, int $weight)
    {
        $this->task = $task;
        $this->weight = $weight;
    }
    
}
