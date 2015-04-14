<?php
namespace watoki\factory\providers;

use watoki\factory\Factory;
use watoki\factory\Injector;
use watoki\factory\Provider;

class MinimalProvider implements Provider {

    protected $injector;

    /** @var callable */
    private $parameterFilter;

    public function __construct(Factory $factory) {
        $this->injector = new Injector($factory);
        $this->parameterFilter = function () {
            return true;
        };
    }

    public function provide($class, array $args = array()) {
        return $this->injector->injectConstructor($class, $args, $this->parameterFilter);
    }

    /**
     * @return callable
     */
    public function getParameterFilter() {
        return $this->parameterFilter;
    }

    /**
     * @param callable $parameterFilter Receives a \ReflectionParameter as argument
     */
    public function setParameterFilter($parameterFilter) {
        $this->parameterFilter = $parameterFilter;
    }
}