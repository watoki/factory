<?php
namespace watoki\factory\filters;

use watoki\factory\Filter;

class NullFilter implements Filter {

    public function filter($value) {
        return $value;
    }
}
 