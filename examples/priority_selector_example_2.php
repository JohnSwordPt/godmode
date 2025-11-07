<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require __DIR__ . '/../vendor/autoload.php';

// --- Concrete Behavior Tasks for the Guard AI --- //

use godmode\core\BehaviorTask;
use godmode\core\StatefulBehaviorTask;
use godmode\data\VectorBehaviorTask;
use godmode\selector\PrioritySelector;

/**
 * Represents the guard's current state for the example.
 * In a real game, this would be part of a game state or entity class.
 */
class GuardState {
    public $isEnemyNearby = false;
    public $isLowHealth = false;
    public $isTargetDefeated = false;
    public $isSafe = true; // For evade task
    public $patrolPointsReached = 0;
    public $currentActivity = 'None';

    public function reset() {
        $this->isEnemyNearby = false;
        $this->isLowHealth = false;
        $this->isTargetDefeated = false;
        $this->isSafe = true;
        $this->patrolPointsReached = 0;
        $this->currentActivity = 'None';
    }
}

// Global guard state for simplicity in this example
$guardState = new GuardState();

class EvadeThreatTask extends StatefulBehaviorTask {
    public function activate(): void {
        global $guardState;
        echo "\n[EVADE] Guard: Activating Evade Threat! Running away...\n";
        $guardState->currentActivity = 'Evading';
        $guardState->isSafe = false;
    }

    protected function updateTask(float $dt): int {
        global $guardState;

        if (!$guardState->isLowHealth || !$guardState->isEnemyNearby) {
            return BehaviorTask::FAIL; // Condition to evade not met
        }

        // We are low on health and an enemy is nearby
        if ($guardState->isSafe) {
            echo "[EVADE] Guard: Evaded successfully!\n";
            return BehaviorTask::SUCCESS;
        }

        echo "[EVADE] Guard: Still evading...\n";
        if (rand(0, 100) < 30) {
            $guardState->isSafe = true;
        }
        return BehaviorTask::RUNNING;
    }

    public function deactivate(): void {
        global $guardState;
        echo "[EVADE] Guard: Deactivating Evade Threat.\n";
        $guardState->currentActivity = 'None';
        $guardState->isSafe = true;
    }
}

class AttackEnemyTask extends StatefulBehaviorTask {
    public function activate(): void {
        global $guardState;
        echo "\n[ATTACK] Guard: Activating Attack Enemy! Engaging...\n";
        $guardState->currentActivity = 'Attacking';
    }

    protected function updateTask(float $dt): int {
        global $guardState;
        if ($guardState->isEnemyNearby && !$guardState->isLowHealth) {
            echo "[ATTACK] Guard: Attacking the enemy!\n";
            // Simulate eventually defeating the enemy
            if (rand(0, 100) < 20) { // 20% chance to defeat enemy each update
                $guardState->isTargetDefeated = true;
                $guardState->isEnemyNearby = false; // Enemy gone
            }
            return BehaviorTask::RUNNING;
        } elseif ($guardState->isTargetDefeated) {
            echo "[ATTACK] Guard: Enemy defeated!\n";
            $guardState->isTargetDefeated = false;
            return BehaviorTask::SUCCESS;
        }
        echo "[ATTACK] Guard: No enemy to attack or too weak.\n";
        return BehaviorTask::FAIL;
    }

    public function deactivate(): void {
        global $guardState;
        echo "[ATTACK] Guard: Deactivating Attack Enemy.\n";
        $guardState->currentActivity = 'None';
    }
}

class PatrolAreaTask extends StatefulBehaviorTask {
    public function activate(): void {
        global $guardState;
        echo "\n[PATROL] Guard: Activating Patrol Area. Starting route.\n";
        $guardState->currentActivity = 'Patrolling';
    }

    protected function updateTask(float $dt): int {
        global $guardState;
        echo "[PATROL] Guard: Patrolling. Reached point " . ($guardState->patrolPointsReached + 1) . "/3.\n";
        if ($guardState->patrolPointsReached < 2) {
            $guardState->patrolPointsReached++;
            return BehaviorTask::RUNNING;
        } else {
            echo "[PATROL] Guard: Completed patrol route.\n";
            $guardState->patrolPointsReached = 0; // Reset for next patrol
            return BehaviorTask::SUCCESS; // Patrol complete, can go to idle or start new patrol
        }
    }

