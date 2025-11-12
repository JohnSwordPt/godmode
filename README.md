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

## Installation

TBD

## Usage

The library is designed for flexibility. You can find various examples demonstrating the use of Behavior Trees and FSMs in the `examples/` directory. These examples cover a range of scenarios from basic task execution to complex AI behaviors and state management.

## Running Tests

Godmode uses PHPUnit for testing. To run the test suite, navigate to the project root and execute:

```bash
./vendor/bin/phpunit
```

## Dependencies

### Core Dependencies

### Development Dependencies
- `phpunit/phpunit`: ^9.5

