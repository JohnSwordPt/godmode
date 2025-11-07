<?php

namespace godmode\task;

use godmode\core\BehaviorTask;

class FunctionTask extends BehaviorTask {

    protected $_f;

    public function __construct(callable $f) {
        $this->_f = $f;
    }

    public function updateTask(float $dt) : int {
        $val = call_user_func_array($this->_f, [$dt]);
        return (is_integer($val)) ? $val : BehaviorTask::SUCCESS;
    }
    
}
