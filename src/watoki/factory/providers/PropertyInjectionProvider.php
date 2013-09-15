<?php
namespace watoki\factory\providers;

use watoki\factory\Factory;

class PropertyInjectionProvider extends DefaultProvider {

    private $includingAnnotations;

    function __construct(Factory $factory, $includingAnnotationProperties = false) {
        parent::__construct($factory);
        $this->includingAnnotations = $includingAnnotationProperties;
    }

    public function provide($class, array $args = array()) {
        $instance = parent::provide($class, $args);

        if ($this->includingAnnotations) {
            $this->injector->injectPropertyAnnotations($instance);
        }

        return $instance;
    }
}