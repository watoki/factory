<?php
namespace spec\watoki\factory\factory;

use spec\watoki\factory\factory\FactoryFixture;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\factory\factory\FactoryFixture factoryFix <-
 */
class ConstructorInjectionTest extends Specification {

    public function testEmptyConstructor() {
        $this->factoryFix->givenTheClass('class SomeClass {}');
        $this->factoryFix->whenIGet_FromTheFactory('SomeClass');
        $this->factoryFix->thenTheObjectShouldBeAnInstanceOf('SomeClass');
    }

    public function testConstructorArguments() {
        $this->factoryFix->givenTheClass('class ClassWithConstructor {
            function __construct($arg1, $arg2) {
                $this->msg = $arg1 . $arg2;
            }
        }');
        $this->factoryFix->whenIGet_WithArguments_FromTheFactory('ClassWithConstructor', array('arg2' => ' World', 'arg1' => 'Hello'));
        $this->factoryFix->thenTheObjectShouldBeAnInstanceOf('ClassWithConstructor');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testDefaultArguments() {
        $this->factoryFix->givenTheClass('class DefaultArguments {
            function __construct($argRequired, $argDefault = " World") {
                $this->msg = $argRequired . $argDefault;
            }
        }');
        $this->factoryFix->whenIGet_WithArguments_FromTheFactory('DefaultArguments', array('argRequired' => 'Hello'));
        $this->factoryFix->thenTheObjectShouldBeAnInstanceOf('DefaultArguments');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testMissingArguments() {
        $this->factoryFix->givenTheClass('class MissingArgument {
            function __construct($arg1, $arg2) {
                $this->msg = $arg1 . $arg2;
            }
        }');
        $this->factoryFix->whenITryToGet_WithArguments_FromTheFactory('MissingArgument', array('arg2' => 'Not enough'));
        $this->factoryFix->thenAnExceptionShouldBeThrown();
    }

    public function testInjectArgumentsByFactory() {
        $this->factoryFix->givenTheClass('class InjectMe {
            function __construct($msg = "Hello World") {
                $this->greeting = $msg;
            }
        }');
        $this->factoryFix->givenTheClass('class InjectingOne {
            function __construct(InjectMe $arg1) {
                $this->msg = $arg1->greeting;
            }
        }');
        $this->factoryFix->whenIGet_FromTheFactory('InjectingOne');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testMixGivenAndInjectedArguments() {
        $this->factoryFix->givenTheClass('class InjectMeToo {
            function __construct($msg = "Hello") {
                $this->greeting = $msg;
            }
        }');
        $this->factoryFix->givenTheClass('class InjectingTwo {
            function __construct(InjectMeToo $arg1, $arg2, $arg3 = "!") {
                $this->msg = $arg1->greeting . $arg2 . $arg3;
            }
        }');
        $this->factoryFix->whenIGet_WithArguments_FromTheFactory('InjectingTwo', array('arg2' => ' World'));
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World!');
    }

    public function testInjectFactory() {
        $this->factoryFix->givenTheClass('class InjectFactory {
            function __construct(\watoki\factory\Factory $factory) {
                $this->factory = $factory;
            }
        }');
        $this->factoryFix->whenIGet_FromTheFactory('InjectFactory');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBeTheFactory('factory');
    }

    public function testRecursiveInjection() {
        $this->factoryFix->givenTheClass('class RecursiveInjectionOne {
            function __construct($msg = "Hello") {
                $this->msg = $msg;
            }
        }');
        $this->factoryFix->givenTheClass('class RecursiveInjectionTwo {
            function __construct(RecursiveInjectionOne $one, $msg = " World") {
                $this->msg = $one->msg . $msg;
            }
        }');
        $this->factoryFix->givenTheClass('class RecursiveInjectionThree {
            function __construct(RecursiveInjectionTwo $two) {
                $this->msg = $two->msg;
            }
        }');
        $this->factoryFix->whenIGet_FromTheFactory('RecursiveInjectionThree');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testOptionalClassArgument() {
        $this->factoryFix->givenTheClass('class OptionalClassArgument {
            function __construct(\DateTime $date = null) {
                $this->date = $date;
            }
        }');
        $this->factoryFix->whenIGet_FromTheFactory('OptionalClassArgument');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('date', null);
    }

}