<?php
namespace watoki\factory\filters;

use watoki\factory\Filter;

class DateTimeFilter implements Filter {

    public function filter($value) {
        return new \DateTime($value);
    }
}