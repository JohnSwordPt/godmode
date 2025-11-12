<?php

namespace ECS;

/**
 * A useful class for systems which simply iterate over a set of nodes, performing the same action on each node. This
 * class removes the need for a lot of boilerplate code in such systems. Extend this class and pass the node type and
 * a node update method into the constructor. The node update method will be called once per node on the update cycle
 * with the node instance and the frame time as parameters. e.g.
 * 
 * <code>package
 * {
 *   public class MySystem extends ListIteratingSystem
 *   {
 *     public function MySystem()
 *     {
 *       super( MyNode, updateNode );
 *     }
 *     
 *     private function updateNode( node : MyNode, time : Number ) : void
 *     {
 *       // process the node here
 *     }
 *   }
 * }</code>
 */
class ListIteratingSystem extends System
{
    /** @var NodeList $nodeList */
    protected $nodeList;
    protected $nodeClass;

    protected $nodeUpdateFunction;
    protected $nodeAddedFunction;
    protected $nodeRemovedFunction;
    
    public function __construct( $nodeClass, $nodeUpdateFunction, $nodeAddedFunction = null, $nodeRemovedFunction = null )
    {
        $this->nodeClass = $nodeClass;
        $this->nodeUpdateFunction = $nodeUpdateFunction;
        $this->nodeAddedFunction = $nodeAddedFunction;
        $this->nodeRemovedFunction = $nodeRemovedFunction;
    }
    
    /**
     * @param Engine $engine
     * @return void
     */
    public function AddToEngine(Engine $engine )
    {
        $this->nodeList = $engine->GetNodeList( $this->nodeClass );
        if( $this->nodeAddedFunction != null )
        {
            foreach ($this->nodeList as $node) {
                call_user_func($this->nodeAddedFunction, $node);
            }
            $this->nodeList->addNodeAdded($this->nodeAddedFunction);
        }
        if( $this->nodeRemovedFunction != null )
        {
            $this->nodeList->addNodeRemoved($this->nodeRemovedFunction);
        }
    }
    
    /**
     * @param Engine $engine
     * @return void
     */
    public function RemoveFromEngine(Engine $engine )
    {
        if( $this->nodeAddedFunction != null )
        {
            $this->nodeList->removeNodeAdded($this->nodeAddedFunction);
        }
        if( $this->nodeRemovedFunction != null )
        {
            $this->nodeList->removeNodeRemoved($this->nodeRemovedFunction);
        }
        $this->nodeList = null;
    }
    
    public function Update( $time )
    {
        $this->nodeList->rewind();
        foreach( $this->nodeList as $node )
        {
            call_user_func($this->nodeUpdateFunction, $node, $time);
        }
    }
}