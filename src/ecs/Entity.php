<?php

declare(strict_types=1);

namespace ECS;

class Entity
{
    private static int $nameCount = 0;

    /** @var array<class-string, object> */
    private array $components;

    protected ?Engine $_engine = null;

    /**
     * Optional, give the entity a name. This can help with debugging and with serialising the entity.
     */
    public string $Name;



    public function __construct(string $name = "")
    {
        $this->components = [];
        if (!empty($name)) {
            $this->Name = $name;
        } else {
            $this->Name = "_entity" . (++self::$nameCount);
        }
    }

    /**
     * Add a component to the entity.
     *
     * @param object $component The component object to add.
     * @param class-string|null $componentClass The class of the component. This is only necessary if the component
     * extends another component class and you want the framework to treat the component as of
     * the base class type. If not set, the class type is determined directly from the component.
     *
     * @return self A reference to the entity. This enables the chaining of calls to add, to make
     * creating and configuring entities cleaner. e.g.
     *
     * <code>$entity = (new Entity())
     *     ->add(new Position(100, 200))
     *     ->add(new Display(new PlayerClip()));</code>
     */
    public function add(object $component, ?string $componentClass = null): self
    {
        $componentClass = $componentClass ?? get_class($component);

        $this->components[$componentClass] = $component;

        if ($this->_engine) {
            $this->_engine->ComponentAdded($this, $componentClass);
        }

        return $this;
    }

    /**
     * Remove a component from the entity.
     *
     * @param class-string $componentClass The class of the component to be removed.
     * @return object|null the component, or null if the component doesn't exist in the entity
     */
    public function remove(string $componentClass): ?object
    {
        $component = $this->components[$componentClass] ?? null;
        if ($component) {
            unset($this->components[$componentClass]);
            if ($this->_engine) {
                $this->_engine->ComponentRemoved($this, $componentClass);
            }
            return $component;
        }
        return null;
    }

    /**
     * Get a component from the entity.
     *
     * @param class-string $componentClass The class of the component requested.
     * @return object|null The component, or null if none was found.
     */
    public function get(string $componentClass): ?object
    {
        return $this->components[$componentClass] ?? null;
    }

    /**
     * Get all components from the entity.
     *
     * @return array<class-string, object> An array containing all the components that are on the entity.
     */
    public function getAll(): array
    {
        return $this->components;
    }

    /**
     * Does the entity have a component of a particular type.
     *
     * @param class-string $componentClass The class of the component sought.
     * @return bool true if the entity has a component of the type, false if not.
     */
    public function has(string $componentClass): bool
    {
        return isset($this->components[$componentClass]);
    }

    public function getEngine(): ?Engine
    {
        return $this->_engine;
    }

    public function setEngine(?Engine $engine): void
    {
        $this->_engine = $engine;
    }
}
