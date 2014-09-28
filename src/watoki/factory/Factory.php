<?php
namespace watoki\factory;

use watoki\factory\providers\DefaultProvider;

class Factory {

    static $CLASS = __CLASS__;

    private $singletons = array();

    /** @var array|Provider[] */
    private $providers = array();

    function __construct() {
        $this->setSingleton(get_class($this), $this);
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
        $normalized = $this->normalizeClass($class);

        if (isset($this->singletons[$normalized])) {
            return $this->singletons[$normalized];
        }

        return $this->findMatchingProvider($class)->provide($class, $args);
    }

    /**
     * Returns the previously as singleton registered instance or creates one as singleton.
     *
     * If $args are provided, a new singleton instance will be created.
     *
     * @param string $class
     * @param null|array $args
     * @throws \Exception If no $args are provided and no singleton of $class is registered.
     * @return mixed The already existing instance of the given class
     */
    public function getSingleton($class, $args = null) {
        $normalized = $this->normalizeClass($class);

        if (isset($this->singletons[$normalized])) {
            return $this->singletons[$normalized];
        } else if (!is_null($args)) {
            return $this->setSingleton($class, $this->getInstance($class, $args));
        } else {
            throw new \Exception("Instance of [$class] does not exist.");
        }
    }

    /**
     * @param string $class
     * @param object $instance
     * @return object The instance
     */
    public function setSingleton($class, $instance) {
        $this->singletons[$this->normalizeClass($class)] = $instance;
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
