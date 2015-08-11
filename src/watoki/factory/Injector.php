<?php
namespace watoki\factory;

use watoki\factory\exception\InjectionException;
use watoki\reflect\MethodAnalyzer;
use watoki\reflect\ClassResolver;

/**
 * Class Injector
 * Inject objects, params into an instance
 *
 * @package watoki\factory
 */
class Injector
{

    /** @var bool */
    private $throwException = true;

    /** @var callable */
    private $injector;

    public function __construct(Factory $factory, callable $internalInjector = null)
    {
        $this->injector = $internalInjector ?: function ($class) use ($factory) {
            return $factory->getInstance($class);
        };
    }

    /**
     * Indicate if an exception must be thrown if an error occur when injecting
     *
     * @param bool $throw Indicate if an exception must be thrown
     */
    public function setThrowWhenCantInjectProperty($throw)
    {
        $this->throwException = $throw;
    }

    /**
     * Instantiate a new object by its constructor
     *
     * @param string $class The class name to instantiate
     * @param array $args List of arguments to use
     * @param callable $parameterFilter Callback function for filtering parameters to inject
     *
     * @return object An instance of $class
     *
     * @throws InjectionException
     */
    public function injectConstructor($class, $args, $parameterFilter)
    {
        $reflection = new \ReflectionClass($class);

        if ($reflection->isAbstract() || $reflection->isInterface()) {
            throw new InjectionException("Cannot instantiate abstract class [$class].");
        }

        if (!$reflection->getConstructor()) {
            return $reflection->newInstance();
        }

        try {
            return $reflection->newInstanceArgs($this->injectMethodArguments($reflection->getConstructor(), $args,
                $parameterFilter));
        } catch (InjectionException $e) {
            throw new InjectionException('Error while injecting constructor of [' . $reflection->getName() . ']: ' . $e->getMessage(),
                0, $e);
        } catch (\ReflectionException $re) {
            throw new InjectionException('Error while injecting constructor of [' . $reflection->getName() . ']: ' . $re->getMessage(),
                0, $re);
        }
    }

    /**
     * Inject object/params in an object method
     *
     * @param object $object Object to call method on
     * @param string $method Name of the method
     * @param array $args List of params to use
     * @param null|callable $parameterFilter If omitted, all missing arguments are injected
     *
     * @return mixed The return value of the method
     *
     * @throws InjectionException
     */
    public function injectMethod($object, $method, $args = array(), $parameterFilter = null)
    {
        $parameterFilter = $parameterFilter ?: function () {
            return true;
        };

        $reflection = new \ReflectionMethod($object, $method);
        $args = $this->injectMethodArguments($reflection, $args, $parameterFilter);

        return $reflection->invokeArgs($object, $args);
    }

    /**
     * Create the list of all arguments to inject in a method
     *
     * @param \ReflectionMethod $method The reflection method
     * @param array $args List of params to inject
     * @param callable $parameterFilter Callback function for filtering parameters to inject
     *
     * @return array Of the injected arguments
     *
     * @throws InjectionException
     */
    public function injectMethodArguments(\ReflectionMethod $method, array $args, $parameterFilter)
    {
        $analyzer = new MethodAnalyzer($method);
        try {
            return $analyzer->fillParameters($args, $this->injector, $parameterFilter);
        } catch (\InvalidArgumentException $e) {
            throw new InjectionException("Cannot inject method [{$method->getDeclaringClass()->getName()}"
                . "::{$method->getName()}]: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Inject value into object properties by reading "@property" (magic properties) annotation (include inherited).
     *
     * @param object $object The object that the properties are injected into
     * @param callable $filter Function to determine if the passed property annotation should be included
     * @param \ReflectionClass $context The class to read the property annotations from (if not class of object)
     *
     * @throws InjectionException
     */
    public function injectPropertyAnnotations($object, $filter, \ReflectionClass $context = null)
    {
        $classReflection = $context ?: new \ReflectionClass($object);

        while ($classReflection) {
            $resolver = new ClassResolver($classReflection);

            $matches = array();
            // RegEx to find @property annotations
            preg_match_all('/@property\s+(\S+)\s+\$?(\S+).*/', $classReflection->getDocComment(), $matches);

            foreach ($matches[0] as $i => $match) {
                // Filtering annotation with the filter class
                if (!call_user_func($filter, trim($match))) {
                    continue;
                }

                $class = $matches[1][$i];
                $propertyName = $matches[2][$i];
                $this->tryToInjectProperty($object, $propertyName, $resolver, $class);
            }

            // Continue with the parent class (search for inherited properties)
            $classReflection = $classReflection->getParentClass();
        }
    }

    /**
     * Inject value into object properties.
     *
     * @param object $object The object that the properties are injected into
     * @param callable $filter Function to determine if the passed \ReflectionProperty should be included
     * @param \ReflectionClass $context The class to read the property annotations from (if not class of object)
     *
     * @throws InjectionException
     */
    public function injectProperties($object, $filter, \ReflectionClass $context = null)
    {
        $classReflection = $context ?: new \ReflectionClass($object);

        foreach ($classReflection->getProperties() as $property) {
            $matches = array();
            // RegEx to find @var annotation
            preg_match('/@var\s+(\S+).*/', $property->getDocComment(), $matches);

            if (empty($matches) || !$filter($property)) {
                continue;
            }

            $resolver = new ClassResolver($property->getDeclaringClass());
            $this->tryToInjectProperty($object, $property->getName(), $resolver, $matches[1]);
        }
    }

    /**
     * Inject a property into an object.
     * Wrapper of self::injectProperty, to enhance error message.
     *
     * @param object $targetObject The object where the property must be injected
     * @param string $propertyName The name of the property to inject
     * @param ClassResolver $resolver The class resolver
     * @param string $class The type (class name) of the property
     *
     * @throws InjectionException
     */
    private function tryToInjectProperty($targetObject, $propertyName, ClassResolver $resolver, $class)
    {
        try {
            $this->injectProperty($targetObject, $propertyName, $resolver, $class);
        } catch (InjectionException $e) {
            $targetClass = get_class($targetObject);
            throw new InjectionException("Error while injecting dependency [$propertyName] " .
                "of [$targetClass]: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Inject a property into an object.
     *
     * @param object $targetObject The object where the property must be injected
     * @param string $propertyName The name of the property to inject
     * @param ClassResolver $resolver The class resolver
     * @param string $class The type (class name) of the property
     *
     * @throws InjectionException
     */
    private function injectProperty($targetObject, $propertyName, ClassResolver $resolver, $class)
    {
        $type = $resolver->resolve($class);
        $classReflection = new \ReflectionClass($targetObject);

        if (!$type) {
            if ($this->throwException) {
                throw new InjectionException("Could not find [$class].");
            } else {
                return;
            }
        }

        $instance = call_user_func($this->injector, $type);

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