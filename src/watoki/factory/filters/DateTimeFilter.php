<?php
namespace watoki\factory\filters;

use watoki\factory\Filter;

class DateTimeFilter implements Filter {

    public function filter($value) {
        if (!$value) {
            return null;
        }
        return new \DateTime($value);
    }
}