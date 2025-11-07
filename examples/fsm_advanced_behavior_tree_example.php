<?php

require __DIR__ . '/../vendor/autoload.php';

use godmode\core\BehaviorTree;
use godmode\decorator\LoopingDecorator;
use godmode\decorator\PredicateFilter;
use godmode\decorator\SemaphoreDecorator;
use godmode\pred\EntryExistsPred;
use godmode\selector\PrioritySelector;
use godmode\selector\SequenceSelector;
use godmode\selector\ParallelSelector;
use godmode\core\Semaphore;
use godmode\data\Blackboard as DataBlackboard;
use godmode\TaskFactory;
use godmode\data\VectorBehaviorTask;

echo "--- Advanced Behavior Tree Example ---" . PHP_EOL;

// 1. Setup Blackboard and TaskFactory
$blackboard = new DataBlackboard();
$taskFactory = new TaskFactory($blackboard);

// 2. Define some basic tasks using the TaskFactory
$logTask = function(string $message) use ($taskFactory) {
    return $taskFactory->call(function() use ($message) {
        echo $message . PHP_EOL;
        return true;
    });
};

$setEntryTask = function(string $key, $value) use ($taskFactory, $blackboard) {
    return $taskFactory->storeEntry($blackboard->getEntry($key), $value);
};

$removeEntryTask = function(string $key) use ($taskFactory, $blackboard) {
    return $taskFactory->removeEntry( $blackboard->getEntry($key) );
};

// 3. Create a Semaphore for resource management
$resourceSemaphore = new Semaphore('resourceSemaphore', 1); // Only one worker can access the resource at a time

// 4. Build the Advanced Behavior Tree

// Task: Acquire Resource
$acquireResourceTask = $taskFactory->call(function() use ($resourceSemaphore) {
    if ($resourceSemaphore->acquire()) {
        echo "Worker acquired resource.";
        return true;
    }
    echo "Worker failed to acquire resource (busy).";
    return false;
});

// Task: Release Resource
$releaseResourceTask = $taskFactory->call(function() use ($resourceSemaphore) {
    $resourceSemaphore->release();
    echo "Worker released resource.";
    return true;
});

// Sequence: Work on Resource (acquire -> do work -> release)
$workOnResourceSequence = new SequenceSelector(new VectorBehaviorTask(
    $logTask("Worker is performing critical work on resource..."),
    $taskFactory->call(function() {
        // Simulate work
        usleep(200000); // 0.2 seconds
        return true;
    })
));

// Decorator: Limit resource work to one at a time (using SemaphoreDecorator)
$limitedResourceWork = new SemaphoreDecorator($resourceSemaphore, $workOnResourceSequence);

// Task: Perform a routine task
$routineTask = $logTask("Worker is performing a routine task.");

// Task: Perform an urgent task (sets an urgent flag)
$urgentTask = new SequenceSelector(new VectorBehaviorTask(
    $logTask("Worker is performing an URGENT task!"),
    $setEntryTask("urgent_flag", true),
    $taskFactory->call(function() {
        // Simulate urgent work
        usleep(100000); // 0.1 seconds
        return true;
    }),
    $removeEntryTask("urgent_flag")
));

// Decorator: Only perform urgent task if 'urgent_condition' is true
$urgentConditionPredicate = new EntryExistsPred($blackboard->getEntry("urgent_condition"));
$conditionalUrgentTask = new PredicateFilter($urgentConditionPredicate, $urgentTask);

// Selector: Prioritize urgent tasks over routine tasks
$priorityTasks = new PrioritySelector(new VectorBehaviorTask(
    $conditionalUrgentTask, // High priority if urgent_condition exists
    $routineTask            // Lower priority
));

// Decorator: Loop the priority tasks 3 times
$loopPriorityTasks = new LoopingDecorator(LoopingDecorator::BREAK_NEVER, 3, $priorityTasks);

// Parallel Selector: Do some background tasks while main tasks are running
$backgroundTasks = new ParallelSelector(ParallelSelector::ALL_COMPLETE, new VectorBehaviorTask(
    $logTask("Background task 1: Monitoring system..."),
    $taskFactory->call(function() {
        // Simulate background work
        usleep(50000); // 0.05 seconds
        echo "Background task 2: Cleaning up...";
        return true;
    })
));

// Main Behavior Tree: Combines all the above
$mainBehaviorTree = new SequenceSelector(new VectorBehaviorTask(
    $logTask("Worker AI starting..."),
    $setEntryTask("urgent_condition", true), // Set urgent condition for the first run
    $loopPriorityTasks,
    $removeEntryTask("urgent_condition"), // Remove urgent condition
    $logTask("Worker AI continuing with normal operations..."),
    $limitedResourceWork, // Perform resource-intensive work
    $backgroundTasks, // Run background tasks
    $logTask("Worker AI finished its cycle.")
));

$behaviorTree = new BehaviorTree($mainBehaviorTree, $blackboard);

// 5. Run the Behavior Tree
echo "--- Running Behavior Tree (Cycle 1) ---" . PHP_EOL;
$behaviorTree->update(1.0);
echo "Blackboard state after Cycle 1:" . PHP_EOL;
// print_r($blackboard->getAll());
echo "";

echo "--- Running Behavior Tree (Cycle 2 - without urgent condition) ---" . PHP_EOL;
$behaviorTree->update(1.0);
echo "Blackboard state after Cycle 2:" . PHP_EOL;
// print_r($blackboard->getAll());
echo "";

echo "--- Running Behavior Tree (Cycle 3 - demonstrating semaphore) ---" . PHP_EOL;
// Simulate another worker trying to acquire the resource
$anotherWorkerTree = new BehaviorTree(new SequenceSelector(new VectorBehaviorTask(
    $logTask("Another worker trying to acquire resource..."),
    $limitedResourceWork, // This should fail to acquire if the first worker holds it
    $logTask("Another worker finished its attempt.")
)), $blackboard);

// Run both trees in sequence to show semaphore effect
$behaviorTree->update(1.0); // First worker acquires
$anotherWorkerTree->update(1.0); // Second worker tries and fails
$behaviorTree->update(1.0); // First worker releases (if it was still holding)
echo "Blackboard state after Cycle 3:";
// print_r($blackboard->getAll());

?>
