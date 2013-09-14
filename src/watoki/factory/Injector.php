<?php
namespace watoki\factory;

class Injector {

    private $factory;

    function __construct(Factory $factory) {
        $this->factory = $factory;
    }

    public function injectConstructor($class, $args) {
        try {
            $reflection = new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new \InvalidArgumentException("Class [$class] doe not exist.");
        }

        if (!$reflection->getConstructor()) {
            return $reflection->newInstance();
        }

        try {
            return $reflection->newInstanceArgs($this->injectMethodArguments($reflection->getConstructor(), $args));
        } catch (\Exception $e) {
            throw new \Exception('Error while injecting constructor of ' . $reflection->getName() . ': ' . $e->getMessage());
        }
    }

    private function injectMethodArguments(\ReflectionMethod $method, array $args) {
        $argArray = array();
        foreach ($method->getParameters() as $param) {
            if (array_key_exists($param->getName(), $args)) {
                $argArray[] = $args[$param->getName()];
            } else if ($param->isDefaultValueAvailable()) {
                $argArray[] = $param->getDefaultValue();
            } else if ($param->getClass()) {
                $argArray[] = $this->factory->getInstance($param->getClass()->getName());
            } else {
                throw new \Exception("Cannot inject parameter [{$param->getName()}]. No class or value given in "
                        . json_encode(array_keys($args)));
            }
        }
        return $argArray;
    }
}