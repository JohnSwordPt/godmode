<?php

namespace godmode\task;

use godmode\core\BehaviorTask;
use godmode\data\MutableEntry;

class StoreEntryTask extends BehaviorTask
{

    /** @var MutableEntry $_value */
    protected $_value;
    protected $_storeVal;

    public function __construct(MutableEntry $value, $storeVal) {
        $this->_value = $value;
        $this->_storeVal = $storeVal;
    }

    public function updateTask(float $dt) :int {
        $this->_value->store($this->_storeVal);
        return BehaviorTask::SUCCESS;
    }
    
}