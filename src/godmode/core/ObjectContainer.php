<?php

namespace godmode\core;

use SplObjectStorage;

/** Implemented by BehaviorTasks that contain other BehaviorTasks */
interface ObjectContainer
{
    function getChildren() : SplObjectStorage;
}
