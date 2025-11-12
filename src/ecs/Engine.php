<?php

namespace ECS;

use Error;

class Engine 
{
    public const DEBUG_ECS = false;
    private $entityNames;
    /** @var EntityList $_entities */
    private $entityList;
    /** @var SystemList $_systems */
    private $systemList;
    private $families;
    private float $previousTime = 0;

    /**
     * Indicates if the engine is currently in its update loop.
     */
    public $updating = false;

    /**
     * The class used to manage node lists. In most cases the default class is sufficient
     * but it is exposed here so advanced developers can choose to create and use a 
     * different implementation.
     * 
     * The class must implement the Family interface.
     */
    // public $familyClass : Class = ComponentMatchingFamily;

    public function __construct()
    {
        $this->entityList = new EntityList();
        $this->systemList = new SystemList();
        $this->entityNames = [];
        $this->families = [];
        $this->previousTime = microtime(true);


    }
    
    public function GetNodeMetaData($nodeClass, $variableName)
    {
        $reflectionProperty = new \ReflectionProperty($nodeClass, $variableName);

        // Try to get the type from the property's type hint (PHP 7.4+)
        $type = $reflectionProperty->getType();
        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            return $type->getName();
        }

        // Fallback to docblock parsing if no type hint or if it's a built-in type
        $docComment = $reflectionProperty->getDocComment();

