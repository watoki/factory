<?php
namespace spec\watoki\factory;

use spec\watoki\factory\FactoryFixture;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\factory\FactoryFixture factoryFix <-
 */
class ConstructorInjectionTest extends Specification {

    public function testEmptyConstructor() {
        $this->factoryFix->givenTheClassDefinition('class SomeClass {}');
        $this->factoryFix->whenIGet_FromTheFactory('SomeClass');
        $this->factoryFix->thenTheObjectShouldBeAnInstanceOf('SomeClass');
    }

    public function testConstructorArguments() {
        $this->factoryFix->givenTheClassDefinition('class ClassWithConstructor {
            function __construct($arg1, $arg2) {
                $this->msg = $arg1 . $arg2;
            }
        }');
        $this->factoryFix->whenIGet_WithArguments_FromTheFactory('ClassWithConstructor', array('arg2' => ' World', 'arg1' => 'Hello'));

        $this->factoryFix->thenTheObjectShouldBeAnInstanceOf('ClassWithConstructor');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testConstructorArgumentsInList() {
        $this->factoryFix->givenTheClassDefinition('class ClassWithConstructorInList {
            function __construct($arg1, $arg2) {
                $this->msg = $arg1 . $arg2;
            }
        }');

        $this->factoryFix->whenIGet_WithArguments_FromTheFactory('ClassWithConstructorInList', array('Hello', ' You'));

        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello You');
    }

    public function testConstructorArgumentsMixed() {
        $this->factoryFix->givenTheClassDefinition('class ClassWithConstructorMixed {
            function __construct($arg1, $arg2) {
                $this->msg = $arg1 . $arg2;
            }
        }');

        $this->factoryFix->whenIGet_WithArguments_FromTheFactory('ClassWithConstructorMixed', array('arg2' => ' World', 0 => 'Hello'));

        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testDefaultArguments() {
        $this->factoryFix->givenTheClassDefinition('class DefaultArguments {
            function __construct($argRequired, $argDefault = " World") {
                $this->msg = $argRequired . $argDefault;
            }
        }');
        $this->factoryFix->whenIGet_WithArguments_FromTheFactory('DefaultArguments', array('argRequired' => 'Hello'));
        $this->factoryFix->thenTheObjectShouldBeAnInstanceOf('DefaultArguments');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testMissingArguments() {
        $this->factoryFix->givenTheClassDefinition('class MissingArgument {
            function __construct($arg1, $arg2) {
                $this->msg = $arg1 . $arg2;
            }
        }');
        $this->factoryFix->whenITryToGet_WithArguments_FromTheFactory('MissingArgument', array('arg2' => 'Not enough'));
        $this->factoryFix->thenAnExceptionShouldBeThrown();
    }

    public function testInjectArgumentsByFactory() {
        $this->factoryFix->givenTheClassDefinition('class InjectMe {
            function __construct($msg = "Hello World") {
                $this->greeting = $msg;
            }
        }');
        $this->factoryFix->givenTheClassDefinition('class InjectingOne {
            function __construct(InjectMe $arg1) {
                $this->msg = $arg1->greeting;
            }
        }');
        $this->factoryFix->whenIGet_FromTheFactory('InjectingOne');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testMixGivenAndInjectedArguments() {
        $this->factoryFix->givenTheClassDefinition('class InjectMeToo {
            function __construct($msg = "Hello") {
                $this->greeting = $msg;
            }
        }');
        $this->factoryFix->givenTheClassDefinition('class InjectingTwo {
            function __construct(InjectMeToo $arg1, $arg2, $arg3 = "!") {
                $this->msg = $arg1->greeting . $arg2 . $arg3;
            }
        }');
        $this->factoryFix->whenIGet_WithArguments_FromTheFactory('InjectingTwo', array('arg2' => ' World'));
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World!');
    }

    public function testInjectFactory() {
        $this->factoryFix->givenTheClassDefinition('class InjectFactory {
            function __construct(\watoki\factory\Factory $factory) {
                $this->factory = $factory;
            }
        }');
        $this->factoryFix->whenIGet_FromTheFactory('InjectFactory');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBeTheFactory('factory');
    }

    public function testRecursiveInjection() {
        $this->factoryFix->givenTheClassDefinition('class RecursiveInjectionOne {
            function __construct($msg = "Hello") {
                $this->msg = $msg;
            }
        }');
        $this->factoryFix->givenTheClassDefinition('class RecursiveInjectionTwo {
            function __construct(RecursiveInjectionOne $one, $msg = " World") {
                $this->msg = $one->msg . $msg;
            }
        }');
        $this->factoryFix->givenTheClassDefinition('class RecursiveInjectionThree {
            function __construct(RecursiveInjectionTwo $two) {
                $this->msg = $two->msg;
            }
        }');
        $this->factoryFix->whenIGet_FromTheFactory('RecursiveInjectionThree');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testOptionalClassArgument() {
        $this->factoryFix->givenTheClassDefinition('class OptionalClassArgument {
            function __construct(\DateTime $date = null) {
                $this->date = $date;
            }
        }');
        $this->factoryFix->whenIGet_FromTheFactory('OptionalClassArgument');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('date', null);
    }

}