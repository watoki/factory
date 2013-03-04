<?php
namespace watoki\factory;

class Factory {

    public $singletons = array();

    function __construct() {
        $this->setSingleton(__CLASS__, $this);
    }

    /**
     * @param $class
     * @param array $args Constructor arguments that cannot be provided by the factory (indexed by parameter name)
     * @return mixed An instance of the given class
     * @throws \Exception If the class or an injected class cannot be constructed
     */
    public function getInstance($class, $args = array()) {
        if (isset($this->singletons[$class])) {
            return $this->singletons[$class];
        }

        $reflClass = new \ReflectionClass($class);
        if (!$reflClass->getConstructor()) {
            return $reflClass->newInstance();
        }
        $argArray = array();
        foreach ($reflClass->getConstructor()->getParameters() as $param) {

            if (array_key_exists($param->getName(), $args)) {
                $argArray[] = $args[$param->getName()];
            } else if ($param->getClass()) {
                $argArray[] = $this->getInstance($param->getClass()->getName());
            } else if ($param->isDefaultValueAvailable()) {
                $argArray[] = $param->getDefaultValue();
            } else {
                throw new \Exception("Argument [{$param->getName()}] missing for constructor of [{$reflClass->getShortName()}].");
            }
        }

        return $reflClass->newInstanceArgs($argArray);
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
