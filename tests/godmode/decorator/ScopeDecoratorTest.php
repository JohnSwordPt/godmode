<?php

namespace tests\godmode\decorator;

use godmode\core\BehaviorTask;
use godmode\core\ScopedResource;
use godmode\decorator\ScopeDecorator;
use PHPUnit\Framework\TestCase;

class ScopeDecoratorTest extends TestCase
{
    private $childTask;
    private $resource;

    protected function setUp(): void
    {
        $this->childTask = $this->createMock(BehaviorTask::class);
        $this->resource = $this->createMock(ScopedResource::class);
    }

    public function testAcquiresResourceOnFirstUpdate()
    {
        $this->resource->expects($this->once())->method('acquire');
        $this->childTask->method('update')->willReturn(BehaviorTask::RUNNING);

        $decorator = new ScopeDecorator($this->childTask);
        $decorator->addResource($this->resource);

        // First update should acquire
        $decorator->update(0);

        // Second update should not acquire again
        $decorator->update(0);
    }

    public function testDoesNotReleaseResourceWhileRunning()
    {
        $this->resource->expects($this->once())->method('acquire');
        $this->resource->expects($this->never())->method('release');

        $decorator = new ScopeDecorator($this->childTask);
        $decorator->addResource($this->resource);

        $this->childTask->method('update')->willReturn(BehaviorTask::RUNNING);
        
        // Update returns RUNNING, so no release
        $decorator->update(0);
        $decorator->update(0);
    }

    public function testReleasesResourceOnSuccess()
    {
        $this->resource->expects($this->once())->method('acquire');
        $this->resource->expects($this->once())->method('release');

        $decorator = new ScopeDecorator($this->childTask);
        $decorator->addResource($this->resource);

        $this->childTask->method('update')->willReturn(BehaviorTask::SUCCESS);
        
        // Update returns SUCCESS, triggers deactivate -> reset -> release
        $decorator->update(0);
    }

    public function testReleasesResourceOnFail()
    {
        $this->resource->expects($this->once())->method('acquire');
        $this->resource->expects($this->once())->method('release');

        $decorator = new ScopeDecorator($this->childTask);
        $decorator->addResource($this->resource);

        $this->childTask->method('update')->willReturn(BehaviorTask::FAIL);
        
        // Update returns FAIL, triggers deactivate -> reset -> release
        $decorator->update(0);
    }

    public function testReleasesResourceOnExplicitReset()
    {
        $this->resource->expects($this->once())->method('acquire');
        $this->resource->expects($this->once())->method('release');

        $decorator = new ScopeDecorator($this->childTask);
        $decorator->addResource($this->resource);

        $this->childTask->method('update')->willReturn(BehaviorTask::RUNNING);
        
        // Run to enter the scope and acquire
        $decorator->update(0);

        // Reset should release
        $decorator->reset();
    }

    public function testReacquiresResourceAfterReset()
    {
        $this->resource->expects($this->exactly(2))->method('acquire');
        // Expect 2 releases: one from the first run completion, one from the second run completion
        $this->resource->expects($this->exactly(2))->method('release');

        $decorator = new ScopeDecorator($this->childTask);
        $decorator->addResource($this->resource);

        // 1. First run: returns SUCCESS (default mock int) -> Acquire #1, Release #1
        $this->childTask->method('update')->willReturn(BehaviorTask::SUCCESS);
        $decorator->update(0);

        // 2. Explicit Reset: Should do nothing as it was already reset by completion
        $decorator->reset();

        // 3. Second run: returns SUCCESS -> Acquire #2, Release #2
        $decorator->update(0);
    }
}