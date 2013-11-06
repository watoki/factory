<?php
namespace watoki\factory\providers;

use watoki\factory\Factory;

class DefaultProvider extends MinimalProvider {

    private $propertyFilter;

    private $annotationFilter;

    private $injectionMethod = 'inject';

    function __construct(Factory $factory) {
        parent::__construct($factory);


        $this->annotationFilter = function ($annotation) {
            return strpos($annotation, '<-') !== false;
        };
        $this->propertyFilter = function (\ReflectionProperty $property) {
            return strpos($property->getDocComment(), '<-') !== false;
        };
    }

    public function provide($class, array $args = array()) {
        $instance = parent::provide($class, $args);

        if ($this->injectionMethod && method_exists($instance, $this->injectionMethod)) {
            $this->injector->injectMethod($instance, $this->injectionMethod);
        }

        $this->injector->injectProperties($instance, $this->propertyFilter);

        $this->injector->injectPropertyAnnotations($instance, $this->annotationFilter);

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

    /**
     * @param string $injectionMethod
     */
    public function setInjectionMethod($injectionMethod) {
        $this->injectionMethod = $injectionMethod;
    }
}