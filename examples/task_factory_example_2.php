<?php

require_once __DIR__ . '/../vendor/autoload.php';

use godmode\TaskFactory;
use godmode\core\BehaviorTree;
use godmode\core\SystemTimeKeeper;
use godmode\core\TimeKeeper;
use godmode\data\Blackboard;
use godmode\data\EntryImpl;
use godmode\data\MutableEntry;
use godmode\data\StaticEntry;
use godmode\task\FunctionTask;
use godmode\util\RandomWeights;
use godmode\core\BehaviorTask;
use godmode\core\ScopedResource;
use godmode\core\Semaphore;

// 1. Setup: Initialize Blackboard, TimeKeeper, RandomStream, and TaskFactory
$blackboard = new Blackboard();
$timeKeeper = new SystemTimeKeeper();
$randomStream = new RandomWeights();
$factory = new TaskFactory($timeKeeper, $randomStream, $blackboard);

echo "--- TaskFactory Example ---" . PHP_EOL;

// Helper function to run and display results
function runTree(string $name, BehaviorTree $tree, Blackboard $blackboard, SystemTimeKeeper $timeKeeper) {
    echo PHP_EOL . "Running: " . $name . PHP_EOL;
    $timeKeeper->reset();
    $tree->update(0);
    $status = $tree->update(0);
    echo "Initial Status: " . $status . PHP_EOL;

    while ($status === BehaviorTask::RUNNING) {
        $timeKeeper->advanceTime(1); // Advance time for decorators like DelayFilter
        $status = $tree->update(1);
        echo "Status: " . $status . PHP_EOL;
    }
    echo "Final Status: " . $status . PHP_EOL;
    echo "Blackboard: " . json_encode($blackboard->getAll()->getArrayCopy()) . PHP_EOL;
}

// --- Basic Tasks ---

// 2. Basic Tasks: call, wait, noOp, storeEntry, removeEntry
$entryA = new EntryImpl($blackboard);
$entryB = new EntryImpl($blackboard);

$task1 = $factory->call(function() { echo "Task 1: Called." . PHP_EOL; return BehaviorTask::SUCCESS; });
$task2 = $factory->wait(new StaticEntry(1)); // Wait for 1 unit of time
$task3 = $factory->noOp();
$task4 = $factory->storeEntry($entryA, "valueA");
$task5 = $factory->removeEntry($entryA);

$sequenceOfBasicTasks = $factory->sequence([
    $task1,
    $factory->call(function() { echo "Task 1.5: Waiting for 1 unit." . PHP_EOL; return BehaviorTask::SUCCESS; }),
    $task2,
    $factory->call(function() { echo "Task 2.5: Wait complete." . PHP_EOL; return BehaviorTask::SUCCESS; }),
    $task3,
    $task4,
    $factory->call(function() use ($entryA) { echo "Task 4: Stored keyA." . PHP_EOL; return BehaviorTask::SUCCESS; }),
    $factory->storeEntry($entryB, 123),
    $factory->call(function() use ($entryB) { echo "Task 4.5: Stored keyB." . PHP_EOL; return BehaviorTask::SUCCESS; }),
    $task5,
    $factory->call(function() { echo "Task 5: Removed keyA." . PHP_EOL; return BehaviorTask::SUCCESS; }),
]);
runTree("Basic Tasks Sequence", new BehaviorTree($sequenceOfBasicTasks), $blackboard, $timeKeeper);

// --- Predicates ---

// 3. Predicates: pred, entryExists, entryNotExists, entryEquals, not, and, or
$blackboard->getEntry("testKey")->store("testValue");
$blackboard->getEntry("numberKey")->store(10);
$pred1 = $factory->pred(function() { echo "Pred 1: Always true." . PHP_EOL; return true; });
$pred2 = $factory->entryExists(new StaticEntry("testKey"));
$pred3 = $factory->entryNotExists(new StaticEntry("nonExistentKey"));
$pred4 = $factory->entryEquals(new StaticEntry("testKey"), "testValue");
$pred5 = $factory->entryEquals(new StaticEntry("numberKey"), 10);

