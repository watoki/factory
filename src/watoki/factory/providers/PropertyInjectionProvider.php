<?php
namespace watoki\factory\providers;

use watoki\factory\Factory;

class PropertyInjectionProvider extends DefaultProvider {

    private $includingAnnotations;

    private $propertyFilter;

    private $annotationFilter;

    function __construct(Factory $factory, $includingAnnotationProperties = false) {
        parent::__construct($factory);
        $this->includingAnnotations = $includingAnnotationProperties;

        $filter = function ($annotation) {
            return strpos($annotation, '<-') !== false;
        };
        $this->annotationFilter = $filter;
        $this->propertyFilter = $filter;
    }

    public function provide($class, array $args = array()) {
        $instance = parent::provide($class, $args);

        $this->injector->injectProperties($instance, $this->propertyFilter);

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

    /**
     * @return callable
     */
    public function getPropertyFilter() {
        return $this->propertyFilter;
    }

    /**
     * @param callable $propertyFilter
     */
    public function setPropertyFilter($propertyFilter) {
        $this->propertyFilter = $propertyFilter;
    }
}