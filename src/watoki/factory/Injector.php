<?php
namespace watoki\factory;

class Injector {

    const INJECTION_MARKER = '<-';

    /** @var bool */
    private $throwException = true;

    /**
     * @var Factory
     */
    private $factory;

    public function __construct(Factory $factory) {
        $this->factory = $factory;
    }

    public function setThrowWhenCantInjectProperty($throw) {
        $this->throwException = $throw;
    }

    public function injectConstructor($class, $args) {
        $reflection = new \ReflectionClass($class);

        if ($reflection->isAbstract() || $reflection->isInterface()) {
            throw new \Exception("Cannot instantiate abstract class [$class].");
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

    public function injectMethod($object, $method, $args = array()) {
        $reflection = new \ReflectionMethod($object, $method);
        $args = $this->injectMethodArguments($reflection, $args);

        return $reflection->invokeArgs($object, $args);
    }

    public function injectMethodArguments(\ReflectionMethod $method, array $args) {
        $resolver = null;
        $argArray = array();
        foreach ($method->getParameters() as $param) {
            if (array_key_exists($param->getName(), $args)) {
                $arg = $args[$param->getName()];
            } else if (array_key_exists($param->getPosition(), $args)) {
                $arg = $args[$param->getPosition()];
            } else if ($param->isDefaultValueAvailable()) {
                $arg = $param->getDefaultValue();
            } else if ($param->getClass()) {
                $arg = $this->factory->getInstance($param->getClass()->getName());
            } else {
                $matches = array();
                $pattern = '/@param\s+(\S+)\s+\$' . $param->getName() . '/';
                $found = preg_match($pattern, $method->getDocComment(), $matches);

                if (!$found) {
                    throw new \Exception("Cannot inject parameter [{$param->getName()}] of [{$method->getDeclaringClass()->getName()}::{$method->getName()}] and not given in arguments "
                        . json_encode(array_keys($args)));
                }

                if (!isset($resolver)) {
                    $resolver = new ClassResolver($method->getDeclaringClass());
                }
                $arg = $this->factory->getInstance($resolver->resolve($matches[1]));
            }
            $argArray[$param->getName()] = $arg;
        }
        return $argArray;
    }

    /**
     * @param object $object The object that the properties are injected into
     * @param callable $filter Function to determine if the passed property annotation should be included
     * @param \ReflectionClass $context The class to read the property annotations from (if not class of object)
     * @throws \Exception
     */
    public function injectPropertyAnnotations($object, $filter, \ReflectionClass $context = null) {
        $classReflection = $context ? : new \ReflectionClass($object);

        while ($classReflection) {
            $resolver = new ClassResolver($classReflection);

            $matches = array();
            preg_match_all('/@property\s+(\S+)\s+\$?(\S+).*/', $classReflection->getDocComment(), $matches);

            foreach ($matches[0] as $i => $match) {
                if (!$filter(trim($match))) {
                    continue;
                }

                $this->injectProperty($matches[2][$i], $object, $resolver, $matches[1][$i], $classReflection);
            }

            $classReflection = $classReflection->getParentClass();
        }
    }

    /**
     * @param object $object The object that the properties are injected into
     * @param callable $filter Function to determine if the passed \ReflectionProperty should be included
     * @param \ReflectionClass $context The class to read the property annotations from (if not class of object)
     * @throws \Exception
     */
    public function injectProperties($object, $filter, \ReflectionClass $context = null) {
        $classReflection = $context ?: new \ReflectionClass($object);
        $resolver = new ClassResolver($classReflection);

        foreach ($classReflection->getProperties() as $property) {
            $matches = array();
            preg_match('/@var\s+(\S+).*/', $property->getDocComment(), $matches);

            if (empty($matches) || !$filter($property)) {
                continue;
            }

            $this->injectProperty($property->getName(), $object, $resolver, $matches[1], $classReflection);
        }
    }

    private function injectProperty($property, $object, ClassResolver $resolver, $className, \ReflectionClass $classReflection) {
        $class = $resolver->resolve($className);

        if (!$class) {
            if ($this->throwException) {
                throw new \Exception("Error while loading dependency [$property] of [{$classReflection->getShortName()}]: Could not find class [$className].");
            } else {
                return;
            }
        }

        if ($classReflection->hasProperty($property)) {
            $reflectionProperty = $classReflection->getProperty($property);
            $reflectionProperty->setAccessible(true);

            if ($reflectionProperty->getValue($object) === null) {
                $reflectionProperty->setValue($object, $this->factory->getInstance($class));
            }
        } else {
            $object->$property = $this->factory->getInstance($class);
        }
    }
}