$notPred = $factory->not($factory->entryExists(new StaticEntry("nonExistentKey"))); // Should be true
$andPred = $factory->and([$pred2, $pred4, $pred5]); // Should be true
$orPred = $factory->or([$factory->entryExists(new StaticEntry("nonExistentKey")), $pred2]); // Should be true

$predicateTestTree = $factory->sequence([
    $factory->enterIf($pred1, $factory->call(function() { echo "Pred 1 is true." . PHP_EOL; return BehaviorTask::SUCCESS; })),
    $factory->enterIf($pred2, $factory->call(function() { echo "Entry 'testKey' exists." . PHP_EOL; return BehaviorTask::SUCCESS; })),
    $factory->enterIf($pred3, $factory->call(function() { echo "Entry 'nonExistentKey' does not exist." . PHP_EOL; return BehaviorTask::SUCCESS; })),
    $factory->enterIf($pred4, $factory->call(function() { echo "Entry 'testKey' equals 'testValue'." . PHP_EOL; return BehaviorTask::SUCCESS; })),
    $factory->enterIf($pred5, $factory->call(function() { echo "Entry 'numberKey' equals 10." . PHP_EOL; return BehaviorTask::SUCCESS; })),
    $factory->enterIf($notPred, $factory->call(function() { echo "Not Predicate is true." . PHP_EOL; return BehaviorTask::SUCCESS; })),
    $factory->enterIf($andPred, $factory->call(function() { echo "AND Predicate is true." . PHP_EOL; return BehaviorTask::SUCCESS; })),
    $factory->enterIf($orPred, $factory->call(function() { echo "OR Predicate is true." . PHP_EOL; return BehaviorTask::SUCCESS; })),
]);
runTree("Predicate Tests", new BehaviorTree($predicateTestTree), $blackboard, $timeKeeper);

// --- Decorators ---

// 4. Decorators: runWhile, enterIf, exitIf, loop, loopForever, loopUntilSuccess, loopUntilFail, loopUntilComplete, withRepeatDelay, withSemaphore, using

// runWhile / exitIf
$counterEntry = $blackboard->getEntry("counter");
    $blackboard->getEntry("counter")->store(0);$incrementTask = $factory->call(function() use ($counterEntry) {
    $current = $counterEntry->value();
    $counterEntry->store($current + 1);
    echo "Counter: " . $counterEntry->value() . PHP_EOL;
    return BehaviorTask::RUNNING; // Keep running until predicate stops it
});
$runWhileExample = $factory->runWhile(
    $factory->pred(function() use ($counterEntry) { return $counterEntry->value() < 3; }),
    $incrementTask
);
runTree("Run While Counter < 3", new BehaviorTree($runWhileExample), $blackboard, $timeKeeper);

    $blackboard->getEntry("counter")->store(0); // Reset counter
$exitIfExample = $factory->exitIf(
    $factory->pred(function() use ($counterEntry) { return $counterEntry->value() >= 3; }),
    $incrementTask
);
runTree("Exit If Counter >= 3", new BehaviorTree($exitIfExample), $blackboard, $timeKeeper);

// loop
    $blackboard->getEntry("loopCount")->store(0);$loopTask = $factory->call(function() use ($blackboard) {
    $current = $blackboard->getEntry("loopCount")->value();
    $blackboard->getEntry("loopCount")->store($current + 1);
    echo "Loop Task: " . $blackboard->getEntry("loopCount")->value() . PHP_EOL;
    return BehaviorTask::SUCCESS;
});
$loopExample = $factory->loop(3, $loopTask);
runTree("Loop 3 times", new BehaviorTree($loopExample), $blackboard, $timeKeeper);

// loopUntilSuccess (demonstrated with a task that eventually succeeds)
    $blackboard->getEntry("successCounter")->store(0);$eventualSuccessTask = $factory->call(function() use ($blackboard) {
    $current = $blackboard->getEntry("successCounter")->value();
    $blackboard->getEntry("successCounter")->store($current + 1);
    echo "Eventual Success Task: " . $blackboard->getEntry("successCounter")->value() . PHP_EOL;
    if ($current >= 2) {
        return BehaviorTask::SUCCESS;
    }
    return BehaviorTask::FAIL;
});
$loopUntilSuccessExample = $factory->loopUntilSuccess($eventualSuccessTask);
runTree("Loop Until Success", new BehaviorTree($loopUntilSuccessExample), $blackboard, $timeKeeper);

