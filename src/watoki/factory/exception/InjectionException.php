<?php
namespace watoki\factory\exception;

/**
 * Class InjectionException
 * A class to defined exception on injection
 *
 * @package watoki\factory\exception
 */
class InjectionException extends \Exception {
    /** @var string The Factory class name */
    public static $CLASS = __CLASS__;
}