    public function deactivate(): void {
        global $guardState;
        echo "[PATROL] Guard: Deactivating Patrol Area.\n";
        $guardState->currentActivity = 'None';
    }
}

class IdleTask extends StatefulBehaviorTask {
    public function activate(): void {
        global $guardState;
        echo "\n[IDLE] Guard: Activating Idle. Resting.\n";
        $guardState->currentActivity = 'Idling';
    }

    protected function updateTask(float $dt): int {
        global $guardState;
        echo "[IDLE] Guard: Idling... waiting for something to happen.\n";
        return BehaviorTask::RUNNING; // Always running if nothing else to do
    }

    public function deactivate(): void {
        global $guardState;
        echo "[IDLE] Guard: Deactivating Idle.\n";
        $guardState->currentActivity = 'None';
    }
}

class GuardAI {

    /** @var PrioritySelector $mainSelector */
    private $mainSelector;

    public function __construct() {
        $tasks = new VectorBehaviorTask();
        
        // Tasks are added in order of priority (highest first)
        $tasks->push(new EvadeThreatTask());  // Highest priority
        $tasks->push(new AttackEnemyTask());  // High priority
        $tasks->push(new PatrolAreaTask());   // Medium priority
        $tasks->push(new IdleTask());         // Lowest priority

        $this->mainSelector = new PrioritySelector($tasks);
        echo "Guard AI initialized with PrioritySelector.\n";
    }

    public function update(float $dt): void {
        echo "\n--- Guard Update (Delta Time: ".sprintf("%.2f", $dt).") ---\n";
        global $guardState;
        echo "Guard State: Enemy Nearby=" . ($guardState->isEnemyNearby ? 'Yes' : 'No') .
             ", Low Health=" . ($guardState->isLowHealth ? 'Yes' : 'No') .
             ", Activity=".$guardState->currentActivity."\n";
        
        $this->mainSelector->update($dt);
    }

    public function reset(): void {
        $this->mainSelector->reset();
        global $guardState;
        $guardState->reset();
        echo "Guard AI and State Reset.\n";
    }
}

// --- Simulation --- //
echo "\n### Guard AI Simulation Start ###\n";
$guardAI = new GuardAI();

// Scenario 1: Idle state initially
echo "\n=== Scenario 1: Initial Idle State ===\n";
for ($i = 0; $i < 3; $i++) {
    $guardAI->update(0.1);
}

// Scenario 2: Patrol state (Idle will eventually yield to Patrol)
echo "\n=== Scenario 2: Patrol State ===\n";
// Note: Patrol is designed to run even if Idle is active, as Patrol is higher priority.
// Idle will be deactivated.
for ($i = 0; $i < 5; $i++) {
    $guardAI->update(0.1);
}

// Scenario 3: Enemy appears, Guard Attacks (Patrol will be interrupted)
echo "\n=== Scenario 3: Enemy Appears, Guard Attacks ===\n";
global $guardState;
$guardState->isEnemyNearby = true;
for ($i = 0; $i < 5; $i++) {
    $guardAI->update(0.1);
}

// Scenario 4: Guard gets low health, Evades (Attack will be interrupted)
echo "\n=== Scenario 4: Guard Low Health, Evades ===\n";
$guardState->isLowHealth = true;
$guardState->isEnemyNearby = true; // Still an enemy to evade from
for ($i = 0; $i < 5; $i++) {
    $guardAI->update(0.1);
}

// Scenario 5: Evade succeeds, then Attack, then Patrol, then Idle
echo "\n=== Scenario 5: Evade Succeeds, Flow Resumes ===\n";
$guardState->isLowHealth = false; // Health recovered or threat too distant
for ($i = 0; $i < 10; $i++) {
    $guardAI->update(0.1);
}

echo "\n### Guard AI Simulation End ###\n";
