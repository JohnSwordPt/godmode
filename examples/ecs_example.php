<?php

namespace ECSDemo;

require_once __DIR__ . '/../vendor/autoload.php';

use ECS\Engine;
use ECS\Entity;
use ECSDemo\Components\Position;
use ECSDemo\Components\Renderable;
use ECSDemo\Systems\RenderSystem;
use ECSDemo\Nodes\RenderNode;

// Initialize the Engine
$engine = new Engine();

// Add a RenderSystem to the engine
$renderSystem = new RenderSystem();
$engine->AddSystem($renderSystem, 1); // Priority 1

// Create entities
$player = (new Entity('Player'))
    ->add(new Position(10, 20))
    ->add(new Renderable('player.png'));

$enemy = (new Entity('Enemy'))
    ->add(new Position(50, 70))
    ->add(new Renderable('enemy.png'));

$tree = (new Entity('Tree'))
    ->add(new Position(100, 150))
    ->add(new Renderable('tree.png'));

$engine->AddEntity($player);
$engine->AddEntity($enemy);
$engine->AddEntity($tree);

echo "--- Initial Update ---\n";
$engine->Update();

// Move the player
$player->get(Position::class)->x += 5;
$player->get(Position::class)->y += 5;

echo "\n--- Second Update (Player moved) ---\n";
$engine->Update();

// Remove the enemy
$engine->RemoveEntity($enemy);

echo "\n--- Third Update (Enemy removed) ---\n";
$engine->Update();

// Add a new entity
$item = (new Entity('Item'))
    ->add(new Position(200, 200))
    ->add(new Renderable('item.png'));
$engine->AddEntity($item);

echo "\n--- Fourth Update (Item added) ---\n";
$engine->Update();

// Clean up
$engine->releaseNodeList(RenderNode::class);
$engine->RemoveAllSystems();
$engine->RemoveAllEntities();

echo "\nECS Example Finished.\n";