// withRepeatDelay
    $blackboard->getEntry("delayCounter")->store(0);$delayTask = $factory->call(function() use ($blackboard) {
    $current = $blackboard->getEntry("delayCounter")->value();
    $blackboard->getEntry("delayCounter")->store($current + 1);
    echo "Delay Task: " . $blackboard->getEntry("delayCounter")->value() . PHP_EOL;
    return BehaviorTask::SUCCESS;
});
$withRepeatDelayExample = $factory->withRepeatDelay(new StaticEntry(2), $delayTask); // 2 units delay
runTree("With Repeat Delay (should run, then wait)", new BehaviorTree($withRepeatDelayExample), $blackboard, $timeKeeper);
runTree("With Repeat Delay (should wait)", new BehaviorTree($withRepeatDelayExample), $blackboard, $timeKeeper); // Should not run immediately
$timeKeeper->advanceTime(2); // Advance time past delay
runTree("With Repeat Delay (should run after delay)", new BehaviorTree($withRepeatDelayExample), $blackboard, $timeKeeper);

// withSemaphore
$semaphore = new Semaphore("mySemaphore", 1); // Only 1 task can run at a time
$semaphoreTask1 = $factory->call(function() { echo "Semaphore Task 1 running." . PHP_EOL; return BehaviorTask::SUCCESS; });
$semaphoreTask2 = $factory->call(function() { echo "Semaphore Task 2 running." . PHP_EOL; return BehaviorTask::SUCCESS; });
$withSemaphoreExample = $factory->parallel([
    $factory->withSemaphore($semaphore, $semaphoreTask1),
    $factory->withSemaphore($semaphore, $semaphoreTask2),
]);
runTree("With Semaphore (only one should run at a time)", new BehaviorTree($withSemaphoreExample), $blackboard, $timeKeeper);

// using (ScopedResource)
$resource = new class implements ScopedResource { public $name = "MyResource"; public function acquire(): void { echo "Acquiring " . $this->name . PHP_EOL; } public function release(): void { echo "Releasing " . $this->name . PHP_EOL; } };
$usingTask = $factory->call(function() { echo "Using resource task running." . PHP_EOL; return BehaviorTask::SUCCESS; });
$usingExample = $factory->using($resource, $usingTask);
runTree("Using Scoped Resource", new BehaviorTree($usingExample), $blackboard, $timeKeeper);

// --- Selectors ---

// 5. Selectors: sequence, parallel, selectWithPriority, selectRandomly

// sequence (already demonstrated)

// parallel
    $parallelTask1 = $factory->call(function() { echo "Parallel Task 1 running." . PHP_EOL; return BehaviorTask::SUCCESS; });
$parallelTask2 = $factory->call(function() { echo "Parallel Task 2 running." . PHP_EOL; return BehaviorTask::SUCCESS; });$parallelExample = $factory->parallel([$parallelTask1, $parallelTask2]);
runTree("Parallel Tasks", new BehaviorTree($parallelExample), $blackboard, $timeKeeper);

// selectWithPriority
    $blackboard->getEntry("priorityTask1Runs")->store(0);
    $priorityTask1 = $factory->call(function() use ($blackboard) {
    $currentRuns = $blackboard->getEntry("priorityTask1Runs")->value();
    $blackboard->getEntry("priorityTask1Runs")->store($currentRuns + 1);
    echo "Priority Task 1 (high) running. Run: " . ($currentRuns + 1) . PHP_EOL;
    if ($currentRuns >= 2) {
        return BehaviorTask::SUCCESS;
    }
    return BehaviorTask::RUNNING;
});
$priorityTask2 = $factory->call(function() { echo "Priority Task 2 (low) running." . PHP_EOL; return BehaviorTask::SUCCESS; });$prioritySelectorExample = $factory->selectWithPriority([$priorityTask1, $priorityTask2]);
runTree("Priority Selector (Task 1 should run first and keep running)", new BehaviorTree($prioritySelectorExample), $blackboard, $timeKeeper);

