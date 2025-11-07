<?php

namespace godmode\example;

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require __DIR__ . '/../vendor/autoload.php';

use godmode\core\BehaviorTask;
use godmode\core\TimeKeeper;
use godmode\data\Blackboard;
use godmode\TaskFactory;

// #################################################################
// 1. MOCK TASKS AND CLASSES
//    In a real game, these would be your actual game logic tasks.
// #################################################################

/**
 * A simple mock task to simulate an action that takes time.
 */
class TimedActionTask extends BehaviorTask {
    private $name;
    private $duration;
    private $elapsed = 0.0;
    private $status;

    public function __construct($name, $duration) {
        $this->name = $name;
        $this->duration = $duration;
        $this->status = BehaviorTask::SUCCESS; // Ready to run
    }

    protected function updateTask(float $dt): int {
        if ($this->status === BehaviorTask::SUCCESS) { // On first run
            echo "[ACTION] Starting: {$this->name}\n";
            $this->elapsed = 0.0;
            $this->status = BehaviorTask::RUNNING;
        }

        if ($this->status === BehaviorTask::RUNNING) {
            $this->elapsed += $dt;
            if ($this->elapsed >= $this->duration) {
                echo "[ACTION] Finished: {$this->name}\n";
                $this->status = BehaviorTask::SUCCESS;
                return BehaviorTask::SUCCESS;
            }
            return BehaviorTask::RUNNING;
        }
        return $this->status;
    }

    public function reset(): void {
        $this->elapsed = 0.0;
        $this->status = BehaviorTask::SUCCESS;
    }
}

/**
 * A mock TimeKeeper to control the simulation time.
 */
class MockTimeKeeper implements TimeKeeper {
    private $time = 0.0;
    public function timeNow(): float { return $this->time; }
    public function advance(float $dt) { $this->time += $dt; }
}


// #################################################################
// 2. SETTING UP THE ENVIRONMENT
// #################################################################

echo "--- Initializing Guard AI Example ---\\n";

$blackboard = new Blackboard();
$timeKeeper = new MockTimeKeeper();
$factory = new TaskFactory($timeKeeper);

// Get mutable entries from the blackboard for our AI's state
$health = $blackboard->getEntry('health');
$threatLevel = $blackboard->getEntry('threat_level');
$isPatrolling = $blackboard->getEntry('is_patrolling');

// Set initial state
$health->store(100);
$threatLevel->store(0);
$isPatrolling->store(false);

// #################################################################
// 3. DEFINING THE BEHAVIOR TREE USING THE TASKFACTORY
// #################################################################

echo "--- Building Behavior Tree ---\\n";

// Create reusable predicates for checking blackboard state
$isHealthLow = $factory->entryEquals($health, 20);
$isThreatHigh = $factory->entryEquals($threatLevel, 10);

// Define the AI's core behaviors (leaf tasks)
$patrolAction = new TimedActionTask('Patrolling', 3.0);
$attackAction = new TimedActionTask('Attacking', 2.0);
$fleeAction = new TimedActionTask('Fleeing', 4.0);

// Build the tree from the bottom up using the factory

// BEHAVIOR: Flee when health is 20
$fleeBehavior = $factory->sequence([
    $factory->pred(function() { echo "[CHECK] Health is low! Must flee!\n"; return true; }),
    $fleeAction,
    $factory->call(function() use ($health) {
        echo "[STATE] Restored some health after fleeing.\n";
        $health->store(50); // Recover some health
    })
]);

// BEHAVIOR: Attack when threat level is 10
$attackBehavior = $factory->sequence([
    $factory->pred(function() { echo "[CHECK] Threat is high! Engaging!\n"; return true; }),
    $attackAction,
    $factory->call(function() use ($threatLevel) {
        echo "[STATE] Threat neutralized.\n";
        $threatLevel->store(0); // Reset threat after attacking
    })
]);

// BEHAVIOR: Default patrol routine
$patrolBehavior = $factory->sequence([
    $factory->storeEntry($isPatrolling, true),
    $factory->pred(function() { echo "[INFO] Starting patrol route.\n"; return true; }),
    $patrolAction,
    $factory->wait(Blackboard::staticEntry(1.0)), // Wait 1 second
    $factory->call(function() use ($threatLevel) {
        // Randomly increase threat level to simulate finding an enemy
        if (rand(0, 1) === 1) {
            echo "[STATE] Spotted a threat!\n";
            $threatLevel->store(10);
        } else {
            echo "[INFO] All clear.\n";
        }
    }),
    $factory->storeEntry($isPatrolling, false)
]);


// ASSEMBLE THE ROOT: A Priority Selector
// The tree will try each of these branches in order, from top to bottom, on every tick.
// 1. If health is low, it will always choose the flee behavior.
// 2. If not, and if threat is high, it will choose the attack behavior.
// 3. Otherwise, it will fall back to the default patrol behavior.
$root = $factory->selectWithPriority([
    $factory->enterIf($isHealthLow, $fleeBehavior),
    $factory->enterIf($isThreatHigh, $attackBehavior),
    $patrolBehavior
]);

// Finally, wrap the entire tree in a loop so it runs continuously.
$tree = $factory->loopForever($root);


// #################################################################
// 4. RUNNING THE SIMULATION
// #################################################################

echo "\n--- Starting Simulation ---\\n";

$simulationTicks = 20;
$dt = 1.0; // Each tick is 1 second

for ($i = 1; $i <= $simulationTicks; $i++) {
    echo "\n======== Tick {$i} (Time: {$timeKeeper->timeNow()}s) ========\\n";
    echo "[STATUS] Health: {$health->value()}, Threat: {$threatLevel->value()}\n";

    // On tick 5, simulate the guard taking a lot of damage
    if ($i === 5) {
        echo "[EVENT] The guard was ambushed! Health dropped significantly.\n";
        $health->store(20);
    }

    // Update the main behavior tree
    $tree->update($dt);

    // Advance the simulation time
    $timeKeeper->advance($dt);
}

echo "\n--- Simulation Finished ---\\n";

