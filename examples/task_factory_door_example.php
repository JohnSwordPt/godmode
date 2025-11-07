<?php

require_once __DIR__ . '/../vendor/autoload.php';

use godmode\core\SystemTimeKeeper;
use godmode\data\Blackboard;
use godmode\TaskFactory;

// 1. Setup
$blackboard = new Blackboard();
$timeKeeper = new SystemTimeKeeper();
$factory = new TaskFactory($timeKeeper);

// Define entries
$userPresent = $blackboard->getEntry('user_present');
$userPresent->store(false);
$doorState = $blackboard->getEntry('door_state');
$doorState->store('locked');
$hasKey = $blackboard->getEntry('has_key');
$hasKey->store(false);

echo "Initial State:" . PHP_EOL;
echo "User Present: " . ($userPresent->value() ? "Yes" : "No") . PHP_EOL;
echo "Door State: " . $doorState->value() . PHP_EOL;
echo "Has Key: " . ($hasKey->value() ? "Yes" : "No") . PHP_EOL . PHP_EOL;

// 2. Define the "Open Door" behavior tree
$openDoorTask = $factory->sequence([
    $factory->entryEquals($userPresent, true),
    $factory->entryEquals($doorState, 'locked'),
    $factory->entryEquals($hasKey, true),
    $factory->call(function() { echo "Attempting to unlock door..." . PHP_EOL; return true; }),
    $factory->storeEntry($doorState, 'unlocked'),
    $factory->call(function() { echo "Door unlocked!" . PHP_EOL; return true; }),
]);

// 3. Test Case 1: Cannot open door (user not present)
echo "--- Test Case 1: User not present ---" . PHP_EOL;
$userPresent->store(false);
$doorState->store('locked');
$hasKey->store(true);
$openDoorTask->update(0.0);
echo "Door State after attempt: " . $doorState->value() . PHP_EOL . PHP_EOL;

// 4. Test Case 2: Cannot open door (no key)
echo "--- Test Case 2: No key ---" . PHP_EOL;
$userPresent->store(true);
$doorState->store('locked');
$hasKey->store(false);
$openDoorTask->update(0.0);
echo "Door State after attempt: " . $doorState->value() . PHP_EOL . PHP_EOL;

// 5. Test Case 3: Successfully open door
echo "--- Test Case 3: Success ---" . PHP_EOL;
$userPresent->store(true);
$doorState->store('locked');
$hasKey->store(true);
$openDoorTask->update(0.0);
echo "Door State after attempt: " . $doorState->value() . PHP_EOL . PHP_EOL;

// 6. Test Case 4: Door already unlocked
echo "--- Test Case 4: Door already unlocked ---" . PHP_EOL;
$userPresent->store(true);
$hasKey->store(true);
$openDoorTask->update(0.0);
echo "Door State after attempt: " . $doorState->value();
