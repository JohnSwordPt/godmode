<?php

namespace godmode\selector;

use ArrayObject;
use godmode\core\BehaviorTask;
use godmode\core\BehaviorTaskContainer;
use godmode\core\RandomStream;
use godmode\core\StatefulBehaviorTask;
use godmode\data\VectorWeightedTask;
use godmode\util\Randoms;

/**
 * A selector that chooses which task to run at random.
 * Each task has a "weight" associated with it that determines how likely it is to be selected
 * relative to the other tasks in the selector. (If all tasks have the same weight, the selection
 * is entirely random.)
 */
class WeightedSelector extends StatefulBehaviorTask implements BehaviorTaskContainer
{

    /** @var Randoms $_rands */
    protected  $_rands;

    /** @var VectorWeightedTask $_children */
    protected  $_children;

    /** @var WeightedTask $_curChild */
    protected $_curChild;

    /**
     * @param RandomStream $rng
     * @param VectorWeightedTask $tasks
     */
    public function __construct (RandomStream $rng, VectorWeightedTask $tasks) {
        $this->_rands = new Randoms($rng);
        $this->_children = $tasks ?? new VectorWeightedTask();
    }

    public function addTask (WeightedTask $task) : void {
        $this->_children->push($task);
    }

    public function getChildren() : ArrayObject
    {
        $tasks = new ArrayObject();
        foreach ($this->_children as $child) {
            $tasks->append($child->task);
        }
        return $tasks;
    }

    public function reset () : void {
        if ($this->_curChild !== null) {
            $this->_curChild->task->deactivate();
            $this->_curChild = null;
        }
    }

    protected function updateTask (float $dt) : int {
        // Are we already running a task?
        $status = null;
        if ($this->_curChild !== null) {
            $status = $this->_curChild->task->update($dt);

            // The task completed
            if ($status != BehaviorTask::RUNNING) {
                $this->_curChild = null;
            }

            // Exit immediately, unless our task failed, in which case we'll try to select
            // another one below
            if ($status != BehaviorTask::FAIL) {
                return $status;
            }
        }

        $numTriedTasks = 0;
        while ($numTriedTasks < $this->_children->count()) {
            $child = $this->chooseNextChild();
            $numTriedTasks++;
            // skip this task on our next call to chooseNextChild
            $child->skip = true;

            $status = $child->task->update($dt);
            if ($status == BehaviorTask::RUNNING) {
                $this->_curChild = $child;
            }

            // Exit immediately, unless our task failed, in which case we'll try to select
            // another one
            if ($status != BehaviorTask::FAIL) {
                $this->resetSkippedStatus();
                return $status;
            }
        }

        $this->resetSkippedStatus();

        // all of our tasks failed
        return BehaviorTask::FAIL;
    }

    protected function chooseNextChild () : WeightedTask {
        $pick = null;
        $total = 0;
        // $children = $this->_children->getArrayCopy();
        foreach ($this->_children as $child) {
            if (!$child->skip) {
                $total += $child->weight;
                if ($pick == null || $this->_rands->getNumber($total) < $child->weight) {
                    $pick = $child;
                }
            }
        }
        return $pick;
    }

    protected function resetSkippedStatus () :void {
        // $children = $this->_children->getArrayCopy();
        foreach ($this->_children as $child) {
            $child->skip = false;
        }
    }

}