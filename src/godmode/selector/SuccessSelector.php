<?php

namespace godmode\selector;

use godmode\core\BehaviorTask;
use godmode\data\VectorBehaviorTask;

/**
 * Executes child tasks in sequence. Succeeds when any child succeeds or all children complete. 
 */
class SuccessSelector extends SequenceSelector {

    /**
     * @param VectorBehaviorTask $tasks
     */
    public function __construct( ?VectorBehaviorTask $tasks = null ) {
        parent::__construct( $tasks );
    }

    protected function updateTask( float $dt ) : int {
        $childStatus = 0;
        while ($this->_childIdx < $this->_children->count()) {
            $this->_curChild = $this->_children[$this->_childIdx];
            $childStatus = $this->_curChild->update($dt);
            if ($childStatus == BehaviorTask::FAIL) {
                $this->_curChild = null;
                $this->_childIdx = $this->_childIdx + 1;
                continue;
            }
            return $childStatus;
        }
        return BehaviorTask::SUCCESS;
    }
    
}