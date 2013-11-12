<?php
namespace watoki\factory\filters;

use watoki\factory\Filter;

class BooleanFilter implements Filter {

    public function filter($value) {
        return strtolower($value) == 'false' ? false : !!$value;
    }
}