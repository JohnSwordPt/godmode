<?php

namespace godmode\selector;

use ArrayObject;
use godmode\core\BehaviorTask;
use godmode\core\BehaviorTaskContainer;
use godmode\core\StatefulBehaviorTask;
use godmode\data\VectorBehaviorTask;
use godmode\TaskFactory;

/**
 * Executes child tasks in sequence. Succeeds when all children have succeeded. 
 * Fails when any child fails.
 */
class SequenceSelector extends StatefulBehaviorTask implements BehaviorTaskContainer {

    /** @var VectorBehaviorTask $_children */
    protected $_children;

    /** @var BehaviorTask $_curChild */
    protected $_curChild;

    protected $_childIdx = 0;

    /**
     * @param VectorBehaviorTask $tasks Tasks to execute in sequence.
     */
    public function __construct( ?VectorBehaviorTask $tasks = null ) {
        $this->_children = $tasks ?? new VectorBehaviorTask();
    }

    public function addTask(BehaviorTask $task) : SequenceSelector {
        $this->_children->push($task);
        return $this;
    }

    public function getChildren() : ArrayObject {
        return $this->_children;
    }

    public function reset() : void {
        if ($this->_curChild !== null) {
            $this->_curChild->deactivate();
            $this->_curChild = null;
        }
        $this->_childIdx = 0;
    }

    protected function updateTask(float $dt) : int {
        while ($this->_childIdx < $this->_children->count()) {
            $this->_curChild = $this->_children[$this->_childIdx];
            $childStatus = $this->_curChild->update($dt);
            if ($childStatus == BehaviorTask::SUCCESS) {
                // the child completed. Move on to the next.
                $this->_curChild = null;
                $this->_childIdx++;
            } else {
                // RUNNING or FAIL return immediately
                return $childStatus;
            }
        }

        // all our children have completed successfully
        return BehaviorTask::SUCCESS;
    }

}
