<?php
namespace spec\watoki\factory;

use watoki\factory\Factory;
use watoki\factory\providers\DefaultProvider;
use watoki\scrut\Fixture;

class FactoryFixture extends Fixture {

    /** @var \Exception|null */
    public $caught;

    public $instance;

    public $instance2;

    static $loaded = array();

    /** @var Factory */
    public $factory;

    private $counter = 0;

    private static $alreadyDefined = array();

    private $tmpDir;

    public function setUp() {
        parent::setUp();
        self::$loaded = array();
        $this->factory = new Factory();
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid();
        @mkdir($this->tmpDir);

        $this->spec->undos[] = function () {
            @rmdir($this->tmpDir);
        };
    }

    public function givenIConfigureTheProviderFor_ToInjectAnyArgument($class) {
        $provider = new DefaultProvider($this->factory);
        $provider->setParameterFilter(function () {
            return true;
        });
        $this->factory->setProvider($class, $provider);
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
        $hash = md5($definition);
        if (in_array($hash, self::$alreadyDefined)) {
            return;
        }
        self::$alreadyDefined[] = $hash;
        $file = $this->tmpDir . DIRECTORY_SEPARATOR . $this->spec->getName() . $this->counter++ . '.php';
        file_put_contents($file, "<?php {$definition}");
        /** @noinspection PhpIncludeInspection */
        include $file;

        $this->spec->undos[] = function () use ($file) {
            @unlink($file);
            @rmdir($this->tmpDir);
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

    public function whenIGet_FromTheFactoryAgain($className) {
        $this->instance2 = $this->factory->getInstance($className);
    }

    public function whenISetAnInstanceOf_AsASingletonFor($singleton, $class) {
        $this->factory->setSingleton(new $singleton, $class);
    }

    public function thenAnExceptionShouldBeThrown() {
        $this->thenA_ShouldBeThrown('Exception');
    }

    public function thenA_ShouldBeThrown($class) {
        $this->spec->assertNotNull($this->caught);
        $this->spec->assertInstanceOf($class, $this->caught);
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

    public function thenTheExceptionMessageShouldContain($string) {
        $this->spec->assertContains($string, $this->caught->getMessage());
    }

}