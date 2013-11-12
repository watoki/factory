<?php
namespace watoki\factory\filters;

use watoki\factory\Filter;

class StringFilter implements Filter {

    public function filter($value) {
        return strval($value);
    }
}