<?php

namespace ECS;

/**
 * The default class for managing a NodeList. This class creates the NodeList and adds and removes
 * nodes to/from the list as the entities and the components in the engine change.
 * 
 * It uses the basic entity matching pattern of an entity system - entities are added to the list if
 * they contain components matching all the public properties of the node class.
 */
class ComponentMatchingFamily implements IFamily
{
    /** @var NodeList $nodes */
    private $nodes;
    private $entities;
    private $nodeClass;
    private $components;
    /** @var Engine $engine */
    private $engine;

    /**
     * The constructor. Creates a ComponentMatchingFamily to provide a NodeList for the
     * given node class.
     * 
     * @param Class The type of node to create and manage a NodeList for.
     * @param Engine The engine that this family is managing teh NodeList for.
     */
    public function __construct( $nodeClass, $engine )
    {
        $this->nodeClass = $nodeClass;
        $this->engine = $engine;
        $this->init();
    }

    /**
     * TODO: maybe the nodes logic can be integrated into the system classes
     * Initialises the class. Creates the nodelist and other tools. Analyses the node to determine
     * what component types the node requires.
     */
    private function init() : void
    {
        $this->nodes = new NodeList();
        $this->entities = [];
        $this->components = [];
        
        $reflection = new \ReflectionClass($this->nodeClass);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            if ($property->getDeclaringClass()->getName() !== Node::class) {
                $componentClass = $this->engine->GetNodeMetaData($this->nodeClass, $property->getName());
                if ($componentClass) {
                    $this->components[$componentClass] = $property->getName();
                }
            }
        }
    }
    
    /**
     * The nodelist managed by this family. This is a reference that remains valid always
     * since it is retained and reused by Systems that use the list. i.e. we never recreate the list,
     * we always modify it in place.
     *
     * @return NodeList
     */
    public function NodeList()
    {
        return $this->nodes;
    }

    /**
     * Called by the engine when an entity has been added to it. We check if the entity should be in
     * this family's NodeList and add it if appropriate.
     *
     * @param Entity $entity
     * @return void
     */
    public function NewEntity( $entity )
    {
        $this->AddIfMatch( $entity );
    }
    
    /**
     * Called by the engine when a component has been added to an entity. We check if the entity is not in
     * this family's NodeList and should be, and add it if appropriate.
     *
     * @param Entity $entity
     * @param Class $componentClass
     * @return void
     */
     public function ComponentAddedToEntity( $entity, $componentClass )
    {
        $this->AddIfMatch( $entity );
    }
    
    /**
     * Called by the engine when a component has been removed from an entity. We check if the removed component
     * is required by this family's NodeList and if so, we check if the entity is in this this NodeList and
     * remove it if so.
     *
     * @param Entity $entity
     * @param Class $componentClass
     * @return void
     */
    public function ComponentRemovedFromEntity( $entity, $componentClass )
    {
        if( isset($this->components[$componentClass]) )
        {
            $this->RemoveIfMatch( $entity );
        }
    }
    
    /**
     * Called by the engine when an entity has been rmoved from it. We check if the entity is in
     * this family's NodeList and remove it if so.
     *
     * @param Entity $entity
     * @return void
     */
    public function RemoveEntity( $entity )
    {
        $this->RemoveIfMatch( $entity );
    }
    
    /**
     * If the entity is not in this family's NodeList, tests the components of the entity to see
     * if it should be in this NodeList and adds it if so.
     * 
     * @param Entity $entity
     * @return void
     */
    private function AddIfMatch( $entity )
    {
        $hash = spl_object_hash($entity);
        if( !isset($this->entities[$hash]) )
        {
            foreach ( $this->components as $componentClass=>$prop )
            {
                if (\ECS\Engine::DEBUG_ECS) {
                    echo "Checking entity '{$entity->Name}' for component '{$componentClass}'...\n";
                }
                if ( !$entity->Has( $componentClass ) )
                {
                    if (\ECS\Engine::DEBUG_ECS) {
                        echo "Entity '{$entity->Name}' DOES NOT have component '{$componentClass}'. Skipping.\n";
                    }
                    return;
                }
                if (\ECS\Engine::DEBUG_ECS) {
                    echo "Entity '{$entity->Name}' HAS component '{$componentClass}'.\n";
                }
            }
            $node = new $this->nodeClass();
            $node->Entity = $entity;
            foreach ( $this->components as $componentClass=>$prop )
            {
                $node->{$prop} = $entity->Get( $componentClass );
            }
            $this->entities[$hash] = $node;
            $this->nodes->push( $node );
            if (\ECS\Engine::DEBUG_ECS) {
                echo "Added entity '{$entity->Name}' to NodeList for node class '{$this->nodeClass}'\n";
            }
        }
    }
    
    /**
     * Removes the entity if it is in this family's NodeList.
     * 
     * @param Entity $entity
     * @return void
     */
    private function RemoveIfMatch( $entity )
    {
        $hash = spl_object_hash($entity);
        if( isset($this->entities[$hash]) )
        {
            $node = $this->entities[$hash];
            unset($this->entities[$hash]);
            
            foreach ($this->nodes as $key => $listNode) {
                if ($listNode === $node) {
                    unset($this->nodes[$key]);
                    break;
                }
            }
            // echo "Removed entity '{$entity->Name}' from NodeList for node class '{$this->nodeClass}'\n";
        }
    }
    
    /**
     * Releases the nodes that were added to the node pool during this engine update, so they can
     * be reused.
     */
    private function ReleaseNodePoolCache()
    {
        // engine.updateComplete.remove( releaseNodePoolCache );
        // nodePool.releaseCache();
    }
    
    /**
     * Removes all nodes from the NodeList.
     */
    public function cleanUp()
    {
        foreach ($this->nodes as $node)
        {
            /** @var Node $node */
            unset($this->entities[spl_object_hash($node->Entity)]);
        }
        $this->nodes->removeAll();
    }
}