<?php
namespace watoki\factory;

use watoki\factory\providers\DefaultProvider;
use watoki\factory\providers\SingletonProvider;

class Factory {

    static $CLASS = __CLASS__;

    /** @var array|Provider[] */
    private $providers = array();

    function __construct() {
        $this->setSingleton($this);
        $this->setProvider('stdClass', new DefaultProvider($this));
    }

    /**
     * Returns an instance of the given class.
     *
     * If the class was registed as singleton, the previous instance is returned regardless of the arguments.
     *
     * @param $class
     * @param array $args Constructor arguments that cannot be provided by the factory (indexed by parameter name)
     * @return mixed An instance of the given class
     * @throws \Exception If the class or an injected class cannot be constructed
     */
    public function getInstance($class, $args = array()) {
        return $this->findMatchingProvider($class)->provide($class, $args);
    }

    /**
     * @param object $instance
     * @param string|null $class If omitted, the class of the instance is used
     * @return object The $instance
     */
    public function setSingleton($instance, $class = null) {
        $class = $class ?: get_class($instance);

        $this->setProvider($class, new SingletonProvider($instance));
        return $instance;
    }

    public function setProvider($class, Provider $provider) {
        $this->providers[$this->normalizeClass($class)] = $provider;
    }

    private function findMatchingProvider($class) {
        $reflection = new \ReflectionClass($class);

        while ($reflection) {
            $normalized = $this->normalizeClass($reflection->getName());
            if (array_key_exists($normalized, $this->providers)) {
                return $this->providers[$normalized];
            }

            $reflection = $reflection->getParentClass();
        }

        $reflection = new \ReflectionClass($class);
        foreach ($reflection->getInterfaces() as $interface) {
            $normalized = $this->normalizeClass($interface->getName());
            if (array_key_exists($normalized, $this->providers)) {
                return $this->providers[$normalized];
            }
        }

        return $this->providers['stdclass'];
    }

    private function normalizeClass($class) {
        return trim(strtolower($class), '\\');
    }
}
