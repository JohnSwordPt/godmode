<?php

namespace BusinessLogicECS\Nodes;

use ECS\Node;
use BusinessLogicECS\Components\OrderComponent;
use BusinessLogicECS\Components\PriceComponent;

class OrderProcessingNode extends Node
{
    /** @var OrderComponent $order */
    public OrderComponent $order;

    /** @var PriceComponent $price */
    public PriceComponent $price;
}
