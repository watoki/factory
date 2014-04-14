<?php
namespace spec\watoki\factory;

use watoki\factory\Factory;
use watoki\scrut\Fixture;
use watoki\scrut\Specification;

class FactoryFixture extends Fixture {

    public $caught;

    public $instance;

    public $instance2;

    static $loaded = array();

    /** @var Factory */
    public $factory;

    private $counter = 0;

    public function __construct(Specification $spec, Factory $factory) {
        parent::__construct($spec, $factory);
        self::$loaded = array();
        $this->factory = new Factory();
    }

    public function givenTheClass_InTheNamespace($className, $namespace) {
        $this->givenTheClassDefinition("namespace $namespace; class $className {}");
    }

    public function givenTheClass_WithTheDocComment($className, $docComment) {
        $this->givenTheClassDefinition("
            $docComment
            class $className {}
        ");
    }

    public function givenTheClass_InTheNameSpace_WithTheDocComment($className, $nameSpace, $docComment) {
        $this->givenTheClassDefinition("
            namespace $nameSpace;

            $docComment
            class $className {}
        ");
    }

    public function givenTheClassDefinition($definition) {
        $dir = __DIR__ . '/tmp';
        $file = $dir . '/' . $this->spec->getName() . $this->counter++ . '.php';
        @mkdir($dir);
        file_put_contents($file, "<?php {$definition}");
        /** @noinspection PhpIncludeInspection */
        include $file;

        $this->spec->undos[] = function () use ($dir, $file) {
            @unlink($file);
            @rmdir($dir);
        };
    }

    public function whenIGet_FromTheFactory($className) {
        $this->instance = $this->factory->getInstance($className);
    }

    public function whenIGet_WithArguments_FromTheFactory($className, $args) {
        $this->instance = $this->factory->getInstance($className, $args);
    }

    public function whenITryToGet_WithArguments_FromTheFactory($className, $args) {
        try {
            $this->whenIGet_WithArguments_FromTheFactory($className, $args);
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }

    public function whenITryToGet_FromTheFactory($className) {
        try {
            $this->whenIGet_FromTheFactory($className);
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }

    public function whenITryToGetTheSingleton($className) {
        try {
            $this->whenIGetTheSingleton($className);
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }

    public function whenIGet_FromTheFactoryAgain($className) {
        $this->instance2 = $this->factory->getInstance($className);
    }

    public function thenAnExceptionShouldBeThrown() {
        $this->spec->assertNotNull($this->caught);
    }

    public function whenIGetTheSingleton($className) {
        $this->instance = $this->factory->getSingleton($className);
    }

    public function thenBothInstancesShouldBeTheSameObject() {
        $this->spec->assertTrue($this->instance === $this->instance2);
    }

    public function thenTheObjectShouldBeAnInstanceOf($className) {
        $this->spec->assertInstanceOf($className, $this->instance);
        $this->spec->assertEquals($className, get_class($this->instance));
    }

    public function thenTheTheProperty_OfTheObjectShouldBe($prop, $value) {
        $this->spec->assertEquals($value, $this->instance->$prop);
    }

    public function thenTheTheProperty_OfTheObjectShouldBeAnInstanceOf($prop, $class) {
        $this->spec->assertInstanceOf($class, @$this->instance->$prop);
    }

    public function theTheProperty_ShouldBeAnInstanceOf($propertyName, $class) {
        $reflection = new \ReflectionProperty(get_class($this->instance), $propertyName);
        $reflection->setAccessible(true);
        $this->spec->assertInstanceOf($class, $reflection->getValue($this->instance));
    }

    public function thenTheShouldBeNoProperty($propertyName) {
        $this->spec->assertFalse(isset($this->instance->$propertyName));
    }

    public function thenTheLoadedDependency_ShouldBe($int, $class) {
        $this->spec->assertEquals($class, self::$loaded[$int - 1]);
    }

    public function thenThereShouldBeAProperty_WithAnInstanceOf($propertyName, $class) {
        $this->spec->assertInstanceOf($class, $this->instance->$propertyName);
    }

    public function thenTheTheProperty_OfTheObjectShouldBeTheFactory($prop) {
        $this->spec->assertTrue($this->factory === $this->instance->$prop);
    }

}