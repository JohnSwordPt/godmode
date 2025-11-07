<?php

namespace godmode\decorator;

use ArrayObject;
use Error;
use godmode\core\BehaviorTask;
use godmode\core\BehaviorTaskContainer;
use godmode\core\StatefulBehaviorTask;
use godmode\data\VectorBehaviorTask;
use godmode\util\GetterSetterTrait;

class LoopingDecorator extends StatefulBehaviorTask implements BehaviorTaskContainer {

    use GetterSetterTrait;

    public const BREAK_NEVER = 0;
    public const BREAK_ON_SUCCESS = 1;
    public const BREAK_ON_FAIL = 2;
    public const BREAK_ON_COMPLETE = 3;

    /** @var BehaviorTask $_task */
    protected $_task;
    /** @var int $_type */
    protected $_type;
    /** @var int $_targetLoopCount */
    protected $_targetLoopCount = 0;
    /** @var int $_curLoopCount */
    protected $_curLoopCount = 0;

    public function __construct(int $type, int $loopCount, BehaviorTask $task) {
        $this->_task = $task;
        $this->_type = $type;
        $this->_targetLoopCount = $loopCount;
    }

    public function getChildren() : ArrayObject {
        return new VectorBehaviorTask($this->_task);
    }

    public function getDescription() {
        return $this->description . " " . $this->typeName($this->_type);
    }

    public function reset() : void {
        $this->_curLoopCount = 0;
        $this->_task->deactivate();
    }

    public function updateTask(float $dt) : int {
        $status = $this->_task->update($dt);
        if ($status == BehaviorTask::RUNNING) {
            return BehaviorTask::RUNNING;
        }

        if (($this->_type == LoopingDecorator::BREAK_ON_COMPLETE && $status != BehaviorTask::RUNNING) ||
            ($this->_type == LoopingDecorator::BREAK_ON_SUCCESS && $status == BehaviorTask::SUCCESS) ||
            ($this->_type == LoopingDecorator::BREAK_ON_FAIL && $status == BehaviorTask::FAIL) ||
            ($this->_targetLoopCount > 0 && $this->_curLoopCount++ >= $this->_targetLoopCount)) {
            // break condition met
            return BehaviorTask::SUCCESS;
        } else {
            return BehaviorTask::RUNNING;
        }
    }

    protected static function typeName($type) {
        switch ($type) {
            case LoopingDecorator::BREAK_NEVER: return "BREAK_NEVER";
            case LoopingDecorator::BREAK_ON_SUCCESS: return "BREAK_ON_SUCCESS";
            case LoopingDecorator::BREAK_ON_FAIL: return "BREAK_ON_FAIL";
            case LoopingDecorator::BREAK_ON_COMPLETE: return "BREAK_ON_COMPLETE";
        }
        throw new Error("Unrecognized type: " . $type);
    }

}