<?php
namespace watoki\factory\providers;

use watoki\factory\Factory;

class PropertyInjectionProvider extends DefaultProvider {

    private $annotationFilter;

    private $includingAnnotations;

    function __construct(Factory $factory, $includingAnnotationProperties = false) {
        parent::__construct($factory);
        $this->includingAnnotations = $includingAnnotationProperties;

        $this->annotationFilter = function ($annotation) {
            return strpos($annotation, '<-') !== false;
        };
    }

    public function provide($class, array $args = array()) {
        $instance = parent::provide($class, $args);

        if ($this->includingAnnotations) {
            $this->injector->injectPropertyAnnotations($instance, $this->annotationFilter);
        }

        return $instance;
    }

    /**
     * @param callable $filter
     */
    public function setAnnotationFilter($filter) {
        $this->annotationFilter = $filter;
    }

    /**
     * @return callable
     */
    public function getAnnotationFilter() {
        return $this->annotationFilter;
    }
}