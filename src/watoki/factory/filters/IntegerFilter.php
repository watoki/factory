<?php
namespace watoki\factory\filters;

use watoki\factory\Filter;

class IntegerFilter implements Filter {

    public function filter($value) {
        return intval($value);
    }
}