<?php
namespace spec\watoki\factory;

use watoki\factory\providers\PropertyInjectionProvider;
use watoki\scrut\Specification;

/**
 * @property FactoryFixture $factoryFix <-
 */
class PropertyInjectionTest extends Specification {

    protected function background() {
        $this->givenPropertyInjectionProviderIsTheDefaultProvider();
    }

    public function testInjectPublicProperty() {
        $this->factoryFix->givenTheClassDefinition('
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

        $this->factoryFix->whenIGet_FromTheFactory('PublicProperty');

        $this->factoryFix->theTheProperty_ShouldBeAnInstanceOf('foo', 'StdClass');
        $this->factoryFix->theTheProperty_ShouldBeAnInstanceOf('bar', 'StdClass');
    }

    public function testProtectedAndPrivateProperties() {
        $this->factoryFix->givenTheClassDefinition('
            class ProtectedAndPrivateProperty {
                /**
                 * @var StdClass <-
                 */
                public $foo;
            }
        ');

        $this->factoryFix->whenIGet_FromTheFactory('ProtectedAndPrivateProperty');

        $this->factoryFix->theTheProperty_ShouldBeAnInstanceOf('foo', 'StdClass');
        $this->factoryFix->theTheProperty_ShouldBeAnInstanceOf('foo', 'StdClass');
    }

    public function testAnnotationInsteadOfMarker() {
        $this->factoryFix->givenTheClassDefinition('
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

        $this->factoryFix->whenIGet_FromTheFactory('AnnotatedProperty');

        $this->factoryFix->theTheProperty_ShouldBeAnInstanceOf('foo', 'StdClass');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('bar', null);
    }

    /** @var PropertyInjectionProvider */
    private $provider;

    private function givenPropertyInjectionProviderIsTheDefaultProvider() {
        $this->provider = new PropertyInjectionProvider($this->factoryFix->factory);
        $this->factoryFix->factory->setProvider('stdClass', $this->provider);
    }

    private function givenOnlyPropertiesWithTheAnnotation_ShouldBeInjected($annotation) {
        $this->provider->setPropertyFilter(function (\ReflectionProperty $property) use ($annotation) {
            return strpos($property->getDocComment(), $annotation) !== false;
        });
    }

}