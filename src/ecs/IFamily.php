<?php

namespace ECS;

interface IFamily
{
    /**
     * Returns the NodeList managed by this class. This should be a reference that remains valid always
     * since it is retained and reused by Systems that use the list. i.e. never recreate the list,
     * always modify it in place.
     * 
     * @return NodeList
     */
    function NodeList();

    /**
     * An entity has been added to the engine. It may already have components so test the entity
     * for inclusion in this family's NodeList.
     * 
     * @param Entity $entity
     * @return void
     */
    function NewEntity( $entity );

    /**
     * An entity has been removed from the engine. If it's in this family's NodeList it should be removed.
     * 
     * @param Entity $entity
     * @return void
     */
    function RemoveEntity( $entity );

    /**
     * A component has been added to an entity. Test whether the entity's inclusion in this family's
     * NodeList should be modified.
     *
     * @param Entity $entity
     * @param Class $componentClass
     * @return void
     */
    function ComponentAddedToEntity( $entity, $componentClass );

    /**
     * A component has been removed from an entity. Test whether the entity's inclusion in this family's
     * NodeList should be modified.
     * 
     * @param Entity $entity
     * @param Class $componentClass
     */
    function ComponentRemovedFromEntity( $entity, $componentClass );

    /**
     * The family is about to be discarded. Clean up all properties as necessary. Usually, you will
     * want to empty the NodeList at this time.
     */
    function cleanUp();
}