<?php

namespace godmode\example;

require_once __DIR__ . '/../vendor/autoload.php';

use godmode\core\BehaviorTask;
use godmode\core\StatefulBehaviorTask;
use godmode\data\Blackboard;
use godmode\selector\SuccessSelector;

/**
 * A task that checks a specific location for keys.
 */
class CheckLocationForKeyTask extends StatefulBehaviorTask {
    private $locationName;
    private $keysAreHere;
    private $blackboard;

    public function __construct(string $locationName, bool $keysAreHere, Blackboard $blackboard) {
        $this->locationName = $locationName;
        $this->keysAreHere = $keysAreHere;
        $this->blackboard = $blackboard;
    }

    protected function updateTask(float $dt): int {
        echo "Checking for keys in: " . $this->locationName . "...\n";

        if ($this->keysAreHere) {
            echo "...Found the keys!\n";
            $this->blackboard->getEntry('keys_found')->store(true);
            return BehaviorTask::SUCCESS;
        } else {
            echo "...Keys not here.\n";
            return BehaviorTask::FAIL;
        }
    }
}

/**
 * Main execution function to simulate running the behavior tree.
 */
function runBehaviorTree(StatefulBehaviorTask $rootTask, Blackboard $blackboard, string $scenario) {
    echo "--- Starting Scenario: " . $scenario . " --- \n";
    $blackboard->getEntry('keys_found')->store(false); // Reset for the new run

    $status = $rootTask->update(0.1);

    echo "\nFinal Status of the 'Find Keys' operation: " . ($status === BehaviorTask::SUCCESS ? 'SUCCESS' : 'FAIL') . "\n";
    $keysFound = $blackboard->getEntry('keys_found')->value();
    echo "Were the keys actually found? " . ($keysFound ? 'Yes' : 'No') . "\n";
    
    // The SuccessSelector succeeds even if all its children fail.
    // This shows the overall 'search' action is complete.
    if ($status === BehaviorTask::SUCCESS && !$keysFound) {
        echo "(The search was completed, but the keys were not in any of the checked locations.)\n";
    }

    $rootTask->reset();
    echo "--- Scenario Finished ---\n";
}

// --- Real-Life Example: Finding Your Keys ---
// The SuccessSelector is perfect for when you want to try a series of actions
// and you only need one of them to succeed. The overall operation is considered
// a success as long as one of the sub-tasks succeeds, or even if all of them fail
// (which simply means you've exhausted your options).

$blackboard = new Blackboard();

// --- Scenario 1: Keys are in the first place you look. ---
$findKeys1 = new SuccessSelector();
$findKeys1->addTask(new CheckLocationForKeyTask('Pockets', true, $blackboard)) // Keys are here
          ->addTask(new CheckLocationForKeyTask('Kitchen Counter', false, $blackboard))
          ->addTask(new CheckLocationForKeyTask('Car', false, $blackboard));

runBehaviorTree($findKeys1, $blackboard, "Keys are in the first location");


echo "\n";

// --- Scenario 2: Keys are in the last place you look. ---
$findKeys2 = new SuccessSelector();
$findKeys2->addTask(new CheckLocationForKeyTask('Pockets', false, $blackboard))
          ->addTask(new CheckLocationForKeyTask('Kitchen Counter', false, $blackboard))
          ->addTask(new CheckLocationForKeyTask('Car', true, $blackboard)); // Keys are here

runBehaviorTree($findKeys2, $blackboard, "Keys are in the last location");


echo "\n";

// --- Scenario 3: Keys are not in any of the locations. ---
$findKeys3 = new SuccessSelector();
$findKeys3->addTask(new CheckLocationForKeyTask('Pockets', false, $blackboard))
          ->addTask(new CheckLocationForKeyTask('Kitchen Counter', false, $blackboard))
          ->addTask(new CheckLocationForKeyTask('Car', false, $blackboard));

runBehaviorTree($findKeys3, $blackboard, "Keys are not found anywhere");