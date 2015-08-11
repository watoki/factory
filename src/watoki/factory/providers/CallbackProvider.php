<?php
namespace watoki\factory\providers;

use watoki\factory\Provider;

/**
 * Class CallbackProvider
 * The provider use a user define callback function to have an instance of a class
 *
 * @package watoki\factory\providers
 */
class CallbackProvider implements Provider
{

    /** @var callable */
    private $callback;

    /**
     * Initialize the provider with the callback function.
     * The callback function have this prototype:
     *
     *   function(string $class, array $args) : object
     *
     * @param callable $callback
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    /** {@inheritdoc} */
    public function provide($class, array $args = array())
    {
        return call_user_func($this->callback, $class, $args);
    }
}