<?php

namespace godmode\selector;

use ArrayObject;
use godmode\core\BehaviorTask;
use godmode\core\StatefulBehaviorTask;
use godmode\data\VectorBehaviorTask;

/**
 * A selector that tries to run each of its children, every update, until it finds one that
 * succeeds.
 *
 * Since children are always run in priority-order, a higher-priority task can interrupt a
 * lower-priority one that began running on a previous update.
 */
class PrioritySelector extends StatefulBehaviorTask
{

    /** @var VectorBehaviorTask $_children */
    protected $_children;
    
    /** @var BehaviorTask $_runningTask */
    protected $_runningTask;

    /**
     * @param VectorBehaviorTask $tasks
     */
    public function __construct( ?VectorBehaviorTask $tasks = null ) {
        $this->_children = $tasks ?? new VectorBehaviorTask();
    }

    public function addTask($task) : PrioritySelector {
        $this->_children->push($task);
        return $this;
    }

    public function getchildren() : ArrayObject {
        return $this->_children;
    }

    public function reset() : void {
        if ($this->_runningTask !== null) {
            $this->_runningTask->deactivate();
            $this->_runningTask = null;
        }
    }

    protected function updateTask(float $dt) : int {
        // iterate all children till we find one that doesn't fail
        $status = BehaviorTask::SUCCESS;
        foreach ($this->_children as $task) {
            $status = $task->update($dt);

            // if the child succeeded, or is still running, we exit the loop
            if ($status != BehaviorTask::FAIL) {
                // Did we interrupt a lower-priority task that was already running?
                // nb: the lower-priority task will be deactivated *after* the higher-priority
                // one is activated
                if ($this->_runningTask != $task && $this->_runningTask !== null) {
                    $this->_runningTask->deactivate();
                }

                $this->_runningTask = ($status == BehaviorTask::RUNNING) ? $task : null;
                break;
            }
        }

        return $status;
    }

}