<?php

namespace godmode\core;

use godmode\core\BehaviorTask;

/**
 * Base class for Tasks with state.
 * StatefulBehaviorTask can have activation and deactivation logic.
 */
class StatefulBehaviorTask extends BehaviorTask
{
    private $running;

    /**
     * Subclasses can override this to reset any state associated with the task.
     * reset() is called whenever the task stops running, either as a result of
     * an update, or by being deactivated.
     */
    public function reset() : void {
    }

    /**
     * Override this method to implement the update logic for the task.
     * updateInternal() is called whenever the task is updated.
     * @param float $dt The time elapsed since the last update.
     * @return int The new status of the task.
     */
    public function updateInternal(float $dt) : int {
        $lastStatus = $this->updateTask($dt);
        $this->running = ($lastStatus == BehaviorTask::RUNNING);
        if (!$this->running) {
            $this->reset();
        }

        return $lastStatus;
    }

    /**
     * Override this method to implement the deactivation logic for the task.
     * deactivateInternal() is called when the task is deactivated.
     */
    public function deactivateInternal() : void {
        if ($this->running) {
            $this->running = false;
            $this->reset();
        }
    }
    
}