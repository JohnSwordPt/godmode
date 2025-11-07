<?php

namespace godmode\example;

require_once __DIR__ . '/../vendor/autoload.php';

use godmode\core\BehaviorTask;
use godmode\core\StatefulBehaviorTask;
use godmode\selector\IterateSelector;
use godmode\selector\SequenceSelector;

/**
 * A simple task that logs a message and returns a specific status.
 */
class LogTask extends StatefulBehaviorTask
{
    private $message;
    private $statusToReturn;

    public function __construct(string $message, int $statusToReturn = BehaviorTask::SUCCESS)
    {
        $this->message = $message;
        $this->statusToReturn = $statusToReturn;
    }

    protected function updateTask(float $dt): int
    {
        echo "  - " . $this->message . " (will return " . ($this->statusToReturn === BehaviorTask::SUCCESS ? 'SUCCESS' : 'FAIL') . ")\n";
        return $this->statusToReturn;
    }
}

/**
 * Helper function to run a task and print its final status.
 */
function run_task(StatefulBehaviorTask $rootTask)
{
    $status = $rootTask->update(0.1);
    $statusText = 'UNKNOWN';
    if ($status === BehaviorTask::SUCCESS) {
        $statusText = 'SUCCESS';
    } elseif ($status === BehaviorTask::FAIL) {
        $statusText = 'FAIL';
    } elseif ($status === BehaviorTask::RUNNING) {
        $statusText = 'RUNNING';
    }
    echo "Selector finished with status: {$statusText}\n";
    $rootTask->reset();
}

echo "--- IterateSelector vs SequenceSelector Example ---\n\n";

// --- Scenario 1: Using IterateSelector ---
// It will execute ALL tasks, even if one fails.
echo "1. Testing IterateSelector:\n";

$iterateSelector = new IterateSelector();
$iterateSelector->addTask(new LogTask("Task 1: Check inventory"))
    ->addTask(new LogTask("Task 2: Sharpen sword"))
    ->addTask(new LogTask("Task 3: Brew potion", BehaviorTask::FAIL)) // This task fails
    ->addTask(new LogTask("Task 4: Read map")); // This task will still run

run_task($iterateSelector);

echo "\nAs you can see, IterateSelector ran all four tasks, including the one after the failure.\n";
echo "It returns SUCCESS because it completed its iteration over all children.\n";

echo "\n---------------------------------------------------\n\n";

// --- Scenario 2: Using SequenceSelector ---
// It will STOP executing at the first task that fails.
echo "2. Testing SequenceSelector for comparison:\n";

$sequenceSelector = new SequenceSelector();
$sequenceSelector->addTask(new LogTask("Task 1: Check inventory"))
    ->addTask(new LogTask("Task 2: Sharpen sword"))
    ->addTask(new LogTask("Task 3: Brew potion", BehaviorTask::FAIL)) // This task fails
    ->addTask(new LogTask("Task 4: Read map")); // This task will NOT run

run_task($sequenceSelector);

echo "\nAs you can see, SequenceSelector stopped after Task 3 failed and did not run Task 4.\n";
echo "It returns FAIL because one of its children failed.\n";

?>
