<?php

namespace watoki\factory;

/**
 * Class AliasFactory.
 *
 * Alias factory allow you to define class alias to reduce class name.
 * For example, instead of:
 *
 * ```php
 * $object = $factory->getInstance('\\My\\Super\\Application\\Entity\\User');
 * ```
 * You can just define an alias and class it:
 *
 * ```php
 * $factory->registerAlias('\\My\\Super\\Application\\Entity\\User', 'User');
 *
 * //... later in your code
 *
 * $object = $factory->getInstance('User');
 * ```
 * (Of course, in most of case we use `use \My\Super\Application\Entity\User` and
 * `$object = $factory->getInstance(User.class)` which is already a short syntax).
 *
 * But you can also define factory override.
 * Let's imagine that your project have a `\SomeOne\Framework\Application` class, but have have extend it in your
 * class `\My\Framework\Application`.
 * To shameless use your class instead of their, just do:
 *
 * ```php
 * use \SomeOne\Framework\Application as BaseApplication;
 * use \My\Framework\Application;
 *
 * $factory->registerAlias(BaseApplication.class, Application.class);
 * ```
 * Now `$factory` will return `\My\Framework\Application` when you ask the factory for `\SomeOne\Framework\Application`
 *
 * @author  MacFJA
 * @license MIT
 * @package watoki\factory
 */
class AliasFactory extends Factory
{
    /**
     * The aliases definition holder.
     *
     * The key is the full class name, the value is the alias name
     *
     * @var array
     */
    protected $aliases;

    /**
     * Define a singleton
     *
     * @param object      $instance The singleton instance
     * @param string|null $class If omitted, the class of the instance is used
     * @param null|string $alias The alias to use
     * @return object
     */
    public function setSingleton($instance, $class = null, $alias = null) {
        $class = $class ?: get_class($instance);
        $this->registerAlias($class, $alias);

        return parent::setSingleton($instance, $class);
    }

    /**
     * Register an alias for a class name
     *
     * @param string      $className The class to aliased
     * @param null|string $alias     The name of the alias, if null, no alias is registered
     */
    public function registerAlias($className, $alias = null) {
        if (is_null($alias)) {
            return;
        }

        $this->aliases[$alias] = $className;
    }

    /**
     * {@inheritDoc}
     */
    public function getInstance($class, $args = array())
    {
        // Search for an alias
        if (array_key_exists($class, $this->aliases)) {
            $class = $this->aliases[$class];
        }
        return parent::getInstance($class, $args);
    }

    /**
     * Define a provider of a class name
     *
     * @param string      $class    The name (or alias) of the class
     * @param Provider    $provider The class provider to use for this class name
     * @param null|String $alias    The alias to use
     */
    public function setProvider($class, Provider $provider, $alias = null)
    {
        $this->registerAlias($class, $alias);

        parent::setProvider($class, $provider);
    }

}