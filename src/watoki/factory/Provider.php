<?php
namespace watoki\factory;

/**
 * Interface Provider
 * Describe how to get an instance from a class name and params
 *
 * @author  "Nikolas Martens" <Nikolas.M@rtens.org>
 * @license MIT
 * @package watoki\factory
 */
interface Provider
{

    /**
     * Get an instance of the class $class with the parameters $args
     *
     * @param string $class
     * @param array $args
     *
     * @return object The $class instance
     */
    public function provide($class, array $args = array());

}