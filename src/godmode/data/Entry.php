<?php

namespace godmode\data;

interface Entry {
    /**
     * @return true if the entry exists in the blackboard
     */
    function exists() : bool;

    /**
     * @return mixed the value stored in the blackboard for this entry
     */
    function value();
}