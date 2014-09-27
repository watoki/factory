<?php
namespace spec\watoki\factory;

use watoki\scrut\Specification;

/**
 * Annotation properties are injected like regular properties.
 *
 * @property FactoryFixture $fix <-
 */
class PropertyAnnotationInjectionTest extends Specification {

    public function testFullyQualifiedClassNames() {
        $this->fix->givenTheClass_InTheNamespace('FullNameDependency', 'some\name\space');
        $this->fix->givenTheClass_WithTheDocComment('FullName', '
            /**
             * @property some\name\space\FullNameDependency foo <-
             */
        ');

        $this->fix->whenIGet_FromTheFactory('FullName');

        $this->fix->thenThereShouldBeAProperty_WithAnInstanceOf('foo', 'some\name\space\FullNameDependency');
    }

    public function testRelativeNamespace() {
        $this->fix->givenTheClass_InTheNamespace('RelativeDependency', 'some\name\space');
        $this->fix->givenTheClass_InTheNameSpace_WithTheDocComment('Relative', 'some\name', '
            /**
             * @property space\RelativeDependency foo <-
             */
        ');

        $this->fix->whenIGet_FromTheFactory('some\name\Relative');

        $this->fix->thenThereShouldBeAProperty_WithAnInstanceOf('foo', 'some\name\space\RelativeDependency');
    }

    public function testClassAliases() {
        $this->fix->givenTheClass_InTheNamespace('AliasedDependency', 'some\name\space');
        $this->fix->givenTheClass_WithTheDocComment('Aliased', '
            use some\name\space\AliasedDependency;

            /**
             * @property AliasedDependency foo <-
             */
        ');

        $this->fix->whenIGet_FromTheFactory('Aliased');

        $this->fix->thenThereShouldBeAProperty_WithAnInstanceOf('foo', 'some\name\space\AliasedDependency');
    }

    public function testWhitespaces() {
        $this->fix->givenTheClass_WithTheDocComment('Whitespaces', '
            /**
             * @property        StdClass    tabs    <-
             * @property    StdClass    spaces   <-
             */
        ');

        $this->fix->whenIGet_FromTheFactory('Whitespaces');

        $this->fix->thenThereShouldBeAProperty_WithAnInstanceOf('tabs', 'StdClass');
        $this->fix->thenThereShouldBeAProperty_WithAnInstanceOf('spaces', 'StdClass');
    }

    public function testDontInjectNotMarkedProperties() {
        $this->fix->givenTheClass_WithTheDocComment('NotMarked', '
            /**
             * @property StdClass not
             * @property StdClass marked <-
             */
        ');

        $this->fix->whenIGet_FromTheFactory('NotMarked');

        $this->fix->thenThereShouldBeAProperty_WithAnInstanceOf('marked', 'StdClass');
        $this->fix->thenTheShouldBeNoProperty('not');
    }

    public function testInjectPropertyWithDollarSign() {
        $this->fix->givenTheClass_WithTheDocComment('DollarSign', '
            /**
             * @property StdClass $foo <-
             */
        ');

        $this->fix->whenIGet_FromTheFactory('DollarSign');

        $this->fix->thenThereShouldBeAProperty_WithAnInstanceOf('foo', 'StdClass');
    }

    public function testInjectProtectedAndPrivateProperty() {
        $this->fix->givenTheClassDefinition('
            /**
             * @property StdClass protected <-
             * @property StdClass private <-
             */
            class ProtectedAndPrivate {
                protected $protected;
                private $private;
            }
        ');

        $this->fix->whenIGet_FromTheFactory('ProtectedAndPrivate');

        $this->fix->theTheProperty_ShouldBeAnInstanceOf('private', 'StdClass');
        $this->fix->theTheProperty_ShouldBeAnInstanceOf('protected', 'StdClass');
    }

    public function testOrder() {
        $this->fix->givenTheClassDefinition('
            class First {
                function __construct() {
                    \spec\watoki\factory\FactoryFixture::$loaded[] = get_class($this);
                }
            }
        ');
        $this->fix->givenTheClassDefinition('
            class Second {
                function __construct() {
                    \spec\watoki\factory\FactoryFixture::$loaded[] = get_class($this);
                }
            }
        ');
        $this->fix->givenTheClass_WithTheDocComment('OrderMatters', '
            /**
             * @property First foo <-
             * @property Second bar <-
             */
        ');

        $this->fix->whenIGet_FromTheFactory('OrderMatters');

        $this->fix->thenTheLoadedDependency_ShouldBe(1, 'First');
        $this->fix->thenTheLoadedDependency_ShouldBe(2, 'Second');
    }

    public function testCreateMembers() {
        $this->fix->givenTheClassDefinition('
            /**
             * @property StdClass foo <-
             */
            class BaseAnnotationClass {}
        ');
        $this->fix->givenTheClassDefinition('
            /**
             * @property StdClass bar <-
             */
            class ChildAnnotationClass extends BaseAnnotationClass {}
        ');

        $this->fix->whenIGet_FromTheFactory('ChildAnnotationClass');

        $this->fix->thenThereShouldBeAProperty_WithAnInstanceOf('bar', 'StdClass');
        $this->fix->thenThereShouldBeAProperty_WithAnInstanceOf('foo', 'StdClass');
    }

    public function testInheritPropertyAnnotation() {
        $this->fix->givenTheClass_InTheNamespace('BaseClassDependency', 'some\name\space');
        $this->fix->givenTheClass_WithTheDocComment('BaseWithInjectedProperty', '
            /**
             * @property some\name\space\BaseClassDependency foo <-
             */
        ');
        $this->fix->givenTheClassDefinition('
            class InheritsProperty extends BaseWithInjectedProperty {}
        ');

        $this->fix->whenIGet_FromTheFactory('InheritsProperty');
        $this->fix->thenThereShouldBeAProperty_WithAnInstanceOf('foo', 'some\name\space\BaseClassDependency');
    }
}