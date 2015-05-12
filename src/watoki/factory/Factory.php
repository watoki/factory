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
        return $this->findMatchingProvider($this->normalizeClass($class))->provide($class, $args);
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
        $isHHVM = defined('HHVM_VERSION');

        // fix for a hhvm issue, see https://github.com/facebook/hhvm/issues/2097
        $parentClasses = $isHHVM ? class_parents($class) : array();

        while ($class) {
            $normalized = $this->normalizeClass($class);
            foreach ($this->providers as $key => $provider) {
                if ($normalized == $key) {
                    return $provider;
                }
            }
            $class = $isHHVM ? array_shift($parentClasses) : get_parent_class($class);
        }
        return $this->providers['stdclass'];
    }

    private function normalizeClass($class) {
        return trim(strtolower($class), '\\');
    }
}
