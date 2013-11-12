<?php
namespace watoki\factory\filters;

use watoki\factory\Filter;

class ArrayFilter implements Filter {

    public function filter($value) {
        return (array) $value;
    }
}