<?php

namespace tests\godmode;

use godmode\core\TimeKeeper;
use godmode\TaskFactory;
use godmode\task\NoOpTask;
use PHPUnit\Framework\TestCase;

class TaskFactoryTest extends TestCase
{
    public function testCreateNoOp()
    {
        $tk = $this->createMock(TimeKeeper::class);
        $factory = new TaskFactory($tk);
        
        $task = $factory->noOp();
        $this->assertInstanceOf(NoOpTask::class, $task);
    }

    // ... other factory methods are similar instantiation checks.
}
