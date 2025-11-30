<?php

namespace godmode\selector;

use ArrayObject;
use Error;
use godmode\core\BehaviorTask;
use godmode\core\BehaviorTaskContainer;
use godmode\core\StatefulBehaviorTask;
use godmode\data\VectorBehaviorTask;

/**
 * A selector that updates all children, every update, until a condition is met.
 */
class ParallelSelector extends StatefulBehaviorTask implements BehaviorTaskContainer {

    /**
     * SUCCESS if all succeed. FAIL if any fail.
     */
    public const ALL_SUCCESS = 0;

    /**
     * SUCCESS if any succeed. FAIL if all fail.
     */
    public const ANY_SUCCESS = 1;

    /**
     * SUCCESS if all fail. FAIL if any succeed.
     */
    public const ALL_FAIL = 2;

    /**
     * SUCCESS if any fail. FAIL if all succeed.
     */
    public const ANY_FAIL = 3;

    /**
     * SUCCESS when all succeed or fail.
     */
    public const ALL_COMPLETE = 4;

    /**
     * SUCCESS when any succeed or fail.
     */
    public const ANY_COMPLETE = 5;

    protected $_type;

    /** @var VectorBehaviorTask $_children */
    protected $_children;

    /**
     * @param integer $type
     * @param VectorBehaviorTask $tasks
     * @return void
     */
    public function __construct( int $type, ?VectorBehaviorTask $tasks = null ) {
        $this->_type = $type;
        $this->_children = $tasks ?? new VectorBehaviorTask();
    }

    public function getchildren() : ArrayObject {
        return $this->_children;
    }

    public function gettype() : int {
        return $this->_type;
    }

    public function reset() : void {
        foreach ($this->_children as $child) {
            $child->deactivate();
        }
    }

    public function addTask( BehaviorTask $task ) : ParallelSelector {
        $this->_children->push($task);
        return $this;
    }

    public function updateTask( float $dt ) : int {
        $runningChildren = false;
        $fails = 0;
        foreach ( $this->_children as $child ) {
            $childStatus = $child->update( $dt );
            if ( $childStatus === BehaviorTask::SUCCESS ) {
                if ( $this->_type === ParallelSelector::ANY_SUCCESS || $this->_type === ParallelSelector::ANY_COMPLETE ) {
                    return BehaviorTask::SUCCESS;
                } else if ( $this->_type === ParallelSelector::ALL_FAIL ) {
                    return BehaviorTask::FAIL;
                }
            } else if ( $childStatus === BehaviorTask::FAIL ) {
                $fails ++;
                if ( $this->_type === ParallelSelector::ANY_FAIL || $this->_type === ParallelSelector::ANY_COMPLETE ) {
                    return BehaviorTask::SUCCESS;
                } else if ( $this->_type === ParallelSelector::ALL_SUCCESS ) {
                    return BehaviorTask::FAIL;
                }
            } else {
                $runningChildren = true;
            }
        }
        if( $this->_type == ParallelSelector::ANY_SUCCESS && $fails == $this->_children->count() ) {
            return BehaviorTask::FAIL;
        }
        return $runningChildren ? BehaviorTask::RUNNING : BehaviorTask::SUCCESS;
    }

    protected static function typeName( int $type ) : string {
        switch ( $type ) {
            case ParallelSelector::ALL_SUCCESS:
                return "ALL_SUCCESS";
            case ParallelSelector::ANY_SUCCESS:
                return "ANY_SUCCESS";
            case ParallelSelector::ALL_FAIL:
                return "ALL_FAIL";
            case ParallelSelector::ANY_FAIL:
                return "ANY_FAIL";
            case ParallelSelector::ALL_COMPLETE:
                return "ALL_COMPLETE";
            case ParallelSelector::ANY_COMPLETE:
                return "ANY_COMPLETE";
        }
        throw new Error( "Unrecognized type " . $type );
    }

}
