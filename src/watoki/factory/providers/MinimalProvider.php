<?php
namespace watoki\factory\providers;

use watoki\factory\Factory;
use watoki\factory\Injector;
use watoki\factory\Provider;

/**
 * Class MinimalProvider
 * A base provider that use injector and a parameter injection filter.
 *
 * @package watoki\factory\providers
 */
class MinimalProvider implements Provider
{
    /** @var Injector */
    protected $injector;

    /** @var callable */
    private $parameterFilter;

    /**
     * Initialize the provider with the class factory.
     * (initialize the injector, based on the factory, and the default filter function)
     *
     * @param Factory $factory The classes factory
     */
    public function __construct(Factory $factory)
    {
        $this->injector = new Injector($factory);
        $this->parameterFilter = function () {
            return true;
        };
    }

    /** {@inheritdoc} */
    public function provide($class, array $args = array())
    {
        return $this->injector->injectConstructor($class, $args, $this->parameterFilter);
    }

    /**
     * Get the function used to filter which parameter can be injected or not
     *
     * @return callable
     */
    public function getParameterFilter()
    {
        return $this->parameterFilter;
    }

    /**
     * Set the function that will be use to filter parameters.
     * The filter function have this prototype:
     *
     *    function(\ReflectionParameter $parameter) : bool
     *
     * @param callable $parameterFilter Receives a \ReflectionParameter as argument
     */
    public function setParameterFilter($parameterFilter)
    {
        $this->parameterFilter = $parameterFilter;
    }
}