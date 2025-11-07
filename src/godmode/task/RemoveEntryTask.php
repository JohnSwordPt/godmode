<?php

namespace godmode\task;

use godmode\core\BehaviorTask;
use godmode\data\MutableEntry;

class RemoveEntryTask extends BehaviorTask {

    /** @var MutableEntry $_entry */
    protected $_entry;

    public function __construct(MutableEntry $entry) {
        $this->_entry = $entry;
    }

    protected function updateTask(float $dt) : int {
        $this->_entry->remove();
        return BehaviorTask::SUCCESS;
    }

    
}
