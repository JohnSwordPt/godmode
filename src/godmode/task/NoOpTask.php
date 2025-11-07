<?php

namespace godmode\task;

use godmode\core\BehaviorTask;

class NoOpTask extends BehaviorTask {

    protected $_status;

    public function __construct(int $status) {
        $this->_status = $status;
    }

    public function updateTask($dt) : int {
        return $this->_status;
    }

}