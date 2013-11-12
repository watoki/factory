<?php
namespace watoki\factory;
 
use watoki\factory\filters\NullFilter;

class FilterFactory {

    /** @var array|Filter[] */
    private $filters = array();

    function __construct(Factory $factory) {
        $factory->setSingleton(get_class($this), $this);
    }

    /**
     * @param string $type
     * @return Filter
     */
    public function getFilter($type) {
        while ($type) {
            $normalized = $this->normalizeType($type);
            if (array_key_exists($normalized, $this->filters)) {
                return $this->filters[$normalized];
            }
            $type = is_object($type) ? get_parent_class($type) : null;
        }
        return new NullFilter();
    }

    public function registerFilter($type, Filter $filter) {
        $this->filters[$this->normalizeType($type)] = $filter;
    }

    private function normalizeType($type) {
        $normalized = trim(strtolower($type), '\\');

        switch ($normalized) {
            case 'int':
                return 'integer';
            case 'bool':
                return 'boolean';
            default:
                return $normalized;
        }
    }
}
 