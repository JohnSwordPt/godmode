<?php

namespace godmode\decorator;

use ArrayObject;
use godmode\core\BehaviorTask;
use godmode\core\StatefulBehaviorTask;
use godmode\core\TimeKeeper;
use godmode\data\Entry;
use godmode\data\VectorBehaviorTask;

/**
 * A decorator that prevents a task from being run more than once in the given interval.
 */
class DelayFilter extends StatefulBehaviorTask
{

    /** @var BehaviorTask $_task */
    protected $_task;
    /** @var Entry $_minDelay */
    protected $_minDelay;
    /** @var TimeKeeper $_minDelay */
    protected $_timeKeeper;

    protected $_taskRunning;
    protected $_lastCompletionTime;

    public function __construct(Entry $minDelay, TimeKeeper $timeKeeper, BehaviorTask $task) {
        $this->_task = $task;
        $this->_minDelay = $minDelay;
        $this->_timeKeeper = $timeKeeper;
        $this->_lastCompletionTime = -PHP_INT_MAX;
    }

    public function getChildren() : ArrayObject {
        $vbt = new VectorBehaviorTask();
        $vbt->push($this->_task);
        return $vbt;
    }

    public function reset() : void {
        if ($this->_taskRunning) {
            $this->_task->deactivate();
            $this->_taskRunning = false;
        }
    }

    protected function updateTask(float $dt) : int {
        $now = $this->_timeKeeper->timeNow();
        if (!$this->_taskRunning && ($now - $this->_lastCompletionTime) < $this->_minDelay->value()) {
            // can't run.
            return BehaviorTask::FAIL;
        }

        $status = $this->_task->update($dt);
        $this->_taskRunning = ($status == BehaviorTask::RUNNING);
        if ($status == BehaviorTask::SUCCESS) {
            $this->_lastCompletionTime = $now;
        }
        return $status;
    }

}
