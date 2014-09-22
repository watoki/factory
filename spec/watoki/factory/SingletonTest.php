<?php
namespace spec\watoki\factory;

use watoki\scrut\Specification;

/**
 * If an object is registered as a singleton of a class, it will be returned each time an instance of this class is requested.
 *
 * @property FactoryFixture factoryFix <-
 */
class SingletonTest extends Specification {

    public function testSingleton() {
        $this->factoryFix->givenTheClassDefinition('class Singleton {
            /**
             * @param $factory <-
             */
            function __construct(\watoki\factory\Factory $factory) {
                $factory->setSingleton(__CLASS__, $this);
            }
        }');
        $this->factoryFix->whenIGet_FromTheFactory('Singleton');
        $this->factoryFix->whenIGet_FromTheFactoryAgain('Singleton');
        $this->factoryFix->thenBothInstancesShouldBeTheSameObject();
    }

    public function testNonExistingSingleton() {
        $this->factoryFix->whenITryToGetTheSingleton('NonExistingSingleton');
        $this->factoryFix->thenAnExceptionShouldBeThrown();
    }

    public function testGetExistingSingleton() {
        $this->factoryFix->givenTheClassDefinition('class SomeSingleton {
            /**
             * @param $factory <-
             */
            function __construct(\watoki\factory\Factory $factory, $arg) {
                $factory->setSingleton(__CLASS__, $this);
                $this->arg = $arg;
            }
        }');
        $this->factoryFix->whenIGet_WithArguments_FromTheFactory('SomeSingleton', array('arg' => 'Special Argument'));
        $this->factoryFix->whenITryToGetTheSingleton('SomeSingleton');
        $this->factoryFix->thenTheObjectShouldBeAnInstanceOf('SomeSingleton');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('arg', 'Special Argument');
    }

}