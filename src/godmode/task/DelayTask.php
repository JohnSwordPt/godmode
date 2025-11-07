<?php

namespace godmode\task;

use godmode\core\BehaviorTask;
use godmode\core\StatefulBehaviorTask;
use godmode\data\Entry;

class DelayTask extends StatefulBehaviorTask {

    /** @var Entry $_time */
    protected $_time;
    protected $_thisTime;
    protected $_elapsedTime;

    public function __construct(Entry $time) {
        $this->_time = $time;
        $this->reset();
    }

    public function reset() : void {
        $this->_thisTime = -1;
    }

    protected function updateTask(float $dt) : int {
        if ($this->_thisTime < 0) {
            $this->_thisTime = max($this->_time->value(), 0);
            $this->_elapsedTime = 0;
        }
        $this->_elapsedTime += $dt;
        return ($this->_elapsedTime >= $this->_thisTime) ? BehaviorTask::SUCCESS : BehaviorTask::RUNNING;
    }

}