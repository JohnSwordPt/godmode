<?php

namespace godmode\util;

trait GetterSetterTrait
{
    public function __set($name, $value) {
        $functionname='set'.$name;
        return $this->$functionname($value);
    }

    public function __get($name) {
        $functionname='get'.$name;
        return $this->$functionname();
    }
}