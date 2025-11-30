# Godmode: Behavior Trees and Finite State Machines for PHP

Godmode is a powerful PHP library designed to provide robust and flexible implementations of Behavior Trees and Finite State Machines (FSMs). It's built to be easily integrated into any PHP application, offering sophisticated control flow and decision-making capabilities for AI, game logic, complex workflows, and more.

## Features

### Behavior Trees
- **Modular and Hierarchical:** Construct complex AI or decision-making logic from simple, reusable tasks.
- **Nodes:** Includes various node types such as Sequence, Selector, Parallel, Iterator, and Weighted selectors.
- **Decorators:** Enhance or modify task behavior with decorators like Looping, Delay, Predicate, and Semaphore.
- **Blackboard System:** Share and manage data across the behavior tree using a flexible blackboard.
- **Task Factory:** Simplify the creation and management of tasks.

### Finite State Machines (FSMs)
- **Clear State Management:** Define states and transitions explicitly.
- **Event-Driven:** Trigger state changes based on events.
- **Flexible:** Easily model complex state-dependent behaviors.

### Entity Component System (ECS)
- **Decoupled Architecture:** Separates data (Components) from logic (Systems).
- **Performance:** Efficient entity management and system iteration.

## Installation

To use this library, you can include it in your project via Composer.

```bash
composer require myself/gamemode
```

*Note: Replace `myself/gamemode` with the actual package name if different.*

## Usage

The library is designed for flexibility. You can find various examples demonstrating the use of Behavior Trees and FSMs in the `examples/` directory. These examples cover a range of scenarios from basic task execution to complex AI behaviors and state management.

## Running Tests

Godmode includes a comprehensive test suite covering core logic, selectors, tasks, and utilities. To run the tests, execute:

```bash
./vendor/bin/phpunit
```

## Dependencies

### Core Dependencies
- None

### Development Dependencies
- `phpunit/phpunit`: ^9.5