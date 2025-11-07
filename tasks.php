<?php

use godmode\core\BehaviorTask;
use godmode\data\Blackboard;
use godmode\pred\BehaviorPredicate;

class DebugTraceTask extends BehaviorTask {
    private $message;
    private $type;
    public function __construct( $message, $type = BehaviorTask::SUCCESS ) {
        $this->message = $message;
        $this->type = $type;
    }
    protected function updateTask( float $dt ) : int {
        echo( $this->message );
        return $this->type;
    }
}

class CountdownTask extends BehaviorTask {
    /** @var Blackboard $bb */
    private $bb;
    public function __construct( Blackboard $bb  ) {
        $this->bb = $bb;
    }
    protected function updateTask( float $dt ) : int {
        $value = $this->bb->getEntry('counter')->value();
        if($value > 0) {
            $value = $value - 1;
            $this->bb->getEntry('counter')->store($value);
            echo " $value ";
            return BehaviorTask::FAIL;
        }
        return BehaviorTask::SUCCESS;
    }
}

class DoStuff extends BehaviorTask {
    private $msg;
    public function __construct( $msg ) {
        $this->msg = $msg;
    }
    protected function updateTask( float $dt ) : int {
        $stuff = $this->msg;
        echo $stuff;
        if( $stuff == ' priority 3 ') {
            return BehaviorTask::RUNNING;
        }
        if( $stuff == ' priority 2 ') {
            return BehaviorTask::SUCCESS;
        }
        // return BehaviorTask::SUCCESS;
        return BehaviorTask::FAIL;
    }
}

class HasMilk extends BehaviorPredicate {
    private $bb;
    public function __construct( Blackboard $bb ) {
        $this->bb = $bb;
    }
    public function evaluate() : bool {
        $milk = $this->bb->getEntry('milk')->value();
        return ($milk > 0);
    }
}

class HasGas extends BehaviorPredicate {
    private $bb;
    public function __construct( Blackboard $bb ) {
        $this->bb = $bb;
    }
    public function evaluate() : bool {
        return ($this->bb->getEntry('gas')->value() > 0);
    }
}

class HasMoney extends BehaviorPredicate {
    private $bb;
    public function __construct( Blackboard $bb ) {
        $this->bb = $bb;
    }
    public function evaluate() : bool {
        return ($this->bb->getEntry('money')->value() > 0);
    }
}

class Drive extends BehaviorPredicate {
    private $bb;
    public function __construct( Blackboard $bb ) {
        $this->bb = $bb;
    }
    public function evaluate() : bool {
        $gas = $this->bb->getEntry('gas')->value();
        $distance = $this->bb->getEntry('distance')->value();
        if ($gas >= $distance) {
            $gas -= $distance;
            $this->bb->getEntry('gas')->store($gas);
            return true;
        }
        return false;
    }
}

class StoreHasProduct extends BehaviorPredicate {
    private $bb;
    public function __construct( Blackboard $bb ) {
        $this->bb = $bb;
    }
    public function evaluate() : bool {
        $product = $this->bb->getEntry('buy')->value();
        $stock = $this->bb->getEntry('store_stock')->value();
        if(isset($stock[$product]) && $stock[$product]['stock'] > 0) {
            echo " [store has {$product}] ";
            return true;
        } 
        echo " [store no {$product}] ";
        return false;
    }
}

class BuyProduct extends BehaviorTask {
    private $bb;
    public function __construct( Blackboard $bb ) {
        $this->bb = $bb;
    }
    protected function updateTask( float $dt ) : int {
        $product = $this->bb->getEntry('buy')->value();
        $money = $this->bb->getEntry('money')->value();
        $stock = $this->bb->getEntry('store_stock')->value();
        // can we buy the product?
        $price = $stock[$product]['price'];
        if ($money < $price) {
            echo " [cannot buy {$product}] ";
            return BehaviorTask::FAIL;
        }
        // buy the product
        $stock[$product]['stock'] --;
        $money -= 1;
        $this->bb->getEntry('money')->store($money);
        $this->bb->getEntry('store_stock')->store($stock);
        $fridge = $this->bb->getEntry('fridge_stock')->value();
        $fridge[$product] ++;
        $this->bb->getEntry('fridge_stock')->store($fridge);
        echo " [buy {$product}] ";
        return BehaviorTask::SUCCESS;
    }
}

class ShowGas extends BehaviorTask {
    private $bb;
    public function __construct( Blackboard $bb ) {
        $this->bb = $bb;
    }
    protected function updateTask( float $dt ) : int {
        $gas = $this->bb->getEntry('gas')->value();
        echo " [gas: $gas] "; // show remaining gas
        return BehaviorTask::SUCCESS;
    }
}

class NeedBuyProduct extends BehaviorPredicate {
    private $bb;
    private $list;
    public function __construct( Blackboard $bb, $list = false ) {
        $this->bb = $bb;
        $this->list = $list;
    }
    public function evaluate() : bool {
        $stock = $this->bb->getEntry('fridge_stock')->value();
        $product2Buy = 0;
        foreach ($stock as $product => $qt) {
            if($qt == 0) {
                $this->bb->getEntry('buy')->store($product);
                echo " [no $product] ";
                $product2Buy ++;
                if(!$this->list) return true;
            }
        }
        if($this->list && $product2Buy) {
            return true;
        }
        return false;
    }
}