        if ($docComment && preg_match('/@var\s+([^\s]+)/', $docComment, $matches)) {
            $componentClassName = $matches[1];
            // Resolve the fully qualified class name
            $nodeClassReflection = new \ReflectionClass($nodeClass);
            $nodeNamespace = $nodeClassReflection->getNamespaceName();
            
            // If the component class name starts with a backslash, it's already fully qualified
            if (strpos($componentClassName, '\\') === 0) {
                return ltrim($componentClassName, '\\'); // Remove leading backslash
            }
            // If it's a relative class name, prepend the node's namespace
            if ($nodeNamespace) {
                return $nodeNamespace . '\\' . $componentClassName;
            }
            return $componentClassName;
        }
        return null;
    }

    /**
     * Add an entity to the game.
     *
     * @param Entity $entity
     * @return void
     */
    public function AddEntity($entity)
    {
        if( isset($this->entityNames[ $entity->Name ]) )
        {
            throw new Error( "The entity name " . $entity->Name . " is already in use by another entity." );
        }
        $entity->setEngine( $this );
        $this->entityList->push( $entity );
        $this->entityNames[ $entity->Name ] = $entity;
        // TODO: implement?
        // entity.componentAdded.add( componentAdded );
        // entity.componentRemoved.add( componentRemoved );
        // $entity->nameChanged.add( entityNameChanged );
        foreach( $this->families as $family )
        {
            $family->NewEntity( $entity );
        }
    }

    /**
     * Remove an entity from the game.
     * 
     * @param Entity The entity to remove.
     * @return void
     */
    public function RemoveEntity($entity)
    {
        // TODO: implement event listeners?
        // $entity->componentAdded.remove( componentAdded );
        // entity.componentRemoved.remove( componentRemoved );
        // $entity.nameChanged.remove( entityNameChanged );
        foreach( $this->families as $family )
        {
            $family->RemoveEntity( $entity );
        }
        unset($this->entityNames[$entity->Name]);
        foreach ($this->entityList as $key => $listEntity) {
            if ($listEntity === $entity) {
                unset($this->entityList[$key]);
                break;
            }
        }
    }

    /**
     * @param Entity $entity
     * @param string $oldName
     * @return void
     */
    private function EntityNameChanged( $entity, $oldName )
    {
        if( $this->entityNames[ $oldName ] == $entity )
        {
            unset($this->entityNames[ $oldName ]);
            $this->entityNames[ $entity->Name ] = $entity;
        }
    }

    /**
     * Get an entity based n its name.
     * 
     * @param string The name of the entity
     * @return Entity entity, or null if no entity with that name exists on the engine
     */
    public function GetEntityByName( $name )
    {
        return $this->entityNames[ $name ];
    }

    /**
     * Remove all entities from the game.
     */
    public function RemoveAllEntities()
    {
        $allEntities = [];
        foreach ($this->entityList as $entity) {
            $allEntities[] = $entity;
        }

        foreach ($allEntities as $entity) {
            $this->RemoveEntity($entity);
        }
        $this->entityList = new EntityList();
    }

    /**
     * Returns array containing all the entities in the engine.
     */
    public function Entities() 
    {
        return iterator_to_array($this->entityList);
    }
    
    /**
     * @private
     */
    public function ComponentAdded( $entity, $componentClass )
    {
        foreach( $this->families as $family )
        {
            $family->ComponentAddedToEntity( $entity, $componentClass );
        }
    }
    
    /**
     * @private
     */
    public function ComponentRemoved( $entity, $componentClass )
    {
        foreach( $this->families as $family )
        {
            $family->ComponentRemovedFromEntity( $entity, $componentClass );
        }
    }

    /**
     * Get a collection of nodes from the engine, based on the type of the node required.
     * 
     * <p>The engine will create the appropriate NodeList if it doesn't already exist and 
     * will keep its contents up to date as entities are added to and removed from the
     * engine.</p>
     * 
     * <p>If a NodeList is no longer required, release it with the releaseNodeList method.</p>
     * 
     * @param Class The type of node required.
     * @return NodeList linked list of all nodes of this type from all entities in the engine.
     */
    public function GetNodeList( $nodeClass )
    {
        if( isset($this->families[$nodeClass]) )
        {
            return $this->families[$nodeClass]->NodeList();
        }
        $family = new ComponentMatchingFamily( $nodeClass, $this );
        $this->families[$nodeClass] = $family;
        foreach( $this->entityList as $entity )
        {
            $family->NewEntity( $entity );
        }
        return $family->NodeList();
    }

    /**
     * If a NodeList is no longer required, this method will stop the engine updating
     * the list and will release all references to the list within the framework
     * classes, enabling it to be garbage collected.
     * 
     * <p>It is not essential to release a list, but releasing it will free
     * up memory and processor resources.</p>
     * 
     * @param nodeClass The type of the node class if the list to be released.
     */
    public function releaseNodeList( $nodeClass )
    {
        if( $this->families[$nodeClass] )
        {
            $this->families[$nodeClass]->cleanUp();
        }
        unset($this->families[$nodeClass]);
    }

    /**
     * Add a system to the engine, and set its priority for the order in which the
     * systems are updated by the engine update loop.
     * 
     * <p>The priority dictates the order in which the systems are updated by the engine update 
     * loop. Lower numbers for priority are updated first. i.e. a priority of 1 is 
     * updated before a priority of 2.</p>
     * 
     * @param System $system
     * @param integer $priority
     * @return void
     */
    public function AddSystem( $system, $priority )
    {
        $system->Priority = $priority;
        $system->AddToEngine( $this );
        $this->systemList->addSystem( $system );
    }

    /**
     * Get the system instance of a particular type from within the game.
     * 
     * @param type The type of system
     * @return The instance of the system type that is in the game, or
     * null if no systems of this type are in the game.
     */
    public function GetSystem($type)
    {
        return $this->systemList->Get($type);
    }

    /**
     * Returns a vector containing all the systems in the engine.
     */
    public function GetSystems()
    {
        return $this->systemList->toArray();
    }
    
    /**
     * Remove a system from the engine.
     * 
     * @param System The system to remove from the engine.
     */
    public function RemoveSystem( $system ) 
    {
        $this->systemList->Remove( $system );
        $system->RemoveFromEngine( $this );
    }
    
    /**
     * Remove all systems from the engine.
     */
    public function RemoveAllSystems()
    {
        $allSystems = $this->systemList->toArray(); // Get all systems before clearing the list
        foreach ($allSystems as $system) {
            $system->RemoveFromEngine($this);
        }
        $this->systemList->RemoveAll();
    }

    /**
     * Update the engine. This causes the engine update loop to run, calling update on all the
     * systems in the engine.
     * 
     * <p>The package net.richardlord.ash.tick contains classes that can be used to provide
     * a steady or variable tick that calls this update method.</p>
     * 
     * @time The duration, in seconds, of this update step.
     */
    public function Update( $time = null ) : void
    {
        $currentTime = microtime(true);
        $dt = $currentTime - $this->previousTime;
        $this->previousTime = $currentTime;

        $this->updating = true;
        foreach ($this->systemList as $system)
        {
            $system->Update($dt);
        }
        $this->updating = false;
        // updateComplete.dispatch();
    }
}
