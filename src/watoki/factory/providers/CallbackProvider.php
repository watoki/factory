<?php
namespace watoki\factory\providers;

use watoki\factory\Provider;

class CallbackProvider implements Provider {

    /** @var callable */
    private $callback;

    public function __construct($callback) {
        $this->callback = $callback;
    }

    public function provide($class, array $args = array()) {
        return call_user_func($this->callback, $class, $args);
    }
}