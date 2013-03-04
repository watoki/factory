<?php
namespace watoki\factory;

class Factory {

    public $singletons = array();

    /**
     * @param $class
     * @param array $args Arguments other than the factory
     * @return mixed
     */
    public function get($class, $args = array()) {
        $reflClass = new \ReflectionClass($class);

        if (isset($this->singletons[$class])) {
            return $this->singletons[$class];
        }

        if ($reflClass->getConstructor()) {
            array_unshift($args, $this);
            $instance = $reflClass->newInstanceArgs($args);
        } else {
            $instance = $reflClass->newInstance();
        }

        if ($this->isSingleton($reflClass)) {
            $this->setSingleton($class, $instance);
        }

        return $instance;
    }

    /**
     * @param $class
     * @param $instance
     * @return mixed
     */
    public function setSingleton($class, $instance) {
        return $this->singletons[$class] = $instance;
    }

    /**
     * @param $reflClass
     * @return boolean
     */
    private function isSingleton(\ReflectionClass $reflClass) {
        return $reflClass->getStaticPropertyValue('isSingleton', false);
    }
}
