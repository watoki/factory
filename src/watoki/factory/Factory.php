<?php
namespace watoki\factory;

class Factory {

    static $CLASS = __CLASS__;

    public $singletons = array();

    function __construct() {
        $this->setSingleton(__CLASS__, $this);
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
        if (isset($this->singletons[$class])) {
            return $this->singletons[$class];
        }

        try {
            $reflClass = new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new \InvalidArgumentException("Class [$class] doe not exist.");
        }

        if (!$reflClass->getConstructor()) {
            return $reflClass->newInstance();
        }
        $argArray = array();
        foreach ($reflClass->getConstructor()->getParameters() as $param) {

            if (array_key_exists($param->getName(), $args)) {
                $argArray[] = $args[$param->getName()];
            } else if ($param->isDefaultValueAvailable()) {
                $argArray[] = $param->getDefaultValue();
            } else if ($param->getClass()) {
                $argArray[] = $this->getInstance($param->getClass()->getName());
            } else {
                throw new \Exception("Argument [{$param->getName()}] missing for constructor of [{$reflClass->getShortName()}].");
            }
        }

        return $reflClass->newInstanceArgs($argArray);
    }

    /**
     * Returns the previously as singleton registered instance.
     *
     * Use this method if you expect the instance to have been created centrally.
     *
     * @param string $class
     * @return mixed The already existing instance of the given class
     * @throws \Exception
     */
    public function getSingleton($class) {
        if (!isset($this->singletons[$class])) {
            throw new \Exception("Instance of [$class] does not exist.");
        }
        return $this->singletons[$class];
    }

    /**
     * @param $class
     * @param $instance
     * @return mixed
     */
    public function setSingleton($class, $instance) {
        return $this->singletons[$class] = $instance;
    }
}
