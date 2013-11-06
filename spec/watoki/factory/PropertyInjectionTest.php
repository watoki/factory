<?php
namespace spec\watoki\factory;

use watoki\factory\providers\DefaultProvider;
use watoki\scrut\Specification;

/**
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

    public function testMethodInjection() {
        $this->fix->givenTheClassDefinition('class MethodInjection {
            public function inject(StdClass $one) {
                $this->one = $one;
            }
        }');
        $this->fix->whenIGet_FromTheFactory('MethodInjection');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBeAnInstanceOf('one', 'StdClass');
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