<?php
namespace spec\watoki\factory;

use watoki\scrut\Specification;

/**
 * @property FactoryFixture $fix <-
 */
class ConstructorInjectionTest extends Specification {

    public function testEmptyConstructor() {
        $this->fix->givenTheClassDefinition('class SomeClass {}');
        $this->fix->whenIGet_FromTheFactory('SomeClass');
        $this->fix->thenTheObjectShouldBeAnInstanceOf('SomeClass');
    }

    public function testConstructorArguments() {
        $this->fix->givenTheClassDefinition('class ClassWithConstructor {
            function __construct($arg1, $arg2) {
                $this->msg = $arg1 . $arg2;
            }
        }');
        $this->fix->whenIGet_WithArguments_FromTheFactory('ClassWithConstructor', array('arg2' => ' World', 'arg1' => 'Hello'));

        $this->fix->thenTheObjectShouldBeAnInstanceOf('ClassWithConstructor');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testConstructorArgumentsInList() {
        $this->fix->givenTheClassDefinition('class ClassWithConstructorInList {
            function __construct($arg1, $arg2) {
                $this->msg = $arg1 . $arg2;
            }
        }');

        $this->fix->whenIGet_WithArguments_FromTheFactory('ClassWithConstructorInList', array('Hello', ' You'));

        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello You');
    }

    public function testConstructorArgumentsMixed() {
        $this->fix->givenTheClassDefinition('class ClassWithConstructorMixed {
            function __construct($arg1, $arg2) {
                $this->msg = $arg1 . $arg2;
            }
        }');

        $this->fix->whenIGet_WithArguments_FromTheFactory('ClassWithConstructorMixed', array('arg2' => ' World', 0 => 'Hello'));

        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testDefaultArguments() {
        $this->fix->givenTheClassDefinition('class DefaultArguments {
            function __construct($argRequired, $argDefault = " World") {
                $this->msg = $argRequired . $argDefault;
            }
        }');
        $this->fix->whenIGet_WithArguments_FromTheFactory('DefaultArguments', array('argRequired' => 'Hello'));
        $this->fix->thenTheObjectShouldBeAnInstanceOf('DefaultArguments');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testDontInjectDefaultArgument() {
        $this->fix->givenTheClassDefinition('class DontInjectDefaultArgument {
            function __construct(StdClass $dont = null) {
                $this->dont = $dont;
            }
        }');
        $this->fix->whenIGet_FromTheFactory('DontInjectDefaultArgument');
        $this->fix->thenTheObjectShouldBeAnInstanceOf('DontInjectDefaultArgument');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('dont', null);
    }

    public function testMissingArguments() {
        $this->fix->givenTheClassDefinition('class MissingArgument {
            function __construct($arg1, $arg2) {
                $this->msg = $arg1 . $arg2;
            }
        }');
        $this->fix->whenITryToGet_WithArguments_FromTheFactory('MissingArgument', array('arg2' => 'Not enough'));
        $this->fix->thenAnExceptionShouldBeThrown();
    }

    public function testInjectArgumentsByFactory() {
        $this->fix->givenTheClassDefinition('class InjectMe {
            function __construct($msg = "Hello World") {
                $this->greeting = $msg;
            }
        }');
        $this->fix->givenTheClassDefinition('class InjectingOne {
            function __construct(InjectMe $arg1) {
                $this->msg = $arg1->greeting;
            }
        }');
        $this->fix->whenIGet_FromTheFactory('InjectingOne');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testMixGivenAndInjectedArguments() {
        $this->fix->givenTheClassDefinition('class InjectMeToo {
            function __construct($msg = "Hello") {
                $this->greeting = $msg;
            }
        }');
        $this->fix->givenTheClassDefinition('class InjectingTwo {
            function __construct(InjectMeToo $arg1, $arg2, $arg3 = "!") {
                $this->msg = $arg1->greeting . $arg2 . $arg3;
            }
        }');
        $this->fix->whenIGet_WithArguments_FromTheFactory('InjectingTwo', array('arg2' => ' World'));
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World!');
    }

    public function testInjectFactory() {
        $this->fix->givenTheClassDefinition('class InjectFactory {
            function __construct(\watoki\factory\Factory $factory) {
                $this->factory = $factory;
            }
        }');
        $this->fix->whenIGet_FromTheFactory('InjectFactory');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBeTheFactory('factory');
    }

    public function testRecursiveInjection() {
        $this->fix->givenTheClassDefinition('class RecursiveInjectionOne {
            function __construct($msg = "Hello") {
                $this->msg = $msg;
            }
        }');
        $this->fix->givenTheClassDefinition('class RecursiveInjectionTwo {
            function __construct(RecursiveInjectionOne $one, $msg = " World") {
                $this->msg = $one->msg . $msg;
            }
        }');
        $this->fix->givenTheClassDefinition('class RecursiveInjectionThree {
            function __construct(RecursiveInjectionTwo $two) {
                $this->msg = $two->msg;
            }
        }');
        $this->fix->whenIGet_FromTheFactory('RecursiveInjectionThree');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testOptionalClassArgument() {
        $this->fix->givenTheClassDefinition('class OptionalClassArgument {
            function __construct(\DateTime $date = null) {
                $this->date = $date;
            }
        }');
        $this->fix->whenIGet_FromTheFactory('OptionalClassArgument');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('date', null);
    }

    public function testDocCommentTypeHints() {
        $this->fix->givenTheClassDefinition('class DocCommentHints {
            /**
             * @param StdClass $one
             * @param DateTime $two
             * @param StdClass $three
             * @param object $four
             */
            function __construct($one, StdClass $two, $three = "foo", $four = "bar") {
                $this->one = $one;
                $this->two = $two;
                $this->three = $three;
                $this->four = $four;
            }
        }');
        $this->fix->whenIGet_FromTheFactory('DocCommentHints');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBeAnInstanceOf('one', 'StdClass');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBeAnInstanceOf('two', 'StdClass');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('three', 'foo');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('four', 'bar');
    }

    public function testRelativeTypeHints() {
        $this->fix->givenTheClassDefinition('namespace one\two; class RelativeDependency {}');
        $this->fix->givenTheClassDefinition('
        namespace one;
        class RelativeTypeHints {
            /**
             * @param two\RelativeDependency $one
             */
            function __construct($one) {
                $this->one = $one;
            }
        }');
        $this->fix->whenIGet_FromTheFactory('one\RelativeTypeHints');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBeAnInstanceOf('one', 'one\two\RelativeDependency');
    }

    public function testAbstractDependency() {
        $this->fix->givenTheClassDefinition('abstract class AbstractDependency {}');
        $this->fix->givenTheClassDefinition('class HasAbstractDependency {
            function __construct(AbstractDependency $itsAbstract) {}
        }');
        $this->fix->whenITryToGet_FromTheFactory('HasAbstractDependency');
        $this->fix->thenAnExceptionShouldBeThrown();
    }

    public function testInterfaceDependency() {
        $this->fix->givenTheClassDefinition('interface InterfaceDependency {}');
        $this->fix->givenTheClassDefinition('class HasInterfaceDependency {
            function __construct(InterfaceDependency $itsAbstract) {}
        }');
        $this->fix->whenITryToGet_FromTheFactory('HasInterfaceDependency');
        $this->fix->thenAnExceptionShouldBeThrown();
    }

}