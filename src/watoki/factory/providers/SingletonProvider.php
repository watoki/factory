<?php
namespace watoki\factory\providers;

use watoki\factory\Provider;

class SingletonProvider implements Provider {

    private $instance;

    /**
     * @param object $instance
     */
    function __construct($instance) {
        $this->instance = $instance;
    }

    public function provide($class, array $args = array()) {
        return $this->instance;
    }
}