// selectRandomly
    $randomTask1 = $factory->call(function() { echo "Random Task 1 running." . PHP_EOL; return BehaviorTask::SUCCESS; });
$randomTask2 = $factory->call(function() { echo "Random Task 2 running." . PHP_EOL; return BehaviorTask::SUCCESS; });$selectRandomlyExample = $factory->selectRandomly($randomStream, [
    $randomTask1, 1, // Task 1 with weight 1
    $randomTask2, 1, // Task 2 with weight 1
]);
runTree("Select Randomly", new BehaviorTree($selectRandomlyExample), $blackboard, $timeKeeper);

// --- Complex Example ---

/*
$agentHealth = $blackboard->getEntry("agentHealth");
    $blackboard->getEntry("agentHealth")->store(100);$blackboard->getEntry("enemyNearby")->store(false);
    $blackboard->getEntry("ammo")->store(5);
$checkHealth = $factory->pred(function() use ($agentHealth) {
    echo "Checking health: " . $agentHealth->value() . PHP_EOL;
    return $agentHealth->value() < 50;
});

$findCover = $factory->call(function() {
    echo "Agent finding cover..." . PHP_EOL;
    return BehaviorTask::SUCCESS;
});

$flee = $factory->sequence([
    $factory->call(function() { echo "Agent fleeing!" . PHP_EOL; return BehaviorTask::SUCCESS; }),
    $findCover,
]);

$attackEnemy = $factory->sequence([
    $factory->enterIf($factory->entryExists(new StaticEntry("enemyNearby")),
        $factory->sequence([
            $factory->pred(function() use ($blackboard) {
                if ($blackboard->getEntry("ammo")->value() > 0) {
                    echo "Attacking enemy! Ammo: " . $blackboard->getEntry("ammo")->value() . PHP_EOL;
                    $blackboard->getEntry("ammo")->store($blackboard->getEntry("ammo")->value() - 1);
                    return true;
                }
                echo "Out of ammo!" . PHP_EOL;
                return false;
            }),
            $factory->call(function() { return BehaviorTask::SUCCESS; }), // Actual attack
        ])
    ),
]);

$reload = $factory->call(function() use ($blackboard) {
    echo "Reloading weapon..." . PHP_EOL;
    $blackboard->getEntry("ammo")->store(10);
    return BehaviorTask::SUCCESS;
});

$patrol = $factory->call(function() {
    echo "Agent patrolling..." . PHP_EOL;
    return BehaviorTask::SUCCESS;
});

$agentBehavior = $factory->selectWithPriority([
    $factory->enterIf($checkHealth, $flee), // High priority: if low health, flee
    $factory->enterIf($factory->entryExists(new StaticEntry("enemyNearby")),
        $factory->sequence([
            $factory->enterIf($factory->pred(function() use ($blackboard) { return $blackboard->getEntry("ammo")->value() === 0; }), $reload),
            $attackEnemy,
        ])
    ),
    $patrol, // Default: patrol
]);

runTree("Agent Behavior (Patrolling)", new BehaviorTree($agentBehavior), $blackboard, $timeKeeper);

    $blackboard->getEntry("enemyNearby")->store(true);runTree("Agent Behavior (Enemy Nearby - Attack)", new BehaviorTree($agentBehavior), $blackboard, $timeKeeper);

    $blackboard->getEntry("ammo")->store(0);runTree("Agent Behavior (Enemy Nearby - Reload then Attack)", new BehaviorTree($agentBehavior), $blackboard, $timeKeeper);

    $blackboard->getEntry("agentHealth")->store(30);runTree("Agent Behavior (Low Health - Flee)", new BehaviorTree($agentBehavior), $blackboard, $timeKeeper);

echo PHP_EOL . "--- TaskFactory Example Complete ---" . PHP_EOL;
*/

