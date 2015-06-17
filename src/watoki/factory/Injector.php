<?php
namespace watoki\factory;

use watoki\factory\exception\InjectionException;
use watoki\reflect\MethodAnalyzer;
use watoki\reflect\ClassResolver;

class Injector {

    /** @var bool */
    private $throwException = true;

    /** @var Factory */
    private $factory;

    /** @var callable */
    private $injector;

    public function __construct(Factory $factory, callable $internalInjector = null) {
        $this->factory = $factory;
        $this->injector = $internalInjector ?: function ($class) use ($factory) {
            return $factory->getInstance($class);
        };
    }

    public function setThrowWhenCantInjectProperty($throw) {
        $this->throwException = $throw;
    }

    /**
     * @param string $class
     * @param array $args
     * @param callable $parameterFilter
     * @return object An instance of $class
     * @throws InjectionException
     */
    public function injectConstructor($class, $args, $parameterFilter) {
        $reflection = new \ReflectionClass($class);

        if ($reflection->isAbstract() || $reflection->isInterface()) {
            throw new InjectionException("Cannot instantiate abstract class [$class].");
        }

        if (!$reflection->getConstructor()) {
            return $reflection->newInstance();
        }

        try {
            return $reflection->newInstanceArgs($this->injectMethodArguments($reflection->getConstructor(), $args, $parameterFilter));
        } catch (InjectionException $e) {
            throw new InjectionException('Error while injecting constructor of [' . $reflection->getName() . ']: ' . $e->getMessage(), 0, $e);
        } catch (\ReflectionException $re) {
            throw new InjectionException('Error while injecting constructor of [' . $reflection->getName() . ']: ' . $re->getMessage(), 0, $re);
        }
    }

    /**
     * @param object $object Object to call method on
     * @param string $method Name of the method
     * @param array $args
     * @param null|callable $parameterFilter If omitted, all missing arguments are injected
     * @return mixed The return value of the method
     * @throws InjectionException
     */
    public function injectMethod($object, $method, $args = array(), $parameterFilter = null) {
        $parameterFilter = $parameterFilter ?: function () {
            return true;
        };

        $reflection = new \ReflectionMethod($object, $method);
        $args = $this->injectMethodArguments($reflection, $args, $parameterFilter);

        return $reflection->invokeArgs($object, $args);
    }

    /**
     * @param \ReflectionMethod $method
     * @param array $args
     * @param callable $parameterFilter
     * @return array Of the injected arguments
     * @throws InjectionException
     */
    public function injectMethodArguments(\ReflectionMethod $method, array $args, $parameterFilter) {
        $analyzer = new MethodAnalyzer($method);
        try {
            return $analyzer->fillParameters($args, $this->injector, $parameterFilter);
        } catch (\InvalidArgumentException $e) {
            throw new InjectionException("Cannot inject method [{$method->getDeclaringClass()->getName()}"
                . "::{$method->getName()}]: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param object $object The object that the properties are injected into
     * @param callable $filter Function to determine if the passed property annotation should be included
     * @param \ReflectionClass $context The class to read the property annotations from (if not class of object)
     * @throws InjectionException
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
                $this->tryToInjectProperty($object, $propertyName, $resolver, $class);
            }

            $classReflection = $classReflection->getParentClass();
        }
    }

    /**
     * @param object $object The object that the properties are injected into
     * @param callable $filter Function to determine if the passed \ReflectionProperty should be included
     * @param \ReflectionClass $context The class to read the property annotations from (if not class of object)
     * @throws InjectionException
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
            $this->tryToInjectProperty($object, $property->getName(), $resolver, $matches[1]);
        }
    }

    private function tryToInjectProperty($targetObject, $propertyName, ClassResolver $resolver, $class) {
        try {
            $this->injectProperty($targetObject, $propertyName, $resolver, $class);
        } catch (InjectionException $e) {
            $targetClass = get_class($targetObject);
            throw new InjectionException("Error while injecting dependency [$propertyName] " .
                    "of [$targetClass]: " . $e->getMessage(), 0, $e);
        }
    }

    private function injectProperty($targetObject, $propertyName, ClassResolver $resolver, $class) {
        $type = $resolver->resolve($class);
        $classReflection = new \ReflectionClass($targetObject);

        if (!$type) {
            if ($this->throwException) {
                throw new InjectionException("Could not find [$class].");
            } else {
                return;
            }
        }

        $instance = $this->factory->getInstance($type);
        if ($classReflection->hasProperty($propertyName)) {
            $reflectionProperty = $classReflection->getProperty($propertyName);
            $reflectionProperty->setAccessible(true);

            if ($reflectionProperty->getValue($targetObject) === null) {
                $reflectionProperty->setValue($targetObject, $instance);
            }
        } else {
            $targetObject->$propertyName = $instance;
        }

    }
}