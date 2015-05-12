<?php
namespace spec\watoki\factory;

use watoki\scrut\Specification;

/**
 * Providers can be used to create instances of classes on-demand.
 *
 * @property \spec\watoki\factory\FactoryFixture factoryFix <-
 */
class ProviderTest extends Specification {

    public function testFindProviderDirectly() {
        $this->factoryFix->givenTheClassDefinition('class MyClass {}');
        $this->givenTheProvider_Providing('SingleMindedProvider', 'return json_decode(\'{"nothing":"just this"}\');');
        $this->givenIHaveSet_ToProvideFor('SingleMindedProvider', 'MyClass');

        $this->factoryFix->whenIGet_FromTheFactory('MyClass');

        $this->factoryFix->thenTheObjectShouldBeAnInstanceOf('stdClass');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('nothing', 'just this');
    }

    public function testFindBaseProvider() {
        $this->factoryFix->givenTheClassDefinition('
            class BaseClass {}
        ');
        $this->factoryFix->givenTheClassDefinition('
            class SubClass extends BaseClass {}
        ');

        $this->givenTheProvider_Providing('BaseProvider', '$o = new $class; $o->provided = "yes"; return $o;');
        $this->givenIHaveSet_ToProvideFor('BaseProvider', 'BaseClass');

        $this->factoryFix->whenIGet_FromTheFactory('SubClass');

        $this->factoryFix->thenTheObjectShouldBeAnInstanceOf('SubClass');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('provided', 'yes');
    }

    public function testFindProviderForInterface() {
        $this->factoryFix->givenTheClassDefinition('
            interface BaseInterface {}
            interface SomeInterface {}
            interface SomeOtherInterface extends BaseInterface {}
            class SomeImplementation implements SomeInterface, SomeOtherInterface {}
        ');

        $this->givenTheProvider_Providing('InterfaceProvider', 'return new \DateTime;');
        $this->givenIHaveSet_ToProvideFor('InterfaceProvider', 'BaseInterface');

        $this->factoryFix->whenIGet_FromTheFactory('SomeImplementation');

        $this->factoryFix->thenTheObjectShouldBeAnInstanceOf('DateTime');
    }

    public function testFindSpecificProviderFirst() {
        $this->factoryFix->givenTheClassDefinition('
            class Base2Class {}
        ');
        $this->factoryFix->givenTheClassDefinition('
            class Sub2Class extends BaseClass {}
        ');

        $this->givenTheProvider_Providing('LastProvider', '$o = new $class; $o->provided = "last"; return $o;');
        $this->givenIHaveSet_ToProvideFor('LastProvider', 'Base2Class');

        $this->givenTheProvider_Providing('FirstProvider', '$o = new $class; $o->provided = "first"; return $o;');
        $this->givenIHaveSet_ToProvideFor('FirstProvider', 'Sub2Class');

        $this->factoryFix->whenIGet_FromTheFactory('Sub2Class');

        $this->factoryFix->thenTheObjectShouldBeAnInstanceOf('Sub2Class');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('provided', 'first');
    }

    public function testNormalizeClassName() {
        $this->factoryFix->givenTheClassDefinition('namespace My; class FooClass {}');
        $this->givenTheProvider_Providing('JustAProvider', 'return json_decode(\'{"nothing":"just this"}\');');
        $this->givenIHaveSet_ToProvideFor('JustAProvider', '\My\FooClass');

        $this->factoryFix->whenIGet_FromTheFactory('my\fooclass');

        $this->factoryFix->thenTheObjectShouldBeAnInstanceOf('stdClass');
        $this->factoryFix->thenTheTheProperty_OfTheObjectShouldBe('nothing', 'just this');
    }

    private function givenTheProvider_Providing($providerName, $statement) {
        $this->factoryFix->givenTheClassDefinition("
            class $providerName implements \\watoki\\factory\\Provider {
                public function provide(\$class, array \$args = array()) {
                    $statement
                }
            }
        ");
    }

    private function givenIHaveSet_ToProvideFor($provider, $class) {
        $this->factoryFix->factory->setProvider($class, new $provider);
    }

}