<?php

namespace godmode\core;

use ArrayObject;

/** Implemented by BehaviorTasks that contain other BehaviorTasks */
interface BehaviorTaskContainer
{
    function getChildren() : ArrayObject;
}
