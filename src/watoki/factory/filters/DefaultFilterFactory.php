<?php
namespace watoki\factory\filters;
 
use watoki\factory\Factory;
use watoki\factory\FilterFactory;

class DefaultFilterFactory extends FilterFactory {

    function __construct(Factory $factory) {
        parent::__construct($factory);

        $this->registerFilter('array', new ArrayFilter());
        $this->registerFilter('boolean', new BooleanFilter());
        $this->registerFilter('DateTime', new DateTimeFilter());
        $this->registerFilter('float', new FloatFilter());
        $this->registerFilter('integer', new IntegerFilter());
        $this->registerFilter('string', new StringFilter());
    }

}
 