<?php

namespace godmode\data;

interface MutableEntry extends Entry
{
    /** Stores a value for this Entry. Null values are considered removed. */
    function store ($value) : void;

    /** Equivalent to store(null) */
    function remove () : void;
}