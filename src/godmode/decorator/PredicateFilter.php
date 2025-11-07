<?php

namespace godmode\decorator;

use godmode\core\BehaviorTask;
use godmode\core\ObjectContainer;
use godmode\core\StatefulBehaviorTask;
use godmode\pred\BehaviorPredicate;
use SplObjectStorage;

class PredicateFilter extends StatefulBehaviorTask implements ObjectContainer {

    /** @var BehaviorPredicate $_pred */
    protected $_pred;
    /** @var BehaviorTask $_task */
    protected $_task;

    public function __construct(BehaviorPredicate $pred, BehaviorTask $task) {
        $this->_pred = $pred;
        $this->_task = $task;
    }

    public function getChildren() : SplObjectStorage {
        $os = new SplObjectStorage();
        $os->attach($this->_task);
        return $os;
    }

    public function reset() : void {
        $this->_task->deactivate();
    }

    public function updateTask(float $dt) :int {
        // call $this->_pred.updateTask so that the pred's $lastStatus gets set
        if ($this->_pred->update($dt) != BehaviorTask::SUCCESS) {
            return BehaviorTask::FAIL;
        }
        return $this->_task->update($dt);
    }

}