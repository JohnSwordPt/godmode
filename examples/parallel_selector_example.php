<?php

namespace godmode\example;

require_once __DIR__ . '/../vendor/autoload.php';

use godmode\core\BehaviorTask;
use godmode\core\StatefulBehaviorTask;
use godmode\selector\ParallelSelector;

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
    }
}

// Our main execution logic
function runBehaviorTree(StatefulBehaviorTask $rootTask, float $deltaTime, int $maxIterations = 10) {
    echo "--- Starting Behavior Tree --- \n";
    for ($i = 0; $i < $maxIterations; $i++) {
        echo "\nIteration " . ($i + 1) . ":\n";
        $status = $rootTask->update($deltaTime);

        if ($status === BehaviorTask::SUCCESS) {
            echo "Behavior Tree: SUCCEEDED!\n";
            break;
        } elseif ($status === BehaviorTask::FAIL) {
            echo "Behavior Tree: FAILED!\n";
            break;
        } else {
            echo "Behavior Tree: RUNNING...\n";
        }
    }
    echo "--- Behavior Tree Finished ---\n";
}

// --- Example Usage ---

echo "--- Scenario 1: ParallelSelector with ALL_SUCCESS ---\n";
$parallel1 = new ParallelSelector(ParallelSelector::ALL_SUCCESS);
$parallel1->addTask(new WaitTask(0.3))
          ->addTask(new LogTask("Task 1.2"))
          ->addTask(new WaitTask(0.5));

runBehaviorTree($parallel1, 0.1, 10);

echo "\n--- Scenario 2: ParallelSelector with ALL_SUCCESS and one failure ---\n";
$parallel2 = new ParallelSelector(ParallelSelector::ALL_SUCCESS);
$parallel2->addTask(new WaitTask(0.3))
          ->addTask(new LogTask("Task 2.2", BehaviorTask::FAIL))
          ->addTask(new WaitTask(0.5));

runBehaviorTree($parallel2, 0.1, 10);


echo "\n--- Scenario 3: ParallelSelector with ANY_SUCCESS ---\n";
$parallel3 = new ParallelSelector(ParallelSelector::ANY_SUCCESS);
$parallel3->addTask(new WaitTask(0.3))
          ->addTask(new LogTask("Task 3.2", BehaviorTask::FAIL))
          ->addTask(new WaitTask(0.5));

runBehaviorTree($parallel3, 0.1, 10);

echo "\n--- Scenario 4: ParallelSelector with ANY_SUCCESS and all fail ---\n";
$parallel4 = new ParallelSelector(ParallelSelector::ANY_SUCCESS);
$parallel4->addTask(new LogTask("Task 4.1", BehaviorTask::FAIL))
          ->addTask(new LogTask("Task 4.2", BehaviorTask::FAIL));

runBehaviorTree($parallel4, 0.1, 10);

echo "\n--- Scenario 5: ParallelSelector with ALL_FAIL ---\n";
$parallel5 = new ParallelSelector(ParallelSelector::ALL_FAIL);
$parallel5->addTask(new LogTask("Task 5.1", BehaviorTask::FAIL))
          ->addTask(new LogTask("Task 5.2", BehaviorTask::FAIL));

runBehaviorTree($parallel5, 0.1, 10);

echo "\n--- Scenario 6: ParallelSelector with ALL_FAIL and one success ---\n";
$parallel6 = new ParallelSelector(ParallelSelector::ALL_FAIL);
$parallel6->addTask(new LogTask("Task 6.1", BehaviorTask::FAIL))
          ->addTask(new LogTask("Task 6.2", BehaviorTask::SUCCESS));

runBehaviorTree($parallel6, 0.1, 10);

echo "\n--- Scenario 7: ParallelSelector with ANY_FAIL ---\n";
$parallel7 = new ParallelSelector(ParallelSelector::ANY_FAIL);
$parallel7->addTask(new LogTask("Task 7.1", BehaviorTask::SUCCESS))
          ->addTask(new LogTask("Task 7.2", BehaviorTask::FAIL));

runBehaviorTree($parallel7, 0.1, 10);

echo "\n--- Scenario 8: ParallelSelector with ANY_FAIL and all success ---\n";
$parallel8 = new ParallelSelector(ParallelSelector::ANY_FAIL);
$parallel8->addTask(new LogTask("Task 8.1", BehaviorTask::SUCCESS))
          ->addTask(new LogTask("Task 8.2", BehaviorTask::SUCCESS));

runBehaviorTree($parallel8, 0.1, 10);

echo "\n--- Scenario 9: ParallelSelector with ALL_COMPLETE ---\n";
$parallel9 = new ParallelSelector(ParallelSelector::ALL_COMPLETE);
$parallel9->addTask(new WaitTask(0.3))
          ->addTask(new LogTask("Task 9.2", BehaviorTask::FAIL))
          ->addTask(new WaitTask(0.5));

runBehaviorTree($parallel9, 0.1, 10);

echo "\n--- Scenario 10: ParallelSelector with ANY_COMPLETE ---\n";
$parallel10 = new ParallelSelector(ParallelSelector::ANY_COMPLETE);
$parallel10->addTask(new WaitTask(0.3))
           ->addTask(new LogTask("Task 10.2", BehaviorTask::FAIL))
           ->addTask(new WaitTask(0.5));

runBehaviorTree($parallel10, 0.1, 10);


