<?php
namespace spec\watoki\factory;

use watoki\scrut\Specification;

/**
 * If an object is registered as a singleton of a class, it will be returned each time an instance of this class is requested.
 *
 * @property FactoryFixture $fix <-
 */
class SingletonTest extends Specification {

    public function testSingletonForOwnClass() {
        $this->fix->givenTheClassDefinition('class Singleton {
            /** @param $factory <- */
            function __construct(\watoki\factory\Factory $factory) {
                $factory->setSingleton($this);
            }
        }');
        $this->fix->whenIGet_FromTheFactory('Singleton');
        $this->fix->whenIGet_FromTheFactoryAgain('Singleton');
        $this->fix->thenBothInstancesShouldBeTheSameObject();
    }

    public function testSingletonForOtherClass() {
        $this->fix->givenTheClassDefinition('class DateTimeSingleton {}');
        $this->fix->whenISetAnInstanceOf_AsASingletonFor('DateTimeSingleton', 'DateTime');
        $this->fix->whenIGet_FromTheFactory('DateTime');
        $this->fix->thenTheObjectShouldBeAnInstanceOf("DateTimeSingleton");
    }

    public function testNonExistingSingleton() {
        $this->fix->whenITryToGet_FromTheFactory('NonExistingSingleton');
        $this->fix->thenAnExceptionShouldBeThrown();
    }

}