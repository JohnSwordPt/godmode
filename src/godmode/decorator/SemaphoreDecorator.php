<?php

namespace godmode\decorator;

use ArrayObject;
use godmode\core\BehaviorTask;
use godmode\core\BehaviorTaskContainer;
use godmode\core\Semaphore;
use godmode\core\StatefulBehaviorTask;
use godmode\data\VectorBehaviorTask;

class SemaphoreDecorator extends StatefulBehaviorTask implements BehaviorTaskContainer {

    /** @var BehaviorTask $_task */
    protected $_task;
    /** @var Semaphore $_semaphore */
    protected $_semaphore;
    /** @var bool $_semaphoreAcquired */
    protected $_semaphoreAcquired;
    
    public function __construct(Semaphore $semaphore, BehaviorTask $task) {
        $this->_task = $task;
        $this->_semaphore = $semaphore;
        $this->_semaphoreAcquired = false;
    }

    public function getChildren() : ArrayObject {
        return new VectorBehaviorTask($this->_task);
    }

    public function getDescription() :string {
        return parent::description() . ":" . $this->_semaphore->getName();
    }

    public function reset() :void {
        parent::reset();
        if ($this->_semaphoreAcquired) {
            $this->_semaphore->release();
            $this->_semaphoreAcquired = false;
        }
        $this->_task->deactivate();
    }

    public function updateTask(float $dt) :int {
        if (!$this->_semaphoreAcquired) {
            $this->_semaphoreAcquired = $this->_semaphore->acquire();
            if (!$this->_semaphoreAcquired) {
                return BehaviorTask::FAIL;
            }
        }
        return $this->_task->update($dt);
    }

}