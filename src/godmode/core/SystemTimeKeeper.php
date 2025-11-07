<?php

namespace godmode\core;

class SystemTimeKeeper implements TimeKeeper
{
    protected float $time = 0.0;

    public function timeNow(): float
    {
        return $this->time;
    }

    public function advanceTime(float $delta)
    {
        $this->time += $delta;
    }

    public function reset()
    {
        $this->time = 0.0;
    }
}
