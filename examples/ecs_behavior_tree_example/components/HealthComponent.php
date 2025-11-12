<?php

namespace ECSBehaviorTreeDemo\Components;

class HealthComponent
{
    public int $health;

    public function __construct(int $health)
    {
        $this->health = $health;
    }
}
