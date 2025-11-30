<?php

namespace tests\fsm;

use fsm\FSM;
use PHPUnit\Framework\TestCase;

class FSMTest extends TestCase
{
    public function testInitialState()
    {
        $fsm = new FSM('idle');
        $this->assertEquals('idle', $fsm->getCurrentState());
    }

    public function testProcessPerformsTransition()
    {
        $fsm = new FSM('idle');
        $fsm->addTransition('start', 'idle', 'running');
        
        $this->assertEquals('idle', $fsm->getCurrentState());
        $fsm->process('start');
        $this->assertEquals('running', $fsm->getCurrentState());
    }

    public function testProcessIgnoresInvalidInput()
    {
        $fsm = new FSM('idle');
        $fsm->addTransition('start', 'idle', 'running');

        $this->assertEquals('idle', $fsm->getCurrentState());
        $fsm->process('invalid_input');
        $this->assertEquals('idle', $fsm->getCurrentState());
    }

    public function testAddTransitionAny()
    {
        $fsm = new FSM('idle');
        $fsm->addTransitionAny('idle', 'stopped');

        $this->assertEquals('idle', $fsm->getCurrentState());
        $fsm->process('any_input_will_do');
        $this->assertEquals('stopped', $fsm->getCurrentState());
    }

    public function testPayloadIsPassedToAction()
    {
        $payload = ['value' => 0];
        $fsm = new FSM('start', $payload);

        $action = function ($symbol, &$payload, $currentState, $nextState, $fsm) {
            $payload['value']++;
        };

        $fsm->addTransition('increment', 'start', 'start', $action);
        $fsm->process('increment');

        $updatedPayload = $fsm->getPayload();
        $this->assertEquals(1, $updatedPayload['value']);
    }

    public function testReset()
    {
        $fsm = new FSM('idle');
        $fsm->addTransition('start', 'idle', 'running');
        $fsm->process('start');
        $this->assertEquals('running', $fsm->getCurrentState());

        $fsm->reset();
        $this->assertEquals('idle', $fsm->getCurrentState());
    }
}
