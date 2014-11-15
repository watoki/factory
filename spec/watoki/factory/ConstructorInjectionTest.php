<?php
namespace spec\watoki\factory;

use watoki\factory\exception\InjectionException;
use watoki\scrut\Specification;

/**
 * As injectable marked parameters of a constructor (any method, actually) are injected if possible.
 *
 * @property FactoryFixture $fix <-
 */
class ConstructorInjectionTest extends Specification {

    protected function background() {
        $this->fix->givenTheClassDefinition('class StandardConstructor {
            /**
             * @param $arg1 <-
             * @param $arg2 <-
             */
            function __construct($arg1, $arg2) {
                $this->msg = $arg1 . $arg2;
            }
        }');
    }

    public function testEmptyConstructor() {
        $this->fix->givenTheClassDefinition('class SomeClass {}');

        $this->fix->whenIGet_FromTheFactory('SomeClass');
        $this->fix->thenTheObjectShouldBeAnInstanceOf('SomeClass');
    }

    public function testMarkedArguments() {
        $this->fix->whenIGet_WithArguments_FromTheFactory('StandardConstructor', array('arg2' => ' World', 'arg1' => 'Hello'));
        $this->fix->thenTheObjectShouldBeAnInstanceOf('StandardConstructor');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testArgumentsAsList() {
        $this->fix->whenIGet_WithArguments_FromTheFactory('StandardConstructor', array('Hello', ' You'));
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello You');
    }

    public function testArgumentsMixedIndexAndName() {
        $this->fix->whenIGet_WithArguments_FromTheFactory('StandardConstructor', array('arg2' => ' World', 0 => 'Hello'));
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

    public function testMissingArguments() {
        $this->fix->whenITryToGet_WithArguments_FromTheFactory('StandardConstructor', array('arg2' => 'Not enough'));
        $this->fix->thenAnExceptionShouldBeThrown();
        $this->fix->thenTheExceptionMessageShouldContain("Cannot inject method [StandardConstructor::__construct]");
        $this->fix->thenTheExceptionMessageShouldContain("Cannot fill parameter [arg1]");
        $this->fix->thenTheExceptionMessageShouldContain("Argument not given and no type hint found.");
    }

    public function testInjectArgumentsWithFactory() {
        $this->fix->givenTheClassDefinition('class InjectMe {
            function __construct($msg = "Hello World") {
                $this->greeting = $msg;
            }
        }');
        $this->fix->givenTheClassDefinition('class InjectingOne {
            /**
             * @param $arg1 <-
             */
            function __construct(InjectMe $arg1) {
                $this->msg = $arg1->greeting;
            }
        }');

        $this->fix->whenIGet_FromTheFactory('InjectingOne');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testOnlyInjectIfParameterIsMarked() {
        $this->fix->givenTheClassDefinition('class OnlyInjectIfParameterIsMarked {
            /**
             * @param $arg1 <-
             * @param $arg2 Marker can be <- anywhere
             * @param $arg3
             */
            function __construct(StdClass $arg1, StdClass $arg2, StdClass $arg3) {}
        }');

        $this->fix->whenITryToGet_FromTheFactory('OnlyInjectIfParameterIsMarked');
        $this->fix->thenAnExceptionShouldBeThrown();
        $this->fix->thenTheExceptionMessageShouldContain("Cannot fill parameter [arg3]");
        $this->fix->thenTheExceptionMessageShouldContain("Argument not given and not marked as injectable.");
    }

    public function testDoNotInjectDefaultArgument() {
        $this->fix->givenTheClassDefinition('class DontInjectDefaultArgument {
            /**
             * @param $dont <-
             */
            function __construct(StdClass $dont = null) {
                $this->dont = $dont;
            }
        }');

        $this->fix->whenIGet_FromTheFactory('DontInjectDefaultArgument');
        $this->fix->thenTheObjectShouldBeAnInstanceOf('DontInjectDefaultArgument');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('dont', null);
    }

    public function testMixGivenAndInjectedArguments() {
        $this->fix->givenTheClassDefinition('class InjectMeToo {
            function __construct($msg = "Hello") {
                $this->greeting = $msg;
            }
        }');
        $this->fix->givenTheClassDefinition('class InjectingTwo {
            /**
             * @param $arg1 <-
             */
            function __construct(InjectMeToo $arg1, $arg2, $arg3 = "!") {
                $this->msg = $arg1->greeting . $arg2 . $arg3;
            }
        }');

        $this->fix->whenIGet_WithArguments_FromTheFactory('InjectingTwo', array('arg2' => ' World'));
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World!');
    }

    public function testInjectFactory() {
        $this->fix->givenTheClassDefinition('class InjectFactory {
            /**
             * @param $factory <-
             */
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
            /**
             * @param $one <-
             */
            function __construct(RecursiveInjectionOne $one, $msg = " World") {
                $this->msg = $one->msg . $msg;
            }
        }');
        $this->fix->givenTheClassDefinition('class RecursiveInjectionThree {
            /**
             * @param $two <-
             */
            function __construct(RecursiveInjectionTwo $two) {
                $this->msg = $two->msg;
            }
        }');

        $this->fix->whenIGet_FromTheFactory('RecursiveInjectionThree');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('msg', 'Hello World');
    }

    public function testDocCommentTypeHints() {
        $this->fix->givenTheClassDefinition('class DocCommentHints {
            /**
             * @param StdClass $one <-
             * @param DateTime $two <-
             * @param StdClass $three <-
             * @param object $four <-
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
             * @param two\RelativeDependency $one <-
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
            /**
             * @param $itsAbstract <-
             */
            function __construct(AbstractDependency $itsAbstract) {}
        }');

        $this->fix->whenITryToGet_FromTheFactory('HasAbstractDependency');
        $this->fix->thenAnExceptionShouldBeThrown();
    }

    public function testInterfaceDependency() {
        $this->fix->givenTheClassDefinition('interface InterfaceDependency {}');
        $this->fix->givenTheClassDefinition('class HasInterfaceDependency {
            /**
             * @param $itsAbstract
             */
            function __construct(InterfaceDependency $itsAbstract) {}
        }');

        $this->fix->whenITryToGet_FromTheFactory('HasInterfaceDependency');
        $this->fix->thenAnExceptionShouldBeThrown();
    }

    public function testMethodInjection() {
        $this->fix->givenTheClassDefinition('class MethodInjection {
            /**
             * @param $one <-
             */
            public function inject(StdClass $one) {
                $this->one = $one;
            }
        }');

        $this->fix->whenIGet_FromTheFactory('MethodInjection');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBeAnInstanceOf('one', 'StdClass');
    }

    public function testInvalidConstructorInjection() {
        $this->fix->givenTheClassDefinition('class InvalidConstructorInjection {
            /**
             * @param NonExistentCLass $one <-
             */
            public function __construct($one) {}
        }');

        $this->fix->whenITryToGet_FromTheFactory('InvalidConstructorInjection');
        $this->fix->thenA_ShouldBeThrown(InjectionException::$CLASS);
        $this->fix->thenTheExceptionMessageShouldContain('Error while injecting constructor of [InvalidConstructorInjection]: ' .
            'Cannot inject method [InvalidConstructorInjection::__construct]: ' .
            'Cannot fill parameter [one] of [InvalidConstructorInjection::__construct]: ' .
            'Class NonExistentCLass does not exist');
    }

}