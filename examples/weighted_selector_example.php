<?php

namespace godmode\example;

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require __DIR__ . '/../vendor/autoload.php';

// Use statements for classes defined in their respective mock namespaces or the actual library
use godmode\core\BehaviorTask;
use godmode\data\VectorWeightedTask;
use godmode\selector\WeightedTask;
use godmode\selector\WeightedSelector;
use godmode\util\RandomWeights;

/**
 * Concrete Mock Behavior Task for demonstration purposes.
 * This task simulates a behavior that takes a certain duration to complete.
 */
class MockBehaviorTask extends BehaviorTask {
    private $name;
    private $status;
    private $duration; // How long the task takes to complete
    private $elapsed;  // How much time has passed for this task
    private $shouldFailOnce; // For specific tasks to simulate failure

    private const READY = 11;

    public function __construct($name, $duration = 1.0, $failOnce = false) {
        $this->name = $name;
        $this->status = self::READY;
        $this->duration = $duration;
        $this->elapsed = 0.0;
        $this->shouldFailOnce = $failOnce;
        echo "[DEBUG] Task '{$this->name}' created.\n";
    }

    public function activate(): void {
        if ($this->status == self::READY) {
            echo "[DEBUG] Task '{$this->name}' activated.\n";
            $this->status = BehaviorTask::RUNNING;
            $this->elapsed = 0.0; // Reset elapsed time on activation
        }
    }

    public function deactivate(): void {
        if ($this->status == BehaviorTask::RUNNING) {
            echo "[DEBUG] Task '{$this->name}' deactivated.\n";
            $this->status = self::READY; // Back to ready
        }
    }

    public function update(float $dt): int {
        if ($this->shouldFailOnce) {
            echo "[TASK-STATUS] '{$this->name}' FAILED (intentional).\n";
            $this->shouldFailOnce = false; // Only fail once then behave normally
            $this->status = self::READY;
            return BehaviorTask::FAIL;
        }

        if ($this->status === self::READY) {
            $this->activate(); // Ensure task is active before updating
        }

        if ($this->status === BehaviorTask::RUNNING) {
            $this->elapsed += $dt;
            echo "[TASK-STATUS] Updating '{$this->name}'... (" . round($this->elapsed, 1) . "/{$this->duration})\n";

            if ($this->elapsed >= $this->duration) {
                echo "[TASK-STATUS] '{$this->name}' SUCCEEDED.\n";
                $this->status = self::READY; // Reset for potential re-selection
                return BehaviorTask::SUCCESS;
            } else {
                return BehaviorTask::RUNNING;
            }
        }
        return $this->status; // Should not reach here in normal flow
    }

    public function reset(): void {
        $this->status = self::READY;
        $this->elapsed = 0.0;
        echo "[DEBUG] Task '{$this->name}' reset.\n";
    }
}


echo "--- Initializing WeightedSelector Example ---\n";

// 1. Create a RandomStream instance (our mock)
$rng = new RandomWeights();

// 2. Define individual behavior tasks using our mock BehaviorTask
$wanderTask = new MockBehaviorTask("Wander", 3.0); // Takes 3 seconds
$eatTask = new MockBehaviorTask("Eat", 2.0);       // Takes 2 seconds
$sleepTask = new MockBehaviorTask("Sleep", 5.0);   // Takes 5 seconds
$searchForFoodTask = new MockBehaviorTask("SearchForFood", 1.0, true); // Fails once

// 3. Create WeightedTask instances for each behavior, assigning weights
// Higher weight = higher probability of selection
$weightedWander = new WeightedTask($wanderTask, 60); // 60% relative chance
$weightedEat = new WeightedTask($eatTask, 30);       // 30% relative chance
$weightedSleep = new WeightedTask($sleepTask, 10);   // 10% relative chance
$weightedSearch = new WeightedTask($searchForFoodTask, 5); // 5% relative chance (will fail first time)

// 4. Create a VectorWeightedTask to hold the collection of weighted tasks
$tasksCollection = new VectorWeightedTask();
$tasksCollection->push($weightedWander);
$tasksCollection->push($weightedEat);
$tasksCollection->push($weightedSleep);
$tasksCollection->push($weightedSearch);

// 5. Instantiate the WeightedSelector from the godmode library
$behaviorSelector = new WeightedSelector($rng, $tasksCollection);

echo "\n--- Simulation Started ---\n";

$dt = 1.0; // Delta time for each update tick (simulating 1 second per tick)
$simulationTicks = 15;

for ($i = 1; $i <= $simulationTicks; $i++) {
    echo "\n======== Tick {" . $i . "} / {" . $simulationTicks . "} ========\n";

    // Update the WeightedSelector
    $status = $behaviorSelector->update($dt);

    switch ($status) {
        case BehaviorTask::SUCCESS:
            echo "[SELECTOR-STATUS] WeightedSelector SUCCEEDED (selected task completed).\n";
            // Important: Reset the selector after success to allow it to pick a new task
            $behaviorSelector->reset();
            break;
        case BehaviorTask::FAIL:
            echo "[SELECTOR-STATUS] WeightedSelector FAILED (all tasks tried and failed).\n";
            // Important: Reset the selector after failure for a fresh attempt
            $behaviorSelector->reset();
            break;
        case BehaviorTask::RUNNING:
            echo "[SELECTOR-STATUS] WeightedSelector is RUNNING (current selected task is ongoing).\n";
            break;
        default:
            echo "[SELECTOR-STATUS] WeightedSelector returned unknown status: {" . $status . "}.\n";
            break;
    }
}

echo "\n--- Simulation Finished ---\n";

    // End godmode\example namespace block
