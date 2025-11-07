<?php

namespace godmode\data;

class StaticEntry implements Entry
{
    protected $_value;

    public function __construct($value)
    {
        $this->_value = $value;
    }

    public function value()
    {
        return $this->_value;
    }

    public function exists() : bool
    {
        return true;
    }

}
