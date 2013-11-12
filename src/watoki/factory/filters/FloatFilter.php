<?php
namespace watoki\factory\filters;

use watoki\factory\Filter;

class FloatFilter implements Filter {

    public function filter($value) {
        return floatval($value);
    }
}