<?php
namespace spec\watoki\factory;

use watoki\scrut\Specification;

/**
 * @property FactoryFixture $factoryFix <-
 */
class PropertyAnnotationInjectionTest extends Specification {

    public function testFullyQualifiedClassNames() {
        $this->factoryFix->givenTheClass_InTheNamespace('FullNameDependency', 'some\name\space');
        $this->factoryFix->givenTheClass_WithTheDocComment('FullName', '
            /**
             * @property some\name\space\FullNameDependency foo <-
             */
        ');

        $this->factoryFix->whenIGet_FromTheFactory('FullName');

        $this->factoryFix->thenThereShouldBeAProperty_WithAnInstanceOf('foo', 'some\name\space\FullNameDependency');
    }

    public function testRelativeNamespace() {
        $this->factoryFix->givenTheClass_InTheNamespace('RelativeDependency', 'some\name\space');
        $this->factoryFix->givenTheClass_InTheNameSpace_WithTheDocComment('Relative', 'some\name', '
            /**
             * @property space\RelativeDependency foo <-
             */
        ');

        $this->factoryFix->whenIGet_FromTheFactory('some\name\Relative');

        $this->factoryFix->thenThereShouldBeAProperty_WithAnInstanceOf('foo', 'some\name\space\RelativeDependency');
    }

    public function testClassAliases() {
        $this->factoryFix->givenTheClass_InTheNamespace('AliasedDependency', 'some\name\space');
        $this->factoryFix->givenTheClass_WithTheDocComment('Aliased', '
            use some\name\space\AliasedDependency;

            /**
             * @property AliasedDependency foo <-
             */
        ');

        $this->factoryFix->whenIGet_FromTheFactory('Aliased');

        $this->factoryFix->thenThereShouldBeAProperty_WithAnInstanceOf('foo', 'some\name\space\AliasedDependency');
    }

    public function testWhitespaces() {
        $this->factoryFix->givenTheClass_WithTheDocComment('Whitespaces', '
            /**
             * @property        StdClass    tabs    <-
             * @property    StdClass    spaces   <-
             */
        ');

        $this->factoryFix->whenIGet_FromTheFactory('Whitespaces');

        $this->factoryFix->thenThereShouldBeAProperty_WithAnInstanceOf('tabs', 'StdClass');
        $this->factoryFix->thenThereShouldBeAProperty_WithAnInstanceOf('spaces', 'StdClass');
    }

    public function testDontInjectNotMarkedProperties() {
        $this->factoryFix->givenTheClass_WithTheDocComment('NotMarked', '
            /**
             * @property StdClass not
             * @property StdClass marked <-
             */
        ');

        $this->factoryFix->whenIGet_FromTheFactory('NotMarked');

        $this->factoryFix->thenThereShouldBeAProperty_WithAnInstanceOf('marked', 'StdClass');
        $this->factoryFix->thenTheShouldBeNoProperty('not');
    }

    public function testInjectPropertyWithDollarSign() {
        $this->factoryFix->givenTheClass_WithTheDocComment('DollarSign', '
            /**
             * @property StdClass $foo <-
             */
        ');

        $this->factoryFix->whenIGet_FromTheFactory('DollarSign');

        $this->factoryFix->thenThereShouldBeAProperty_WithAnInstanceOf('foo', 'StdClass');
    }

    public function testInjectProtectedAndPrivateProperty() {
        $this->factoryFix->givenTheClassDefinition('
            /**
             * @property StdClass protected <-
             * @property StdClass private <-
             */
            class ProtectedAndPrivate {
                protected $protected;
                private $private;
            }
        ');

        $this->factoryFix->whenIGet_FromTheFactory('ProtectedAndPrivate');

        $this->factoryFix->theTheProperty_ShouldBeAnInstanceOf('private', 'StdClass');
        $this->factoryFix->theTheProperty_ShouldBeAnInstanceOf('protected', 'StdClass');
    }

    public function testOrder() {
        $this->factoryFix->givenTheClassDefinition('
            class First {
                function __construct() {
                    \spec\watoki\factory\FactoryFixture::$loaded[] = get_class($this);
                }
            }
        ');
        $this->factoryFix->givenTheClassDefinition('
            class Second {
                function __construct() {
                    \spec\watoki\factory\FactoryFixture::$loaded[] = get_class($this);
                }
            }
        ');
        $this->factoryFix->givenTheClass_WithTheDocComment('OrderMatters', '
            /**
             * @property First foo <-
             * @property Second bar <-
             */
        ');

        $this->factoryFix->whenIGet_FromTheFactory('OrderMatters');

        $this->factoryFix->thenTheLoadedDependency_ShouldBe(1, 'First');
        $this->factoryFix->thenTheLoadedDependency_ShouldBe(2, 'Second');
    }

    public function testCreateMembers() {
        $this->factoryFix->givenTheClassDefinition('
            /**
             * @property StdClass foo <-
             */
            class BaseAnnotationClass {}
        ');
        $this->factoryFix->givenTheClassDefinition('
            /**
             * @property StdClass bar <-
             */
            class ChildAnnotationClass extends BaseAnnotationClass {}
        ');

        $this->factoryFix->whenIGet_FromTheFactory('ChildAnnotationClass');

        $this->factoryFix->thenThereShouldBeAProperty_WithAnInstanceOf('bar', 'StdClass');
        $this->factoryFix->thenThereShouldBeAProperty_WithAnInstanceOf('foo', 'StdClass');
    }
}