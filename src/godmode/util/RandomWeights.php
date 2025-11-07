<?php

namespace godmode\util;

use godmode\core\RandomStream;

class RandomWeights implements RandomStream {

    private $seed;
    private $M = 2147483647;
    private $A = 16807;
    private $Q;
    private $R;
    private $low = 0;
    private $high = 10;

    public function __construct() {
        $this->seed = 1;
        $this->low = 0;
        $this->high = 10;
        $this->Q = (int)($this->M / $this->A);
        $this->R = $this->M % $this->A;
    }

    public function next() : int {
        $this->seed = (int)($this->A * ($this->seed % $this->Q) - $this->R * (int)($this->seed / $this->Q));
        if ($this->seed <= 0) {
            $this->seed += $this->M;
        }
        return $this->seed;
    }

    public function nextInt($n) : int {
        $this->high = $n;
        return $this->low + ( $this->next() % ($this->high - $this->low) );
    }

    public function nextNumber() : float {
        $rnd = $this->next() * ( 1.0 / $this->M );
        return $rnd;
    }
}