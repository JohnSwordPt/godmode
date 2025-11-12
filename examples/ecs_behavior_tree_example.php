<?php

namespace ECSBehaviorTreeDemo;

require_once __DIR__ . '/../vendor/autoload.php';

use ECS\Engine;
use ECS\Entity;
use ECSDemo\Components\Position;
use godmode\core\BehaviorTree;
use godmode\core\BehaviorTask;
use godmode\core\StatefulBehaviorTask;
use godmode\selector\SequenceSelector;
use godmode\selector\PrioritySelector;
use godmode\pred\FunctionPredicate;
use godmode\decorator\PredicateFilter;

use ECSBehaviorTreeDemo\Components\BehaviorTreeComponent;
use ECSBehaviorTreeDemo\Components\HealthComponent;
use ECSBehaviorTreeDemo\Nodes\BehaviorTreeNode;
use ECSBehaviorTreeDemo\Systems\BehaviorTreeSystem;

// --- Custom Behavior Tasks ---

class MoveTowardsTargetTask extends StatefulBehaviorTask
{
    private Entity $entity;
    private Entity $target;
    private float $speed;
    private float $threshold;

    public function __construct(Entity $entity, Entity $target, float $speed, float $threshold = 5.0)
    {
        $this->entity = $entity;
        $this->target = $target;
        $this->speed = $speed;
        $this->threshold = $threshold;
    }

    protected function updateTask(float $dt): int
    {
        /** @var Position $entityPos */
        $entityPos = $this->entity->get(Position::class);
        /** @var Position $targetPos */
        $targetPos = $this->target->get(Position::class);

        if (!$entityPos || !$targetPos) {
            echo "MoveTowardsTargetTask: Missing Position component.\n";
            return BehaviorTask::FAIL;
        }

        $dx = $targetPos->x - $entityPos->x;
        $dy = $targetPos->y - $entityPos->y;
        $distance = sqrt($dx * $dx + $dy * $dy);

        if ($distance <= $this->threshold) {
            echo "MoveTowardsTargetTask: Reached target. Distance: " . round($distance, 2) . "\n";
            return BehaviorTask::SUCCESS;
        }

        $directionX = $dx / $distance;
        $directionY = $dy / $distance;

        $dx = $targetPos->x - $entityPos->x;
        $dy = $targetPos->y - $entityPos->y;
        $distance = sqrt($dx * $dx + $dy * $dy);

        if ($distance <= $this->threshold) {
            echo "MoveTowardsTargetTask: Reached target. Distance: " . round($distance, 2) . "\n";
            return BehaviorTask::SUCCESS;
        }

        $directionX = $dx / $distance;
        $directionY = $dy / $distance;

        $entityPos->x += $directionX * $this->speed * $dt;
        $entityPos->y += $directionY * $this->speed * $dt;

        echo sprintf(
            "MoveTowardsTargetTask: Moving %s towards target. Current: (%.2f, %.2f), Target: (%.2f, %.2f), Distance: %.2f\n",
            $this->entity->Name,
            $entityPos->x, $entityPos->y,
            $targetPos->x, $targetPos->y,
            $distance
        );

        return BehaviorTask::RUNNING;
    }

    public function reset(): void {}
    public function deactivate(): void {}
}

class CheckHealthTask extends StatefulBehaviorTask
{
    private Entity $entity;
    private int $minHealth;

    public function __construct(Entity $entity, int $minHealth)
    {
        $this->entity = $entity;
        $this->minHealth = $minHealth;
    }

    protected function updateTask(float $dt): int
    {
        /** @var HealthComponent $healthComp */
        $healthComp = $this->entity->get(HealthComponent::class);

        if (!$healthComp) {
            echo "CheckHealthTask: Missing HealthComponent.\n";
            return BehaviorTask::FAIL;
        }

        if ($healthComp->health > $this->minHealth) {
            echo sprintf("CheckHealthTask: %s health (%d) is above %d.\n", $this->entity->Name, $healthComp->health, $this->minHealth);
            return BehaviorTask::SUCCESS;
        } else {
            echo sprintf("CheckHealthTask: %s health (%d) is at or below %d.\n", $this->entity->Name, $healthComp->health, $this->minHealth);
            return BehaviorTask::FAIL;
        }
    }

    public function reset(): void {}
    public function deactivate(): void {}
}

class AttackTask extends StatefulBehaviorTask
{
    private Entity $attacker;
    private Entity $target;
    private int $damage;

    public function __construct(Entity $attacker, Entity $target, int $damage)
    {
        $this->attacker = $attacker;
        $this->target = $target;
        $this->damage = $damage;
    }

    protected function updateTask(float $dt): int
    {
        /** @var HealthComponent $targetHealth */
        $targetHealth = $this->target->get(HealthComponent::class);

        if (!$targetHealth) {
            echo "AttackTask: Target missing HealthComponent.\n";
            return BehaviorTask::FAIL;
        }

        $targetHealth->health -= $this->damage;
        echo sprintf(
            "AttackTask: %s attacked %s for %d damage. %s health: %d\n",
            $this->attacker->Name,
            $this->target->Name,
            $this->damage,
            $this->target->Name,
            $targetHealth->health
        );

        return BehaviorTask::SUCCESS;
    }

    public function reset(): void {}
    public function deactivate(): void {}
}

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

// --- ECS Setup ---
$engine = new Engine();

// Add the BehaviorTreeSystem
$behaviorTreeSystem = new BehaviorTreeSystem();
$engine->AddSystem($behaviorTreeSystem, 1);

// --- Entities ---
$player = (new Entity('Player'))
    ->add(new Position(0, 0))
    ->add(new HealthComponent(100));

$enemy = (new Entity('Enemy'))
    ->add(new Position(50, 50))
    ->add(new HealthComponent(50));

$engine->AddEntity($player);
$engine->AddEntity($enemy);

// --- Player's Behavior Tree ---
// Sequence: Check if enemy is healthy, then move towards and attack
$playerAttackSequence = (new SequenceSelector())
    ->addTask(new CheckHealthTask($enemy, 1)) // Check if enemy has health > 0
    ->addTask(new MoveTowardsTargetTask($player, $enemy, 10.0, 10.0)) // Move towards enemy
    ->addTask(new AttackTask($player, $enemy, 10)); // Attack enemy

// Priority Selector: If enemy is healthy, attack; otherwise, do nothing (or patrol, etc.)
$playerRootSelector = (new PrioritySelector())
    ->addTask($playerAttackSequence)
    ->addTask(new LogTask("Player: Enemy is defeated or not found, patrolling...", BehaviorTask::RUNNING)); // Default if attack fails

$playerBehaviorTree = new BehaviorTree($playerRootSelector);
$player->add(new BehaviorTreeComponent($playerBehaviorTree));

// --- Simulation Loop ---
echo "--- Starting ECS-Behavior Tree Simulation ---\n";
$deltaTime = 1.0; // Simulate 1 second per update

for ($i = 0; $i < 100; $i++) {
    echo "\n--- Simulation Tick " . ($i + 1) . " ---\n";
    $engine->Update($deltaTime);

    // Break if enemy is defeated
    if ($enemy->get(HealthComponent::class)->health <= 0) {
        echo "\nEnemy defeated! Simulation ends.\n";
        break;
    }
}

echo "\n--- ECS-Behavior Tree Simulation Finished ---\n";

// Clean up
$engine->releaseNodeList(BehaviorTreeNode::class);
$engine->RemoveAllSystems();
$engine->RemoveAllEntities();

?>
