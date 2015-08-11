<?php
namespace watoki\factory;

use watoki\factory\providers\DefaultProvider;
use watoki\factory\providers\SingletonProvider;

/**
 * Class Factory.
 * The factory that build all of your object
 *
 * @package watoki\factory
 */
class Factory
{
    /** @var string The Factory class name */
    public static $CLASS = __CLASS__;

    /** @var array|Provider[] */
    private $providers = array();

    /**
     * Initialize the class.
     * (Define the default provider, and self register as singleton)
     */
    public function __construct()
    {
        $this->setSingleton($this);
        $this->setProvider('stdClass', new DefaultProvider($this));
    }

    /**
     * Returns an instance of the given class.
     *
     * If the class was registed as singleton, the previous instance is returned regardless of the arguments.
     *
     * @param string $class The name (or alias) of the class to get
     * @param array $args Constructor arguments that cannot be provided by the factory (indexed by parameter name)
     *
     * @return mixed An instance of the given class
     *
     * @throws \Exception If the class or an injected class cannot be constructed
     */
    public function getInstance($class, $args = array())
    {
        return $this->findMatchingProvider($class)->provide($class, $args);
    }

    /**
     * Define a singleton
     *
     * @param object $instance The singleton instance
     * @param string|null $class If omitted, the class of the instance is used
     *
     * @return object The $instance
     */
    public function setSingleton($instance, $class = null)
    {
        $class = $class ?: get_class($instance);

        $this->setProvider($class, new SingletonProvider($instance));
        return $instance;
    }

    /**
     * Define a provider of a class name
     *
     * @param string $class The name (or alias) of the class
     * @param Provider $provider The class provider to use for this class name
     */
    public function setProvider($class, Provider $provider)
    {
        $this->providers[$this->normalizeClass($class)] = $provider;
    }

    /**
     * Find the provider to use to get a instance for the class $class
     *
     * @param string $class The name (or alias) of the class
     *
     * @return Provider The provider to use
     */
    private function findMatchingProvider($class)
    {
        $reflection = new \ReflectionClass($class);

        /*
         * First check if a provider is defined for a concrete class
         * (check the class, and all parents class)
         */
        while ($reflection) {
            $normalized = $this->normalizeClass($reflection->getName());
            if (array_key_exists($normalized, $this->providers)) {
                return $this->providers[$normalized];
            }

            $reflection = $reflection->getParentClass();
        }

        /*
         * Check if one of the class interface have a provider defined
         */
        $reflection = new \ReflectionClass($class);
        foreach ($reflection->getInterfaces() as $interface) {
            $normalized = $this->normalizeClass($interface->getName());
            if (array_key_exists($normalized, $this->providers)) {
                return $this->providers[$normalized];
            }
        }

        /*
         * Finally, fallback to the default provider (the provider defined for StdClass)
         */
        return $this->providers['stdclass'];
    }

    /**
     * Transform a class name into a string that can be used as a key (identifier)
     *
     * @param string $class The class name to transform
     * @return string The "ready to use as key" class name
     */
    private function normalizeClass($class)
    {
        return trim(strtolower($class), '\\');
    }
}
