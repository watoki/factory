<?php
namespace spec\watoki\factory;

use watoki\factory\exception\InjectionException;
use watoki\factory\providers\DefaultProvider;
use watoki\scrut\Specification;

/**
 * As injectable marked properties of a class are injected when it's instantiated.
 *
 * @property FactoryFixture $fix <-
 */
class PropertyInjectionTest extends Specification {

    public function testInjectPublicProperty() {
        $this->fix->givenTheClassDefinition('
            class PublicProperty {
                /**
                 * @var StdClass <-
                 */
                protected $foo;

                /**
                 * @var StdClass <-
                 */
                private $bar;
            }
        ');

        $this->fix->whenIGet_FromTheFactory('PublicProperty');

        $this->fix->theTheProperty_ShouldBeAnInstanceOf('foo', 'StdClass');
        $this->fix->theTheProperty_ShouldBeAnInstanceOf('bar', 'StdClass');
    }

    public function testProtectedAndPrivateProperties() {
        $this->fix->givenTheClassDefinition('
            class ProtectedAndPrivateProperty {
                /**
                 * @var StdClass <-
                 */
                public $foo;
            }
        ');

        $this->fix->whenIGet_FromTheFactory('ProtectedAndPrivateProperty');

        $this->fix->theTheProperty_ShouldBeAnInstanceOf('foo', 'StdClass');
        $this->fix->theTheProperty_ShouldBeAnInstanceOf('foo', 'StdClass');
    }

    public function testAnnotationInsteadOfMarker() {
        $this->fix->givenTheClassDefinition('
            class AnnotatedProperty {
                /**
                 * @inject
                 * @var StdClass
                 */
                public $foo;

                /**
                 * @var StdClass
                 */
                public $bar;
            }
        ');
        $this->givenOnlyPropertiesWithTheAnnotation_ShouldBeInjected('@inject');

        $this->fix->whenIGet_FromTheFactory('AnnotatedProperty');

        $this->fix->theTheProperty_ShouldBeAnInstanceOf('foo', 'StdClass');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('bar', null);
    }

    public function testDontInjectPropertiesWithValues() {
        $this->fix->givenTheClassDefinition('
            class PropertyWithValue {
                /** @var StdClass <- */
                public $foo = "not null";

                /** @var StdClass <- */
                public $bar;
            }
        ');
        $this->fix->whenIGet_FromTheFactory('PropertyWithValue');

        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('foo', 'not null');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBeAnInstanceOf('bar', 'StdClass');
    }

    public function testInParentAliasedTypeHint() {
        $this->fix->givenTheClassDefinition('namespace here; class MyAliasedClass {}');
        $this->fix->givenTheClassDefinition('use here\MyAliasedClass; class AliasingParent {
            /** @var MyAliasedClass <- */
            public $foo;
        }');
        $this->fix->givenTheClassDefinition('class AliasingSubClass extends AliasingParent {}');
        $this->fix->whenIGet_FromTheFactory('AliasingSubClass');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBeAnInstanceOf('foo', 'here\MyAliasedClass');
    }

    public function testInvalidPropertyInjection() {
        $this->fix->givenTheClassDefinition('class InvalidPropertyInjection {
            /** @var NonExistentClass <- */
            public $foo;
        }');

        $this->fix->whenITryToGet_FromTheFactory('InvalidPropertyInjection');
        $this->fix->thenA_ShouldBeThrown(InjectionException::$CLASS);
        $this->fix->thenTheExceptionMessageShouldContain('Error while injecting dependency [foo] of [InvalidPropertyInjection]: Could not find [NonExistentClass].');
    }

    public function testCascadingInjectionErrors() {
        $this->fix->givenTheClass_WithTheDocComment('CascadingAnnotationInjection', '
        /**
         * @property NotExisting $baz <-
         */');
        $this->fix->givenTheClassDefinition('class CascadingConstructorInjection {
            /** @param $bar <- */
            function __construct(CascadingAnnotationInjection $bar) {}
        }');
        $this->fix->givenTheClassDefinition('class CascadingPropertyInjection {
            /** @var CascadingConstructorInjection <- */
            public $foo;
        }');

        $this->fix->whenITryToGet_FromTheFactory('CascadingPropertyInjection');
        $this->fix->thenA_ShouldBeThrown(InjectionException::$CLASS);
        $this->fix->thenTheExceptionMessageShouldContain(
            'Error while injecting dependency [foo] of [CascadingPropertyInjection]: ' .
            'Error while injecting constructor of [CascadingConstructorInjection]: ' .
            'Cannot inject method [CascadingConstructorInjection::__construct]: ' .
            'Cannot fill parameter [bar] of [CascadingConstructorInjection::__construct]: ' .
            'Error while injecting dependency [baz] of [CascadingAnnotationInjection]: ' .
            'Could not find [NotExisting].');
    }

    /** @var DefaultProvider */
    private $provider;

    private function givenOnlyPropertiesWithTheAnnotation_ShouldBeInjected($annotation) {
        $this->provider = new DefaultProvider($this->fix->factory);
        $this->fix->factory->setProvider('StdClass', $this->provider);
        $this->provider->setPropertyFilter(function (\ReflectionProperty $property) use ($annotation) {
            return strpos($property->getDocComment(), $annotation) !== false;
        });
    }

}