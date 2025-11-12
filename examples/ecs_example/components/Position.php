<?php

namespace ECSDemo\Components;

class Position
{
    public float $x = 0.0;
    public float $y = 0.0;

    public function __construct(float $x, float $y)
    {
        $this->x = $x;
        $this->y = $y;
    }
}
