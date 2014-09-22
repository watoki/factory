<?php
namespace watoki\factory;

class Injector {

    const INJECTION_MARKER = '<-';

    /** @var bool */
    private $throwException = true;

    /** @var Factory <- */
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
        $analyzer = new MethodAnalyzer($method);
        try {
            return $analyzer->fillParameters($args, $this->factory);
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException("Cannot inject method [{$method->getDeclaringClass()->getName()}"
                . "::{$method->getName()}]: " . $e->getMessage(), 0, $e);
        }
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
                if (!call_user_func($filter, trim($match))) {
                    continue;
                }

                $class = $matches[1][$i];
                $propertyName = $matches[2][$i];
                $this->injectProperty($object, $propertyName, $resolver->resolve($class));
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
        $classReflection = $context ? : new \ReflectionClass($object);

        foreach ($classReflection->getProperties() as $property) {
            $matches = array();
            preg_match('/@var\s+(\S+).*/', $property->getDocComment(), $matches);

            if (empty($matches) || !$filter($property)) {
                continue;
            }

            $resolver = new ClassResolver($property->getDeclaringClass());
            $this->injectProperty($object, $property->getName(), $resolver->resolve($matches[1]));
        }
    }

    private function injectProperty($targetObject, $propertyName, $type) {
        $classReflection = new \ReflectionClass($targetObject);

        if (!$type) {
            if ($this->throwException) {
                throw new \Exception("Error while loading dependency [$propertyName] of [{$classReflection->getShortName()}]: "
                    . "Could not find [$type].");
            } else {
                return;
            }
        }

        if ($classReflection->hasProperty($propertyName)) {
            $reflectionProperty = $classReflection->getProperty($propertyName);
            $reflectionProperty->setAccessible(true);

            if ($reflectionProperty->getValue($targetObject) === null) {
                $reflectionProperty->setValue($targetObject, $this->factory->getInstance($type));
            }
        } else {
            $targetObject->$propertyName = $this->factory->getInstance($type);
        }

    }
}