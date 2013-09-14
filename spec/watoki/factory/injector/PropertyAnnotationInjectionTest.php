<?php
namespace spec\watoki\factory\injector;

use watoki\scrut\Specification;

/**
 * @property InjectorFixture injector <-
 */
class PropertyAnnotationInjectionTest extends Specification {

    public function testFullyQualifiedClassNames() {
        $this->injector->givenTheClass_InTheNamespace('FullNameDependency', 'some\name\space');
        $this->injector->givenTheClass_WithTheDocComment('FullName', '
            /**
             * @property some\name\space\FullNameDependency foo <-
             */
        ');

        $this->injector->whenIInjectTheProperties();

        $this->injector->thenThereShouldBeAProperty_WithAnInstanceOf('foo', 'some\name\space\FullNameDependency');
    }

    public function testRelativeNamespace() {
        $this->injector->givenTheClass_InTheNamespace('RelativeDependency', 'some\name\space');
        $this->injector->givenTheClass_InTheNameSpace_WithTheDocComment('Relative', 'some\name', '
            /**
             * @property space\RelativeDependency foo <-
             */
        ');

        $this->injector->whenIInjectTheProperties();

        $this->injector->thenThereShouldBeAProperty_WithAnInstanceOf('foo', 'some\name\space\RelativeDependency');
    }

    public function testClassAliases() {
        $this->injector->givenTheClass_InTheNamespace('AliasedDependency', 'some\name\space');
        $this->injector->givenTheClass_WithTheDocComment('Aliased', '
            use some\name\space\AliasedDependency;

            /**
             * @property AliasedDependency foo <-
             */
        ');

        $this->injector->whenIInjectTheProperties();

        $this->injector->thenThereShouldBeAProperty_WithAnInstanceOf('foo', 'some\name\space\AliasedDependency');
    }

    public function testWhitespaces() {
        $this->injector->givenTheClass_WithTheDocComment('Whitespaces', '
            /**
             * @property        StdClass    tabs    <-
             * @property    StdClass    spaces   <-
             * @property StdClass noSpace<-
             */
        ');

        $this->injector->whenIInjectTheProperties();

        $this->injector->thenThereShouldBeAProperty_WithAnInstanceOf('tabs', 'StdClass');
        $this->injector->thenThereShouldBeAProperty_WithAnInstanceOf('spaces', 'StdClass');
        $this->injector->thenThereShouldBeAProperty_WithAnInstanceOf('noSpace', 'StdClass');
    }

    public function testDontInjectNotMarkedProperties() {
        $this->injector->givenTheClass_WithTheDocComment('NotMarked', '
            /**
             * @property StdClass not
             * @property StdClass marked <-
             */
        ');

        $this->injector->whenIInjectTheProperties();

        $this->injector->thenThereShouldBeAProperty_WithAnInstanceOf('marked', 'StdClass');
        $this->injector->thenTheShouldBeNoProperty('not');
    }

    public function testInjectPropertyWithDollarSign() {
        $this->injector->givenTheClass_WithTheDocComment('DollarSign', '
            /**
             * @property StdClass $foo <-
             */
        ');

        $this->injector->whenIInjectTheProperties();

        $this->injector->thenThereShouldBeAProperty_WithAnInstanceOf('foo', 'StdClass');
    }

    public function testInjectProtectedAndPrivateProperty() {
        $this->injector->givenTheClassDefinition_OfTheClass('
            /**
             * @property StdClass protected <-
             * @property StdClass private <-
             */
            class ProtectedAndPrivate {
                protected $protected;
                private $private;
            }
        ', 'ProtectedAndPrivate');

        $this->injector->whenIInjectTheProperties();

        $this->injector->theTheProperty_ShouldBeAnInstanceOf('private', 'StdClass');
        $this->injector->theTheProperty_ShouldBeAnInstanceOf('protected', 'StdClass');
    }

    public function testOrder() {
        $this->injector->givenTheClassDefinition_OfTheClass('
            class First {
                function __construct() {
                    \spec\watoki\factory\injector\InjectorFixture::$loaded[] = get_class($this);
                }
            }
        ', 'First');
        $this->injector->givenTheClassDefinition_OfTheClass('
            class Second {
                function __construct() {
                    \spec\watoki\factory\injector\InjectorFixture::$loaded[] = get_class($this);
                }
            }
        ', 'Second');
        $this->injector->givenTheClass_WithTheDocComment('OrderMatters', '
            /**
             * @property First foo <-
             * @property Second bar <-
             */
        ');

        $this->injector->whenIInjectTheProperties();

        $this->injector->thenTheLoadedDependency_ShouldBe(1, 'First');
        $this->injector->thenTheLoadedDependency_ShouldBe(2, 'Second');
    }
}