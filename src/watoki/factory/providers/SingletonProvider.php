<?php
namespace watoki\factory\providers;

use watoki\factory\Provider;

/**
 * Class SingletonProvider
 * A singleton provider. When use, the same instance of the class is return.
 *
 * @package watoki\factory\providers
 */
class SingletonProvider implements Provider
{

    private $instance;

    /**
     * Initialize the provider with the instance to use a singleton
     *
     * @param object $instance The instance to use a singleton
     */
    public function __construct($instance)
    {
        $this->instance = $instance;
    }

    /** {@inheritdoc} */
    public function provide($class, array $args = array())
    {
        return $this->instance;
    }
}