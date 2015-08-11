<?php
namespace watoki\factory\providers;

use watoki\factory\Factory;

/**
 * Class DefaultProvider
 * The default provider. Use annotations.
 *
 * @author  "Nikolas Martens" <Nikolas.M@rtens.org>
 * @license MIT
 * @package watoki\factory\providers
 */
class DefaultProvider extends MinimalProvider
{
    /** The injection token reads in annotation */
    const INJECTION_TOKEN = '<-';
    /** @var \Closure|callable The function to find if a property (magic variable) must be injected or not */
    private $propertyFilter;
    /** @var \Closure|callable The function to find if the class variable must be injected on instantiation or not */
    private $annotationFilter;
    /** @var string The name of the function to use (if exists) to inject properties(variables) into the new instance. */
    private $injectionMethod = 'inject';

    /**
     * Initialize the provider with the class factory.
     * (initialize the injector, based on the factory, and "@var", "@param", "@property" filter functions)
     *
     * @param Factory $factory The classes factory
     */
    function __construct(Factory $factory)
    {
        parent::__construct($factory);

        /*
         * Change the default filter, to a new one that check if the param annotation contains the injection token
         */
        $this->setParameterFilter(function (\ReflectionParameter $parameter) {
            $pattern = '/@param.+\$' . $parameter->getName() . '.+' . DefaultProvider::INJECTION_TOKEN . '/';
            return preg_match($pattern, $parameter->getDeclaringFunction()->getDocComment());
        });
        /*
         * A callback function to check if the @property (magic variables) annotation contains teh injection token
         */
        $this->annotationFilter = function ($annotation) {
            return strpos($annotation, DefaultProvider::INJECTION_TOKEN) !== false;
        };
        /*
         * A callback function to check if the @var annotation contains teh injection token
         */
        $this->propertyFilter = function (\ReflectionProperty $property) {
            return strpos($property->getDocComment(), DefaultProvider::INJECTION_TOKEN) !== false;
        };
    }

    /** {@inheritdoc} */
    public function provide($class, array $args = array())
    {
        $instance = parent::provide($class, $args);

        if ($this->injectionMethod && method_exists($instance, $this->injectionMethod)) {
            $this->injector->injectMethod($instance, $this->injectionMethod);
        }

        $this->injector->injectProperties($instance, $this->getPropertyFilter());

        $this->injector->injectPropertyAnnotations($instance, $this->getAnnotationFilter());

        return $instance;
    }

    /**
     * Set the callback function to use to filter "@property" annotation.
     * The filter function have this prototype:
     *
     *    function(string $classname) : bool
     *
     * @param callable $filter
     */
    public function setAnnotationFilter($filter)
    {
        $this->annotationFilter = $filter;
    }

    /**
     * Get the function used to filter "@property" annotation
     *
     * @return callable
     */
    public function getAnnotationFilter()
    {
        return $this->annotationFilter;
    }

    /**
     * Set the callback function to use to filter "@var" annotation.
     * The filter function have this prototype:
     *
     *    function(string $classname) : bool
     *
     * @return callable
     */
    public function getPropertyFilter()
    {
        return $this->propertyFilter;
    }

    /**
     * Get the function used to filter "@property" annotation
     *
     * @param callable $propertyFilter
     */
    public function setPropertyFilter($propertyFilter)
    {
        $this->propertyFilter = $propertyFilter;
    }

    /**
     * Set the name of the instance function to used for injecting properties
     *
     * @param string $injectionMethod
     */
    public function setInjectionMethod($injectionMethod)
    {
        $this->injectionMethod = $injectionMethod;
    }
}