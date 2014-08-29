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

    public function injectMethodArguments(\ReflectionMethod $method, array $args, FilterFactory $filters = null) {
        $argArray = array();
        foreach ($method->getParameters() as $param) {
            $type = $this->findTypeHint($method, $param);

            if ($this->hasValue($param, $args)) {
                $value = $this->getValue($param, $args);
                if ($type && $filters) {
                    $arg = $filters->getFilter($type)->filter($value);
                } else {
                    $arg = $value;
                }
            } else if ($param->isDefaultValueAvailable()) {
                $arg = $param->getDefaultValue();
            } else if (!$type) {
                throw new \Exception("Cannot inject parameter [{$param->getName()}] of [{$method->getDeclaringClass()->getName()}"
                    . "::{$method->getName()}]: Argument not given and no type hint found.");
            } else {
                try {
                    $arg = $this->factory->getInstance($type);
                } catch (\Exception $e) {
                    throw new \Exception("Cannot inject parameter [{$param->getName()}] of [{$method->getDeclaringClass()->getName()}"
                        . "::{$method->getName()}]: " . $e->getMessage(), 0, $e);
                }
            }

            $argArray[$param->getName()] = $arg;
        }
        return $argArray;
    }

    private function findTypeHint(\ReflectionMethod $method, \ReflectionParameter $param) {
        if ($param->getClass()) {
            return $param->getClass()->getName();
        }

        $matches = array();
        $pattern = '/@param\s+(\S+)\s+\$' . $param->getName() . '/';
        $found = preg_match($pattern, $method->getDocComment(), $matches);

        if (!$found) {
            return null;
        }
        $type = $matches[1];

        $resolver = new ClassResolver($method->getDeclaringClass());
        $resolved = $resolver->resolve($type);

        return $resolved ?: $type;
    }

    private function getValue(\ReflectionParameter $param, array $args) {
        if (array_key_exists($param->getName(), $args)) {
            return $args[$param->getName()];
        } else if (array_key_exists($param->getPosition(), $args)) {
            return $args[$param->getPosition()];
        } else {
            return null;
        }
    }

    private function hasValue(\ReflectionParameter $param, array $args) {
        return array_key_exists($param->getName(), $args) || array_key_exists($param->getPosition(), $args);
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

                $this->injectProperty($object, $matches[2][$i], $resolver->resolve($matches[1][$i]));
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