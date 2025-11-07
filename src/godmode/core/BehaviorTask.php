<?php

namespace godmode\core;

class BehaviorTask
{
    public const RUNNING = 1;
    public const SUCCESS = 2;
    public const FAIL = 3;

    public $_name;
    public $_lastStatus;

    /**
     * Updates the behavior tree.
     *
     * Subclasses do not override this function; instead they should override updateTask()
     */
    public function update (float $dt) :int {
        return $this->updateInternal($dt);
    }

    /**
     * Deactivates the task. External code should call this to dispose of the task tree.
     *
     * BehaviorTaskContainers should deactivate any active child tasks in their reset() function.
     */
    public function deactivate () : void {
        $this->deactivateInternal();
    }

    /** Returns a description of the task. Subclasses can optionally override. */
    public function description () : String {
        $out = $this->className($this);
        if ($this->_name !== null) {
            $out = '"' . $this->_name . '" ' . $out;
        }
        return $out;
    }

    /** Subclasses should override this to perform update logic. */
    protected function updateTask (float $dt) : int {
        return BehaviorTask::SUCCESS;
    }

    protected static function className (object $obj) : String {
        return get_class($obj);
    }

    protected function updateInternal (float $dt) : int {
        $this->_lastStatus = $this->updateTask($dt);
        return $this->_lastStatus;
    }

    protected function deactivateInternal () : void {
    }

}