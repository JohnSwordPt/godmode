<?php

namespace godmode\selector;

use godmode\core\BehaviorTask;
use godmode\data\VectorBehaviorTask;

/**
 * A selector that iterates through all tasks no matter if they fail or succeed
 */
class IterateSelector extends SequenceSelector {

    /**
     * @param VectorBehaviorTask $tasks
     */
    public function __construct( ?VectorBehaviorTask $tasks = null ) {
        parent::__construct( $tasks );
    }

    public function updateTask( float $dt ) : int {
        $childStatus = 0;
        $count = $this->_children->count();
        if($count) {
            while ( $this->_childIdx < $count ) {
                $this->_curChild = $this->_children[$this->_childIdx];
                $childStatus = $this->_curChild->update($dt);
                if ( $childStatus == BehaviorTask::SUCCESS || $childStatus == BehaviorTask::FAIL ) {
                    $this->_curChild = null;
                    $this->_childIdx = $this->_childIdx + 1;
                    continue;
                }
                return $childStatus;
            }
        }
        return BehaviorTask::SUCCESS;
    }
}