<?php

namespace ECS;

use SplDoublyLinkedList;

/**
 * Used internally, this is an ordered list of Systems for use by the game loop.
 */
class SystemList extends SplDoublyLinkedList
{
    /**
     * Adds a system to the list, maintaining order by priority.
     *
     * @param System $system
     * @return void
     */
    public function addSystem( $system )
    {
        if ($this->isEmpty()) {
            $this->push($system);
            return;
        }

        $added = false;
        for ($this->rewind(); $this->valid(); $this->next()) {
            $currentSystem = $this->current();
            if ($system->Priority < $currentSystem->Priority) {
                $this->add($this->key(), $system);
                $added = true;
                break;
            }
        }

        if (!$added) {
            $this->push($system);
        }
    }

    /**
     * Removes a system from the list.
     *
     * @param System $system
     * @return void
     */
    public function Remove($system)
    {
        for ($this->rewind(); $this->valid(); $this->next()) {
            if ($this->current() === $system) {
                $this->offsetUnset($this->key());
                return;
            }
        }
    }

    /**
     * Removes all systems from the list.
     */
    public function RemoveAll()
    {
        while (!$this->isEmpty()) {
            $this->pop();
        }
    }

    /**
     * Get the system instance of a particular type from within the list.
     *
     * @param string $type The type of system
     * @return System|null The instance of the system type that is in the list, or
     * null if no systems of this type are in the list.
     */
    public function Get($type)
    {
        for ($this->rewind(); $this->valid(); $this->next()) {
            if($this->current() instanceof $type) {
                return $this->current();
            }
        }
        return null;
    }

    /**
     * Converts the SystemList to a standard PHP array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return iterator_to_array($this);
    }
}
