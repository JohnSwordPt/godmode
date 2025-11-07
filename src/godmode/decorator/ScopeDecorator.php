<?php

namespace godmode\decorator;

use ArrayObject;
use godmode\core\BehaviorTask;
use godmode\core\BehaviorTaskContainer;
use godmode\core\ScopedResource;
use godmode\core\StatefulBehaviorTask;
use godmode\data\VectorBehaviorTask;
use godmode\data\VectorScopedResource;

class ScopeDecorator extends StatefulBehaviorTask implements BehaviorTaskContainer {

    /** @var BehaviorTask $_task */
    protected $_task;

    /** @var VectorScopedResource $_resources */
    protected $_resources;

    /** @var bool $_entered */
    protected $_entered;

    public function __construct(BehaviorTask $task, ?VectorScopedResource $resources = null) {
        $this->_task = $task;
        $this->_resources = $resources ?? new VectorScopedResource();
    }

    public function addResource(ScopedResource $resource) :void {
        $this->_resources->push($resource);
    }

    public function getChildren() : ArrayObject {
        $vbt = new VectorBehaviorTask();
        $vbt->push($this->_task);
        return $vbt;
    }

    public function reset() :void {
        if ($this->_entered) {
            $this->_entered = false;
            // $resources = $this->_resources->getArrayCopy();
            foreach ($this->_resources as $resource) {
                $resource->release();
            }
        }
        $this->_task->deactivate();
    }

    protected function updateTask($dt) :int {
        if (!$this->_entered) {
            $this->_entered = true;
            // $resources = $this->_resources->getArrayCopy();
            foreach ($this->_resources as $resource) {
                $resource->acquire();
            }
        }
        return $this->_task->update($dt);
    }

}