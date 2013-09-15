<?php
namespace watoki\factory;

class Injector {

    const INJECTION_MARKER = '<-';

    private $factory;

    function __construct(Factory $factory) {
        $this->factory = $factory;
    }

    public function injectConstructor($class, $args) {
        $reflection = new \ReflectionClass($class);

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

    /**
     * @param object $object The object that the properties are injected into
     * @param callable $filter Function to determine if the passed property annotation should be included
     * @throws \Exception
     */
    public function injectPropertyAnnotations($object, $filter) {
        $classReflection = new \ReflectionClass($object);
        $resolver = new ClassResolver($classReflection);

        $matches = array();
        preg_match_all('/@property\s+(\S+)\s+\$?(\S+).*/', $classReflection->getDocComment(), $matches);

        foreach ($matches[0] as $i => $match) {
            if (!$filter(trim($match))) {
                continue;
            }

            $className = $matches[1][$i];
            $property = $matches[2][$i];

            $class = $resolver->resolve($className);

            if (!$class) {
                throw new \Exception("Error while loading dependency [$property] of [{$classReflection->getShortName()}]: Could not find class [$className].");
            }

            if ($classReflection->hasProperty($property)) {
                $reflectionProperty = $classReflection->getProperty($property);
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($object, $this->factory->getInstance($class));
            } else {
                $object->$property = $this->factory->getInstance($class);
            }
        }
    }
}