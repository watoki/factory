<?php
namespace watoki\factory;

class FactoryTest extends \PHPUnit_Framework_TestCase {

    public function testEmptyConstructor() {
        $this->given->theClass('class SomeClass {}');
        $this->when->iGet_FromTheFactory('SomeClass');
        $this->then->theObjectShouldBeAnInstanceOf('SomeClass');
    }

    public function testConstructorArguments() {
        $this->given->theClass('class ClassWithConstructor {
            function __construct($arg1, $arg2) {
                $this->msg = $arg1 . $arg2;
            }
        }');
        $this->when->iGet_WithArguments_FromTheFactory('ClassWithConstructor', array('arg2' => ' World', 'arg1' => 'Hello'));
        $this->then->theObjectShouldBeAnInstanceOf('ClassWithConstructor');
        $this->then->theTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testDefaultArguments() {
        $this->given->theClass('class DefaultArguments {
            function __construct($argRequired, $argDefault = " World") {
                $this->msg = $argRequired . $argDefault;
            }
        }');
        $this->when->iGet_WithArguments_FromTheFactory('DefaultArguments', array('argRequired' => 'Hello'));
        $this->then->theObjectShouldBeAnInstanceOf('DefaultArguments');
        $this->then->theTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testMissingArguments() {
        $this->given->theClass('class MissingArgument {
            function __construct($arg1, $arg2) {
                $this->msg = $arg1 . $arg2;
            }
        }');
        $this->when->iTryToGet_WithArguments_FromTheFactory('MissingArgument', array('arg2' => 'Not enough'));
        $this->then->anExceptionShouldBeThrown();
    }

    public function testInjectArgumentsByFactory() {
        $this->given->theClass('class InjectMe {
            function __construct($msg = "Hello World") {
                $this->greeting = $msg;
            }
        }');
        $this->given->theClass('class InjectingOne {
            function __construct(InjectMe $arg1) {
                $this->msg = $arg1->greeting;
            }
        }');
        $this->when->iGet_FromTheFactory('InjectingOne');
        $this->then->theTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testMixGivenAndInjectedArguments() {
        $this->given->theClass('class InjectMeToo {
            function __construct($msg = "Hello") {
                $this->greeting = $msg;
            }
        }');
        $this->given->theClass('class InjectingTwo {
            function __construct(InjectMeToo $arg1, $arg2, $arg3 = "!") {
                $this->msg = $arg1->greeting . $arg2 . $arg3;
            }
        }');
        $this->when->iGet_WithArguments_FromTheFactory('InjectingTwo', array('arg2' => ' World'));
        $this->then->theTheProperty_OfTheObjectShouldBe('msg', 'Hello World!');
    }

    public function testInjectFactory() {
        $this->given->theClass('class InjectFactory {
            function __construct(\watoki\factory\Factory $factory) {
                $this->factory = $factory;
            }
        }');
        $this->when->iGet_FromTheFactory('InjectFactory');
        $this->then->theTheProperty_OfTheObjectShouldBeTheFactory('factory');
    }

    public function testSingleton() {
        $this->given->theClass('class Singleton {
            function __construct(\watoki\factory\Factory $factory) {
                $factory->setSingleton(__CLASS__, $this);
            }
        }');
        $this->when->iGet_FromTheFactory('Singleton');
        $this->when->iGet_FromTheFactoryAgain('Singleton');
        $this->then->bothInstancesShouldBeTheSameObject();
    }

    public function testRecursiveInjection() {
        $this->given->theClass('class RecursiveInjectionOne {
            function __construct($msg = "Hello") {
                $this->msg = $msg;
            }
        }');
        $this->given->theClass('class RecursiveInjectionTwo {
            function __construct(RecursiveInjectionOne $one, $msg = " World") {
                $this->msg = $one->msg . $msg;
            }
        }');
        $this->given->theClass('class RecursiveInjectionThree {
            function __construct(RecursiveInjectionTwo $two) {
                $this->msg = $two->msg;
            }
        }');
        $this->when->iGet_FromTheFactory('RecursiveInjectionThree');
        $this->then->theTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testNonExistingSingleton() {
        $this->when->iTryToGetTheSingleton('NonExistingSingleton');
        $this->then->anExceptionShouldBeThrown();
    }

    public function testGetExistingSingleton() {
        $this->given->theClass('class SomeSingleton {
            function __construct(\watoki\factory\Factory $factory, $arg) {
                $factory->setSingleton(__CLASS__, $this);
                $this->arg = $arg;
            }
        }');
        $this->when->iGet_WithArguments_FromTheFactory('SomeSingleton', array('arg' => 'Special Argument'));
        $this->when->iTryToGetTheSingleton('SomeSingleton');
        $this->then->theObjectShouldBeAnInstanceOf('SomeSingleton');
        $this->then->theTheProperty_OfTheObjectShouldBe('arg', 'Special Argument');
    }

    /////////////////////////////// SET-UP ///////////////////////////////////

    /** @var FactoryTest_Given */
    public $given;
    /** @var FactoryTest_When */
    public $when;
    /** @var FactoryTest_Then */
    public $then;

    protected function setUp() {
        parent::setUp();
        $this->given = new FactoryTest_Given();
        $this->when = new FactoryTest_When();
        $this->then = new FactoryTest_Then($this);
    }

}

class FactoryTest_Given {

    public function theClass($classDefinition) {
        eval($classDefinition);
    }
}

class FactoryTest_When {

    public $instance;

    public $caught;

    public $instance2;

    function __construct() {
        $this->factory = new Factory();
    }

    public function iGet_FromTheFactory($className) {
        $this->instance = $this->factory->getInstance($className);
    }

    public function iGet_WithArguments_FromTheFactory($className, $args) {
        $this->instance = $this->factory->getInstance($className, $args);
    }

    public function iTryToGet_WithArguments_FromTheFactory($className, $args) {
        try {
            $this->iGet_WithArguments_FromTheFactory($className, $args);
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }

    public function iGet_FromTheFactoryAgain($className) {
        $this->instance2 = $this->factory->getInstance($className);
    }

    public function iTryToGetTheSingleton($className) {
        try {
            $this->iGetTheSingleton($className);
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }

    private function iGetTheSingleton($className) {
        $this->instance = $this->factory->getSingleton($className);
    }
}

class FactoryTest_Then {

    function __construct(FactoryTest $test) {
        $this->test = $test;
    }

    public function theObjectShouldBeAnInstanceOf($className) {
        $this->test->assertInstanceOf($className, $this->test->when->instance);
    }

    public function theTheProperty_OfTheObjectShouldBe($prop, $value) {
        $this->test->assertEquals($value, $this->test->when->instance->$prop);
    }

    public function anExceptionShouldBeThrown() {
        $this->test->assertNotNull($this->test->when->caught);
    }

    public function theTheProperty_OfTheObjectShouldBeTheFactory($prop) {
        $this->test->assertTrue($this->test->when->factory === $this->test->when->instance->$prop);
    }

    public function bothInstancesShouldBeTheSameObject() {
        $this->test->assertTrue($this->test->when->instance === $this->test->when->instance2);
    }
}