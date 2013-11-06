<?php
namespace watoki\factory\providers;

use watoki\factory\Factory;
use watoki\factory\Injector;
use watoki\factory\Provider;

class MinimalProvider implements Provider {

    protected $injector;

    public function __construct(Factory $factory) {
        $this->injector = new Injector($factory);
    }

    public function provide($class, array $args = array()) {
        return $this->injector->injectConstructor($class, $args);
    }
}