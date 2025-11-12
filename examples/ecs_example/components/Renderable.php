<?php

namespace ECSDemo\Components;

class Renderable
{
    public string $sprite = "default.png";

    public function __construct(string $sprite)
    {
        $this->sprite = $sprite;
    }
}
