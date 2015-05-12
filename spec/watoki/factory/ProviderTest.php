<?php
namespace spec\watoki\factory;

use watoki\scrut\Specification;

/**
 * Providers can be used to create instances of classes on-demand.
 *
 * @property \spec\watoki\factory\FactoryFixture $fix <-
 */
class ProviderTest extends Specification {

    public function testFindProviderDirectly() {
        $this->fix->givenTheClassDefinition('class MyClass {}');
        $this->givenTheProvider_Providing('SingleMindedProvider', 'return json_decode(\'{"nothing":"just this"}\');');
        $this->givenIHaveSet_ToProvideFor('SingleMindedProvider', 'MyClass');

        $this->fix->whenIGet_FromTheFactory('MyClass');

        $this->fix->thenTheObjectShouldBeAnInstanceOf('stdClass');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('nothing', 'just this');
    }

    public function testFindBaseProvider() {
        $this->fix->givenTheClassDefinition('
            class BaseClass {}
        ');
        $this->fix->givenTheClassDefinition('
            class SubClass extends BaseClass {}
        ');

        $this->givenTheProvider_Providing('BaseProvider', '$o = new $class; $o->provided = "yes"; return $o;');
        $this->givenIHaveSet_ToProvideFor('BaseProvider', 'BaseClass');

        $this->fix->whenIGet_FromTheFactory('SubClass');

        $this->fix->thenTheObjectShouldBeAnInstanceOf('SubClass');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('provided', 'yes');
    }

    public function testFindProviderForInterface() {
        $this->fix->givenTheClassDefinition('
            interface BaseInterface {}
            interface SomeInterface {}
            interface SomeOtherInterface extends BaseInterface {}
            class SomeImplementation implements SomeInterface, SomeOtherInterface {}
        ');

        $this->givenTheProvider_Providing('InterfaceProvider', 'return new \DateTime;');
        $this->givenIHaveSet_ToProvideFor('InterfaceProvider', 'BaseInterface');

        $this->fix->whenIGet_FromTheFactory('SomeImplementation');

        $this->fix->thenTheObjectShouldBeAnInstanceOf('DateTime');
    }

    public function testFindSpecificProviderFirst() {
        $this->fix->givenTheClassDefinition('
            class Base2Class {}
        ');
        $this->fix->givenTheClassDefinition('
            class Sub2Class extends BaseClass {}
        ');

        $this->givenTheProvider_Providing('LastProvider', '$o = new $class; $o->provided = "last"; return $o;');
        $this->givenIHaveSet_ToProvideFor('LastProvider', 'Base2Class');

        $this->givenTheProvider_Providing('FirstProvider', '$o = new $class; $o->provided = "first"; return $o;');
        $this->givenIHaveSet_ToProvideFor('FirstProvider', 'Sub2Class');

        $this->fix->whenIGet_FromTheFactory('Sub2Class');

        $this->fix->thenTheObjectShouldBeAnInstanceOf('Sub2Class');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('provided', 'first');
    }

    public function testNormalizeClassName() {
        $this->fix->givenTheClassDefinition('namespace My; class FooClass {}');
        $this->givenTheProvider_Providing('JustAProvider', 'return json_decode(\'{"nothing":"just this"}\');');
        $this->givenIHaveSet_ToProvideFor('JustAProvider', '\My\FooClass');

        $this->fix->whenIGet_FromTheFactory('my\fooclass');

        $this->fix->thenTheObjectShouldBeAnInstanceOf('stdClass');
        $this->fix->thenTheTheProperty_OfTheObjectShouldBe('nothing', 'just this');
    }

    private function givenTheProvider_Providing($providerName, $statement) {
        $this->fix->givenTheClassDefinition("
            class $providerName implements \\watoki\\factory\\Provider {
                public function provide(\$class, array \$args = array()) {
                    $statement
                }
            }
        ");
    }

    private function givenIHaveSet_ToProvideFor($provider, $class) {
        $this->fix->factory->setProvider($class, new $provider);
    }

}