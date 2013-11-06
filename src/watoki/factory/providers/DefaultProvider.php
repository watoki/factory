<?php
namespace watoki\factory\providers;

use watoki\factory\Factory;
use watoki\factory\Injector;
use watoki\factory\Provider;

class DefaultProvider implements Provider {

    protected $injector;

    private $injectionMethod = 'inject';

    public function __construct(Factory $factory) {
        $this->injector = new Injector($factory);
    }

    public function provide($class, array $args = array()) {
        $instance = $this->injector->injectConstructor($class, $args);

        if ($this->injectionMethod && method_exists($instance, $this->injectionMethod)) {
            $this->injector->injectMethod($instance, $this->injectionMethod);
        }

        return $instance;
    }

    /**
     * @param string $injectionMethod
     */
    public function setInjectionMethod($injectionMethod) {
        $this->injectionMethod = $injectionMethod;
    }
}