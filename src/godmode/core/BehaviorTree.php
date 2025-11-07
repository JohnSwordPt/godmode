<?php

namespace godmode\core;

class BehaviorTree
{

    /**
     * If true, the tree will generate a human readable String describing the state of the tree.
     * This is slow, and should not be used in production code.
     */
    public $debug;

    /** If both this and debug are true, the tree status will be printed to the console every update. */
    public $debugPrint;

    /** @var BehaviorTask $_root */
    protected $_root;
    protected $_lastTreeStatus;

    public function __construct(BehaviorTask $root) {
        $this->_root = $root;
    }

    /**
     * If 'debug' is true, returns the status of the tree as of the last update
     * @return string
     */
    public function treeStatus() : string {
        return $this->_lastTreeStatus ?? "";
    }

    /**
     * Updates the tree
     * 
     * @param dt: number
     * @return float
     */
    public function update(float $dt): int {
        if ($this->debug) {
            $this->clearStatus($this->_root);
        }

        $status = $this->_root->update($dt);

        if ($this->debug) {
            $this->_lastTreeStatus = $this->getStatusString($this->_root, 0);
            if ($this->debugPrint) {
                echo($this->_lastTreeStatus);
            }
        }

        return $status;
    }

    protected function clearStatus(BehaviorTask $task) : void {
        $task->_lastStatus = 0;
        if ($task instanceof BehaviorTaskContainer) {
            foreach ($task->getChildren() as $child) {
                $this->clearStatus($child);
            }
        }
    }

    protected function getStatusString(BehaviorTask $task, $depth = 0) {
        $out = "";
        if ($depth > 0) {
            $out .= "\n";
            for ($ii = 0; $ii < $depth; $ii++) {
                $out .= "- ";
            }
        }

        $out .= "[" . $task->description() . "]:" . $this->statusName($task->_lastStatus);

        if ($task instanceof BehaviorTaskContainer) {
            foreach ($task->getChildren() as $child) {
                $out .= $this->getStatusString($child, $depth + 1);
            }
        }

        return $out;
    }

    protected static function statusName (int $status) : String {
        switch ($status) {
            case BehaviorTask::RUNNING: return "RUNNING";
            case BehaviorTask::SUCCESS: return "SUCCESS";
            case BehaviorTask::FAIL: return "FAIL";
            default: return "INACTIVE";
        }
    }
    
}
