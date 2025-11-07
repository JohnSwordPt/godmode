<?php

namespace godmode\core;

use Error;

class Semaphore
{

    protected $_name;
    protected $_maxUsers;
    protected $_refCount;

    public function __construct(string $name, int $maxUsers)
    {
        $this->_name = $name;
        $this->_maxUsers = $maxUsers;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function isAcquired()
    {
        return $this->_refCount > 0;
    }

    public function acquire()
    {
        if ($this->_refCount < $this->_maxUsers) {
            $this->_refCount++;
            return true;
        } else {
            return false;
        }
    }

    public function release()
    {
        if ($this->_refCount <= 0) {
            throw new Error("refCount is 0");
        }
        $this->_refCount--;
    }

    public function __toString()
    {
        return "[name={$this->_name}, refCount={$this->_refCount}]";
    }

}
