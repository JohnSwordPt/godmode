<?php

namespace godmode\example;

require_once __DIR__ . '/../vendor/autoload.php';

use godmode\core\BehaviorTask;
use godmode\core\StatefulBehaviorTask;
use godmode\data\VectorBehaviorTask;
use godmode\selector\SequenceSelector;

// Mock BehaviorTask implementations for the example
class LogTask extends StatefulBehaviorTask {
    private $message;
    private $statusToReturn;

    public function __construct(string $message, int $statusToReturn = BehaviorTask::SUCCESS) {
        $this->message = $message;
        $this->statusToReturn = $statusToReturn;
    }

    protected function updateTask(float $dt): int {
        echo "LogTask: " . $this->message . "\n";
        return $this->statusToReturn;
    }

    public function reset(): void {}
    public function deactivate(): void {}
}

class WaitTask extends StatefulBehaviorTask {
    private $duration;
    private $elapsedTime = 0.0;

    public function __construct(float $duration) {
        $this->duration = $duration;
    }

    protected function updateTask(float $dt): int {
        $this->elapsedTime += $dt;
        if ($this->elapsedTime >= $this->duration) {
            echo "WaitTask: Waited for " . $this->duration . " seconds. Success!\n";
            return BehaviorTask::SUCCESS;
        }
        echo "WaitTask: Waiting... (" . round($this->elapsedTime, 2) . "/" . $this->duration . ")\n";
        return BehaviorTask::RUNNING;
    }

    public function reset(): void {
        $this->elapsedTime = 0.0;
        echo "WaitTask: Resetting.\n";
    }

    public function deactivate(): void {
        echo "WaitTask: Deactivating.\n";
    }
}

// Our main execution logic (e.g., in a game loop or scheduler)
function runBehaviorTree(StatefulBehaviorTask $rootTask, float $deltaTime, int $iterations = 10) {
    echo "--- Starting Behavior Tree --- \n";
    for ($i = 0; $i < $iterations; $i++) {
        echo "\nIteration " . ($i + 1) . ":\n";
        $status = $rootTask->update($deltaTime);

        if ($status === BehaviorTask::SUCCESS) {
            echo "Behavior Tree: SUCCEEDED!\n";
            $rootTask->reset(); // Reset for next run if needed
            break;
        } elseif ($status === BehaviorTask::FAIL) {
            echo "Behavior Tree: FAILED!\n";
            $rootTask->reset(); // Reset for next run if needed
            break;
        } else {
            echo "Behavior Tree: RUNNING...\n";
        }
    }
    echo "--- Behavior Tree Finished ---\n";
}

// --- Example Usage ---

echo "--- Scenario 1: All tasks succeed ---\n";
$sequence1 = new SequenceSelector();
$sequence1->addTask(new LogTask("Task 1: Initializing..."))
          ->addTask(new WaitTask(0.5)) // Takes 0.5 seconds
          ->addTask(new LogTask("Task 2: Processing data..."))
          ->addTask(new WaitTask(0.2)) // Takes 0.2 seconds
          ->addTask(new LogTask("Task 3: Finalizing."));

runBehaviorTree($sequence1, 0.1, 10); // Run with 0.1s delta time, max 10 iterations

echo "\n--- Scenario 2: One task fails ---\n";
$sequence2 = new SequenceSelector();
$sequence2->addTask(new LogTask("Task A: Preparing..."))
          ->addTask(new LogTask("Task B: Encountered an error!", BehaviorTask::FAIL)) // This task will cause the sequence to fail
          ->addTask(new LogTask("Task C: This will not be reached."));

runBehaviorTree($sequence2, 0.1, 3); // Max 3 iterations

echo "\n--- Scenario 3: Sequence with pre-defined tasks (using VectorBehaviorTask) ---\n";
$predefinedTasks = new VectorBehaviorTask();
$predefinedTasks->push(new LogTask("Predefined Task 1: Starting up."));
$predefinedTasks->push(new LogTask("Predefined Task 2: Almost done."));

$sequence3 = new SequenceSelector($predefinedTasks);
$sequence3->addTask(new LogTask("Added Task: Wrapping up."));

runBehaviorTree($sequence3, 0.1, 5);