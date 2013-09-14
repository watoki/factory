<?php
namespace spec\watoki\factory;

use watoki\factory\Factory;
use watoki\scrut\Fixture;
use watoki\scrut\Specification;

class FactoryFixture extends Fixture {

    private $instance;

    private $caught;

    private $instance2;

    /** @var Factory */
    private $factory;

    public function __construct(Specification $spec, Factory $factory) {
        parent::__construct($spec, $factory);
        $this->factory = new Factory();
    }

    public function givenTheClass($classDefinition) {
        eval($classDefinition);
    }

    public function whenIGet_FromTheFactory($className) {
        $this->instance = $this->factory->getInstance($className);
    }

    public function whenIGet_WithArguments_FromTheFactory($className, $args) {
        $this->instance = $this->factory->getInstance($className, $args);
    }

    public function whenITryToGet_WithArguments_FromTheFactory($className, $args) {
        try {
            $this->whenIGet_WithArguments_FromTheFactory($className, $args);
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }

    public function whenITryToGetTheSingleton($className) {
        try {
            $this->whenIGetTheSingleton($className);
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }

    public function whenIGet_FromTheFactoryAgain($className) {
        $this->instance2 = $this->factory->getInstance($className);
    }

    public function thenAnExceptionShouldBeThrown() {
        $this->spec->assertNotNull($this->caught);
    }

    public function whenIGetTheSingleton($className) {
        $this->instance = $this->factory->getSingleton($className);
    }

    public function thenBothInstancesShouldBeTheSameObject() {
        $this->spec->assertTrue($this->instance === $this->instance2);
    }

    public function thenTheObjectShouldBeAnInstanceOf($className) {
        $this->spec->assertInstanceOf($className, $this->instance);
    }

    public function thenTheTheProperty_OfTheObjectShouldBe($prop, $value) {
        $this->spec->assertEquals($value, $this->instance->$prop);
    }

    public function thenTheTheProperty_OfTheObjectShouldBeTheFactory($prop) {
        $this->spec->assertTrue($this->factory === $this->instance->$prop);
    }

}