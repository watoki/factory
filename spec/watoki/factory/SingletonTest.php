<?php
namespace spec\watoki\factory;

use watoki\scrut\Specification;

/**
 * If an object is registered as a singleton of a class, it will be returned each time an instance of this class is requested.
 *
 * @property FactoryFixture $fix <-
 */
class SingletonTest extends Specification {

    public function testSingleton() {
        $this->fix->givenTheClassDefinition('class Singleton {
            /** @param $factory <- */
            function __construct(\watoki\factory\Factory $factory) {
                $factory->setSingleton(__CLASS__, $this);
            }
        }');
        $this->fix->whenIGet_FromTheFactory('Singleton');
        $this->fix->whenIGet_FromTheFactoryAgain('Singleton');
        $this->fix->thenBothInstancesShouldBeTheSameObject();
    }

    public function testNonExistingSingleton() {
        $this->fix->whenITryToGetTheSingleton('NonExistingSingleton');
        $this->fix->thenAnExceptionShouldBeThrown();
    }

    public function testGetExistingSingleton() {
        $this->fix->givenTheClassDefinition('class GetExistingSingleton {
            /** @param $factory <- */
            function __construct(\watoki\factory\Factory $factory, $arg) {
                $factory->setSingleton(__CLASS__, $this);
                $this->arg = $arg;
            }
        }');
        $this->fix->whenIGet_WithArguments_FromTheFactory('GetExistingSingleton', array('arg' => 'Special Argument'));
        $this->fix->whenIGetTheSingleton('GetExistingSingleton');
        $this->fix->thenTheObjectShouldBeAnInstanceOf('GetExistingSingleton');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('arg', 'Special Argument');
    }

    public function testCreateNewSingleton() {
        $this->fix->givenTheClassDefinition('class CreateNewSingleton {
            function __construct($arg) {
                $this->arg = $arg;
            }
        }');
        $this->fix->whenIGetTheSingleton_WithTheArguments('CreateNewSingleton', array('Hello'));
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('arg', 'Hello');

        $this->fix->whenIGetTheSingleton('CreateNewSingleton');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('arg', 'Hello');
    }

}